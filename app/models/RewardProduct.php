<?php

class RewardProduct
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Lấy tất cả sản phẩm của doanh nghiệp
     */
    public function findByBusiness(int $businessId, array $filters = []): array
    {
        $sql = "
            SELECT 
                product_id,
                business_id,
                product_name,
                description,
                category,
                required_points,
                stock_quantity,
                image_url,
                status,
                is_featured,
                total_redeemed,
                created_at,
                updated_at
            FROM reward_products
            WHERE business_id = :business_id
        ";

        $params = ['business_id' => $businessId];

        // Filter by category
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params['category'] = $filters['category'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        // Search by name
        if (!empty($filters['search'])) {
            $sql .= " AND product_name LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Filter by points range
        if (!empty($filters['points_min'])) {
            $sql .= " AND required_points >= :points_min";
            $params['points_min'] = (int)$filters['points_min'];
        }
        if (!empty($filters['points_max'])) {
            $sql .= " AND required_points <= :points_max";
            $params['points_max'] = (int)$filters['points_max'];
        }

        $sql .= " ORDER BY is_featured DESC, created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tất cả sản phẩm active (dành cho hộ gia đình)
     */
    public function findAllActive(array $filters = []): array
    {
        $sql = "
            SELECT 
                rp.product_id,
                rp.business_id,
                rp.product_name,
                rp.description,
                rp.category,
                rp.required_points,
                rp.stock_quantity,
                rp.image_url,
                rp.status,
                rp.is_featured,
                rp.total_redeemed,
                rp.created_at
            FROM reward_products rp
            WHERE rp.status = 'active' AND rp.stock_quantity > 0
        ";

        $params = [];

        // Filter by category
        if (!empty($filters['category'])) {
            $sql .= " AND rp.category = :category";
            $params['category'] = $filters['category'];
        }

        // Search by name
        if (!empty($filters['search'])) {
            $sql .= " AND rp.product_name LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Filter by points range
        if (!empty($filters['points_min'])) {
            $sql .= " AND rp.required_points >= :points_min";
            $params['points_min'] = (int)$filters['points_min'];
        }
        if (!empty($filters['points_max'])) {
            $sql .= " AND rp.required_points <= :points_max";
            $params['points_max'] = (int)$filters['points_max'];
        }

        $sql .= " ORDER BY rp.is_featured DESC, rp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm theo ID
     */
    public function find(int $productId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM reward_products
            WHERE product_id = :product_id
        ");
        $stmt->execute(['product_id' => $productId]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    }

    /**
     * Tạo sản phẩm mới
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reward_products (
                business_id,
                product_name,
                description,
                category,
                required_points,
                stock_quantity,
                image_url,
                status,
                is_featured
            ) VALUES (
                :business_id,
                :product_name,
                :description,
                :category,
                :required_points,
                :stock_quantity,
                :image_url,
                :status,
                :is_featured
            )
        ");

        $stmt->execute([
            'business_id' => (int)$data['business_id'],
            'product_name' => $data['product_name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'other',
            'required_points' => (int)$data['required_points'],
            'stock_quantity' => (int)($data['stock_quantity'] ?? 0),
            'image_url' => $data['image_url'] ?? null,
            'status' => $data['status'] ?? 'active',
            'is_featured' => (bool)($data['is_featured'] ?? false)
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update(int $productId, array $data): bool
    {
        $fields = [];
        $params = ['product_id' => $productId];

        if (isset($data['product_name'])) {
            $fields[] = 'product_name = :product_name';
            $params['product_name'] = $data['product_name'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = :description';
            $params['description'] = $data['description'];
        }
        if (isset($data['category'])) {
            $fields[] = 'category = :category';
            $params['category'] = $data['category'];
        }
        if (isset($data['required_points'])) {
            $fields[] = 'required_points = :required_points';
            $params['required_points'] = (int)$data['required_points'];
        }
        if (isset($data['stock_quantity'])) {
            $fields[] = 'stock_quantity = :stock_quantity';
            $params['stock_quantity'] = (int)$data['stock_quantity'];
        }
        if (isset($data['image_url'])) {
            $fields[] = 'image_url = :image_url';
            $params['image_url'] = $data['image_url'];
        }
        if (isset($data['status'])) {
            $fields[] = 'status = :status';
            $params['status'] = $data['status'];
        }
        if (isset($data['is_featured'])) {
            $fields[] = 'is_featured = :is_featured';
            $params['is_featured'] = (bool)$data['is_featured'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE reward_products SET " . implode(', ', $fields) . " WHERE product_id = :product_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Xóa sản phẩm
     */
    public function delete(int $productId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reward_products WHERE product_id = :product_id");
        return $stmt->execute(['product_id' => $productId]);
    }

    /**
     * Cập nhật tồn kho
     */
    public function updateStock(int $productId, int $quantity, string $operation = 'add'): bool
    {
        if ($operation === 'add') {
            $sql = "UPDATE reward_products SET stock_quantity = stock_quantity + :quantity WHERE product_id = :product_id";
        } else {
            $sql = "UPDATE reward_products SET stock_quantity = stock_quantity - :quantity WHERE product_id = :product_id AND stock_quantity >= :quantity";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'product_id' => $productId,
            'quantity' => abs($quantity)
        ]);
    }

    /**
     * Cập nhật trạng thái
     */
    public function updateStatus(int $productId, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE reward_products SET status = :status WHERE product_id = :product_id");
        return $stmt->execute([
            'product_id' => $productId,
            'status' => $status
        ]);
    }

    /**
     * Tăng số lượt đổi
     */
    public function incrementRedeemed(int $productId, int $quantity = 1): bool
    {
        $stmt = $this->db->prepare("UPDATE reward_products SET total_redeemed = total_redeemed + :quantity WHERE product_id = :product_id");
        return $stmt->execute([
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }

    /**
     * Lấy thống kê sản phẩm của doanh nghiệp
     */
    public function getStatistics(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                SUM(CASE WHEN status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_products,
                SUM(total_redeemed) as total_redemptions,
                SUM(total_redeemed * required_points) as total_points_spent
            FROM reward_products
            WHERE business_id = :business_id
        ");
        $stmt->execute(['business_id' => $businessId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Kiểm tra sản phẩm có thuộc doanh nghiệp không
     */
    public function belongsToBusiness(int $productId, int $businessId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM reward_products WHERE product_id = :product_id AND business_id = :business_id");
        $stmt->execute([
            'product_id' => $productId,
            'business_id' => $businessId
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Đổi điểm lấy sản phẩm (có transaction)
     * @throws Exception
     */
    public function redeemProduct(int $householdId, int $productId, int $quantity = 1, array $deliveryInfo = []): int
    {
        try {
            $this->db->beginTransaction();

            // 1. Lock và lấy thông tin sản phẩm
            $stmt = $this->db->prepare("
                SELECT product_id, business_id, product_name, required_points, stock_quantity, status
                FROM reward_products 
                WHERE product_id = :product_id
                FOR UPDATE
            ");
            $stmt->execute(['product_id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại');
            }

            if ($product['status'] !== 'active') {
                throw new Exception('Sản phẩm không còn hoạt động');
            }

            if ($product['stock_quantity'] < $quantity) {
                throw new Exception('Sản phẩm không đủ số lượng');
            }

            // 2. Lấy điểm hiện tại của hộ (lock row)
            $stmt = $this->db->prepare("
                SELECT current_points 
                FROM household_profiles 
                WHERE user_id = :user_id 
                FOR UPDATE
            ");
            $stmt->execute(['user_id' => $householdId]);
            $currentPoints = (float)$stmt->fetchColumn();

            $totalPointsNeeded = $product['required_points'] * $quantity;

            if ($currentPoints < $totalPointsNeeded) {
                throw new Exception('Không đủ điểm để đổi sản phẩm');
            }

            // 3. Trừ điểm từ hộ gia đình
            $stmt = $this->db->prepare("
                UPDATE household_profiles 
                SET current_points = current_points - :points
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                'points' => $totalPointsNeeded,
                'user_id' => $householdId
            ]);

            // 4. Ghi log transaction điểm cho hộ gia đình (trừ)
            $stmt = $this->db->prepare("
                INSERT INTO household_point_transactions (household_id, amount, transaction_type, description)
                VALUES (:household_id, :amount, 'redemption', :description)
            ");
            $stmt->execute([
                'household_id' => $householdId,
                'amount' => -$totalPointsNeeded,
                'description' => 'Đổi ' . $quantity . 'x ' . $product['product_name']
            ]);

            // 5. Trừ stock sản phẩm
            $stmt = $this->db->prepare("
                UPDATE reward_products 
                SET stock_quantity = stock_quantity - :quantity,
                    total_redeemed = total_redeemed + :quantity
                WHERE product_id = :product_id
            ");
            $stmt->execute([
                'quantity' => $quantity,
                'product_id' => $productId
            ]);

            // 6. Tạo đơn đổi thưởng (business_id = NULL vì admin quản lý)
            // Lưu ý: Mã đổi thưởng sẽ được generate từ redemption_id khi cần (format: RD + redemption_id)
            $stmt = $this->db->prepare("
                INSERT INTO reward_redemptions (
                    household_id, product_id, business_id, quantity, points_spent,
                    status, delivery_address, delivery_phone, notes
                ) VALUES (
                    :household_id, :product_id, :business_id, :quantity, :points_spent,
                    'pending', :delivery_address, :delivery_phone, :notes
                )
            ");
            $stmt->execute([
                'household_id' => $householdId,
                'product_id' => $productId,
                'business_id' => NULL,
                'quantity' => $quantity,
                'points_spent' => $totalPointsNeeded,
                'delivery_address' => $deliveryInfo['address'] ?? '',
                'delivery_phone' => $deliveryInfo['phone'] ?? '',
                'notes' => $deliveryInfo['notes'] ?? ''
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
     * Sinh mã đổi thưởng unique
     * Format: RD + YYYYMMDD + 6 ký tự random
     * Ví dụ: RD20251116-A3F2B1
     */
    private function generateRedemptionCode(): string
    {
        $prefix = 'RD' . date('Ymd') . '-';
        $maxAttempts = 10;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            // Sinh 6 ký tự random (A-Z, 0-9)
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $randomString = '';
            for ($j = 0; $j < 6; $j++) {
                $randomString .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            $code = $prefix . $randomString;
            
            // Check unique
            $stmt = $this->db->prepare("SELECT 1 FROM reward_redemptions WHERE redemption_code = :code");
            $stmt->execute(['code' => $code]);
            
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        }
        
        throw new Exception('Không thể tạo mã đổi thưởng unique');
    }

    /**
     * Lấy thông tin đơn đổi thưởng theo mã
     * Hỗ trợ cả redemption_code trong DB và mã generate (RD + redemption_id)
     */
    public function getRedemptionByCode(string $code): ?array
    {
        // Nếu code có format RD + số (ví dụ: RD00000005), extract redemption_id
        $redemptionId = null;
        if (preg_match('/^RD(\d+)$/i', $code, $matches)) {
            $redemptionId = (int)$matches[1];
        }
        
        // Build query - tìm bằng redemption_code hoặc redemption_id
        if ($redemptionId !== null) {
            // Tìm bằng redemption_id (mã generate)
            $stmt = $this->db->prepare("
                SELECT 
                    rr.*,
                    rp.product_name,
                    rp.image_url,
                    rp.required_points,
                    h.full_name as household_name,
                    h.phone_number as household_phone
                FROM reward_redemptions rr
                JOIN reward_products rp ON rr.product_id = rp.product_id
                JOIN users h ON rr.household_id = h.user_id
                WHERE rr.redemption_id = :redemption_id
            ");
            $stmt->execute(['redemption_id' => $redemptionId]);
        } else {
            // Tìm bằng redemption_code trong DB (nếu có field này)
            $stmt = $this->db->prepare("
                SELECT 
                    rr.*,
                    rp.product_name,
                    rp.image_url,
                    rp.required_points,
                    h.full_name as household_name,
                    h.phone_number as household_phone
                FROM reward_redemptions rr
                JOIN reward_products rp ON rr.product_id = rp.product_id
                JOIN users h ON rr.household_id = h.user_id
                WHERE rr.redemption_code = :code
            ");
            $stmt->execute(['code' => $code]);
        }
        
        $redemption = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu tìm thấy, thêm redemption_code vào kết quả nếu chưa có
        if ($redemption && empty($redemption['redemption_code'])) {
            $redemption['redemption_code'] = 'RD' . str_pad((string)$redemption['redemption_id'], 8, '0', STR_PAD_LEFT);
        }
        
        return $redemption ?: null;
    }

    /**
     * Xác nhận giao hàng (khi quét QR)
     * Hỗ trợ cả redemption_code trong DB và mã generate (RD + redemption_id)
     */
    public function confirmDelivery(string $code, ?int $businessId = null): bool
    {
        try {
            $this->db->beginTransaction();

            // Nếu code có format RD + số (ví dụ: RD00000005), extract redemption_id
            $redemptionId = null;
            if (preg_match('/^RD(\d+)$/i', $code, $matches)) {
                $redemptionId = (int)$matches[1];
            }

            // Lấy thông tin đơn
            if ($redemptionId !== null) {
                // Tìm bằng redemption_id (mã generate)
                $stmt = $this->db->prepare("
                    SELECT redemption_id, business_id, status
                    FROM reward_redemptions
                    WHERE redemption_id = :redemption_id
                    FOR UPDATE
                ");
                $stmt->execute(['redemption_id' => $redemptionId]);
            } else {
                // Tìm bằng redemption_code trong DB (nếu có field này)
                $stmt = $this->db->prepare("
                    SELECT redemption_id, business_id, status
                    FROM reward_redemptions
                    WHERE redemption_code = :code
                    FOR UPDATE
                ");
                $stmt->execute(['code' => $code]);
            }
            
            $redemption = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$redemption) {
                throw new Exception('Không tìm thấy đơn đổi thưởng');
            }

            // Nếu businessId được cung cấp (business mode), kiểm tra ownership
            if ($businessId !== null && $redemption['business_id'] != $businessId) {
                throw new Exception('Đơn này không thuộc doanh nghiệp của bạn');
            }

            if ($redemption['status'] === 'delivered') {
                throw new Exception('Đơn đã được giao hàng rồi');
            }

            if ($redemption['status'] === 'cancelled') {
                throw new Exception('Đơn đã bị hủy');
            }

            // Cập nhật trạng thái
            if ($redemptionId !== null) {
                $stmt = $this->db->prepare("
                    UPDATE reward_redemptions
                    SET status = 'delivered',
                        delivered_at = NOW()
                    WHERE redemption_id = :redemption_id
                ");
                $stmt->execute(['redemption_id' => $redemptionId]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE reward_redemptions
                    SET status = 'delivered',
                        delivered_at = NOW()
                    WHERE redemption_code = :code
                ");
                $stmt->execute(['code' => $code]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy tất cả sản phẩm cho Admin (business_id IS NULL)
     * 
     * @param array $filters Filters: category, status, search
     * @return array Danh sách sản phẩm
     */
    public function findAllForAdmin(array $filters = []): array
    {
        $sql = "
            SELECT 
                product_id,
                product_name,
                description,
                category,
                required_points,
                stock_quantity,
                image_url,
                status,
                is_featured,
                total_redeemed,
                created_at,
                updated_at
            FROM reward_products
            WHERE business_id IS NULL
        ";

        $params = [];

        // Filter by category
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params['category'] = $filters['category'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        // Search by product name
        if (!empty($filters['search'])) {
            $sql .= " AND product_name LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY is_featured DESC, created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê cho Admin dashboard
     * 
     * @return array Thống kê: total_products, active_products, total_stock, total_redeemed
     */
    public function getAdminStatistics(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                SUM(stock_quantity) as total_stock,
                SUM(total_redeemed) as total_redeemed
            FROM reward_products
            WHERE business_id IS NULL
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết sản phẩm cho Admin (verify business_id IS NULL)
     * 
     * @param int $productId ID sản phẩm
     * @return array|null Thông tin sản phẩm hoặc null
     */
    public function findForAdmin(int $productId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM reward_products 
            WHERE product_id = :id AND business_id IS NULL
        ");
        $stmt->execute(['id' => $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Kiểm tra xem có đơn đổi thưởng nào đang dùng sản phẩm này không
     * 
     * @param int $productId ID sản phẩm
     * @return int Số lượng đơn đổi thưởng
     */
    public function countActiveRedemptions(int $productId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM reward_redemptions 
            WHERE product_id = :product_id 
              AND status IN ('pending', 'confirmed')
        ");
        $stmt->execute(['product_id' => $productId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Tạo sản phẩm mới cho admin (business_id = NULL)
     */
    public function createForAdmin(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reward_products (
                business_id, product_name, description, category, 
                required_points, stock_quantity, image_url, 
                status, is_featured
            ) VALUES (
                NULL, :product_name, :description, :category,
                :required_points, :stock_quantity, :image_url,
                'active', :is_featured
            )
        ");

        $stmt->execute([
            'product_name' => $data['product_name'],
            'description' => $data['description'],
            'category' => $data['category'],
            'required_points' => $data['required_points'],
            'stock_quantity' => $data['stock_quantity'],
            'image_url' => $data['image_url'],
            'is_featured' => $data['is_featured'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Cập nhật sản phẩm admin
     */
    public function updateForAdmin(int $productId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE reward_products SET
                product_name = :product_name,
                description = :description,
                category = :category,
                required_points = :required_points,
                stock_quantity = :stock_quantity,
                image_url = :image_url,
                is_featured = :is_featured,
                updated_at = CURRENT_TIMESTAMP
            WHERE product_id = :product_id AND business_id IS NULL
        ");

        return $stmt->execute([
            'product_name' => $data['product_name'],
            'description' => $data['description'],
            'category' => $data['category'],
            'required_points' => $data['required_points'],
            'stock_quantity' => $data['stock_quantity'],
            'image_url' => $data['image_url'],
            'is_featured' => $data['is_featured'] ?? 0,
            'product_id' => $productId,
        ]);
    }

    /**
     * Đếm tổng số redemption của một sản phẩm
     */
    public function countRedemptions(int $productId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reward_redemptions 
            WHERE product_id = :product_id
        ");
        $stmt->execute(['product_id' => $productId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Xóa hoặc inactive sản phẩm admin
     */
    public function deleteForAdmin(int $productId): array
    {
        // Kiểm tra xem có đơn đổi thưởng nào chưa
        $count = $this->countRedemptions($productId);

        if ($count > 0) {
            // Không xóa, chỉ inactive
            $stmt = $this->db->prepare("
                UPDATE reward_products 
                SET status = 'inactive', updated_at = CURRENT_TIMESTAMP
                WHERE product_id = :product_id AND business_id IS NULL
            ");
            $stmt->execute(['product_id' => $productId]);
            
            return [
                'success' => true,
                'message' => 'Đã ẩn sản phẩm (không thể xóa vì đã có đơn đổi thưởng)',
                'action' => 'inactive'
            ];
        } else {
            // Xóa hoàn toàn
            $stmt = $this->db->prepare("
                DELETE FROM reward_products 
                WHERE product_id = :product_id AND business_id IS NULL
            ");
            $stmt->execute(['product_id' => $productId]);
            
            return [
                'success' => true,
                'message' => 'Xóa sản phẩm thành công!',
                'action' => 'deleted'
            ];
        }
    }

    /**
     * Cập nhật trạng thái sản phẩm admin
     */
    public function updateStatusForAdmin(int $productId, string $status): bool
    {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception('Trạng thái không hợp lệ');
        }

        $stmt = $this->db->prepare("
            UPDATE reward_products 
            SET status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE product_id = :product_id AND business_id IS NULL
        ");
        
        return $stmt->execute([
            'status' => $status,
            'product_id' => $productId,
        ]);
    }
}
