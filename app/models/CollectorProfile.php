<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/CreditTransaction.php';

/**
 * CollectorProfile Model
 * Quản lý thông tin cộng tác viên và điểm tích lũy
 */
class CollectorProfile
{
    private PDO $db;
    private CreditTransaction $creditModel;
    
    // Tỷ lệ chuyển đổi: 1 điểm = 1,000 VNĐ
    private const CONVERSION_RATE = 1000;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->creditModel = new CreditTransaction();
    }

    // ========================================
    // PROFILE MANAGEMENT
    // ========================================

    /**
     * Lấy thông tin profile CTV
     */
    public function getProfile(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                cp.*,
                u.full_name,
                u.email,
                u.phone_number,
                u.created_at as user_created_at
            FROM collector_profiles cp
            LEFT JOIN users u ON cp.user_id = u.user_id
            WHERE cp.user_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    /**
     * Tạo profile mới cho CTV
     */
    public function create(int $userId, array $data = []): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO collector_profiles (
                user_id, 
                current_points,
                verification_status
            ) VALUES (
                :user_id,
                0,
                'pending'
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
        $allowedFields = ['bio', 'service_areas', 'verification_status'];
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

        $sql = "UPDATE collector_profiles SET " . implode(', ', $updates) . 
               " WHERE user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ========================================
    // WALLET & POINTS MANAGEMENT
    // ========================================

    /**
     * Lấy thông tin ví đầy đủ
     * Bao gồm: available_points, pending_points, total_earned
     */
    public function getWallet(int $userId): array
    {
        // Lấy điểm hiện tại
        $profile = $this->getProfile($userId);
        $availablePoints = $profile ? (float)$profile['current_points'] : 0;

        // Tính điểm đang chờ (từ các đơn in_transit)
        $stmt = $this->db->prepare("
            SELECT SUM(advance_given) as pending_points
            FROM order_participants
            WHERE collector_id = :user_id
              AND status = 'in_transit'
        ");

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pendingPoints = $result ? (float)($result['pending_points'] ?? 0) : 0;

        // Tính tổng đã kiếm được từ transactions
        $stmt2 = $this->db->prepare("
            SELECT SUM(CASE WHEN status = 'confirmed' THEN final_collector_points ELSE 0 END) as total_earned
            FROM order_participants
            WHERE collector_id = :user_id
        ");
        $stmt2->execute(['user_id' => $userId]);
        $earnedResult = $stmt2->fetch(PDO::FETCH_ASSOC);
        $totalEarned = (float)($earnedResult['total_earned'] ?? 0);

        return [
            'available_points' => $availablePoints,
            'pending_points' => $pendingPoints,
            'total_points' => $availablePoints + $pendingPoints,
            'available_vnd' => $availablePoints * self::CONVERSION_RATE,
            'pending_vnd' => $pendingPoints * self::CONVERSION_RATE,
            'total_earned' => $totalEarned,
            'conversion_rate' => self::CONVERSION_RATE
        ];
    }

    /**
     * Lấy số điểm hiện tại
     */
    public function getCurrentPoints(int $userId): float
    {
        $stmt = $this->db->prepare("
            SELECT current_points 
            FROM collector_profiles 
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (float)$result['current_points'] : 0;
    }

    /**
     * Cộng điểm cho CTV
     * 
     * @param int $userId ID CTV
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
            UPDATE collector_profiles 
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
            'transaction_type' => 'earning',
            'amount' => $points,
            'balance_after' => $newBalance,
            'description' => $reason
        ]);

        return $newBalance;
    }

    /**
     * Trừ điểm của CTV
     * 
     * @param int $userId ID CTV
     * @param float $points Số điểm trừ
     * @param string $reason Lý do trừ điểm
     * @param int|null $participantId ID participant liên quan (nếu có)
     * @return float Số dư sau khi trừ
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

        // Check if already in transaction to avoid nested transactions
        if ($this->db->inTransaction()) {
            return $this->deductPointsWithoutTransaction($userId, $points, $reason, $participantId);
        }

        $this->db->beginTransaction();

        try {
            $newBalance = $this->deductPointsWithoutTransaction($userId, $points, $reason, $participantId);
            $this->db->commit();
            return $newBalance;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function deductPointsWithoutTransaction(int $userId, float $points, string $reason, ?int $participantId = null): float
    {
        // Trừ điểm
        $stmt = $this->db->prepare("
            UPDATE collector_profiles 
            SET current_points = current_points - ?
            WHERE user_id = ?
              AND current_points >= ?
        ");

        $stmt->execute([$points, $userId, $points]);

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
    }

    // ========================================
    // ADVANCE OPERATIONS (ỨNG TIỀN)
    // ========================================

    /**
     * Ứng tiền cho CTV khi thu gom rác
     * Tiền ứng sẽ ở trạng thái pending cho đến khi DN xác nhận
     * 
     * @param int $userId ID CTV
     * @param int $participantId ID participant
     * @param float $advanceAmount Số điểm ứng trước
     * @return bool
     */
    public function advancePoints(int $userId, int $participantId, float $advanceAmount): bool
    {
        $this->db->beginTransaction();

        try {
            // Cập nhật advance_given trong order_participants
            $stmt = $this->db->prepare("
                UPDATE order_participants
                SET advance_given = :advance,
                    status = 'in_transit'
                WHERE participant_id = :participant_id
                  AND collector_id = :user_id
            ");

            $stmt->execute([
                'advance' => $advanceAmount,
                'participant_id' => $participantId,
                'user_id' => $userId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Không thể ứng tiền cho đơn hàng này');
            }

            // Log transaction (pending)
            $currentBalance = $this->getCurrentPoints($userId);
            
            $this->logTransaction($userId, [
                'participant_id' => $participantId,
                'transaction_type' => 'advance_given',
                'amount' => $advanceAmount,
                'balance_after' => $currentBalance,
                'description' => 'Ứng tiền cho đơn #' . $participantId . ' (chờ xác nhận)'
            ]);

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Hoàn trả tiền ứng và trả hoa hồng khi DN xác nhận
     * 
     * @param int $userId ID CTV
     * @param int $participantId ID participant
     * @param float $advanceGiven Số tiền đã ứng
     * @param float $finalCommission Hoa hồng cuối cùng của CTV
     * @return float Số dư mới
     */
    public function returnAdvanceWithCommission(
        int $userId, 
        int $participantId, 
        float $advanceGiven, 
        float $finalCommission
    ): float {
        $this->db->beginTransaction();

        try {
            // Hoàn trả tiền ứng (trừ ra khỏi pending)
            // Cộng hoa hồng thực tế vào available
            $netChange = $finalCommission - $advanceGiven;

            if ($netChange > 0) {
                // Hoa hồng > tiền ứng: cộng thêm
                $newBalance = $this->addPoints(
                    $userId, 
                    $netChange,
                    "Hoàn tất đơn #$participantId - Hoa hồng vượt tiền ứng",
                    $participantId
                );
            } elseif ($netChange < 0) {
                // Hoa hồng < tiền ứng: trừ bớt
                $newBalance = $this->deductPoints(
                    $userId,
                    abs($netChange),
                    "Hoàn tất đơn #$participantId - Điều chỉnh tiền ứng",
                    $participantId
                );
            } else {
                // Bằng nhau: chỉ log
                $newBalance = $this->getCurrentPoints($userId);
                
                $this->logTransaction($userId, [
                    'participant_id' => $participantId,
                    'transaction_type' => 'advance_settled',
                    'amount' => 0,
                    'balance_after' => $newBalance,
                    'description' => "Hoàn tất đơn #$participantId - Hoa hồng = Tiền ứng"
                ]);
            }

            $this->db->commit();

            return $newBalance;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Kiểm tra CTV có đủ điểm để ứng không
     */
    public function canAffordAdvance(int $userId, float $advanceAmount): bool
    {
        return true; // CTV không cần có điểm trước để ứng
        // Tiền ứng được theo dõi qua order_participants.advance_given
        // Điều chỉnh khi DN xác nhận
    }

    // ========================================
    // TOPUP MANAGEMENT (NẠP ĐIỂM)
    // ========================================

    /**
     * Tạo yêu cầu nạp điểm
     * REFACTORED: Sử dụng CreditTransaction (dùng chung với doanh nghiệp)
     * 
     * @param int $userId ID CTV
     * @param float $amountVnd Số tiền nạp (VNĐ)
     * @param string $bankTransactionId Mã giao dịch ngân hàng (deprecated)
     * @return array [transaction_id, transaction_code, amount, credits, bank_info]
     */
    public function createTopup(int $userId, float $amountVnd, string $bankTransactionId = ''): array
    {
        // Sử dụng CreditTransaction model (dùng chung với doanh nghiệp)
        // Note: CreditTransaction.business_id được dùng cho cả collector (user_id chung)
        $result = $this->creditModel->create($userId, $amountVnd, 'bank_transfer');
        
        // Backward compatibility: map fields để API response giống cũ
        return [
            'topup_id' => $result['transaction_id'],          // Compatibility
            'transaction_id' => $result['transaction_id'],
            'transaction_code' => $result['transaction_code'],
            'amount_vnd' => $result['amount'],
            'amount' => $result['amount'],                     // Compatibility
            'points_to_receive' => $result['credits'],        // Compatibility
            'credits' => $result['credits'],
            'status' => $result['status'],
            'bank_info' => $result['bank_info'],
            'expires_in' => $result['expires_in'],
            'created_at' => $result['created_at']
        ];
    }

    /**
     * Xác nhận nạp điểm thành công
     * REFACTORED: Sử dụng CreditTransaction (dùng chung với doanh nghiệp)
     * 
     * @param int $transactionId ID transaction (topup_id cũ)
     * @param string|null $bankTransactionId Mã giao dịch ngân hàng (từ webhook)
     * @return array [success, points_received, new_balance]
     */
    public function confirmTopup(int $transactionId, ?string $bankTransactionId = null): array
    {
        // Sử dụng CreditTransaction->complete() (tự động cộng điểm)
        $success = $this->creditModel->complete($transactionId, $bankTransactionId);
        
        if (!$success) {
            throw new Exception('Không thể xác nhận nạp điểm');
        }
        
        // Lấy thông tin transaction để trả về
        $transaction = $this->creditModel->findById($transactionId);
        
        if (!$transaction) {
            throw new Exception('Không tìm thấy transaction');
        }
        
        // Lấy balance mới
        $newBalance = $this->getCurrentPoints((int)$transaction['business_id']);
        
        return [
            'success' => true,
            'points_received' => (float)$transaction['credits'],
            'points_added' => (float)$transaction['credits'],  // Backward compatibility
            'new_balance' => $newBalance,
            'transaction_code' => $transaction['transaction_code'],
            'completed_at' => $transaction['completed_at']
        ];
    }

    /**
     * Kiểm tra trạng thái topup
     */
    public function checkTopupStatus(int $topupId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM collector_topup_requests
            WHERE topup_id = :topup_id
        ");

        $stmt->execute(['topup_id' => $topupId]);
        $topup = $stmt->fetch(PDO::FETCH_ASSOC);

        return $topup ?: null;
    }

    /**
     * Lấy lịch sử nạp điểm
     */
    public function getTopupHistory(int $userId, array $filters = []): array
    {
        // Sử dụng CreditTransaction (đã unified với doanh nghiệp)
        $sql = "
            SELECT 
                ct.transaction_id,
                ct.transaction_code,
                ct.amount,
                ct.amount as amount_vnd,
                ct.credits,
                ct.status,
                ct.payment_method,
                ct.bank_transaction_id,
                ct.created_at,
                ct.completed_at
            FROM credit_transactions ct
            WHERE ct.business_id = :user_id
        ";

        $params = ['user_id' => $userId];

        if (!empty($filters['status'])) {
            $sql .= " AND ct.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY ct.created_at DESC";

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
            FROM collector_point_transactions
            WHERE collector_id = :user_id
        ";

        $params = ['user_id' => $userId];

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
            INSERT INTO collector_point_transactions (
                collector_id,
                participant_id,
                transaction_type,
                amount,
                balance_after,
                description,
                created_at
            ) VALUES (
                :collector_id,
                :participant_id,
                :transaction_type,
                :amount,
                :balance_after,
                :description,
                NOW()
            )
        ");

        $stmt->execute([
            'collector_id' => $userId,
            'participant_id' => $data['participant_id'] ?? null,
            'transaction_type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'balance_after' => $data['balance_after'],
            'description' => $data['description']
        ]);

        return (int)$this->db->lastInsertId();
    }

    // ========================================
    // WITHDRAWAL MANAGEMENT
    // ========================================

    /**
     * Tạo yêu cầu rút tiền
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
                    'collector',
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
              AND user_type = 'collector'
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
        $wallet = $this->getWallet($userId);
        
        // Thống kê từ order_participants
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'confirmed' THEN final_collector_points ELSE 0 END) as total_commission_earned,
                SUM(CASE WHEN status = 'confirmed' THEN quantity ELSE 0 END) as total_waste_collected
            FROM order_participants
            WHERE collector_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
        $orderStats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'available_points' => $wallet['available_points'],
            'pending_points' => $wallet['pending_points'],
            'total_points_earned' => (float)($orderStats['total_commission_earned'] ?? 0),
            'available_vnd' => $wallet['available_vnd'],
            'pending_vnd' => $wallet['pending_vnd'],
            'total_orders' => (int)($orderStats['total_orders'] ?? 0),
            'completed_orders' => (int)($orderStats['completed_orders'] ?? 0),
            'total_commission_earned' => (float)($orderStats['total_commission_earned'] ?? 0),
            'total_waste_collected_kg' => (float)($orderStats['total_waste_collected'] ?? 0),
            'conversion_rate' => self::CONVERSION_RATE
        ];
    }

    /**
     * Lấy thông tin topup theo code
     */
    public function getTopupByCode(int $userId, string $topupCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                topup_id,
                topup_code,
                amount_vnd,
                points_received,
                status,
                confirmed_at
            FROM collector_topup_transactions
            WHERE topup_code = :code 
              AND collector_id = :collector_id
            LIMIT 1
        ");
        
        $stmt->execute([
            'code' => $topupCode,
            'collector_id' => $userId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Đảm bảo profile tồn tại, nếu không thì tạo mới
     */
    private function ensureProfileExists(int $userId): void
    {
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
