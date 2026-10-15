<?php
declare(strict_types=1);

class AuthController extends BaseController
{
    public function index(): void
    {
        $this->dangNhap();
    }

    public function dangNhap(): void
    {
        $this->render('auth/login', [
            'title' => 'Đăng nhập',
        ]);
    }

    public function dangKy(): void
    {
        $this->render('auth/register', [
            'title' => 'Đăng ký',
        ]);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        header('Location: index.php?controller=auth&action=dangNhap');
        exit;
    }
}
