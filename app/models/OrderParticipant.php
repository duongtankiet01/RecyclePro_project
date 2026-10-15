<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/HouseholdProfile.php';
require_once __DIR__ . '/CollectorProfile.php';

/**
 * OrderParticipant Model
 * Quản lý participants trong đơn hàng (hộ gia đình + CTV)
 * Xử lý luồng: Accept → Collect → Deliver → Confirm
 */
class OrderParticipant
{
    private PDO $db;
    private HouseholdProfile $householdModel;
    private CollectorProfile $collectorModel;

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
     * Tìm participant theo ID
     */
    public function find(int $participantId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                op.*,
                bo.business_id,
                bo.waste_type,
                bo.points_per_kg,
                bo.quantity as business_original_quantity,
                bo.notes as order_notes,
                u.full_name as household_name,
                u.phone_number as household_phone,
                cu.full_name as collector_name,
                cu.phone_number as collector_phone,
                bu.full_name as business_name,
                bu.phone_number as business_phone,
                bu.email as business_email,
                bp.company_name,
                bp.contact_person,
                ua.address_line as business_address,
                ua.province_code as business_province_code,
                ua.ward_code as business_ward_code,
                hua.address_line,
                hua.province_code,
                hua.ward_code
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            LEFT JOIN users u ON op.household_id = u.user_id
            LEFT JOIN users cu ON op.collector_id = cu.user_id
            LEFT JOIN users bu ON bo.business_id = bu.user_id
            LEFT JOIN business_profiles bp ON bo.business_id = bp.user_id
            LEFT JOIN user_addresses ua ON bo.business_id = ua.user_id AND ua.is_primary = 1
            LEFT JOIN user_addresses hua ON op.household_id = hua.user_id AND hua.is_primary = 1
            WHERE op.participant_id = :participant_id
        ");

        $stmt->execute(['participant_id' => $participantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Lấy danh sách participants của một đơn
     */
    public function findByOrder(int $orderId, array $filters = []): array
    {
        $sql = "
            SELECT 
                op.*,
                u.full_name as household_name,
                u.phone_number as household_phone,
                cu.full_name as collector_name
            FROM order_participants op
            LEFT JOIN users u ON op.household_id = u.user_id
            LEFT JOIN users cu ON op.collector_id = cu.user_id
            WHERE op.order_id = :order_id
        ";

        $params = ['order_id' => $orderId];

        // Filters
        if (!empty($filters['status'])) {
            $sql .= " AND op.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['exclude_cancelled'])) {
            $sql .= " AND op.status != 'cancelled'";
        }

        $sql .= " ORDER BY op.accepted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy participants theo household
     */
    public function findByHousehold(int $householdId, array $filters = []): array
    {
        $sql = "
            SELECT 
                op.*,
                bo.waste_type,
                bo.points_per_kg,
                bo.business_id,
                u.full_name as business_name,
                bp.company_name,
                cu.full_name as collector_name,
                ua.address_line as business_address,
                ua.province_code as business_province_code,
                ua.ward_code as business_ward_code
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            LEFT JOIN users u ON bo.business_id = u.user_id
            LEFT JOIN business_profiles bp ON bo.business_id = bp.user_id
            LEFT JOIN users cu ON op.collector_id = cu.user_id
            LEFT JOIN user_addresses ua ON bo.business_id = ua.user_id AND ua.is_primary = 1
            WHERE op.household_id = :household_id
        ";

        $params = ['household_id' => $householdId];

        if (!empty($filters['status'])) {
            $sql .= " AND op.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['exclude_cancelled'])) {
            $sql .= " AND op.status != 'cancelled'";
        }

        $sql .= " ORDER BY op.accepted_at DESC";

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
     * Lấy orders của household với thông tin business_orders
     * Dùng cho trang quản lý đơn của household
     * 
     * @param int $householdId ID hộ gia đình
     * @return array Danh sách orders
     */
    public function getHouseholdOrdersWithBusinessInfo(int $householdId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                bo.order_id,
                bo.waste_type,
                bo.quantity as business_quantity,
                bo.points_per_kg,
                bo.status,
                bo.created_at,
                op.participant_id,
                op.quantity,
                op.delivery_method,
                op.accepted_at,
                op.collector_id
            FROM business_orders bo
            INNER JOIN order_participants op ON bo.order_id = op.order_id
            WHERE op.household_id = :household_id
              AND op.status != 'cancelled'
            ORDER BY bo.created_at DESC
        ");
        $stmt->execute(['household_id' => $householdId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy participants theo collector
     */
    public function findByCollector(int $collectorId, array $filters = []): array
    {
        $sql = "
            SELECT 
                op.*,
                bo.waste_type,
                bo.points_per_kg,
                bo.business_id,
                u.full_name as household_name,
                u.phone_number as household_phone,
                ua.address_line,
                ua.province_code,
                ua.ward_code,
                bu.full_name as business_name,
                bp.company_name,
                ba.address_line as business_address_line,
                ba.province_code as business_province_code,
                ba.ward_code as business_ward_code
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            LEFT JOIN users u ON op.household_id = u.user_id
            LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
            LEFT JOIN users bu ON bo.business_id = bu.user_id
            LEFT JOIN business_profiles bp ON bo.business_id = bp.user_id
            LEFT JOIN user_addresses ba ON bo.business_id = ba.user_id AND ba.is_primary = 1
            WHERE op.collector_id = :collector_id
              AND op.delivery_method = 'ctv'
        ";

        $params = ['collector_id' => $collectorId];

        if (!empty($filters['status'])) {
            $sql .= " AND op.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['exclude_cancelled'])) {
            $sql .= " AND op.status != 'cancelled'";
        }

        $sql .= " ORDER BY op.accepted_at DESC";

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
    // HOUSEHOLD ACCEPT ORDER
    // ========================================

    /**
     * Hộ gia đình nhận đơn
     * 
     * @param int $orderId ID đơn hàng
     * @param int $householdId ID hộ gia đình
     * @param float $quantity Số lượng đăng ký
     * @param string $deliveryMethod 'self' hoặc 'ctv'
     * @return int participant_id
     * @throws Exception
     */
    public function acceptByHousehold(
        int $orderId, 
        int $householdId, 
        float $quantity, 
        string $deliveryMethod = 'self'
    ): int {
        if (!in_array($deliveryMethod, ['self', 'ctv'])) {
            throw new Exception('Phương thức giao hàng không hợp lệ');
        }

        if ($quantity <= 0) {
            throw new Exception('Số lượng phải lớn hơn 0');
        }

        $this->db->beginTransaction();

        try {
            // Kiểm tra đơn còn available không
            $stmt = $this->db->prepare("
                SELECT 
                    order_id,
                    quantity,
                    quantity_remaining,
                    status
                FROM business_orders
                WHERE order_id = :order_id
                FOR UPDATE
            ");

            $stmt->execute(['order_id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Đơn hàng không tồn tại');
            }

            if ($order['status'] !== 'pending') {
                throw new Exception('Đơn hàng không còn mở để nhận');
            }

            $quantityRemaining = (float)$order['quantity_remaining'];

            if ($quantityRemaining <= 0) {
                throw new Exception('Đơn hàng đã đủ số lượng');
            }

            if ($quantity > $quantityRemaining) {
                throw new Exception(
                    "Chỉ còn {$quantityRemaining} kg. Vui lòng nhập số lượng nhỏ hơn hoặc bằng."
                );
            }

            // Kiểm tra hộ đã nhận đơn này chưa
            $stmt = $this->db->prepare("
                SELECT participant_id 
                FROM order_participants 
                WHERE order_id = :order_id 
                  AND household_id = :household_id 
                  AND status != 'cancelled'
            ");

            $stmt->execute([
                'order_id' => $orderId,
                'household_id' => $householdId
            ]);

            if ($stmt->fetch()) {
                throw new Exception('Bạn đã nhận đơn này rồi');
            }

            // Thêm participant
            // Status: 'accepted' nếu tự giao (đang trên đường), 'pending' nếu CTV (chờ CTV nhận)
            $initialStatus = ($deliveryMethod === 'self') ? 'accepted' : 'pending';
            
            $stmt = $this->db->prepare("
                INSERT INTO order_participants (
                    order_id,
                    household_id,
                    quantity,
                    delivery_method,
                    status,
                    accepted_at
                ) VALUES (
                    :order_id,
                    :household_id,
                    :quantity,
                    :delivery_method,
                    :status,
                    NOW()
                )
            ");

            $stmt->execute([
                'order_id' => $orderId,
                'household_id' => $householdId,
                'quantity' => $quantity,
                'delivery_method' => $deliveryMethod,
                'status' => $initialStatus
            ]);

            $participantId = (int)$this->db->lastInsertId();

            // Cập nhật quantity_accepted trong business_orders
            $stmt = $this->db->prepare("
                UPDATE business_orders
                SET quantity_accepted = quantity_accepted + :quantity
                WHERE order_id = :order_id
            ");

            $stmt->execute([
                'quantity' => $quantity,
                'order_id' => $orderId
            ]);

            // Nếu đã đủ số lượng, đổi status sang 'active'
            $newRemaining = $quantityRemaining - $quantity;
            if ($newRemaining <= 0) {
                $stmt = $this->db->prepare("
                    UPDATE business_orders
                    SET status = 'active'
                    WHERE order_id = :order_id
                ");
                $stmt->execute(['order_id' => $orderId]);
            }

            $this->db->commit();

            return $participantId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Hủy đơn đã nhận (household)
     */
    public function cancelByHousehold(int $participantId, int $householdId): bool
    {
        $this->db->beginTransaction();

        try {
            // Lấy thông tin participant
            $stmt = $this->db->prepare("
                SELECT 
                    op.participant_id,
                    op.order_id,
                    op.household_id,
                    op.quantity,
                    op.status,
                    bo.quantity_remaining,
                    bo.status as order_status
                FROM order_participants op
                INNER JOIN business_orders bo ON op.order_id = bo.order_id
                WHERE op.participant_id = :participant_id
                  AND op.household_id = :household_id
                  AND op.status IN ('pending', 'accepted')
                FOR UPDATE
            ");

            $stmt->execute([
                'participant_id' => $participantId,
                'household_id' => $householdId
            ]);

            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                throw new Exception('Không tìm thấy đơn hoặc đơn không thể hủy');
            }

            $orderId = (int)$participant['order_id'];
            $cancelledQuantity = (float)$participant['quantity'];

            // Cập nhật status thành cancelled
            $stmt = $this->db->prepare("
                UPDATE order_participants
                SET status = 'cancelled',
                    cancelled_at = NOW()
                WHERE participant_id = :participant_id
            ");

            $stmt->execute(['participant_id' => $participantId]);

            // Trả lại số lượng
            $stmt = $this->db->prepare("
                UPDATE business_orders
                SET quantity_accepted = quantity_accepted - :quantity
                WHERE order_id = :order_id
            ");

            $stmt->execute([
                'quantity' => $cancelledQuantity,
                'order_id' => $orderId
            ]);

            // Nếu đơn đang active và còn lại > 0, đổi về pending
            if ($participant['order_status'] === 'active') {
                $stmt = $this->db->prepare("
                    SELECT quantity_remaining 
                    FROM business_orders 
                    WHERE order_id = :order_id
                ");
                $stmt->execute(['order_id' => $orderId]);
                $remaining = (float)$stmt->fetchColumn();

                if ($remaining > 0) {
                    $stmt = $this->db->prepare("
                        UPDATE business_orders
                        SET status = 'pending'
                        WHERE order_id = :order_id
                    ");
                    $stmt->execute(['order_id' => $orderId]);
                }
            }

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ========================================
    // COLLECTOR OPERATIONS
    // ========================================

    /**
     * CTV nhận đơn (chỉ gán collector_id, chưa ứng)
     */
    public function acceptByCollector(int $participantId, int $collectorId): bool
    {
        $this->db->beginTransaction();

        try {
            // Lấy thông tin participant
            $stmt = $this->db->prepare("
                SELECT 
                    participant_id,
                    collector_id,
                    status,
                    delivery_method
                FROM order_participants
                WHERE participant_id = :participant_id
                  AND delivery_method = 'ctv'
                  AND status = 'pending'
                FOR UPDATE
            ");

            $stmt->execute(['participant_id' => $participantId]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                throw new Exception('Đơn không tồn tại hoặc không khả dụng');
            }

            if ($participant['collector_id'] !== null) {
                throw new Exception('Đơn đã được CTV khác nhận');
            }

            // Gán collector_id
            $stmt = $this->db->prepare("
                UPDATE order_participants
                SET collector_id = :collector_id,
                    status = 'accepted',
                    updated_at = NOW()
                WHERE participant_id = :participant_id
            ");

            $stmt->execute([
                'collector_id' => $collectorId,
                'participant_id' => $participantId
            ]);

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * CTV hủy đơn đã nhận
     * Gỡ collector_id và đưa đơn về trạng thái pending để CTV khác nhận
     */
    public function cancelByCollector(int $participantId, int $collectorId): bool
    {
        $this->db->beginTransaction();

        try {
            // Lấy thông tin participant
            $stmt = $this->db->prepare("
                SELECT 
                    op.participant_id,
                    op.order_id,
                    op.collector_id,
                    op.status,
                    bo.status as order_status
                FROM order_participants op
                INNER JOIN business_orders bo ON op.order_id = bo.order_id
                WHERE op.participant_id = :participant_id
                  AND op.collector_id = :collector_id
                  AND op.status = 'accepted'
                FOR UPDATE
            ");

            $stmt->execute([
                'participant_id' => $participantId,
                'collector_id' => $collectorId
            ]);

            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                throw new Exception('Không tìm thấy đơn hoặc bạn không có quyền hủy');
            }

            $orderId = (int)$participant['order_id'];

            // Gỡ CTV khỏi participant (nhưng giữ lại record cho household)
            $stmt = $this->db->prepare("
                UPDATE order_participants 
                SET collector_id = NULL,
                    status = 'pending',
                    collected_at = NULL,
                    updated_at = NOW()
                WHERE participant_id = :participant_id
            ");
            $stmt->execute(['participant_id' => $participantId]);

            // Cập nhật status của business_order về pending nếu cần
            if ($participant['order_status'] !== 'pending') {
                $stmt = $this->db->prepare("
                    UPDATE business_orders 
                    SET status = 'pending'
                    WHERE order_id = :order_id
                ");
                $stmt->execute(['order_id' => $orderId]);
            }

            $this->db->commit();

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * CTV xác nhận thu gom và ứng điểm
     * 
     * @param int $participantId ID participant
     * @param int $collectorId ID CTV
     * @param float $actualQuantity Số lượng thực tế thu gom
     * @return array [advance_given, household_balance, collector_balance]
     */
    public function confirmPickup(int $participantId, int $collectorId, float $actualQuantity): array
    {
        if ($actualQuantity <= 0) {
            throw new Exception('Số lượng thực tế phải lớn hơn 0');
        }

        $this->db->beginTransaction();

        try {
            // Lấy thông tin participant
            $participant = $this->find($participantId);

            if (!$participant) {
                throw new Exception('Đơn không tồn tại');
            }

            if ((int)$participant['collector_id'] !== $collectorId) {
                throw new Exception('Bạn không phải CTV của đơn này');
            }

            if ((float)$participant['advance_given'] > 0) {
                throw new Exception('Đơn này đã được ứng điểm rồi');
            }

            // Kiểm tra số lượng không vượt quá dự kiến
            $expectedQuantity = (float)$participant['quantity'];
            if ($actualQuantity > $expectedQuantity) {
                throw new Exception(
                    "Số lượng thực tế ({$actualQuantity} kg) không được vượt quá " .
                    "số lượng hộ đăng ký ({$expectedQuantity} kg)"
                );
            }

            // Tính điểm ứng (60% dựa trên actual quantity)
            $totalPoints = $actualQuantity * (float)$participant['points_per_kg'];
            $advanceAmount = $totalPoints * 0.60; // CTV ứng 60% cho hộ

            // Kiểm tra CTV có đủ điểm để ứng không
            $currentCtvPoints = $this->collectorModel->getCurrentPoints($collectorId);
            if ($currentCtvPoints < $advanceAmount) {
                throw new Exception(
                    'Bạn không đủ điểm để ứng. Cần ' . number_format($advanceAmount, 0) . 
                    ' điểm, hiện có ' . number_format($currentCtvPoints, 0) . ' điểm.'
                );
            }

            // Cập nhật participant: số lượng thực tế, advance_given, status
            $stmt = $this->db->prepare("
                UPDATE order_participants
                SET quantity = :actual_quantity,
                    advance_given = :advance,
                    status = 'in_transit',
                    collected_at = NOW(),
                    updated_at = NOW()
                WHERE participant_id = :participant_id
            ");

            $stmt->execute([
                'actual_quantity' => $actualQuantity,
                'advance' => $advanceAmount,
                'participant_id' => $participantId
            ]);

            // Trừ điểm CTV
            $ctvBalanceAfter = $this->collectorModel->deductPoints(
                $collectorId,
                $advanceAmount,
                'Ứng điểm cho đơn #' . $participantId,
                $participantId
            );

            // Cộng điểm cho hộ gia đình
            $householdId = (int)$participant['household_id'];
            $householdBalanceAfter = $this->householdModel->addPoints(
                $householdId,
                $advanceAmount,
                'Nhận ứng từ CTV cho đơn #' . $participantId,
                $participantId
            );

            $this->db->commit();

            return [
                'advance_given' => $advanceAmount,
                'actual_quantity' => $actualQuantity,
                'household_balance' => $householdBalanceAfter,
                'collector_balance' => $ctvBalanceAfter
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ========================================
    // BUSINESS CONFIRM DELIVERY
    // ========================================

    /**
     * Doanh nghiệp xác nhận nhận rác và phân phối điểm
     * 
     * @param int $participantId ID participant
     * @param int $businessId ID doanh nghiệp
     * @param float $actualQuantity Số lượng DN thực tế nhận
     * @return array [household_points, collector_points, platform_fee]
     */
    public function confirmDelivery(int $participantId, int $businessId, float $actualQuantity): array
    {
        if ($actualQuantity <= 0) {
            throw new Exception('Số lượng thực tế phải lớn hơn 0');
        }

        $this->db->beginTransaction();

        try {
            // Lấy thông tin participant
            $participant = $this->find($participantId);

            if (!$participant) {
                throw new Exception('Đơn không tồn tại');
            }

            if ((int)$participant['business_id'] !== $businessId) {
                throw new Exception('Bạn không phải doanh nghiệp của đơn này');
            }

            if (!in_array($participant['status'], ['pending', 'accepted', 'in_transit'])) {
                throw new Exception('Đơn đã được xác nhận hoặc không hợp lệ');
            }

            // Kiểm tra số lượng DN nhận không vượt quá số lượng CTV thu gom
            $ctvCollectedQuantity = (float)$participant['quantity'];
            $maxAllowed = $ctvCollectedQuantity > 0 ? $ctvCollectedQuantity : (float)$participant['business_original_quantity'];

            if ($actualQuantity > $maxAllowed) {
                throw new Exception(
                    "Khối lượng DN nhận ({$actualQuantity} kg) không được vượt quá " .
                    "khối lượng CTV đã thu gom ({$maxAllowed} kg)"
                );
            }

            // Tính điểm
            $pointsPerKg = (float)$participant['points_per_kg'];
            $totalPointsFromBusiness = $actualQuantity * $pointsPerKg;
            $deliveryMethod = $participant['delivery_method'];
            $advanceGiven = (float)($participant['advance_given'] ?? 0);

            $householdPoints = 0;
            $collectorPoints = 0;
            $platformFee = 0;

            if ($deliveryMethod === 'self') {
                // Hộ tự giao: 95% cho hộ, 5% phí hệ thống
                $householdPoints = floor($totalPointsFromBusiness * 0.95);
                $platformFee = $totalPointsFromBusiness - $householdPoints;
            } else {
                // CTV thu gom: 60% hộ, 35% CTV, 5% hệ thống
                $householdPoints = floor($totalPointsFromBusiness * 0.60);
                $collectorPoints = floor($totalPointsFromBusiness * 0.35);
                $platformFee = floor($totalPointsFromBusiness * 0.05);
            }

            // Điều chỉnh quantity nếu DN nhận ít hơn
            $quantityDiff = $actualQuantity - $maxAllowed;
            if ($quantityDiff < 0) {
                $stmt = $this->db->prepare("
                    UPDATE business_orders
                    SET quantity_accepted = quantity_accepted + :quantity_diff
                    WHERE order_id = :order_id
                ");
                $stmt->execute([
                    'quantity_diff' => $quantityDiff,
                    'order_id' => $participant['order_id']
                ]);
            }

            // Cập nhật participant status = confirmed
            $stmt = $this->db->prepare("
                UPDATE order_participants
                SET status = 'confirmed',
                    quantity = :actual_quantity,
                    delivered_at = NOW(),
                    final_household_points = :household_pts,
                    final_collector_points = :collector_pts,
                    system_fee = :system_fee
                WHERE participant_id = :participant_id
            ");

            $stmt->execute([
                'actual_quantity' => $actualQuantity,
                'household_pts' => $householdPoints,
                'collector_pts' => $collectorPoints,
                'system_fee' => $platformFee,
                'participant_id' => $participantId
            ]);

            // Phân phối điểm
            $householdId = (int)$participant['household_id'];
            $collectorId = $participant['collector_id'] ? (int)$participant['collector_id'] : null;

            if ($deliveryMethod === 'self') {
                // Hộ tự giao: cộng điểm bình thường
                $this->householdModel->addPoints(
                    $householdId,
                    $householdPoints,
                    'Nhận điểm từ DN (tự giao) cho đơn #' . $participantId,
                    $participantId
                );
            } else {
                // CTV thu gom: điều chỉnh tiền ứng
                // Hộ: đã nhận 100% ứng, giờ chỉ giữ 60%
                $adjustHousehold = $householdPoints - $advanceGiven;
                
                $this->householdModel->adjustPoints(
                    $householdId,
                    $adjustHousehold,
                    'Điều chỉnh sau xác nhận DN (60% - ứng 100%) cho đơn #' . $participantId,
                    $participantId
                );

                // CTV: nhận lại ứng + hoa hồng 35%
                if ($collectorId) {
                    $adjustCollector = $advanceGiven + $collectorPoints;
                    
                    $this->collectorModel->addPoints(
                        $collectorId,
                        $adjustCollector,
                        'Nhận lại ứng + hoa hồng 35% cho đơn #' . $participantId,
                        $participantId
                    );
                }
            }

            // ✅ Kiểm tra xem đơn có thể chuyển sang completed không
            // Chỉ chuyển khi: tất cả participants đã confirmed VÀ quantity_remaining <= 0
            $stmt = $this->db->prepare("
                SELECT 
                    bo.quantity_remaining,
                    bo.quantity,
                    bo.quantity_accepted,
                    COUNT(op.participant_id) as total_participants,
                    SUM(CASE WHEN op.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN op.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
                FROM business_orders bo
                LEFT JOIN order_participants op ON bo.order_id = op.order_id
                WHERE bo.order_id = :order_id
                GROUP BY bo.order_id
            ");
            $stmt->execute(['order_id' => $participant['order_id']]);
            $orderStats = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($orderStats) {
                $totalActive = (int)$orderStats['total_participants'] - (int)$orderStats['cancelled_count'];
                $confirmedCount = (int)$orderStats['confirmed_count'];
                $quantityRemaining = (float)$orderStats['quantity_remaining'];

                // Chỉ chuyển sang completed khi:
                // 1. Tất cả participants đang active đều đã confirmed
                // 2. VÀ quantity_remaining <= 0 (đã thu gom đủ hoặc hơn số lượng cần)
                if ($totalActive > 0 && $confirmedCount === $totalActive && $quantityRemaining <= 0) {
                    $stmt = $this->db->prepare("
                        UPDATE business_orders
                        SET status = 'completed',
                            updated_at = NOW()
                        WHERE order_id = :order_id
                    ");
                    $stmt->execute(['order_id' => $participant['order_id']]);
                }
            }

            $this->db->commit();

            return [
                'participant_id' => $participantId,
                'actual_quantity' => $actualQuantity,
                'points_from_business' => $totalPointsFromBusiness,
                'household_points_final' => $householdPoints,
                'collector_points_final' => $collectorPoints,
                'platform_fee' => $platformFee,
                'delivery_method' => $deliveryMethod
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ========================================
    // STATISTICS & HELPERS
    // ========================================

    /**
     * Đếm số participants theo status
     */
    public function countByStatus(int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM order_participants
            WHERE order_id = :order_id
            GROUP BY status
        ");

        $stmt->execute(['order_id' => $orderId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Lấy danh sách participants chờ CTV nhận
     */
    public function getAvailableForCollectors(array $filters = []): array
    {
        $sql = "
            SELECT 
                op.participant_id,
                op.order_id,
                op.quantity,
                op.accepted_at,
                bo.waste_type,
                bo.points_per_kg,
                u.full_name as household_name,
                u.phone_number as household_phone,
                ua.address_line,
                ua.province_code,
                ua.ward_code
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            INNER JOIN users u ON op.household_id = u.user_id
            LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
            WHERE op.delivery_method = 'ctv'
              AND op.status = 'pending'
              AND op.collector_id IS NULL
        ";

        $params = [];

        if (!empty($filters['province_code'])) {
            $sql .= " AND ua.province_code = :province_code";
            $params['province_code'] = $filters['province_code'];
        }

        $sql .= " ORDER BY op.accepted_at DESC";

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
     * Get all participants by collector (CTV)
     * 
     * @param int $collectorId
     * @param array $filters Optional filters (limit, status)
     * @return array Array of participant records with order details
     */
    public function getByCollector(int $collectorId, array $filters = []): array
    {
        $sql = "
            SELECT 
                op.participant_id,
                op.status,
                op.quantity,
                op.advance_given,
                op.final_collector_points,
                op.accepted_at,
                op.delivered_at,
                bo.waste_type,
                bo.points_per_kg,
                u.full_name as household_name,
                ua.address_line
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            INNER JOIN users u ON op.household_id = u.user_id
            LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
            WHERE op.collector_id = :collector_id
        ";

        $params = ['collector_id' => $collectorId];

        if (!empty($filters['status'])) {
            $sql .= " AND op.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY op.accepted_at DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
        } else {
            $sql .= " LIMIT 100";
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
     * Cập nhật status
     */
    public function updateStatus(int $participantId, string $status): bool
    {
        $allowedStatuses = ['pending', 'accepted', 'in_transit', 'delivered', 'confirmed', 'cancelled'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Status không hợp lệ');
        }

        $stmt = $this->db->prepare("
            UPDATE order_participants
            SET status = :status,
                updated_at = NOW()
            WHERE participant_id = :participant_id
        ");

        return $stmt->execute([
            'status' => $status,
            'participant_id' => $participantId
        ]);
    }

    /**
     * Lấy danh sách deliveries đang chờ xác nhận của business
     * 
     * @param int $businessId Business ID
     * @return array Danh sách participants đang pending hoặc in_transit
     */
    public function getPendingDeliveriesForBusiness(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                op.participant_id,
                op.order_id,
                op.household_id,
                op.collector_id,
                op.quantity as expected_quantity,
                op.delivery_method,
                op.status,
                op.advance_given,
                op.collected_at,
                op.created_at,
                bo.order_id as business_order_id,
                bo.waste_type,
                bo.points_per_kg,
                u.full_name as household_name,
                u.phone_number as household_phone,
                ua.address_line as household_address,
                cu.full_name as collector_name,
                cu.phone_number as collector_phone
            FROM order_participants op
            INNER JOIN business_orders bo ON op.order_id = bo.order_id
            INNER JOIN users u ON op.household_id = u.user_id
            LEFT JOIN user_addresses ua ON op.household_id = ua.user_id AND ua.is_primary = 1
            LEFT JOIN users cu ON op.collector_id = cu.user_id
            WHERE bo.business_id = :business_id
              AND op.status IN ('pending', 'in_transit')
            ORDER BY op.collected_at DESC, op.created_at DESC
        ");
        
        $stmt->execute(['business_id' => $businessId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
