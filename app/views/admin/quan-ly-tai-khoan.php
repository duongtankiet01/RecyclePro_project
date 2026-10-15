<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản - RecyclePro Admin</title>
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
                <a href="index.php?controller=admin&action=dashboard" class="nav-link" data-page="dashboard">
                    <i class="fas fa-home"></i>
                    Trang chủ
                </a>
            </div>
            
            <div class="nav-item">
                <a href="index.php?controller=admin&action=quanLyTaiKhoan" class="nav-link active" data-page="accounts">
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
                        <h1>Quản lý tài khoản</h1>
                        <p class="header-breadcrumb">Quản lý người dùng hệ thống</p>
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
            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Bộ lọc</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="quanLyTaiKhoan">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="filterUserType" class="form-label">Loại tài khoản</label>
                                <select class="form-select" id="filterUserType" name="role" onchange="this.form.submit()">
                                    <option value="">Tất cả</option>
                                    <option value="household" <?= ($filters['role'] ?? '') === 'household' ? 'selected' : '' ?>>Hộ gia đình</option>
                                    <option value="business" <?= ($filters['role'] ?? '') === 'business' ? 'selected' : '' ?>>Doanh nghiệp</option>
                                    <option value="collector" <?= ($filters['role'] ?? '') === 'collector' ? 'selected' : '' ?>>Cộng tác viên</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filterStatus" class="form-label">Trạng thái</label>
                                <select class="form-select" id="filterStatus" name="status" onchange="this.form.submit()">
                                    <option value="">Tất cả</option>
                                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                    <option value="suspended" <?= ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Bị khóa</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="searchUser" class="form-label">Tìm kiếm</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchUser" name="search"
                                           placeholder="Tên, email, số điện thoại..." 
                                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <a href="index.php?controller=admin&action=quanLyTaiKhoan" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Xóa bộ lọc
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Accounts Table -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách tài khoản</h5>
                        <div>
                            <button class="btn btn-primary btn-sm" onclick="refreshTable()">
                                <i class="fas fa-sync-alt me-2"></i>Làm mới
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="accountsTable">
                            <thead>
                                <tr>
                                    <th width="20%">Tên</th>
                                    <th width="20%">Email</th>
                                    <th width="15%">Số điện thoại</th>
                                    <th width="12%">Loại tài khoản</th>
                                    <th width="12%">Trạng thái</th>
                                    <th width="11%">Ngày đăng ký</th>
                                    <th width="10%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="accountsTableBody">
                                <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>Chưa có tài khoản nào</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($account['role'] === 'household'): ?>
                                                        <i class="fas fa-home text-primary me-2"></i>
                                                    <?php elseif ($account['role'] === 'business'): ?>
                                                        <i class="fas fa-building text-info me-2"></i>
                                                    <?php elseif ($account['role'] === 'collector'): ?>
                                                        <i class="fas fa-motorcycle text-success me-2"></i>
                                                    <?php endif; ?>
                                                    <strong><?= htmlspecialchars($account['full_name']) ?></strong>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($account['email']) ?></td>
                                            <td><?= htmlspecialchars($account['phone_number'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if ($account['role'] === 'household'): ?>
                                                    <span class="badge bg-primary">Hộ gia đình</span>
                                                <?php elseif ($account['role'] === 'business'): ?>
                                                    <span class="badge bg-info">Doanh nghiệp</span>
                                                <?php elseif ($account['role'] === 'collector'): ?>
                                                    <span class="badge bg-success">Cộng tác viên</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($account['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Hoạt động</span>
                                                <?php elseif ($account['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                                <?php elseif ($account['status'] === 'suspended'): ?>
                                                    <span class="badge bg-danger">Bị khóa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($account['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($account['created']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" onclick="viewUser(<?= $account['user_id'] ?>)" 
                                                            data-bs-toggle="tooltip" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($account['status'] === 'pending'): ?>
                                                        <button class="btn btn-outline-success" onclick="approveUser(<?= $account['user_id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Duyệt">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php elseif ($account['status'] === 'active'): ?>
                                                        <button class="btn btn-outline-warning" onclick="suspendUser(<?= $account['user_id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Tạm khóa">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    <?php elseif ($account['status'] === 'suspended'): ?>
                                                        <button class="btn btn-outline-success" onclick="unlockUser(<?= $account['user_id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Mở khóa">
                                                            <i class="fas fa-unlock"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Hiển thị 1-3 trong tổng số 1,234 tài khoản</small>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Trước</a>
                                </li>
                                <li class="page-item active">
                                    <a class="page-link" href="#">1</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">3</a>
                                </li>
                                <li class="page-item">
                                    <span class="page-link">...</span>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">412</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Sau</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- User Detail Modal -->
    <div class="modal fade" id="userDetailModal" tabindex="-1" aria-labelledby="userDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailModalLabel">
                        <i class="fas fa-user me-2"></i>Chi tiết tài khoản
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="userDetailContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">
                        <i class="fas fa-save me-2"></i>Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class AccountManagement {
            constructor() {
                this.currentPage = 1;
                this.itemsPerPage = 20;
                this.totalItems = 0;
                this.filters = {};
                this.currentEditingUser = null;
                this.userDetailModal = null;
                
                this.initializeEventListeners();
                this.initializeTooltips();
                // Không cần load lại dữ liệu vì đã có sẵn từ PHP
                // this.loadAccountsData();
            }
            
            initializeEventListeners() {
                // Search input
                document.getElementById('searchUser').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.searchUsers();
                    }
                });
                
                // Filter changes
                ['filterUserType', 'filterStatus'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('change', () => {
                            this.applyFilters();
                        });
                    }
                });
            }
            
            initializeTooltips() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            
            async loadAccountsData() {
                try {
                    this.showLoading();
                    
                    // TODO: Replace with actual API call
                    const response = await this.fetchAccountsFromAPI();
                    
                    this.updateStatsCards(response.stats);
                    this.updateAccountsTable(response.accounts);
                    this.updatePagination(response.pagination);
                    
                } catch (error) {
                    console.error('Error loading accounts:', error);
                    this.showToast('Có lỗi xảy ra khi tải dữ liệu', 'danger');
                } finally {
                    this.hideLoading();
                }
            }
            
            // TODO: Replace with actual API integration
            async fetchAccountsFromAPI() {
                // TODO: Implement actual API call
                return {
                    stats: {
                        total: 0,
                        pending: 0,
                        blocked: 0,
                        active: 0
                    },
                    accounts: [],
                    pagination: {
                        currentPage: this.currentPage,
                        totalPages: 0,
                        totalItems: 0
                    }
                };
            }
            
            updateStatsCards(stats) {
                // Kiểm tra element tồn tại trước khi update
                const totalEl = document.getElementById('totalAccounts');
                const pendingEl = document.getElementById('pendingAccounts');
                const blockedEl = document.getElementById('blockedAccounts');
                const activeEl = document.getElementById('activeAccounts');
                
                if (totalEl) totalEl.textContent = stats.total.toLocaleString();
                if (pendingEl) pendingEl.textContent = stats.pending;
                if (blockedEl) blockedEl.textContent = stats.blocked;
                if (activeEl) activeEl.textContent = stats.active.toLocaleString();
            }
            
            updateAccountsTable(accounts) {
                // TODO: Update table with dynamic data
                console.log('Updating table with accounts:', accounts);
            }
            
            updatePagination(pagination) {
                // TODO: Update pagination controls
                console.log('Updating pagination:', pagination);
            }
            
            
            searchUsers() {
                // Không cần nữa - dùng form submit
            }
            
            applyFilters() {
                // Không cần nữa - dùng form submit
            }
            
            resetFilters() {
                // Không cần nữa - dùng link redirect
            }
            
            refreshTable() {
                location.reload();
            }
            
            
            viewUser(userId) {
                this.loadUserDetail(userId);
            }
            
            approveUser(userId) {
                this.showConfirmModal(
                    'Bạn có chắc chắn muốn duyệt tài khoản này?',
                    async () => {
                        try {
                            await this.performUserAction('approve', userId);
                            this.showToast('Tài khoản đã được duyệt thành công', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } catch (error) {
                            this.showToast(error.message || 'Có lỗi xảy ra khi duyệt tài khoản', 'danger');
                        }
                    },
                    'Duyệt tài khoản'
                );
            }
            
            rejectUser(userId) {
                this.showConfirmModal(
                    'Bạn có chắc chắn muốn từ chối tài khoản này?',
                    async () => {
                        try {
                            await this.performUserAction('reject', userId);
                            this.showToast('Tài khoản đã bị từ chối', 'danger');
                            setTimeout(() => location.reload(), 1000);
                        } catch (error) {
                            this.showToast(error.message || 'Có lỗi xảy ra khi từ chối tài khoản', 'danger');
                        }
                    },
                    'Từ chối tài khoản'
                );
            }
            
            suspendUser(userId) {
                this.showConfirmModal(
                    'Bạn có chắc chắn muốn tạm khóa tài khoản này?',
                    async () => {
                        try {
                            await this.performUserAction('suspend', userId);
                            this.showToast('Tài khoản đã bị tạm khóa', 'warning');
                            setTimeout(() => location.reload(), 1000);
                        } catch (error) {
                            this.showToast(error.message || 'Có lỗi xảy ra khi tạm khóa tài khoản', 'danger');
                        }
                    },
                    'Tạm khóa tài khoản'
                );
            }
            
            unlockUser(userId) {
                this.showConfirmModal(
                    'Bạn có chắc chắn muốn mở khóa tài khoản này?',
                    async () => {
                        try {
                            await this.performUserAction('unlock', userId);
                            this.showToast('Tài khoản đã được mở khóa', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } catch (error) {
                            this.showToast(error.message || 'Có lỗi xảy ra khi mở khóa tài khoản', 'danger');
                        }
                    },
                    'Mở khóa tài khoản'
                );
            }
            
            deleteUser(userId) {
                this.showConfirmModal(
                    'Bạn có chắc chắn muốn xóa tài khoản này? Hành động này không thể hoàn tác.',
                    async () => {
                        try {
                            // TODO: API call to delete user
                            await this.performUserAction('delete', userId);
                            this.showToast('Tài khoản đã được xóa', 'success');
                            this.loadAccountsData();
                        } catch (error) {
                            this.showToast('Có lỗi xảy ra khi xóa tài khoản', 'danger');
                        }
                    },
                    'Xóa tài khoản'
                );
            }
            
            async performUserAction(action, userId) {
                try {
                    const response = await fetch('index.php?controller=api_admin&action=updateUserStatus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin', // Đảm bảo gửi cookie/session
                        body: JSON.stringify({
                            user_id: userId,
                            action: action
                        })
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        console.error('API Error:', result);
                        throw new Error(result.message || 'Có lỗi xảy ra');
                    }

                    return result;
                } catch (error) {
                    console.error('Error performing user action:', error);
                    throw error;
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
        
        async loadUserDetail(userId) {
            try {
                this.showLoading();
                const response = await fetch(`index.php?controller=api_admin&action=getUserDetail&user_id=${userId}`);
                const result = await response.json();
                if (!result.success || !result.data || !result.data.user) {
                    this.showToast(result.message || 'Không thể tải thông tin tài khoản', 'danger');
                    return;
                }
                this.currentEditingUser = result.data.user;
                this.renderUserDetailForm(result.data.user);
                const modalEl = document.getElementById('userDetailModal');
                this.userDetailModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                this.userDetailModal.show();
            } catch (error) {
                console.error('Error loading user detail:', error);
                this.showToast('Có lỗi xảy ra khi tải thông tin tài khoản', 'danger');
            } finally {
                this.hideLoading();
            }
        }

        renderUserDetailForm(user) {
            const content = document.getElementById('userDetailContent');
            const roleBadge = this.getRoleBadge(user.role);
            const businessFields = user.role === 'business'
                ? `
                    <div class="border-top pt-3 mt-3">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-building me-2 text-info"></i>Thông tin doanh nghiệp</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tên doanh nghiệp</label>
                                <input type="text" class="form-control" name="company_name" value="${this.escapeHtml(user.company_name || '')}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Người liên hệ</label>
                                <input type="text" class="form-control" name="contact_person" value="${this.escapeHtml(user.contact_person || '')}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mã số thuế</label>
                                <input type="text" class="form-control" name="tax_code" value="${this.escapeHtml(user.tax_code || '')}">
                            </div>
                        </div>
                    </div>
                ` : '';

            const collectorFields = user.role === 'collector'
                ? `
                    <div class="border-top pt-3 mt-3">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-motorcycle me-2 text-success"></i>Thông tin CTV</h6>
                        <div class="col-md-6">
                            <label class="form-label">Phương tiện hoạt động</label>
                            <select class="form-select" name="vehicle_type" required>
                                ${this.buildVehicleOptions(user.vehicle_type || '')}
                            </select>
                        </div>
                    </div>
                ` : '';

            content.innerHTML = `
                <form id="adminEditUserForm" class="row g-3 needs-validation" novalidate>
                    <input type="hidden" name="user_id" value="${user.id}">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" name="full_name" value="${this.escapeHtml(user.full_name || '')}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="${this.escapeHtml(user.email)}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" name="phone_number" value="${this.escapeHtml(user.phone_number || '')}" pattern="^0[0-9]{9}$" required>
                        <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loại tài khoản</label>
                        <div>${roleBadge}</div>
                    </div>
                    ${businessFields}
                    ${collectorFields}
                </form>
            `;

            const saveBtn = document.getElementById('saveUserBtn');
            saveBtn.onclick = () => this.saveUserChanges();
            saveBtn.classList.remove('d-none');
        }

        async saveUserChanges() {
            console.log('saveUserChanges called');
            const form = document.getElementById('adminEditUserForm');
            if (!form) {
                console.error('Form not found');
                this.showToast('Không tìm thấy form', 'danger');
                return;
            }
            
            console.log('Form validation starting...');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                console.warn('Form validation failed');
                this.showToast('Vui lòng kiểm tra lại thông tin', 'warning');
                return;
            }

            const formData = new FormData(form);
            const payload = {
                user_id: parseInt(formData.get('user_id'), 10),
                full_name: formData.get('full_name').trim(),
                phone_number: formData.get('phone_number').trim()
            };

            // Không cần status vì admin chỉnh sửa thông tin, không đổi trạng thái ở đây
            // status được đổi qua các nút approve/suspend/unlock

            const currentRole = this.currentEditingUser?.role || '';
            console.log('Current role:', currentRole);
            
            if (currentRole === 'business') {
                payload.company_name = formData.get('company_name').trim();
                payload.contact_person = formData.get('contact_person').trim();
                payload.tax_code = formData.get('tax_code').trim();
            }
            if (currentRole === 'collector') {
                payload.vehicle_type = formData.get('vehicle_type');
            }

            console.log('Payload:', payload);

            if (!/^0[0-9]{9}$/.test(payload.phone_number)) {
                this.showToast('Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.', 'warning');
                return;
            }

            const saveBtn = document.getElementById('saveUserBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...';

            try {
                console.log('Sending request to API...');
                const response = await fetch('index.php?controller=api_admin&action=updateUser', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);
                
                if (result.success) {
                    this.showToast(result.message || 'Cập nhật thành công', 'success');
                    if (this.userDetailModal) {
                        this.userDetailModal.hide();
                    }
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(result.message || 'Không thể cập nhật tài khoản', 'danger');
                }
            } catch (error) {
                console.error('Error saving user:', error);
                this.showToast('Không thể kết nối tới máy chủ', 'danger');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        getRoleBadge(role) {
            const map = {
                household: '<span class="badge bg-primary">Hộ gia đình</span>',
                business: '<span class="badge bg-info text-dark">Doanh nghiệp</span>',
                collector: '<span class="badge bg-success">Cộng tác viên</span>',
            };
            return map[role] || `<span class="badge bg-secondary">${this.escapeHtml(role)}</span>`;
        }

        getStatusSelect(selected) {
            const statuses = [
                { value: 'pending', label: 'Chờ duyệt', class: 'text-warning' },
                { value: 'active', label: 'Hoạt động', class: 'text-success' },
                { value: 'suspended', label: 'Bị khóa', class: 'text-danger' },
                { value: 'rejected', label: 'Đã từ chối', class: 'text-secondary' },
            ];
            const options = statuses.map(status => `
                <option value="${status.value}" ${status.value === selected ? 'selected' : ''}>
                    ${status.label}
                </option>
            `).join('');
            return `<select class="form-select" name="status">${options}</select>`;
        }

        buildVehicleOptions(selected) {
            const options = [
                { value: 'motorbike', label: 'Xe máy' },
                { value: 'three_wheeler', label: 'Xe ba gác' },
                { value: 'truck', label: 'Xe tải nhỏ' },
                { value: 'other', label: 'Khác' },
            ];
            return options.map(opt => `
                <option value="${opt.value}" ${opt.value === selected ? 'selected' : ''}>${opt.label}</option>
            `).join('');
        }

        escapeHtml(str) {
            return (str || '').replace(/[&<>"']/g, (match) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[match]));
        }
        }
        
        // Global functions for buttons
        function viewUser(userId) { accountManager.viewUser(userId); }
        function approveUser(userId) { accountManager.approveUser(userId); }
        function rejectUser(userId) { accountManager.rejectUser(userId); }
        function suspendUser(userId) { accountManager.suspendUser(userId); }
        function unlockUser(userId) { accountManager.unlockUser(userId); }
        function deleteUser(userId) { accountManager.deleteUser(userId); }
        function searchUsers() { accountManager.searchUsers(); }
        function applyFilters() { accountManager.applyFilters(); }
        function resetFilters() { accountManager.resetFilters(); }
        function refreshTable() { accountManager.refreshTable(); }
        
        function logout() {
            accountManager.showConfirmModal(
                'Bạn có chắc chắn muốn đăng xuất?',
                () => {
                    localStorage.removeItem('adminToken');
                    localStorage.removeItem('adminName');
                    window.location.href = 'index.php?controller=auth&action=dangNhap';
                },
                'Đăng xuất'
            );
        }
        
        function showProfile() {}
        function showSettings() {}
        
        // Initialize account management
        let accountManager;
        document.addEventListener('DOMContentLoaded', () => {
            accountManager = new AccountManagement();
        });
        
        // TODO: API Integration Points for Controller
        /*
        1. User Management APIs
           - GET /api/admin/users - Get users list with filters and pagination
           - GET /api/admin/users/{id} - Get user details
           - PUT /api/admin/users/{id}/status - Update user status (approve, reject, suspend, unlock)
           - DELETE /api/admin/users/{id} - Delete user
           
        2. Statistics APIs
           - GET /api/admin/stats/users - Get user statistics
           
        3. Export APIs
           - GET /api/admin/export/users - Export users to Excel
           
        4. Bulk Operations
           - POST /api/admin/users/bulk-action - Perform bulk actions on multiple users
        */
    </script>
</body>
</html>