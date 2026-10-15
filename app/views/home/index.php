<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecyclePro - Kết nối mọi hành trình tái chế</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #198754;
            --accent-color: #0d6efd;
            --light-bg: #f4f8f6;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: white;
            color: #1f2a37;
        }

        .hero {
            background: linear-gradient(135deg, rgba(25,135,84,0.1), rgba(13,110,253,0.12));
            min-height: 75vh;
            display: flex;
            align-items: center;
            padding: 5rem 0 3rem;
        }

        .hero-title {
            font-size: clamp(2.5rem, 4vw, 3.5rem);
            font-weight: 700;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            color: #4b5563;
        }

        .cta-btn {
            padding: 0.85rem 2.5rem;
            border-radius: 999px;
            font-weight: 600;
            box-shadow: 0 12px 30px -10px rgba(25,135,84,0.35);
        }

        .cta-secondary {
            border-radius: 999px;
            padding: 0.85rem 2.2rem;
            font-weight: 600;
        }

        .stats-card {
            background: white;
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 24px 60px -40px rgba(0,0,0,0.4);
        }

        .feature-card {
            border-radius: 20px;
            padding: 2rem;
            background: white;
            height: 100%;
            box-shadow: 0 12px 35px -25px rgba(13,110,253,0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 30px 45px -30px rgba(25,135,84,0.4);
        }

        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(25,135,84,0.15);
            color: var(--primary-color);
        }

        .step-card {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            background: white;
            height: 100%;
        }

        .step-number {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--primary-color);
            color: white;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .actors-card {
            background: var(--light-bg);
            border-radius: 18px;
            padding: 1.5rem;
            height: 100%;
        }

        footer {
            background: #0b1726;
            color: rgba(255,255,255,0.75);
            padding: 2.5rem 0;
        }

        .navbar {
            padding: 1.1rem 0;
        }

        .badge-soft-success {
            background: rgba(25,135,84,0.12);
            color: var(--primary-color);
            font-weight: 600;
        }

        .floating-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            background: linear-gradient(140deg, #146c43, #0d6efd);
            color: white;
        }

        .floating-card::after {
            content: '';
            position: absolute;
            top: -30%;
            right: -15%;
            width: 360px;
            height: 360px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            filter: blur(0px);
        }

        .floating-card small {
            color: rgba(255,255,255,0.8);
        }

        @media (max-width: 991px) {
            .hero {
                padding-top: 5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-success" href="index.php">
                <i class="fas fa-recycle me-2"></i>RecyclePro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="#gioi-thieu">Giới thiệu</a></li>
                    <li class="nav-item"><a class="nav-link" href="#loi-ich">Lợi ích</a></li>
                    <li class="nav-item"><a class="nav-link" href="#doi-tuong">Dành cho ai?</a></li>
                    <li class="nav-item"><a class="nav-link" href="#bat-dau">Bắt đầu</a></li>
                </ul>
                <div class="d-flex gap-2 ms-lg-4 mt-3 mt-lg-0">
                    <a href="index.php?controller=auth&action=dangNhap" class="btn btn-outline-success cta-secondary">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                    </a>
                    <a href="index.php?controller=auth&action=dangKy" class="btn btn-success cta-btn">
                        <i class="fas fa-user-plus me-2"></i>Đăng ký miễn phí
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero" id="gioi-thieu">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="badge badge-soft-success mb-3">Nền tảng quản lý tái chế toàn diện</span>
                    <h1 class="hero-title mb-4">Kết nối hộ gia đình, doanh nghiệp và cộng tác viên để tái chế hiệu quả hơn</h1>
                    <p class="hero-subtitle mb-4">
                        RecyclePro giúp tối ưu quá trình thu gom rác tái chế, minh bạch hóa giao dịch và tạo nên hệ sinh thái xanh bền vững cho mọi bên tham gia.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="index.php?controller=auth&action=dangKy" class="btn btn-success cta-btn">
                            <i class="fas fa-rocket me-2"></i>Bắt đầu ngay
                        </a>
                        <a href="index.php?controller=auth&action=dangNhap" class="btn btn-outline-success cta-secondary">
                            <i class="fas fa-play me-2"></i>Trải nghiệm demo
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <div class="stats-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-circle bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4">+18 nghìn</div>
                                    <small class="text-secondary">Đơn tái chế được xử lý mỗi tháng</small>
                                </div>
                            </div>
                        </div>
                        <div class="ms-lg-3">
                            <small class="text-muted d-block">Được tin dùng bởi</small>
                            <div class="fw-semibold">12k+ hộ gia đình • 240 doanh nghiệp</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="floating-card p-4 p-lg-5 mt-4 mt-lg-0">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="icon-circle bg-white bg-opacity-10 text-white">
                                <i class="fas fa-chart-pie"></i>
                            </span>
                            <div>
                                <h4 class="mb-1">Theo dõi</h4>
                                <small>Quản lý đơn thu gom, đổi điểm và thanh toán ngay trên dashboard</small>
                            </div>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-6">
                                <div class="actors-card h-100 bg-white text-dark">
                                    <div class="icon-circle mb-3" style="background: rgba(13,110,253,0.15); color: #0d6efd;">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <h6 class="fw-semibold">Hộ gia đình</h6>
                                    <small>Đăng đơn nhanh, theo dõi lịch sử và tích điểm linh hoạt.</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="actors-card h-100 bg-white text-dark">
                                    <div class="icon-circle mb-3" style="background: rgba(25,135,84,0.15); color: var(--primary-color);">
                                        <i class="fas fa-hand-holding"></i>
                                    </div>
                                    <h6 class="fw-semibold">Doanh nghiệp & CTV</h6>
                                    <small>Quản lý chiến dịch đổi điểm, đơn thu gom và tài chính tập trung.</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <small class="text-white-50">Tích hợp hoàn toàn với RecyclePro Admin để báo cáo và phân tích.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="loi-ich">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">Lợi ích nổi bật</span>
                <h2 class="mt-3">Một nền tảng, ba trải nghiệm tối ưu</h2>
                <p class="text-muted">RecyclePro đồng bộ quy trình từ phát sinh nhu cầu đến thu gom, đổi điểm và báo cáo tài chính.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-circle mb-3"><i class="fas fa-bolt"></i></div>
                        <h5>Đăng đơn và nhận đơn linh hoạt</h5>
                        <p class="text-muted">Doanh nghiệp tạo đơn thu gom, hộ gia đình chọn đơn gần nhà trong 1 phút. CTV chủ động tìm và nhận đơn phù hợp theo khu vực.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-circle mb-3"><i class="fas fa-gift"></i></div>
                        <h5>Đa dạng hình thức quy đổi</h5>
                        <p class="text-muted">Tích điểm đổi quà, nhận tiền mặt hoặc chuyển đổi thành tín dụng môi trường chỉ trong vài thao tác.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-circle mb-3"><i class="fas fa-shield-alt"></i></div>
                        <h5>Giám sát minh bạch</h5>
                        <p class="text-muted">Admin theo dõi toàn bộ hoạt động, xử lý tranh chấp, phê duyệt rút tiền và xuất báo cáo chỉ với một bảng điều khiển.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light" id="doi-tuong">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <h2 class="fw-bold mb-3">Dành cho mọi đối tượng trong hệ sinh thái tái chế</h2>
                    <p class="text-muted">RecyclePro được thiết kế riêng cho từng vai trò với trải nghiệm thân thiện nhưng vẫn mạnh mẽ.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="actors-card h-100">
                                <h6 class="fw-bold text-success">Hộ gia đình</h6>
                                <ul class="text-muted small ps-3 mb-0">
                                    <li>Đăng đơn thu gom, theo dõi trạng thái</li>
                                    <li>Tích điểm đổi quà, rút ví điện tử</li>
                                    <li>Lịch sử giao dịch rõ ràng</li>
                                </ul>
                                <a href="index.php?controller=auth&action=dangNhap" class="btn btn-link text-success p-0 mt-3">Truy cập trang hộ gia đình <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="actors-card h-100">
                                <h6 class="fw-bold text-success">Cộng tác viên thu gom</h6>
                                <ul class="text-muted small ps-3 mb-0">
                                    <li>Nhận đơn gần nhất, tối ưu lộ trình</li>
                                    <li>Quản lý ví điểm và lịch làm việc</li>
                                    <li>Xử lý xác nhận thu gom nhanh chóng</li>
                                </ul>
                                <a href="index.php?controller=auth&action=dangNhap" class="btn btn-link text-success p-0 mt-3">Truy cập trang CTV <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="actors-card h-100">
                                <h6 class="fw-bold text-success">Doanh nghiệp thu mua</h6>
                                <ul class="text-muted small ps-3 mb-0">
                                    <li>Quản lý đơn hàng, chiến dịch đổi điểm</li>
                                    <li>Theo dõi tồn kho tín dụng môi trường</li>
                                    <li>Tự động hóa xác nhận giao rác</li>
                                </ul>
                                <a href="index.php?controller=auth&action=dangNhap" class="btn btn-link text-success p-0 mt-3">Truy cập trang doanh nghiệp <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="actors-card h-100">
                                <h6 class="fw-bold text-success">Ban quản trị</h6>
                                <ul class="text-muted small ps-3 mb-0">
                                    <li>Dashboard báo cáo và thống kê</li>
                                    <li>Quản lý tài khoản, rút tiền, tranh chấp</li>
                                    <li>Phát triển chính sách khuyến khích</li>
                                </ul>
                                <a href="index.php?controller=auth&action=dangNhap" class="btn btn-link text-success p-0 mt-3">Truy cập RecyclePro Admin <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="bat-dau">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <h2 class="fw-bold mb-4">Bắt đầu chỉ trong ba bước</h2>
                    <p class="text-muted">Đăng ký tài khoản miễn phí, tùy chọn vai trò phù hợp và trải nghiệm hệ thống tái chế thông minh ngay hôm nay.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="step-card">
                                <div class="step-number">1</div>
                                <h6 class="fw-semibold">Đăng ký tài khoản</h6>
                                <p class="text-muted small">Chọn vai trò (Hộ gia đình, Doanh nghiệp, CTV) và hoàn tất thông tin cơ bản.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="step-card">
                                <div class="step-number">2</div>
                                <h6 class="fw-semibold">Thiết lập hồ sơ</h6>
                                <p class="text-muted small">Cập nhật địa chỉ, khu vực hoạt động, thông tin thanh toán và tài liệu cần thiết.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="step-card">
                                <div class="step-number">3</div>
                                <h6 class="fw-semibold">Khởi tạo hoạt động</h6>
                                <p class="text-muted small">Đăng đơn thu gom, nhận đơn phù hợp hoặc tạo chiến dịch đổi điểm chỉ bằng vài cú click.</p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="index.php?controller=auth&action=dangKy" class="btn btn-success cta-btn">
                            <i class="fas fa-user-circle me-2"></i>Tạo tài khoản ngay
                        </a>
                        <a href="index.php?controller=auth&action=dangNhap" class="btn btn-outline-success cta-secondary">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập nếu đã có tài khoản
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h5 class="text-white mb-3">RecyclePro</h5>
                    <p class="mb-0">Nền tảng quản trị tái chế toàn diện, hướng tới mục tiêu giảm rác thải và tạo ra các giá trị bền vững cho cộng đồng.</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <div class="d-flex justify-content-lg-end gap-3">
                        <a href="#gioi-thieu" class="link-light text-decoration-none">Giới thiệu</a>
                        <a href="#loi-ich" class="link-light text-decoration-none">Tính năng</a>
                        <a href="index.php?controller=auth&action=dangNhap" class="link-light text-decoration-none">Đăng nhập</a>
                        <a href="index.php?controller=auth&action=dangKy" class="link-light text-decoration-none">Đăng ký</a>
                    </div>
                    <small class="d-block mt-3">© <?php echo date('Y'); ?> RecyclePro. All rights reserved.</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
