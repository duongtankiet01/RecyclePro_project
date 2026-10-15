<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

/**
 * HouseholdProfile Model
 * Quản lý thông tin hộ gia đình và điểm tích lũy
 */
class HouseholdProfile
{
    private PDO $db;
    
    // Tỷ lệ chuyển đổi: 1 điểm = 1,000 VNĐ
    private const CONVERSION_RATE = 1000;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ========================================
    // PROFILE MANAGEMENT
    // ========================================

    /**
     * Lấy thông tin profile hộ gia đình
     */
    public function getProfile(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                hp.*,
                u.full_name,
                u.email,
                u.phone_number,
                u.created_at as user_created_at
            FROM household_profiles hp
            LEFT JOIN users u ON hp.user_id = u.user_id
            WHERE hp.user_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    /**
     * Tạo profile mới cho hộ gia đình
     */
    public function create(int $userId, array $data = []): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO household_profiles (
                user_id, 
                current_points
            ) VALUES (
                :user_id,
                0
            )
            ON DUPLICATE KEY UPDATE user_id = user_id
        ");

        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Cập nhật thông tin profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['bio', 'preferred_waste_types'];
        $updates = [];
        $params = ['user_id' => $userId];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return true;
        }

        $sql = "UPDATE household_profiles SET " . implode(', ', $updates) . 
               " WHERE user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ========================================
    // POINTS MANAGEMENT
    // ========================================

    /**
     * Lấy số điểm hiện tại
     */
    public function getCurrentPoints(int $userId): float
    {
        $stmt = $this->db->prepare("
            SELECT current_points 
            FROM household_profiles 
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (float)$result['current_points'] : 0;
    }

    /**
     * Cộng điểm cho hộ gia đình
     * 
     * @param int $userId ID hộ gia đình
     * @param float $points Số điểm cộng
     * @param string $reason Lý do cộng điểm
     * @param int|null $participantId ID participant liên quan (nếu có)
     * @return float Số dư sau khi cộng
     */
    public function addPoints(int $userId, float $points, string $reason, ?int $participantId = null): float
    {
        // Check if already in transaction
        if ($this->db->inTransaction()) {
            return $this->addPointsWithoutTransaction($userId, $points, $reason, $participantId);
        }

        $this->db->beginTransaction();

        try {
            $newBalance = $this->addPointsWithoutTransaction($userId, $points, $reason, $participantId);
            $this->db->commit();
            return $newBalance;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Add points without transaction (for use inside existing transaction)
     */
    private function addPointsWithoutTransaction(int $userId, float $points, string $reason, ?int $participantId = null): float
    {
        // Đảm bảo profile tồn tại
        $this->ensureProfileExists($userId);

        // Cộng điểm
        $stmt = $this->db->prepare("
            UPDATE household_profiles 
            SET current_points = current_points + :points
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            'points' => $points,
            'user_id' => $userId
        ]);

        // Lấy số dư mới
        $newBalance = $this->getCurrentPoints($userId);

        // Log transaction
        $this->logTransaction($userId, [
            'participant_id' => $participantId,
            'transaction_type' => 'advance',
            'amount' => $points,
            'balance_after' => $newBalance,
            'description' => $reason
        ]);

        return $newBalance;
    }

    /**
     * Trừ điểm của hộ gia đình
     * 
     * @param int $userId ID hộ gia đình
     * @param float $points Số điểm trừ
     * @param string $reason Lý do trừ điểm
     * @param int|null $participantId ID participant liên quan (nếu có)
     * @return float Số dư sau khi trừ
     * @throws Exception Nếu không đủ điểm
     */
    public function deductPoints(int $userId, float $points, string $reason, ?int $participantId = null): float
    {
        $currentPoints = $this->getCurrentPoints($userId);

        if ($currentPoints < $points) {
            throw new Exception(
                'Không đủ điểm. Hiện có: ' . number_format($currentPoints, 0, ',', '.') . 
                ' điểm, cần: ' . number_format($points, 0, ',', '.') . ' điểm'
            );
        }

        try {
            // Trừ điểm
            $stmt = $this->db->prepare("
                UPDATE household_profiles 
                SET current_points = current_points - :points
                WHERE user_id = :user_id
                  AND current_points >= :min_points
            ");

            $stmt->execute([
                'points' => $points,
                'min_points' => $points,
                'user_id' => $userId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Không đủ điểm để thực hiện giao dịch');
            }

            // Lấy số dư mới
            $newBalance = $this->getCurrentPoints($userId);

            // Log transaction
            $this->logTransaction($userId, [
                'participant_id' => $participantId,
                'transaction_type' => 'withdrawal',
                'amount' => -$points,
                'balance_after' => $newBalance,
                'description' => $reason
            ]);

            return $newBalance;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Điều chỉnh điểm (có thể âm hoặc dương)
     */
    public function adjustPoints(int $userId, float $adjustment, string $reason, ?int $participantId = null): float
    {
        // Check if already in transaction
        if ($this->db->inTransaction()) {
            return $this->adjustPointsWithoutTransaction($userId, $adjustment, $reason, $participantId);
        }

        $this->db->beginTransaction();

        try {
            $newBalance = $this->adjustPointsWithoutTransaction($userId, $adjustment, $reason, $participantId);
            $this->db->commit();
            return $newBalance;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Adjust points without transaction (for use inside existing transaction)
     */
    private function adjustPointsWithoutTransaction(int $userId, float $adjustment, string $reason, ?int $participantId = null): float
    {
        $this->ensureProfileExists($userId);

        $stmt = $this->db->prepare("
            UPDATE household_profiles 
            SET current_points = current_points + :adjustment
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            'adjustment' => $adjustment,
            'user_id' => $userId
        ]);

        $newBalance = $this->getCurrentPoints($userId);

        $this->logTransaction($userId, [
            'participant_id' => $participantId,
            'transaction_type' => 'adjustment',
            'amount' => $adjustment,
            'balance_after' => $newBalance,
            'description' => $reason
        ]);

        return $newBalance;
    }

    // ========================================
    // TRANSACTION HISTORY
    // ========================================

    /**
     * Lấy lịch sử giao dịch điểm
     */
    public function getPointTransactions(int $userId, array $filters = []): array
    {
        $sql = "
            SELECT 
                transaction_id,
                participant_id,
                transaction_type,
                amount,
                balance_after,
                description,
                created_at
            FROM household_point_transactions
            WHERE household_id = :user_id
        ";

        $params = ['user_id' => $userId];

        // Apply filters
        if (!empty($filters['transaction_type'])) {
            $sql .= " AND transaction_type = :transaction_type";
            $params['transaction_type'] = $filters['transaction_type'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND created_at >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND created_at <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if (isset($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ghi log giao dịch điểm
     */
    public function logTransaction(int $userId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO household_point_transactions (
                household_id,
                participant_id,
                transaction_type,
                amount,
                balance_after,
                description,
                created_at
            ) VALUES (
                :household_id,
                :participant_id,
                :transaction_type,
                :amount,
                :balance_after,
                :description,
                NOW()
            )
        ");

        $stmt->execute([
            'household_id' => $userId,
            'participant_id' => $data['participant_id'] ?? null,
            'transaction_type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'balance_after' => $data['balance_after'],
            'description' => $data['description']
        ]);

        return (int)$this->db->lastInsertId();
    }

    // ========================================
    // ADDRESS MANAGEMENT
    // ========================================

    /**
     * Lấy địa chỉ của hộ gia đình
     */
    public function getAddress(int $userId, bool $isPrimary = true): ?array
    {
        $sql = "
            SELECT 
                address_id,
                user_id,
                address_line,
                province_code,
                district_code,
                ward_code,
                is_primary
            FROM user_addresses
            WHERE user_id = :user_id
        ";

        if ($isPrimary) {
            $sql .= " AND is_primary = 1";
        }

        $sql .= " ORDER BY is_primary DESC, created_at DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        return $address ?: null;
    }

    /**
     * Lấy tất cả địa chỉ của hộ
     */
    public function getAllAddresses(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM user_addresses
            WHERE user_id = :user_id
            ORDER BY is_primary DESC, created_at DESC
        ");

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ========================================
    // WITHDRAWAL MANAGEMENT
    // ========================================

    /**
     * Tạo yêu cầu rút tiền
     * 
     * @param int $userId ID hộ gia đình
     * @param float $points Số điểm muốn rút
     * @param array $bankInfo Thông tin ngân hàng
     * @return array [request_code, amount_vnd, points_deducted, remaining_points]
     */
    public function createWithdrawal(int $userId, float $points, array $bankInfo): array
    {
        $amountVnd = $points * self::CONVERSION_RATE;

        $this->db->beginTransaction();

        try {
            // Trừ điểm
            $newBalance = $this->deductPoints(
                $userId, 
                $points, 
                'Rút tiền - Yêu cầu #' . uniqid()
            );

            // Tạo withdrawal request
            $requestCode = 'WD' . date('ymd') . strtoupper(substr(uniqid(), -6));

            $stmt = $this->db->prepare("
                INSERT INTO withdrawal_requests (
                    user_id,
                    user_type,
                    request_code,
                    points_requested,
                    amount_requested,
                    bank_account_number,
                    bank_name,
                    account_holder_name,
                    status,
                    requested_at
                ) VALUES (
                    :user_id,
                    'household',
                    :request_code,
                    :points,
                    :amount,
                    :bank_account,
                    :bank_name,
                    :account_holder,
                    'pending',
                    NOW()
                )
            ");

            $stmt->execute([
                'user_id' => $userId,
                'request_code' => $requestCode,
                'points' => $points,
                'amount' => $amountVnd,
                'bank_account' => $bankInfo['account_number'],
                'bank_name' => $bankInfo['bank_name'],
                'account_holder' => $bankInfo['account_holder']
            ]);

            $this->db->commit();

            return [
                'request_code' => $requestCode,
                'amount_vnd' => $amountVnd,
                'points_deducted' => $points,
                'remaining_points' => $newBalance
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy lịch sử rút tiền
     */
    public function getWithdrawalHistory(int $userId, array $filters = []): array
    {
        $sql = "
            SELECT *
            FROM withdrawal_requests
            WHERE user_id = :user_id
              AND user_type = 'household'
        ";

        $params = ['user_id' => $userId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY requested_at DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if (isset($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ========================================
    // STATISTICS
    // ========================================

    /**
     * Lấy thống kê tổng quan
     */
    public function getStatistics(int $userId): array
    {
        $profile = $this->getProfile($userId);
        
        // Thống kê từ order_participants
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'confirmed' THEN final_household_points ELSE 0 END) as total_points_earned,
                SUM(CASE WHEN status = 'confirmed' THEN quantity ELSE 0 END) as total_waste_collected
            FROM order_participants
            WHERE household_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
        $orderStats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'current_points' => (float)($profile['current_points'] ?? 0),
            'total_points_earned' => (float)($orderStats['total_points_earned'] ?? 0),
            'current_balance_vnd' => (float)($profile['current_points'] ?? 0) * self::CONVERSION_RATE,
            'total_orders' => (int)($orderStats['total_orders'] ?? 0),
            'completed_orders' => (int)($orderStats['completed_orders'] ?? 0),
            'total_waste_collected_kg' => (float)($orderStats['total_waste_collected'] ?? 0),
            'conversion_rate' => self::CONVERSION_RATE
        ];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Đảm bảo profile tồn tại, nếu không thì tạo mới
     */
    private function ensureProfileExists(int $userId): void
    {
        // Kiểm tra user có tồn tại không
        $stmt = $this->db->prepare("SELECT user_id, role FROM users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User ID {$userId} không tồn tại trong hệ thống");
        }
        
        if ($user['role'] !== 'household') {
            throw new Exception("User ID {$userId} không phải là household (role: {$user['role']})");
        }
        
        $profile = $this->getProfile($userId);
        
        if (!$profile) {
            $this->create($userId);
        }
    }

    /**
     * Chuyển đổi điểm sang VNĐ
     */
    public function pointsToVnd(float $points): float
    {
        return $points * self::CONVERSION_RATE;
    }

    /**
     * Chuyển đổi VNĐ sang điểm
     */
    public function vndToPoints(float $vnd): float
    {
        return $vnd / self::CONVERSION_RATE;
    }
}
