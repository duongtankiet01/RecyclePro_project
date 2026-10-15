<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{PAGE_TITLE}} - RecyclePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <nav class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-recycle me-2"></i>RecyclePro</h4>
            <p>Admin Panel</p>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php?controller=admin&action=dashboard" class="nav-link" data-page="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Tổng quan
                </a>
            </div>
            
            <div class="nav-divider"></div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=quanLyTaiKhoan" class="nav-link" data-page="accounts">
                    <i class="fas fa-users"></i>
                    Quản lý tài khoản
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=quanLyDon" class="nav-link" data-page="orders">
                    <i class="fas fa-clipboard-list"></i>
                    Quản lý đơn thu gom
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=baoCaoThongKe" class="nav-link" data-page="reports">
                    <i class="fas fa-chart-bar"></i>
                    Lập báo cáo
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=quanLyRutTien" class="nav-link" data-page="withdrawals">
                    <i class="fas fa-money-check-alt"></i>
                    Thanh toán
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
                        <h1>{{PAGE_TITLE}}</h1>
                        <p class="header-breadcrumb">{{BREADCRUMB}}</p>
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
            {{CONTENT}}
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Common Modals -->
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="fas fa-question-circle me-2"></i>Xác nhận
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    Bạn có chắc chắn muốn thực hiện hành động này?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="confirmModalAction">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">
                        <i class="fas fa-user me-2"></i>Hồ sơ Admin
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="profileForm">
                        <div class="mb-3">
                            <label for="profileEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="profileEmail" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="profileName" class="form-label">Tên hiển thị</label>
                            <input type="text" class="form-control" id="profileName">
                        </div>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="currentPassword">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="newPassword">
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirmPassword">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="updateProfile()">Cập nhật</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global Admin Layout Functions
        class AdminLayout {
            constructor() {
                this.currentPage = this.getCurrentPage();
                this.initializeLayout();
                this.checkAuthentication();
            }
            
            getCurrentPage() {
                const path = window.location.pathname;
                const filename = path.split('/').pop().split('.')[0];
                return filename;
            }
            
            initializeLayout() {
                // Set active navigation
                this.setActiveNavigation();
                
                // Initialize sidebar toggle for mobile
                this.initializeSidebarToggle();
                
                // Initialize tooltips
                this.initializeTooltips();
                
                // Load admin info
                this.loadAdminInfo();
            }
            
            setActiveNavigation() {
                const navLinks = document.querySelectorAll('.nav-link[data-page]');
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.dataset.page === this.currentPage) {
                        link.classList.add('active');
                    }
                });
            }
            
            initializeSidebarToggle() {
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebar = document.getElementById('sidebar');
                
                if (sidebarToggle) {
                    sidebarToggle.addEventListener('click', () => {
                        sidebar.classList.toggle('show');
                    });
                }
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', (e) => {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            sidebar.classList.remove('show');
                        }
                    }
                });
            }
            
            initializeTooltips() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            
            checkAuthentication() {
                const token = localStorage.getItem('adminToken');
                if (!token && this.currentPage !== 'dang-nhap') {
                    window.location.href = 'index.php?controller=auth&action=dangNhap';
                    return;
                }
                
                // TODO: Validate token with backend
                // this.validateToken(token);
            }
            
            loadAdminInfo() {
                // TODO: Load admin info from backend
                const adminName = localStorage.getItem('adminName') || 'Admin';
                const adminNameEl = document.getElementById('adminName');
                if (adminNameEl) {
                    adminNameEl.textContent = adminName;
                }
            }
            
            showLoading() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
            }
            
            hideLoading() {
                document.getElementById('loadingOverlay').classList.add('d-none');
            }
            
            showToast(message, type = 'info') {
                // Create toast notification
                const toastContainer = this.getOrCreateToastContainer();
                const toastId = 'toast-' + Date.now();
                
                const toastHTML = `
                    <div class="toast align-items-center text-bg-${type} border-0" role="alert" 
                         aria-live="assertive" aria-atomic="true" id="${toastId}">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-${this.getToastIcon(type)} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                    data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                toastContainer.insertAdjacentHTML('beforeend', toastHTML);
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                
                // Remove toast after it's hidden
                toastElement.addEventListener('hidden.bs.toast', () => {
                    toastElement.remove();
                });
            }
            
            getOrCreateToastContainer() {
                let container = document.getElementById('toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container';
                    container.className = 'toast-container position-fixed top-0 end-0 p-3';
                    container.style.zIndex = '1055';
                    document.body.appendChild(container);
                }
                return container;
            }
            
            getToastIcon(type) {
                const icons = {
                    'success': 'check-circle',
                    'danger': 'exclamation-circle',
                    'warning': 'exclamation-triangle',
                    'info': 'info-circle'
                };
                return icons[type] || 'info-circle';
            }
            
            showConfirmModal(message, onConfirm, title = 'Xác nhận') {
                const modal = document.getElementById('confirmModal');
                const modalTitle = document.getElementById('confirmModalLabel');
                const modalBody = document.getElementById('confirmModalBody');
                const confirmBtn = document.getElementById('confirmModalAction');
                
                modalTitle.innerHTML = `<i class="fas fa-question-circle me-2"></i>${title}`;
                modalBody.textContent = message;
                
                // Remove previous event listeners
                const newConfirmBtn = confirmBtn.cloneNode(true);
                confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                
                // Add new event listener
                newConfirmBtn.addEventListener('click', () => {
                    onConfirm();
                    bootstrap.Modal.getInstance(modal).hide();
                });
                
                new bootstrap.Modal(modal).show();
            }
        }
        
        // Global functions
        function logout() {
            adminLayout.showConfirmModal(
                'Bạn có chắc chắn muốn đăng xuất?',
                () => {
                    localStorage.removeItem('adminToken');
                    localStorage.removeItem('adminName');
                    window.location.href = 'index.php?controller=auth&action=dangNhap';
                },
                'Đăng xuất'
            );
        }
        
        function showProfile() {
            // TODO: Load current admin profile data
            const modal = new bootstrap.Modal(document.getElementById('profileModal'));
            modal.show();
        }
        
        function showSettings() {
            adminLayout.showToast('Chức năng cài đặt đang được phát triển', 'info');
        }
        
        function updateProfile() {
            // TODO: Implement profile update
            adminLayout.showToast('Cập nhật hồ sơ thành công', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('profileModal'));
            modal.hide();
        }
        
        // Initialize admin layout when DOM is loaded
        let adminLayout;
        document.addEventListener('DOMContentLoaded', () => {
            adminLayout = new AdminLayout();
        });
        
        // TODO: API Integration Points for Controller
        /*
        1. Authentication & Authorization
           - POST /api/admin/login
           - POST /api/admin/logout
           - GET /api/admin/validate-token
           
        2. Profile Management
           - GET /api/admin/profile
           - PUT /api/admin/profile
           - PUT /api/admin/change-password
           
        3. Navigation & Permissions
           - GET /api/admin/permissions
           - Role-based menu rendering
           
        4. System Settings
           - GET /api/admin/settings
           - PUT /api/admin/settings
        */
    </script>
    
    {{ADDITIONAL_SCRIPTS}}
</body>
</html>