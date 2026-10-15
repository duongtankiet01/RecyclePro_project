<?php
abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "View not found: {$view}";
            return;
        }

        extract($data);
        require $viewFile;
    }

    /**
     * Lấy thông tin user hiện tại từ session
     * Method này sẽ được override trong các controller cụ thể nếu cần thông tin chi tiết hơn
     */
    protected function getCurrentUser(): array
    {
        if (!isset($_SESSION['user_id'])) {
            return [
                'id' => null,
                'full_name' => 'Khách',
                'email' => '',
                'role' => 'guest',
            ];
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'full_name' => $_SESSION['user_name'] ?? 'Người dùng',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'guest',
        ];
    }

    /**
     * Trả về JSON success response
     */
    protected function jsonSuccess(string $message, array $data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Trả về JSON error response
     */
    protected function jsonError(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Lấy JSON input từ request body
     */
    protected function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?? [];
    }

    /**
     * Bắt buộc người dùng phải đăng nhập
     * Nếu chưa đăng nhập, redirect về trang đăng nhập
     */
    protected function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            // Lưu URL hiện tại để redirect sau khi đăng nhập
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
            
            // Redirect về trang chủ (có thể customize theo role)
            header('Location: /KLTN_2025/index.php?controller=home&action=index');
            exit;
        }
    }

    /**
     * Bắt buộc người dùng phải có role cụ thể
     * Nếu không đúng role, hiển thị 403 Forbidden
     * 
     * @param string $requiredRole Role cần thiết: 'business', 'household', 'collector', 'admin'
     */
    protected function requireRole(string $requiredRole): void
    {
        // Trước tiên phải đăng nhập
        $this->requireAuth();
        
        $userRole = $_SESSION['user_role'] ?? 'guest';
        
        if ($userRole !== $requiredRole) {
            http_response_code(403);
            echo $this->renderForbiddenPage($requiredRole);
            exit;
        }
    }

    /**
     * Bắt buộc người dùng phải có 1 trong nhiều roles
     * 
     * @param array $allowedRoles Danh sách các role được phép
     */
    protected function requireRoles(array $allowedRoles): void
    {
        // Trước tiên phải đăng nhập
        $this->requireAuth();
        
        $userRole = $_SESSION['user_role'] ?? 'guest';
        
        if (!in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            echo $this->renderForbiddenPage(implode(', ', $allowedRoles));
            exit;
        }
    }

    /**
     * Render trang 403 Forbidden
     */
    private function renderForbiddenPage(string $requiredRole): string
    {
        $currentRole = $_SESSION['user_role'] ?? 'guest';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không có quyền truy cập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <i class="fas fa-ban text-danger" style="font-size: 120px;"></i>
                <h1 class="mt-4">403 - Không có quyền truy cập</h1>
                <p class="text-muted mb-4">
                    Bạn không có quyền truy cập vào trang này.<br>
                    <small>
                        <strong>Role yêu cầu:</strong> {$requiredRole}<br>
                        <strong>Role hiện tại:</strong> {$currentRole}
                    </small>
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <a href="/KLTN_2025/index.php?controller=home&action=index" class="btn btn-primary">
                        <i class="fas fa-home"></i> Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
