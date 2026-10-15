<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/HouseholdProfile.php';
require_once __DIR__ . '/CollectorProfile.php';

/**
 * Withdrawal Model
 * Quản lý yêu cầu rút tiền cho household và collector
 */
class Withdrawal
{
    private PDO $db;
    private HouseholdProfile $householdModel;
    private CollectorProfile $collectorModel;
    
    // Tỷ lệ chuyển đổi: 1 điểm = 1,000 VNĐ
    private const CONVERSION_RATE = 1000;
    
    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->householdModel = new HouseholdProfile();
        $this->collectorModel = new CollectorProfile();
    }

    // ========================================
    // DATA ACCESS
    // ========================================

    /**
     * Tìm withdrawal request theo ID
     */
    public function find(int $requestId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                wr.*,
                u.full_name,
                u.email,
                u.phone_number
            FROM withdrawal_requests wr
            LEFT JOIN users u ON wr.user_id = u.user_id
            WHERE wr.request_id = :request_id
        ");

        $stmt->execute(['request_id' => $requestId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Tìm withdrawal theo request code
     */
    public function findByCode(string $requestCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                wr.*,
                u.full_name,
                u.email,
                u.phone_number
            FROM withdrawal_requests wr
            LEFT JOIN users u ON wr.household_id = u.user_id
            WHERE wr.request_code = :request_code
        ");

        $stmt->execute(['request_code' => $requestCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Lấy danh sách withdrawal requests
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT 
                wr.*,
                u.full_name as household_name,
                u.email,
                u.phone_number
            FROM withdrawal_requests wr
            LEFT JOIN users u ON wr.household_id = u.user_id
            WHERE 1=1
        ";

        $params = [];

        // Filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND wr.household_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['user_type'])) {
            // User type filter ignored for withdrawal_requests (household only)
        }

        if (!empty($filters['status'])) {
            $sql .= " AND wr.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND wr.requested_at >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND wr.requested_at <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        // Sorting
        $sql .= " ORDER BY wr.requested_at DESC";

        // Pagination
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            if (isset($filters['offset'])) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if (isset($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
            if (isset($filters['offset'])) {
                $stmt->bindValue(':offset', (int)$filters['offset'], PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách withdrawal của user
     */
    public function getByUser(int $userId, string $userType, array $filters = []): array
    {
        $filters['user_id'] = $userId;
        $filters['user_type'] = $userType;
        
        return $this->getAll($filters);
    }

    /**
     * Đếm số lượng requests theo filter
     */
    public function count(array $filters = []): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM withdrawal_requests wr
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND wr.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['user_type'])) {
            $sql .= " AND wr.user_type = :user_type";
            $params['user_type'] = $filters['user_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND wr.status = :status";
            $params['status'] = $filters['status'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // ========================================
    // CREATE WITHDRAWAL
    // ========================================

    /**
     * Tạo yêu cầu rút tiền
     * 
     * @param int $userId ID user (household hoặc collector)
     * @param string $userType 'household' hoặc 'collector'
     * @param float $points Số điểm muốn rút
     * @param array $bankInfo Thông tin ngân hàng
     * @return array [request_id, request_code, amount_vnd, points_deducted]
     * @throws Exception
     */
    public function create(int $userId, string $userType, float $points, array $bankInfo): array
    {
        // Validate user type
        if (!in_array($userType, ['household', 'collector'])) {
            throw new Exception('User type không hợp lệ');
        }

        // Validate points
        if ($points <= 0) {
            throw new Exception('Số điểm phải lớn hơn 0');
        }

        // Validate bank info
        if (empty($bankInfo['account_number']) || empty($bankInfo['bank_name']) || empty($bankInfo['account_holder'])) {
            throw new Exception('Thông tin ngân hàng không đầy đủ');
        }

        // Tính số tiền VNĐ
        $amountVnd = $points * self::CONVERSION_RATE;

        $this->db->beginTransaction();

        try {
            // Trừ điểm từ profile (sử dụng model tương ứng)
            $description = 'Rút tiền - Yêu cầu #' . uniqid();
            
            if ($userType === 'household') {
                $newBalance = $this->householdModel->deductPoints($userId, $points, $description);
            } else {
                $newBalance = $this->collectorModel->deductPoints($userId, $points, $description);
            }

            // Generate request code: WD + 17 chữ số (khớp với Sepay config)
            // Format: WD + timestamp(10 số) + random(7 số)
            $timestamp = time(); // 10 chữ số
            $random = str_pad((string)rand(0, 9999999), 7, '0', STR_PAD_LEFT); // 7 chữ số
            $requestCode = 'WD' . $timestamp . $random; // WD + 17 số

            // Tạo withdrawal request
            $stmt = $this->db->prepare("
                INSERT INTO withdrawal_requests (
                    user_id,
                    user_type,
                    household_id,
                    request_code,
                    points_deducted,
                    amount_requested,
                    bank_account_number,
                    bank_name,
                    account_holder_name,
                    status,
                    requested_at
                ) VALUES (
                    :user_id,
                    :user_type,
                    :household_id,
                    :request_code,
                    :points,
                    :amount,
                    :bank_account,
                    :bank_name,
                    :account_holder,
                    :status,
                    NOW()
                )
            ");

            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType,
                'household_id' => $userId, // Giữ household_id cho backward compatibility
                'request_code' => $requestCode,
                'points' => $points,
                'amount' => $amountVnd,
                'bank_account' => $bankInfo['account_number'],
                'bank_name' => $bankInfo['bank_name'],
                'account_holder' => $bankInfo['account_holder'],
                'status' => self::STATUS_PENDING
            ]);

            $requestId = (int)$this->db->lastInsertId();

            $this->db->commit();

            return [
                'request_id' => $requestId,
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

    // ========================================
    // UPDATE STATUS
    // ========================================

    /**
     * Cập nhật status của withdrawal request
     */
    public function updateStatus(int $requestId, string $newStatus, ?int $adminId = null, ?string $notes = null): bool
    {
        $allowedStatuses = [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED
        ];

        if (!in_array($newStatus, $allowedStatuses)) {
            throw new Exception('Status không hợp lệ');
        }

        // Lấy thông tin request hiện tại
        $request = $this->find($requestId);
        if (!$request) {
            throw new Exception('Không tìm thấy yêu cầu rút tiền');
        }

        $this->db->beginTransaction();

        try {
            // Cập nhật status
            $sql = "
                UPDATE withdrawal_requests
                SET status = :status,
                    processed_by = :admin_id,
                    admin_notes = :notes
            ";

            $params = [
                'status' => $newStatus,
                'admin_id' => $adminId,
                'notes' => $notes,
                'request_id' => $requestId
            ];

            // Thêm timestamp tương ứng
            if ($newStatus === self::STATUS_PROCESSING) {
                $sql .= ", processing_at = NOW()";
            } elseif ($newStatus === self::STATUS_COMPLETED) {
                $sql .= ", completed_at = NOW()";
            } elseif ($newStatus === self::STATUS_REJECTED) {
                $sql .= ", rejected_at = NOW()";
            } elseif ($newStatus === self::STATUS_CANCELLED) {
                $sql .= ", cancelled_at = NOW()";
            }

            $sql .= " WHERE request_id = :request_id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Nếu bị reject hoặc cancel, hoàn lại điểm
            if (in_array($newStatus, [self::STATUS_REJECTED, self::STATUS_CANCELLED])) {
                $this->refundPoints($requestId, $notes);
            }

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Approve withdrawal request
     */
    public function approve(int $requestId, int $adminId, ?string $notes = null): bool
    {
        return $this->updateStatus($requestId, self::STATUS_PROCESSING, $adminId, $notes);
    }

    /**
     * Complete withdrawal request
     */
    public function complete(int $requestId, int $adminId, ?string $transactionRef = null): bool
    {
        $notes = $transactionRef ? "Chuyển khoản thành công. Ref: {$transactionRef}" : "Đã chuyển khoản thành công";
        return $this->updateStatus($requestId, self::STATUS_COMPLETED, $adminId, $notes);
    }

    /**
     * Reject withdrawal request (hoàn lại điểm)
     */
    public function reject(int $requestId, int $adminId, string $reason): bool
    {
        return $this->updateStatus($requestId, self::STATUS_REJECTED, $adminId, $reason);
    }

    /**
     * Cancel withdrawal request (user tự hủy, hoàn lại điểm)
     */
    public function cancel(int $requestId, int $userId): bool
    {
        // Kiểm tra quyền: chỉ user tạo request mới được hủy
        $request = $this->find($requestId);
        
        if (!$request) {
            throw new Exception('Không tìm thấy yêu cầu rút tiền');
        }

        if ((int)$request['user_id'] !== $userId) {
            throw new Exception('Bạn không có quyền hủy yêu cầu này');
        }

        if ($request['status'] !== self::STATUS_PENDING) {
            throw new Exception('Chỉ có thể hủy yêu cầu đang chờ xử lý');
        }

        return $this->updateStatus($requestId, self::STATUS_CANCELLED, null, 'User tự hủy');
    }

    // ========================================
    // REFUND POINTS
    // ========================================

    /**
     * Hoàn lại điểm khi reject/cancel
     */
    private function refundPoints(int $requestId, ?string $reason = null): void
    {
        $request = $this->find($requestId);
        
        if (!$request) {
            throw new Exception('Không tìm thấy yêu cầu rút tiền');
        }

        $userId = (int)$request['user_id'];
        $userType = $request['user_type'];
        $points = (float)$request['points_deducted'];
        $requestCode = $request['request_code'];

        $description = "Hoàn lại điểm do {$requestCode} bị từ chối/hủy";
        if ($reason) {
            $description .= " - Lý do: {$reason}";
        }

        // Log để debug
        error_log("Refunding points: user_id={$userId}, user_type={$userType}, points={$points}");

        // Hoàn lại điểm
        try {
            if ($userType === 'household') {
                $this->householdModel->addPoints($userId, $points, $description);
            } else {
                $this->collectorModel->addPoints($userId, $points, $description);
            }
        } catch (Exception $e) {
            error_log("Error refunding points: " . $e->getMessage());
            error_log("Request data: " . json_encode($request));
            throw $e;
        }
    }

    // ========================================
    // VALIDATION
    // ========================================

    /**
     * Kiểm tra user có đủ điểm để rút không
     */
    public function canWithdraw(int $userId, string $userType, float $points): bool
    {
        if ($userType === 'household') {
            $currentPoints = $this->householdModel->getCurrentPoints($userId);
        } else {
            $currentPoints = $this->collectorModel->getCurrentPoints($userId);
        }

        return $currentPoints >= $points;
    }

    /**
     * Lấy số điểm tối thiểu để rút
     */
    public function getMinWithdrawalPoints(): float
    {
        // Tối thiểu 10,000 VNĐ = 10 điểm
        return 10;
    }

    /**
     * Lấy số điểm tối đa có thể rút
     */
    public function getMaxWithdrawalPoints(int $userId, string $userType): float
    {
        if ($userType === 'household') {
            return $this->householdModel->getCurrentPoints($userId);
        } else {
            return $this->collectorModel->getCurrentPoints($userId);
        }
    }

    // ========================================
    // STATISTICS
    // ========================================

    /**
     * Lấy thống kê withdrawal
     */
    public function getStatistics(array $filters = []): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN status = 'completed' THEN amount_requested ELSE 0 END) as total_amount_paid,
                SUM(CASE WHEN status = 'completed' THEN points_deducted ELSE 0 END) as total_points_withdrawn,
                SUM(CASE WHEN status = 'pending' THEN amount_requested ELSE 0 END) as pending_amount
            FROM withdrawal_requests
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['user_type'])) {
            // user_type filter ignored (household only table)
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND requested_at >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND requested_at <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy thống kê withdrawal của user
     */
    public function getUserStatistics(int $userId, string $userType): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN status = 'completed' THEN amount_requested ELSE 0 END) as total_amount_withdrawn,
                SUM(CASE WHEN status = 'completed' THEN points_deducted ELSE 0 END) as total_points_withdrawn,
                SUM(CASE WHEN status = 'pending' THEN amount_requested ELSE 0 END) as pending_amount
            FROM withdrawal_requests
            WHERE household_id = :user_id
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

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

    /**
     * Lấy conversion rate
     */
    public function getConversionRate(): int
    {
        return self::CONVERSION_RATE;
    }

    /**
     * Get status text in Vietnamese
     */
    public function getStatusText(string $status): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_REJECTED => 'Đã từ chối',
            self::STATUS_CANCELLED => 'Đã hủy'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Lấy số lượng yêu cầu chờ xử lý
     */
    public function getPendingCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM withdrawal_requests 
            WHERE status = 'pending'
        ");
        return (int)$stmt->fetchColumn();
    }
}
