<?php

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/RewardProduct.php';

class ApiRewardProductController extends BaseController
{
    private PDO $db;
    private RewardProduct $rewardProductModel;

    public function __construct()
    {
        // Tạo kết nối database
        $config = require __DIR__ . '/../../../config/database.php';
        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
            $config['user'],
            $config['password']
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->rewardProductModel = new RewardProduct($this->db);
    }

    /**
     * Lấy danh sách sản phẩm của doanh nghiệp
     * GET /index.php?controller=api_reward_product&action=list
     */
    public function list(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được truy cập', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];

            // Lấy filters
            $filters = [];
            if (isset($_GET['category'])) {
                $filters['category'] = $_GET['category'];
            }
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (isset($_GET['points_min'])) {
                $filters['points_min'] = (int)$_GET['points_min'];
            }
            if (isset($_GET['points_max'])) {
                $filters['points_max'] = (int)$_GET['points_max'];
            }

            $products = $this->rewardProductModel->findByBusiness($businessId, $filters);
            $statistics = $this->rewardProductModel->getStatistics($businessId);

            $this->jsonSuccess('Lấy danh sách sản phẩm thành công', [
                'products' => $products,
                'statistics' => $statistics
            ]);

        } catch (Exception $e) {
            error_log('Get reward products error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi lấy danh sách sản phẩm', 500);
        }
    }

    /**
     * Lấy danh sách sản phẩm công khai (dành cho hộ gia đình)
     * GET /index.php?controller=api_reward_product&action=list_public
     */
    public function list_public(): void
    {
        try {
            // Lấy filters
            $filters = [];
            if (isset($_GET['category'])) {
                $filters['category'] = $_GET['category'];
            }
            if (isset($_GET['business_id'])) {
                $filters['business_id'] = (int)$_GET['business_id'];
            }
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (isset($_GET['points_min'])) {
                $filters['points_min'] = (int)$_GET['points_min'];
            }
            if (isset($_GET['points_max'])) {
                $filters['points_max'] = (int)$_GET['points_max'];
            }

            $products = $this->rewardProductModel->findAllActive($filters);

            $this->jsonSuccess('Lấy danh sách sản phẩm thành công', [
                'products' => $products,
                'total' => count($products)
            ]);

        } catch (Exception $e) {
            error_log('Get public products error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi lấy danh sách sản phẩm', 500);
        }
    }

    /**
     * Tạo sản phẩm mới
     * POST /index.php?controller=api_reward_product&action=create
     */
    public function create(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được truy cập', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            // Validate required fields
            if (empty($data['product_name'])) {
                $this->jsonError('Tên sản phẩm không được để trống', 422);
                return;
            }

            if (empty($data['required_points']) || $data['required_points'] <= 0) {
                $this->jsonError('Điểm cần thiết phải lớn hơn 0', 422);
                return;
            }

            if (!isset($data['stock_quantity']) || $data['stock_quantity'] < 0) {
                $this->jsonError('Số lượng tồn kho không hợp lệ', 422);
                return;
            }

            // Validate category
            $validCategories = ['household', 'food', 'personal', 'eco', 'other'];
            if (isset($data['category']) && !in_array($data['category'], $validCategories)) {
                $this->jsonError('Danh mục không hợp lệ', 422);
                return;
            }

            $data['business_id'] = $businessId;
            $productId = $this->rewardProductModel->create($data);

            $product = $this->rewardProductModel->find($productId);

            $this->jsonSuccess('Tạo sản phẩm thành công', [
                'product' => $product
            ]);

        } catch (Exception $e) {
            error_log('Create reward product error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi tạo sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật sản phẩm
     * PUT /index.php?controller=api_reward_product&action=update
     */
    public function update(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được truy cập', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            if (empty($data['product_id'])) {
                $this->jsonError('Product ID không được để trống', 422);
                return;
            }

            $productId = (int)$data['product_id'];

            // Kiểm tra sản phẩm có thuộc doanh nghiệp không
            if (!$this->rewardProductModel->belongsToBusiness($productId, $businessId)) {
                $this->jsonError('Sản phẩm không tồn tại hoặc không thuộc doanh nghiệp này', 404);
                return;
            }

            // Validate nếu có
            if (isset($data['required_points']) && $data['required_points'] <= 0) {
                $this->jsonError('Điểm cần thiết phải lớn hơn 0', 422);
                return;
            }

            unset($data['product_id']);
            unset($data['business_id']); // Không cho phép thay đổi business_id

            $success = $this->rewardProductModel->update($productId, $data);

            if ($success) {
                $product = $this->rewardProductModel->find($productId);
                $this->jsonSuccess('Cập nhật sản phẩm thành công', [
                    'product' => $product
                ]);
            } else {
                $this->jsonError('Không có gì thay đổi', 400);
            }

        } catch (Exception $e) {
            error_log('Update reward product error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi cập nhật sản phẩm', 500);
        }
    }

    /**
     * Xóa sản phẩm
     * DELETE /index.php?controller=api_reward_product&action=delete
     */
    public function delete(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được truy cập', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            if (empty($data['product_id'])) {
                $this->jsonError('Product ID không được để trống', 422);
                return;
            }

            $productId = (int)$data['product_id'];

            // Kiểm tra sản phẩm có thuộc doanh nghiệp không
            if (!$this->rewardProductModel->belongsToBusiness($productId, $businessId)) {
                $this->jsonError('Sản phẩm không tồn tại hoặc không thuộc doanh nghiệp này', 404);
                return;
            }

            $success = $this->rewardProductModel->delete($productId);

            if ($success) {
                $this->jsonSuccess('Xóa sản phẩm thành công');
            } else {
                $this->jsonError('Không thể xóa sản phẩm', 500);
            }

        } catch (Exception $e) {
            error_log('Delete reward product error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi xóa sản phẩm', 500);
        }
    }

    /**
     * Cập nhật trạng thái sản phẩm
     * PUT /index.php?controller=api_reward_product&action=update_status
     */
    public function update_status(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được truy cập', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            if (empty($data['product_id']) || empty($data['status'])) {
                $this->jsonError('Product ID và status không được để trống', 422);
                return;
            }

            $productId = (int)$data['product_id'];
            $status = $data['status'];

            // Validate status
            $validStatuses = ['active', 'inactive', 'out_of_stock'];
            if (!in_array($status, $validStatuses)) {
                $this->jsonError('Trạng thái không hợp lệ', 422);
                return;
            }

            // Kiểm tra quyền
            if (!$this->rewardProductModel->belongsToBusiness($productId, $businessId)) {
                $this->jsonError('Sản phẩm không tồn tại hoặc không thuộc doanh nghiệp này', 404);
                return;
            }

            $success = $this->rewardProductModel->updateStatus($productId, $status);

            if ($success) {
                $this->jsonSuccess('Cập nhật trạng thái thành công', [
                    'product_id' => $productId,
                    'new_status' => $status
                ]);
            } else {
                $this->jsonError('Không thể cập nhật trạng thái', 500);
            }

        } catch (Exception $e) {
            error_log('Update product status error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi cập nhật trạng thái', 500);
        }
    }

    /**
     * Cập nhật tồn kho
     * PUT /index.php?controller=api_reward_product&action=update_stock
     */
    public function update_stock(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được truy cập', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            if (empty($data['product_id']) || !isset($data['quantity'])) {
                $this->jsonError('Product ID và quantity không được để trống', 422);
                return;
            }

            $productId = (int)$data['product_id'];
            $quantity = (int)$data['quantity'];
            $operation = $data['operation'] ?? 'add';

            if ($quantity <= 0) {
                $this->jsonError('Số lượng phải > 0', 422);
                return;
            }

            // Kiểm tra quyền
            if (!$this->rewardProductModel->belongsToBusiness($productId, $businessId)) {
                $this->jsonError('Sản phẩm không tồn tại hoặc không thuộc doanh nghiệp này', 404);
                return;
            }

            $success = $this->rewardProductModel->updateStock($productId, $quantity, $operation);

            if ($success) {
                $product = $this->rewardProductModel->find($productId);
                $this->jsonSuccess('Cập nhật tồn kho thành công', [
                    'product' => $product
                ]);
            } else {
                $this->jsonError('Không thể cập nhật tồn kho', 500);
            }

        } catch (Exception $e) {
            error_log('Update stock error: ' . $e->getMessage());
            $this->jsonError('Lỗi khi cập nhật tồn kho', 500);
        }
    }

    /**
     * Đổi điểm lấy sản phẩm
     * POST /index.php?controller=api_reward_product&action=redeem
     */
    public function redeem(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'household') {
                $this->jsonError('Chỉ hộ gia đình mới được đổi điểm', 403);
                return;
            }

            $householdId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            // Validate
            if (empty($data['product_id'])) {
                $this->jsonError('Thiếu thông tin sản phẩm', 422);
                return;
            }

            $productId = (int)$data['product_id'];
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

            if ($quantity < 1) {
                $this->jsonError('Số lượng phải >= 1', 422);
                return;
            }

            // Lấy thông tin giao hàng
            $deliveryInfo = [
                'address' => $data['delivery_address'] ?? '',
                'phone' => $data['delivery_phone'] ?? '',
                'notes' => $data['notes'] ?? ''
            ];

            // Thực hiện đổi điểm
            $redemptionId = $this->rewardProductModel->redeemProduct(
                $householdId,
                $productId,
                $quantity,
                $deliveryInfo
            );

            $this->jsonSuccess('Đổi điểm thành công!', [
                'redemption_id' => $redemptionId,
                'message' => 'Đơn đổi thưởng đã được tạo. Vui lòng mang mã đổi thưởng đến Admin để nhận quà.'
            ], 201);

        } catch (Exception $e) {
            error_log('Redeem product error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Kiểm tra mã đổi thưởng
     * POST /index.php?controller=api_reward_product&action=verify_redemption
     */
    public function verify_redemption(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                $this->jsonError('Chỉ admin mới được kiểm tra mã', 403);
                return;
            }

            // Đọc dữ liệu JSON
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['redemption_code'])) {
                $this->jsonError('Vui lòng nhập mã đổi thưởng', 400);
                return;
            }

            $code = trim($input['redemption_code']);

            // Lấy thông tin đơn
            $redemption = $this->rewardProductModel->getRedemptionByCode($code);

            if (!$redemption) {
                $this->jsonError('Mã đổi thưởng không tồn tại', 404);
                return;
            }

            if ($redemption['status'] === 'delivered') {
                $this->jsonError('Đơn này đã được giao hàng rồi', 400);
                return;
            }

            if ($redemption['status'] === 'cancelled') {
                $this->jsonError('Đơn này đã bị hủy', 400);
                return;
            }

            $this->jsonSuccess('Mã hợp lệ', [
                'redemption' => $redemption
            ]);

        } catch (Exception $e) {
            error_log('Verify redemption error: ' . $e->getMessage());
            $this->jsonError('Lỗi hệ thống: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xác nhận giao hàng
     * POST /index.php?controller=api_reward_product&action=complete_redemption
     */
    public function complete_redemption(): void
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                $this->jsonError('Chỉ admin mới được xác nhận giao hàng', 403);
                return;
            }

            // Đọc dữ liệu JSON
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['redemption_code'])) {
                $this->jsonError('Vui lòng nhập mã đổi thưởng', 400);
                return;
            }

            $code = trim($input['redemption_code']);

            // Xác nhận giao hàng (admin mode - không cần kiểm tra business_id)
            $this->rewardProductModel->confirmDelivery($code, null);

            $this->jsonSuccess('Đã xác nhận giao hàng thành công!');

        } catch (Exception $e) {
            error_log('Complete redemption error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }
}
