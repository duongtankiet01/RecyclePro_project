<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Đăng nhập - RecyclePro'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, rgba(25,135,84,0.15) 0%, rgba(13,110,253,0.12) 100%);
        }

        .auth-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .auth-card {
            max-width: 880px;
            width: 100%;
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 60px -40px rgba(15,118,110,0.45);
        }

        .auth-header {
            background: linear-gradient(135deg, #146c43, #0d6efd);
            color: #fff;
            padding: 2.5rem;
        }

        .auth-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .auth-body {
            padding: 2.5rem;
            background: #fff;
        }

        .input-group-text {
            background: rgba(25,135,84,0.12);
            border: 1px solid rgba(0,0,0,0.08);
            border-right: none;
            color: #198754;
        }

        .input-group .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-white text-success rounded-pill px-3 py-2 fw-semibold">RecyclePro</span>
                        <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-semibold">Tái chế xanh</span>
                    </div>
                    <h1 class="mb-2">Chào mừng quay trở lại!</h1>
                    <p class="mb-0">Đăng nhập để tiếp tục hành trình tái chế thông minh.</p>
                </div>
                <div class="text-lg-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Về trang chủ
                    </a>
                </div>
            </div>
            <div class="auth-body">
                <div class="row g-4">
                    <div class="col-lg-6 border-end">
                        <h2 class="h4 fw-bold mb-4">Đăng nhập</h2>
                        <form class="row g-3 needs-validation auth-form" data-mode="login" novalidate>
                            <div class="col-12">
                                <label for="loginEmail" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="loginEmail" data-field="email" required>
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                            </div>
                            <div class="col-12">
                                <label for="loginPassword" class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="loginPassword" data-field="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('loginPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-arrow-right-to-bracket me-2"></i>Đăng nhập
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <div class="h-100 d-flex flex-column justify-content-center">
                            <h3 class="fw-semibold mb-3">Chưa có tài khoản?</h3>
                            <p class="text-muted">Lựa chọn vai trò phù hợp (Hộ gia đình, Doanh nghiệp, CTV) và hoàn tất đăng ký chỉ trong vài phút.</p>
                            <a href="index.php?controller=auth&action=dangKy" class="btn btn-success btn-lg mt-2">
                                <i class="fas fa-user-plus me-2"></i>Tạo tài khoản ngay
                            </a>
                            <hr class="my-4">
                            <h6 class="fw-semibold mb-3">Đăng nhập nhanh theo vai trò</h6>
                            <ul class="list-unstyled text-muted small mb-0">
                                <li class="mb-2"><i class="fas fa-home me-2 text-success"></i>Hộ gia đình: Quản lý đơn, tích điểm</li>
                                <li class="mb-2"><i class="fas fa-recycle me-2 text-success"></i>CTV: Nhận đơn gần nhất</li>
                                <li class="mb-2"><i class="fas fa-building me-2 text-success"></i>Doanh nghiệp: Quản lý chiến dịch</li>
                                <li><i class="fas fa-user-shield me-2 text-success"></i>Admin: Vận hành hệ thống</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

