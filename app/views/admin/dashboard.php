<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng quan - RecyclePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <nav class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-recycle me-2"></i>RecyclePro Admin</h4>
            <p>Admin Panel</p>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php?controller=admin&action=dashboard" class="nav-link active" data-page="dashboard">
                    <i class="fas fa-home"></i>
                    Trang chủ
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=quanLyTaiKhoan" class="nav-link" data-page="accounts">
                    <i class="fas fa-users"></i>
                    Quản lý tài khoản
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin_reward_product&action=index" class="nav-link" data-page="reward-products">
                    <i class="fas fa-gift"></i>
                    Quản lý sản phẩm
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin_reward_product&action=redemptions" class="nav-link" data-page="redemptions">
                    <i class="fas fa-exchange-alt"></i>
                    Đơn đổi thưởng
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=quanLyRutTien" class="nav-link" data-page="withdrawals">
                    <i class="fas fa-wallet"></i>
                    Quản lý rút tiền
                </a>
            </div>
            
            <div class="nav-divider"></div>
            
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-content">
                <div class="header-title">
                    <button class="btn btn-link d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1>Trang chủ</h1>
                        <p class="header-breadcrumb">Tổng quan hệ thống</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <span id="adminName">Admin</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="logout()">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="admin-content">
            <!-- Stats Overview -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Tổng đơn hôm nay</span>
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="todayOrders">0</div>
                    <div class="stat-change">
                        <span class="text-muted">Đơn hàng hôm nay</span>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-header">
                        <span class="stat-title">Chờ xử lý</span>
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="pendingItems">0</div>
                    <div class="stat-change">
                        <a href="index.php?controller=admin&action=quanLyRutTien" class="text-decoration-none">Xem chi tiết →</a>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-title">Doanh thu hôm nay</span>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="todayRevenue">0đ</div>
                    <div class="stat-change">
                        <span class="text-muted">Điểm đã hoàn thành</span>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-header">
                        <span class="stat-title">Tổng tài khoản</span>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="totalAccounts">0</div>
                    <div class="stat-change">
                        <a href="index.php?controller=admin&action=quanLyTaiKhoan" class="text-decoration-none">Quản lý →</a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Cần xử lý ngay</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-money-check-alt text-warning me-2"></i>
                                            <strong>Yêu cầu rút tiền</strong>
                                            <br><small class="text-muted">Chờ duyệt</small>
                                        </div>
                                        <a href="index.php?controller=admin&action=quanLyRutTien" class="btn btn-sm btn-outline-warning">Xử lý</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Thống kê nhanh</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="h4 text-primary" id="totalOrdersStat">0</div>
                                    <small class="text-muted">Tổng đơn</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 text-success" id="totalRevenueStat">0đ</div>
                                    <small class="text-muted">Tổng doanh thu</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 text-info" id="totalUsersStat">0</div>
                                    <small class="text-muted">Người dùng</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 text-warning" id="pendingWithdrawalsStat">0</div>
                                    <small class="text-muted">Chờ thanh toán</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    class Dashboard {
        constructor() {
            this.loadDashboardData();
        }
        
        loadDashboardData() {
            // Lấy dữ liệu từ PHP
            const stats = <?php echo json_encode($summary); ?>;
            this.updateStats(stats);
        }
        
        updateStats(stats) {
            document.getElementById('todayOrders').textContent = this.formatNumber(stats.todayOrders || 0);
            document.getElementById('pendingItems').textContent = this.formatNumber(stats.pendingWithdrawals || 0);
            document.getElementById('todayRevenue').textContent = this.formatCurrency(stats.todayRevenue || 0);
            document.getElementById('totalAccounts').textContent = this.formatNumber(stats.totalUsers || 0);
            document.getElementById('totalOrdersStat').textContent = this.formatNumber(stats.totalOrders || 0);
            document.getElementById('totalRevenueStat').textContent = this.formatCurrency(stats.totalRevenue || 0);
            document.getElementById('totalUsersStat').textContent = this.formatNumber(stats.totalUsers || 0);
            document.getElementById('pendingWithdrawalsStat').textContent = this.formatNumber(stats.pendingWithdrawals || 0);
        }
        
        formatNumber(num) {
            return Number(num).toLocaleString('vi-VN');
        }
        
        formatCurrency(num) {
            return Number(num).toLocaleString('vi-VN') + 'đ';
        }
    }

    function logout() {
        if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
            window.location.href = 'index.php?controller=auth&action=logout';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        new Dashboard();
        
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    });
    </script>
</body>
</html>
