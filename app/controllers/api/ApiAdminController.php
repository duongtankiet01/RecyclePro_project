<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../database/Database.php';
require_once __DIR__ . '/../../models/User.php';

class ApiAdminController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
            $this->jsonError('Không có quyền truy cập', 403);
        }
    }

    /**
     * Cập nhật trạng thái user
     * PUT /index.php?controller=api_admin&action=updateUserStatus
     * Body: { "user_id": 123, "action": "suspend|unlock|approve|reject|delete" }
     */
    public function updateUserStatus(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');

        // Lấy dữ liệu từ request
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['user_id']) || !isset($input['action'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu thông tin user_id hoặc action'
            ]);
            return;
        }

        $userId = (int)$input['user_id'];
        $action = $input['action'];

        try {
            $result = false;
            $message = '';

            switch ($action) {
                case 'suspend':
                    $result = $this->userModel->updateStatus($userId, 'suspended');
                    $message = 'Tài khoản đã bị tạm khóa';
                    break;

                case 'unlock':
                    $result = $this->userModel->updateStatus($userId, 'active');
                    $message = 'Tài khoản đã được mở khóa';
                    break;

                case 'approve':
                    $result = $this->userModel->updateStatus($userId, 'active');
                    $message = 'Tài khoản đã được duyệt';
                    break;

                case 'reject':
                    $result = $this->userModel->updateStatus($userId, 'rejected');
                    $message = 'Tài khoản đã bị từ chối';
                    break;

                case 'delete':
                    $result = $this->userModel->updateStatus($userId, 'deleted');
                    $message = 'Tài khoản đã được xóa';
                    break;

                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Action không hợp lệ'
                    ]);
                    return;
            }

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không thể cập nhật trạng thái'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error updating user status: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy chi tiết user
     * GET /index.php?controller=api_admin&action=getUserDetail&user_id=123
     */
    public function getUserDetail(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');

        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

        if ($userId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID không hợp lệ'
            ]);
            return;
        }

        try {
            $user = $this->userModel->getFullProfile($userId);
            
            if ($user) {
                $this->jsonSuccess('Lấy thông tin thành công', ['user' => $user]);
            } else {
                $this->jsonError('Không tìm thấy user', 404);
            }
        } catch (Exception $e) {
            error_log("Error fetching user detail: " . $e->getMessage());
            $this->jsonError('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Admin cập nhật thông tin user
     * POST /index.php?controller=api_admin&action=updateUser
     */
    public function updateUser(): void
    {
        $this->requireAdmin();

        $data = $this->getJsonInput();
        $userId = (int)($data['user_id'] ?? 0);

        if ($userId <= 0) {
            $this->jsonError('User ID không hợp lệ', 400);
            return;
        }

        try {
            $updated = $this->userModel->updateByAdmin($userId, $data);
            $this->jsonSuccess('Cập nhật tài khoản thành công', ['user' => $updated]);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }
}
