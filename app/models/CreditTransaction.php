<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

/**
 * CreditTransaction Model
 * Quản lý giao dịch nạp điểm (credit) cho doanh nghiệp
 */
class CreditTransaction
{
    private PDO $db;
    
    // Tỷ lệ chuyển đổi: 1,000 VNĐ = 1 credit
    private const CONVERSION_RATE = 1000;
    
    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ========================================
    // DATA ACCESS
    // ========================================

    /**
     * Tìm transaction theo ID
     */
    public function find(int $transactionId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ct.*,
                u.full_name,
                u.email,
                bp.company_name
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.business_id = u.user_id
            LEFT JOIN business_profiles bp ON ct.business_id = bp.user_id
            WHERE ct.transaction_id = :transaction_id
        ");

        $stmt->execute(['transaction_id' => $transactionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Tìm transaction theo transaction code
     */
    public function findByCode(string $transactionCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ct.*,
                u.full_name,
                u.email,
                bp.company_name
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.business_id = u.user_id
            LEFT JOIN business_profiles bp ON ct.business_id = bp.user_id
            WHERE ct.transaction_code = :transaction_code
        ");

        $stmt->execute(['transaction_code' => $transactionCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Lấy danh sách transactions
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT 
                ct.*,
                u.full_name,
                u.email,
                bp.company_name
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.business_id = u.user_id
            LEFT JOIN business_profiles bp ON ct.business_id = bp.user_id
            WHERE 1=1
        ";

        $params = [];

        // Filters
        if (!empty($filters['business_id'])) {
            $sql .= " AND ct.business_id = :business_id";
            $params['business_id'] = $filters['business_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND ct.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND ct.payment_method = :payment_method";
            $params['payment_method'] = $filters['payment_method'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND ct.created_at >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND ct.created_at <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        // Sorting
        $sql .= " ORDER BY ct.created_at DESC";

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
     * Lấy transactions của business
     */
    public function getByBusiness(int $businessId, array $filters = []): array
    {
        $filters['business_id'] = $businessId;
        return $this->getAll($filters);
    }

    /**
     * Đếm số lượng transactions
     */
    public function count(array $filters = []): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM credit_transactions ct
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['business_id'])) {
            $sql .= " AND ct.business_id = :business_id";
            $params['business_id'] = $filters['business_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND ct.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND ct.payment_method = :payment_method";
            $params['payment_method'] = $filters['payment_method'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // ========================================
    // CREATE TRANSACTION
    // ========================================

    /**
     * Tạo yêu cầu nạp credit
     * 
     * @param int $businessId ID doanh nghiệp
     * @param float $amount Số tiền VNĐ
     * @param string $paymentMethod 'bank_transfer', 'momo', 'vnpay', etc.
     * @return array [transaction_id, transaction_code, amount, credits, bank_info]
     * @throws Exception
     */
    public function create(int $businessId, float $amount, string $paymentMethod = 'bank_transfer'): array
    {
        // Validate amount
        if ($amount <= 0) {
            throw new Exception('Số tiền phải lớn hơn 0');
        }

        // Minimum 10,000 VNĐ
        if ($amount < 10000) {
            throw new Exception('Số tiền nạp tối thiểu 10,000 VNĐ');
        }

        // Calculate credits: 1,000 VNĐ = 1 credit
        $credits = $amount / self::CONVERSION_RATE;

        // Generate transaction code: TXN + timestamp + random
        $transactionCode = 'TXN' . date('YmdHis') . rand(100, 999);

        $this->db->beginTransaction();

        try {
            // Create transaction record
            $stmt = $this->db->prepare("
                INSERT INTO credit_transactions (
                    business_id, 
                    amount, 
                    credits, 
                    payment_method,
                    status, 
                    transaction_code,
                    ip_address,
                    user_agent,
                    created_at
                ) VALUES (
                    :business_id,
                    :amount,
                    :credits,
                    :payment_method,
                    :status,
                    :transaction_code,
                    :ip,
                    :user_agent,
                    NOW()
                )
            ");

            $stmt->execute([
                'business_id' => $businessId,
                'amount' => $amount,
                'credits' => $credits,
                'payment_method' => $paymentMethod,
                'status' => self::STATUS_PENDING,
                'transaction_code' => $transactionCode,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $transactionId = (int)$this->db->lastInsertId();

            $this->db->commit();

            // Generate bank info for payment
            $bankInfo = $this->generateBankInfo($amount, $transactionCode);

            return [
                'transaction_id' => $transactionId,
                'transaction_code' => $transactionCode,
                'amount' => $amount,
                'credits' => $credits,
                'status' => self::STATUS_PENDING,
                'bank_info' => $bankInfo,
                'expires_in' => 3600, // 1 hour
                'created_at' => date('Y-m-d H:i:s')
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
     * Cập nhật status của transaction
     */
    public function updateStatus(int $transactionId, string $newStatus, ?string $reference = null, ?string $notes = null): bool
    {
        $allowedStatuses = [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_REFUNDED
        ];

        if (!in_array($newStatus, $allowedStatuses)) {
            throw new Exception('Status không hợp lệ');
        }

        // Get transaction details
        $transaction = $this->find($transactionId);
        if (!$transaction) {
            throw new Exception('Không tìm thấy giao dịch');
        }

        $this->db->beginTransaction();

        try {
            $sql = "
                UPDATE credit_transactions
                SET status = :status,
                    updated_at = NOW()
            ";

            $params = [
                'status' => $newStatus,
                'transaction_id' => $transactionId
            ];

            // Add specific fields based on status
            if ($newStatus === self::STATUS_PROCESSING) {
                $sql .= ", processing_at = NOW()";
            } elseif ($newStatus === self::STATUS_COMPLETED) {
                $sql .= ", completed_at = NOW()";
                if ($reference) {
                    $sql .= ", bank_transaction_id = :reference";
                    $params['reference'] = $reference;
                }
            } elseif ($newStatus === self::STATUS_FAILED) {
                $sql .= ", failed_at = NOW()";
                if ($notes) {
                    $sql .= ", failure_reason = :notes";
                    $params['notes'] = $notes;
                }
            }

            if ($reference && $newStatus !== self::STATUS_COMPLETED) {
                $sql .= ", payment_reference = :reference";
                $params['reference'] = $reference;
            }

            $sql .= " WHERE transaction_id = :transaction_id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // If completed, add credits to business profile
            if ($newStatus === self::STATUS_COMPLETED) {
                $this->addCreditsToProfile((int)$transaction['business_id'], (float)$transaction['credits']);
            }

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Complete transaction (webhook hoặc admin xác nhận)
     */
    public function complete(int $transactionId, ?string $bankTransactionId = null): bool
    {
        return $this->updateStatus($transactionId, self::STATUS_COMPLETED, $bankTransactionId);
    }

    /**
     * Complete transaction by code (webhook)
     */
    public function completeByCode(string $transactionCode, ?string $bankTransactionId = null): bool
    {
        $transaction = $this->findByCode($transactionCode);
        
        if (!$transaction) {
            throw new Exception('Không tìm thấy giao dịch với mã: ' . $transactionCode);
        }

        return $this->complete((int)$transaction['transaction_id'], $bankTransactionId);
    }

    /**
     * Fail transaction
     */
    public function fail(int $transactionId, string $reason): bool
    {
        return $this->updateStatus($transactionId, self::STATUS_FAILED, null, $reason);
    }

    // ========================================
    // BUSINESS PROFILE CREDITS
    // ========================================

    /**
     * Thêm credits vào profile (business hoặc collector)
     */
    private function addCreditsToProfile(int $userId, float $credits): void
    {
        // Check user role
        $stmt = $this->db->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        if ($role === 'business') {
            // Cộng vào business_profiles
            $stmt = $this->db->prepare("
                UPDATE business_profiles
                SET current_credit = current_credit + :credits
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                'credits' => $credits,
                'user_id' => $userId
            ]);
            
        } elseif ($role === 'collector') {
            // Cộng vào collector_profiles (points)
            $stmt = $this->db->prepare("
                UPDATE collector_profiles
                SET current_points = current_points + :points
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                'points' => $credits,  // Credits = Points (cùng tỷ lệ 1:1000)
                'user_id' => $userId
            ]);
        } else {
            throw new Exception("Invalid user role: {$role}");
        }

        // Log transaction (no-op for now)
        $this->logCreditTransaction($userId, $credits, 'purchase', 'Nạp điểm thành công');
    }

    /**
     * Log credit transaction in business_credit_transactions
     * NOTE: Table was dropped in migration_003. Logging is now handled by credit_transactions table.
     */
    private function logCreditTransaction(int $businessId, float $amount, string $type, string $description): void
    {
        // No-op: business_credit_transactions table no longer exists
        // All transaction logging is handled by the main credit_transactions table
        return;
    }

    // ========================================
    // VALIDATION
    // ========================================

    /**
     * Validate amount
     */
    public function validateAmount(float $amount): bool
    {
        return $amount >= 10000; // Minimum 10,000 VNĐ
    }

    /**
     * Get minimum amount
     */
    public function getMinAmount(): float
    {
        return 10000;
    }

    // ========================================
    // STATISTICS
    // ========================================

    /**
     * Lấy thống kê transactions
     */
    public function getStatistics(array $filters = []): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount_completed,
                SUM(CASE WHEN status = 'completed' THEN credits ELSE 0 END) as total_credits_purchased,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
            FROM credit_transactions
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['business_id'])) {
            $sql .= " AND business_id = :business_id";
            $params['business_id'] = $filters['business_id'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND created_at >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND created_at <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Generate bank info for payment
     */
    private function generateBankInfo(float $amount, string $transactionCode): array
    {
        return [
            'bank_name' => 'MB Bank',
            'bank_code' => 'MB',
            'account_number' => '0373784694',
            'account_name' => 'LE THANH NHAN',
            'amount' => $amount,
            'content' => 'NAP ' . $transactionCode,
            'qr_code_url' => $this->generateQRCode($amount, $transactionCode)
        ];
    }

    /**
     * Generate QR Code URL (VietQR)
     */
    private function generateQRCode(float $amount, string $content): string
    {
        $bankCode = 'MB';
        $accountNumber = '0373784694';
        $template = 'compact';
        
        return sprintf(
            'https://api.vietqr.io/image/%s-%s-%s.jpg?amount=%d&addInfo=%s&accountName=%s',
            $bankCode,
            $accountNumber,
            $template,
            (int)$amount,
            urlencode($content),
            urlencode('LE THANH NHAN')
        );
    }

    /**
     * Chuyển đổi VNĐ sang credits
     */
    public function vndToCredits(float $vnd): float
    {
        return $vnd / self::CONVERSION_RATE;
    }

    /**
     * Chuyển đổi credits sang VNĐ
     */
    public function creditsToVnd(float $credits): float
    {
        return $credits * self::CONVERSION_RATE;
    }

    /**
     * Get conversion rate
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
            self::STATUS_PENDING => 'Chờ thanh toán',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_COMPLETED => 'Thành công',
            self::STATUS_FAILED => 'Thất bại',
            self::STATUS_REFUNDED => 'Đã hoàn tiền'
        ];

        return $statusMap[$status] ?? $status;
    }
}
