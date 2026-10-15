<?php

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/RewardProduct.php';
require_once __DIR__ . '/../../models/RewardRedemption.php';

class AdminRewardProductController extends BaseController
{
    private RewardProduct $rewardProductModel;
    private RewardRedemption $redemptionModel;
    private PDO $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/database.php';
        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
            $config['user'],
            $config['password']
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->rewardProductModel = new RewardProduct($this->db);
        $this->redemptionModel = new RewardRedemption($this->db);
    }

    /**
     * Danh sách sản phẩm đổi điểm
     */
    public function index(): void
    {
        // ✅ SECURITY: Chỉ admin được truy cập
        $this->requireRole('admin');

        // Lấy filters từ query string
        $filters = [
            'category' => $_GET['category'] ?? '',
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        // Lấy sản phẩm từ Model
        $products = $this->rewardProductModel->findAllForAdmin($filters);

        // Lấy thống kê từ Model
        $stats = $this->rewardProductModel->getAdminStatistics();

        $this->render('admin/san-pham-doi-diem', [
            'currentUser' => $this->getCurrentUser(),
            'products' => $products,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Form thêm sản phẩm
     */
    public function create(): void
    {
        $this->requireRole('admin');
        // Render form hoặc trả về JSON nếu dùng modal
        echo json_encode(['success' => true, 'message' => 'Form thêm sản phẩm']);
    }

    /**
     * Lưu sản phẩm mới
     */
    public function store(): void
    {
        $this->requireRole('admin');

        try {
            // Validate input
            $productName = trim($_POST['product_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $requiredPoints = (int)($_POST['required_points'] ?? 0);
            $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
            $imageUrl = trim($_POST['image_url'] ?? '');
            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

            if (empty($productName) || empty($category) || $requiredPoints <= 0) {
                throw new Exception('Vui lòng điền đầy đủ thông tin sản phẩm');
            }

            // Insert product using model
            $this->rewardProductModel->createForAdmin([
                'product_name' => $productName,
                'description' => $description,
                'category' => $category,
                'required_points' => $requiredPoints,
                'stock_quantity' => $stockQuantity,
                'image_url' => $imageUrl,
                'is_featured' => $isFeatured,
            ]);

            $_SESSION['success_message'] = 'Thêm sản phẩm thành công!';
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;

        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;
        }
    }

    /**
     * Form sửa sản phẩm
     */
    public function edit(): void
    {
        $this->requireRole('admin');

        $productId = (int)($_GET['id'] ?? 0);

        $product = $this->rewardProductModel->findForAdmin($productId);

        if (!$product) {
            $_SESSION['error_message'] = 'Không tìm thấy sản phẩm';
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;
        }

        $this->render('admin/sua-san-pham-doi-diem', [
            'currentUser' => $this->getCurrentUser(),
            'product' => $product,
        ]);
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update(): void
    {
        $this->requireRole('admin');

        try {
            $productId = (int)($_POST['product_id'] ?? 0);
            $productName = trim($_POST['product_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $requiredPoints = (int)($_POST['required_points'] ?? 0);
            $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
            $imageUrl = trim($_POST['image_url'] ?? '');
            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

            if (empty($productName) || empty($category) || $requiredPoints <= 0) {
                throw new Exception('Vui lòng điền đầy đủ thông tin sản phẩm');
            }

            $this->rewardProductModel->updateForAdmin($productId, [
                'product_name' => $productName,
                'description' => $description,
                'category' => $category,
                'required_points' => $requiredPoints,
                'stock_quantity' => $stockQuantity,
                'image_url' => $imageUrl,
                'is_featured' => $isFeatured,
            ]);

            $_SESSION['success_message'] = 'Cập nhật sản phẩm thành công!';
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;

        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;
        }
    }

    /**
     * Xóa sản phẩm
     */
    public function delete(): void
    {
        $this->requireRole('admin');

        try {
            $productId = (int)($_POST['product_id'] ?? 0);

            $result = $this->rewardProductModel->deleteForAdmin($productId);

            $_SESSION['success_message'] = $result['message'];
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;

        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
            header('Location: index.php?controller=admin_reward_product&action=index');
            exit;
        }
    }

    /**
     * Bật/tắt trạng thái sản phẩm
     */
    public function toggleStatus(): void
    {
        $this->requireRole('admin');

        try {
            $productId = (int)($_POST['product_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');

            $this->rewardProductModel->updateStatusForAdmin($productId, $status);

            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Danh sách đơn đổi thưởng
     */
    public function redemptions(): void
    {
        $this->requireRole('admin');

        // Filters
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $filters = [];
        if (!empty($status)) {
            $filters['status'] = $status;
        }
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $redemptions = $this->redemptionModel->findAllForAdmin($filters);
        $stats = $this->redemptionModel->getAdminStatistics();

        $this->render('admin/don-doi-thuong', [
            'currentUser' => $this->getCurrentUser(),
            'redemptions' => $redemptions,
            'stats' => $stats,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    /**
     * Xác nhận giao hàng
     */
    public function confirmDelivery(): void
    {
        $this->requireRole('admin');

        try {
            $redemptionCode = trim($_POST['redemption_code'] ?? '');

            $rewardProduct = new RewardProduct($this->db);
            $rewardProduct->confirmDelivery($redemptionCode, null); // null = admin mode

            echo json_encode(['success' => true, 'message' => 'Xác nhận giao hàng thành công']);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
