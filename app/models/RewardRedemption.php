<?php
declare(strict_types=1);

/**
 * RewardRedemption Model - Quản lý việc đổi thưởng của hộ gia đình
 * 
 * Xử lý:
 * - Lấy lịch sử đổi thưởng
 * - Tạo đơn đổi thưởng mới
 * - Cập nhật trạng thái đơn đổi thưởng
 */
class RewardRedemption
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Lấy lịch sử đổi thưởng của hộ gia đình
     * 
     * @param int $householdId ID hộ gia đình
     * @param array $options Tùy chọn: ['limit' => 10, 'offset' => 0]
     * @return array Danh sách đơn đổi thưởng
     */
    public function findByHousehold(int $householdId, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;

        $stmt = $this->db->prepare("
            SELECT 
                rr.redemption_id,
                rr.quantity,
                rr.points_spent,
                rr.status,
                rr.redeemed_at,
                rr.delivered_at,
                rp.product_name,
                rp.image_url
            FROM reward_redemptions rr
            JOIN reward_products rp ON rr.product_id = rp.product_id
            WHERE rr.household_id = :household_id
            ORDER BY rr.redeemed_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':household_id', $householdId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số đơn đổi thưởng của hộ gia đình
     * 
     * @param int $householdId ID hộ gia đình
     * @return int Số lượng đơn
     */
    public function countByHousehold(int $householdId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM reward_redemptions 
            WHERE household_id = :household_id
        ");
        $stmt->execute(['household_id' => $householdId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy thông tin chi tiết một đơn đổi thưởng
     * 
     * @param int $redemptionId ID đơn đổi thưởng
     * @param int|null $householdId ID hộ gia đình (để verify ownership)
     * @return array|null Thông tin đơn hoặc null
     */
    public function findById(int $redemptionId, ?int $householdId = null): ?array
    {
        $sql = "
            SELECT 
                rr.*,
                rp.product_name,
                rp.image_url,
                rp.required_points as points_needed,
                b.full_name as business_name,
                b.phone_number as business_phone
            FROM reward_redemptions rr
            JOIN reward_products rp ON rr.product_id = rp.product_id
            LEFT JOIN users b ON rr.business_id = b.user_id
            WHERE rr.redemption_id = :redemption_id
        ";
        
        $params = ['redemption_id' => $redemptionId];
        
        if ($householdId !== null) {
            $sql .= " AND rr.household_id = :household_id";
            $params['household_id'] = $householdId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Tạo đơn đổi thưởng mới
     * 
     * @param array $data ['household_id', 'product_id', 'business_id', 'quantity', 'points_spent']
     * @return int ID đơn mới tạo
     * @throws Exception Nếu có lỗi
     */
    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO reward_redemptions (
                    household_id,
                    product_id,
                    business_id,
                    quantity,
                    points_spent,
                    status,
                    redeemed_at
                ) VALUES (
                    :household_id,
                    :product_id,
                    :business_id,
                    :quantity,
                    :points_spent,
                    'pending',
                    NOW()
                )
            ");

            $stmt->execute([
                'household_id' => $data['household_id'],
                'product_id' => $data['product_id'],
                'business_id' => $data['business_id'],
                'quantity' => $data['quantity'],
                'points_spent' => $data['points_spent']
            ]);

            $redemptionId = (int)$this->db->lastInsertId();

            $this->db->commit();

            return $redemptionId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật trạng thái đơn đổi thưởng
     * 
     * @param int $redemptionId ID đơn
     * @param string $status Trạng thái mới: pending, processing, completed, cancelled
     * @return bool True nếu thành công
     */
    public function updateStatus(int $redemptionId, string $status): bool
    {
        $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }

        $stmt = $this->db->prepare("
            UPDATE reward_redemptions
            SET status = :status,
                delivered_at = CASE WHEN :status = 'completed' THEN NOW() ELSE delivered_at END
            WHERE redemption_id = :redemption_id
        ");

        return $stmt->execute([
            'status' => $status,
            'redemption_id' => $redemptionId
        ]);
    }

    /**
     * Tính tổng điểm đã chi tiêu của hộ gia đình
     * 
     * @param int $householdId ID hộ gia đình
     * @return int Tổng điểm đã tiêu
     */
    public function getTotalPointsSpent(int $householdId): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(points_spent), 0) 
            FROM reward_redemptions 
            WHERE household_id = :household_id
              AND status IN ('pending', 'confirmed', 'delivered')
        ");
        $stmt->execute(['household_id' => $householdId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy danh sách đơn đổi thưởng cho admin (business_id IS NULL)
     */
    public function findAllForAdmin(array $filters = []): array
    {
        $sql = "
            SELECT 
                rr.*,
                rp.product_name,
                rp.image_url,
                u.full_name as household_name,
                u.phone_number as household_phone
            FROM reward_redemptions rr
            JOIN reward_products rp ON rr.product_id = rp.product_id
            JOIN users u ON rr.household_id = u.user_id
            WHERE rr.business_id IS NULL
        ";

        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND rr.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (u.full_name LIKE :search OR u.phone_number LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY rr.redeemed_at DESC LIMIT 100";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê đơn đổi thưởng admin
     */
    public function getAdminStatistics(): array
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(points_spent) as total_points
            FROM reward_redemptions
            WHERE business_id IS NULL
        ")->fetch(PDO::FETCH_ASSOC);

        return $result ?: [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'delivered' => 0,
            'total_points' => 0
        ];
    }
}
