<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

/**
 * BusinessOrder Model
 * Quản lý các đơn hàng thu gom của doanh nghiệp
 */
class BusinessOrder
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ========================================
    // DATA ACCESS METHODS
    // ========================================

    /**
     * Tạo đơn hàng mới
     * 
     * @param int $businessId ID doanh nghiệp
     * @param array $data Dữ liệu đơn hàng [wasteType, quantity, pointsPerKg, notes]
     * @return int ID đơn hàng vừa tạo
     * @throws Exception Nếu không đủ credit hoặc lỗi database
     */
    public function create(int $businessId, array $data): int
    {
        $wasteType = $data['waste_type'];
        $quantity = (float)$data['quantity'];
        $pointsPerKg = (float)$data['points_per_kg'];
        $notes = $data['notes'] ?? '';

        $totalPoints = $this->calculateTotalPoints($quantity, $pointsPerKg);

        // Kiểm tra credit
        if (!$this->hasEnoughCredit($businessId, $totalPoints)) {
            $currentCredit = $this->getCurrentCredit($businessId);
            throw new Exception(
                'Không đủ credit. Bạn có ' . number_format($currentCredit, 0, ',', '.') . 
                ' credit, cần ' . number_format($totalPoints, 0, ',', '.') . ' credit'
            );
        }

        $this->db->beginTransaction();

        try {
            // Trừ credit
            $this->deductCredit($businessId, $totalPoints);

            // Tạo đơn
            $stmt = $this->db->prepare("
                INSERT INTO business_orders (
                    business_id, waste_type, quantity, 
                    quantity_remaining, points_per_kg, 
                    total_points, status, notes, created_at
                ) VALUES (
                    :business_id, :waste_type, :quantity,
                    :quantity_remaining, :points_per_kg,
                    :total_points, 'pending', :notes, NOW()
                )
            ");

            $stmt->execute([
                'business_id' => $businessId,
                'waste_type' => $wasteType,
                'quantity' => $quantity,
                'quantity_remaining' => $quantity,
                'points_per_kg' => $pointsPerKg,
                'total_points' => $totalPoints,
                'notes' => $notes
            ]);

            $orderId = (int)$this->db->lastInsertId();

            $this->db->commit();

            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy thông tin đơn hàng theo ID
     */
    public function find(int $orderId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                bo.*,
                u.full_name as business_name,
                u.phone_number as business_phone,
                u.email as business_email,
                bp.company_name,
                bp.contact_person,
                ua.address_line,
                ua.province_code,
                ua.ward_code
            FROM business_orders bo
            LEFT JOIN users u ON bo.business_id = u.user_id
            LEFT JOIN business_profiles bp ON bo.business_id = bp.user_id
            LEFT JOIN user_addresses ua ON bo.business_id = ua.user_id AND ua.is_primary = 1
            WHERE bo.order_id = :order_id
        ");

        $stmt->execute(['order_id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        return $order ?: null;
    }

    /**
     * Lấy danh sách đơn hàng của doanh nghiệp
     */
    public function findByBusiness(int $businessId, array $filters = []): array
    {
        $sql = "
            SELECT 
                bo.*,
                COUNT(CASE WHEN op.status != 'cancelled' THEN op.participant_id ELSE NULL END) as participant_count,
                SUM(CASE WHEN op.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                GROUP_CONCAT(DISTINCT CASE WHEN op.status != 'cancelled' THEN u.full_name ELSE NULL END SEPARATOR ', ') as household_names
            FROM business_orders bo
            LEFT JOIN order_participants op ON bo.order_id = op.order_id
            LEFT JOIN users u ON op.household_id = u.user_id
            WHERE bo.business_id = :business_id
        ";

        $params = ['business_id' => $businessId];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND bo.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['waste_type'])) {
            $sql .= " AND bo.waste_type = :waste_type";
            $params['waste_type'] = $filters['waste_type'];
        }

        $sql .= " GROUP BY bo.order_id ORDER BY bo.created_at DESC";

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
     * Cập nhật đơn hàng (chỉ khi chưa có hộ nhận)
     */
    public function updateForBusiness(int $orderId, int $businessId, array $data): array
    {
        $order = $this->find($orderId);

        if (!$order || (int)$order['business_id'] !== $businessId) {
            throw new Exception('Không tìm thấy đơn hàng');
        }

        if ($order['status'] !== 'pending') {
            throw new Exception('Chỉ có thể chỉnh sửa đơn đang chờ hộ gia đình nhận');
        }

        if ($this->hasActiveParticipants($orderId)) {
            throw new Exception('Đơn đã có hộ gia đình/CTV nhận, không thể chỉnh sửa');
        }

        $wasteType = trim($data['waste_type'] ?? '');
        $quantity = (float)($data['quantity'] ?? 0);
        $pointsPerKg = (float)($data['points_per_kg'] ?? 0);
        $notes = $data['notes'] ?? null;

        if ($wasteType === '' || $quantity <= 0 || $pointsPerKg <= 0) {
            throw new Exception('Dữ liệu cập nhật không hợp lệ');
        }

        $newTotalPoints = $this->calculateTotalPoints($quantity, $pointsPerKg);
        $oldTotalPoints = (float)$order['total_points'];

        $this->db->beginTransaction();

        try {
            if ($newTotalPoints > $oldTotalPoints) {
                $diff = $newTotalPoints - $oldTotalPoints;
                if (!$this->hasEnoughCredit($businessId, $diff)) {
                    throw new Exception('Không đủ credit để cập nhật đơn');
                }
                $this->deductCredit($businessId, $diff);
            } elseif ($newTotalPoints < $oldTotalPoints) {
                $diff = $oldTotalPoints - $newTotalPoints;
                if ($diff > 0) {
                    $this->refundCredit($businessId, $diff);
                }
            }

            $quantityAccepted = (float)($order['quantity_accepted'] ?? 0);
            $newQuantityRemaining = max(0, $quantity - $quantityAccepted);

            $stmt = $this->db->prepare("
                UPDATE business_orders
                SET waste_type = :waste_type,
                    quantity = :quantity,
                    quantity_remaining = :quantity_remaining,
                    points_per_kg = :points_per_kg,
                    total_points = :total_points,
                    notes = :notes,
                    updated_at = NOW()
                WHERE order_id = :order_id
            ");

            $stmt->execute([
                'waste_type' => $wasteType,
                'quantity' => $quantity,
                'quantity_remaining' => $newQuantityRemaining,
                'points_per_kg' => $pointsPerKg,
                'total_points' => $newTotalPoints,
                'notes' => $notes,
                'order_id' => $orderId
            ]);

            $this->db->commit();

            return $this->find($orderId);

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE business_orders 
            SET status = :status,
                updated_at = NOW()
            WHERE order_id = :order_id
        ");

        return $stmt->execute([
            'status' => $status,
            'order_id' => $orderId
        ]);
    }

    /**
     * Cập nhật số lượng còn lại của đơn
     */
    public function updateQuantityRemaining(int $orderId, float $quantity): bool
    {
        $stmt = $this->db->prepare("
            UPDATE business_orders 
            SET quantity_remaining = quantity_remaining + :quantity,
                updated_at = NOW()
            WHERE order_id = :order_id
        ");

        return $stmt->execute([
            'quantity' => $quantity,
            'order_id' => $orderId
        ]);
    }

    /**
     * Giảm số lượng còn lại (khi hộ nhận đơn)
     */
    public function decreaseQuantityRemaining(int $orderId, float $quantity): bool
    {
        $stmt = $this->db->prepare("
            UPDATE business_orders 
            SET quantity_remaining = quantity_remaining - :quantity_decrease,
                quantity_accepted = quantity_accepted + :quantity_increase,
                updated_at = NOW()
            WHERE order_id = :order_id 
              AND quantity_remaining >= :quantity_check
        ");

        $stmt->execute([
            'quantity_decrease' => $quantity,
            'quantity_increase' => $quantity,
            'quantity_check' => $quantity,
            'order_id' => $orderId
        ]);

        return $stmt->rowCount() > 0;
    }

    // ========================================
    // BUSINESS LOGIC METHODS
    // ========================================

    /**
     * Tính tổng điểm của đơn
     */
    public function calculateTotalPoints(float $quantity, float $pointsPerKg): float
    {
        return $quantity * $pointsPerKg;
    }

    /**
     * Kiểm tra doanh nghiệp có đủ credit không
     */
    public function hasEnoughCredit(int $businessId, float $requiredPoints): bool
    {
        $currentCredit = $this->getCurrentCredit($businessId);
        return $currentCredit >= $requiredPoints;
    }

    /**
     * Lấy số credit hiện tại
     */
    public function getCurrentCredit(int $businessId): float
    {
        $stmt = $this->db->prepare("
            SELECT current_credit 
            FROM business_profiles 
            WHERE user_id = :business_id
        ");

        $stmt->execute(['business_id' => $businessId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (float)$result['current_credit'] : 0;
    }

    /**
     * Trừ credit của doanh nghiệp
     */
    public function deductCredit(int $businessId, float $points): bool
    {
        $stmt = $this->db->prepare("
            UPDATE business_profiles 
            SET current_credit = current_credit - :points_deduct
            WHERE user_id = :business_id
              AND current_credit >= :points_check
        ");

        $stmt->execute([
            'points_deduct' => $points,
            'points_check' => $points,
            'business_id' => $businessId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Hoàn credit cho doanh nghiệp (khi hủy đơn)
     */
    public function refundCredit(int $businessId, float $points): bool
    {
        $stmt = $this->db->prepare("
            UPDATE business_profiles 
            SET current_credit = current_credit + :points
            WHERE user_id = :business_id
        ");

        return $stmt->execute([
            'points' => $points,
            'business_id' => $businessId
        ]);
    }

    // ========================================
    // RELATIONSHIP METHODS
    // ========================================

    /**
     * Lấy danh sách participants của đơn
     */
    public function getParticipants(int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                op.*,
                h.full_name as household_name,
                h.phone_number as household_phone,
                c.full_name as collector_name,
                c.phone_number as collector_phone
            FROM order_participants op
            LEFT JOIN users h ON op.household_id = h.user_id
            LEFT JOIN users c ON op.collector_id = c.user_id
            WHERE op.order_id = :order_id
            ORDER BY op.created_at DESC
        ");

        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách đơn hàng available (đang mở, chưa đủ số lượng)
     * Dùng cho household xem feed
     */
    public function getAvailableOrders(array $filters = []): array
    {
        $sql = "
            SELECT 
                bo.*,
                u.full_name as business_name,
                bp.company_name,
                ua.address_line as business_address,
                ua.province_code,
                ua.ward_code,
                COUNT(op.participant_id) as current_participants,
                COALESCE(SUM(op.quantity), 0) as collected_quantity
            FROM business_orders bo
            INNER JOIN users u ON bo.business_id = u.user_id
            LEFT JOIN business_profiles bp ON bo.business_id = bp.user_id
            LEFT JOIN user_addresses ua ON bo.business_id = ua.user_id AND ua.is_primary = 1
            LEFT JOIN order_participants op ON bo.order_id = op.order_id 
                AND op.status IN ('accepted', 'collected', 'in_transit', 'confirmed')
            WHERE bo.status IN ('pending', 'active')
              AND bo.quantity_remaining > 0
            GROUP BY bo.order_id
            ORDER BY bo.created_at DESC
        ";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách giao hàng chờ xác nhận
     */
    public function getPendingDeliveries(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                op.participant_id,
                op.order_id,
                op.household_id,
                op.collector_id,
                op.quantity as expected_quantity,
                op.delivery_method,
                op.status as participant_status,
                op.accepted_at,
                op.collected_at,
                op.advance_given,
                bo.waste_type,
                bo.points_per_kg,
                u.full_name as household_name,
                u.phone_number as household_phone,
                cu.full_name as collector_name,
                cu.phone_number as collector_phone
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            INNER JOIN users u ON op.household_id = u.user_id
            LEFT JOIN users cu ON op.collector_id = cu.user_id
            WHERE bo.business_id = :business_id
              AND (
                  (op.delivery_method = 'self' AND op.status = 'accepted')
                  OR (op.delivery_method = 'ctv' AND op.status = 'in_transit')
              )
            ORDER BY op.collected_at DESC, op.accepted_at DESC
        ");

        $stmt->execute(['business_id' => $businessId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra đơn có thể bị hủy không
     */
    public function canCancel(int $orderId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN status NOT IN ('cancelled') THEN 1 ELSE 0 END) AS active_participants
            FROM order_participants
            WHERE order_id = :order_id
        ");

        $stmt->execute(['order_id' => $orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Không cho phép hủy nếu đã có bất kỳ participant nào nhận đơn
        return (int)($result['active_participants'] ?? 0) === 0;
    }

    /**
     * Hủy đơn hàng và hoàn credit
     */
    public function cancel(int $orderId, string $reason = ''): bool
    {
        if (!$this->canCancel($orderId)) {
            throw new Exception('Không thể hủy đơn đang được xử lý');
        }

        $order = $this->find($orderId);
        if (!$order) {
            throw new Exception('Không tìm thấy đơn hàng');
        }

        $this->db->beginTransaction();

        try {
            // Hoàn credit
            $this->refundCredit((int)$order['business_id'], (float)$order['total_points']);

            // Cập nhật trạng thái đơn
            $stmt = $this->db->prepare("
                UPDATE business_orders 
                SET status = 'cancelled',
                    notes = CONCAT(COALESCE(notes, ''), '\nLý do hủy: ', :reason),
                    updated_at = NOW()
                WHERE order_id = :order_id
            ");

            $stmt->execute([
                'reason' => $reason,
                'order_id' => $orderId
            ]);

            // Hủy tất cả participants đang pending
            $stmt = $this->db->prepare("
                UPDATE order_participants
                SET status = 'cancelled'
                WHERE order_id = :order_id
                  AND status = 'pending'
            ");

            $stmt->execute(['order_id' => $orderId]);

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cancelByBusiness(int $orderId, int $businessId, string $reason = ''): bool
    {
        $order = $this->find($orderId);
        if (!$order || (int)$order['business_id'] !== $businessId) {
            throw new Exception('Không tìm thấy đơn hàng');
        }

        return $this->cancel($orderId, $reason);
    }

    // ========================================
    // STATISTICS METHODS
    // ========================================

    /**
     * Lấy thống kê đơn hàng của doanh nghiệp
     */
    public function getStatistics(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(quantity) as total_quantity,
                SUM(total_points) as total_points,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
            FROM business_orders
            WHERE business_id = :business_id
        ");

        $stmt->execute(['business_id' => $businessId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy số đơn hôm nay
     */
    public function getTodayCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM business_orders 
            WHERE DATE(created_at) = CURDATE()
        ");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy tổng số đơn
     */
    public function getTotalCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM business_orders");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy doanh thu hôm nay (điểm đã hoàn thành)
     */
    public function getTodayRevenue(): int
    {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(total_points), 0) FROM business_orders 
            WHERE status = 'confirmed' AND DATE(created_at) = CURDATE()
        ");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy tổng doanh thu (điểm đã hoàn thành)
     */
    public function getTotalRevenue(): int
    {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(total_points), 0) FROM business_orders 
            WHERE status = 'confirmed'
        ");
        return (int)$stmt->fetchColumn();
    }

    private function hasActiveParticipants(int $orderId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM order_participants 
            WHERE order_id = :order_id 
              AND status NOT IN ('cancelled')
        ");

        $stmt->execute(['order_id' => $orderId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
