<?php
// Determine active page
$currentController = $_GET['controller'] ?? '';
$currentAction = $_GET['action'] ?? '';

function isActive($controller, $action) {
    global $currentController, $currentAction;
    return ($currentController === $controller && $currentAction === $action) ? 'active' : '';
}
?>
<!-- Sidebar -->
<nav class="admin-sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-recycle me-2"></i>RecyclePro Admin</h4>
        <p>Admin Panel</p>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="index.php?controller=admin&action=dashboard" class="nav-link <?= isActive('admin', 'dashboard') ?>" data-page="dashboard">
                <i class="fas fa-home"></i>
                Trang chủ
            </a>
        </div>
        
        <div class="nav-item">
            <a href="index.php?controller=admin&action=quanLyTaiKhoan" class="nav-link <?= isActive('admin', 'quanLyTaiKhoan') ?>" data-page="accounts">
                <i class="fas fa-users"></i>
                Quản lý tài khoản
            </a>
        </div>
        
        <div class="nav-item">
            <a href="index.php?controller=admin_reward_product&action=index" class="nav-link <?= isActive('admin_reward_product', 'index') ?>" data-page="reward-products">
                <i class="fas fa-gift"></i>
                Quản lý sản phẩm
            </a>
        </div>
        
        <div class="nav-item">
            <a href="index.php?controller=admin_reward_product&action=redemptions" class="nav-link <?= isActive('admin_reward_product', 'redemptions') ?>" data-page="redemptions">
                <i class="fas fa-exchange-alt"></i>
                Đơn đổi thưởng
            </a>
        </div>
        
        <div class="nav-item">
            <a href="index.php?controller=admin&action=quanLyRutTien" class="nav-link <?= isActive('admin', 'quanLyRutTien') ?>" data-page="withdrawals">
                <i class="fas fa-wallet"></i>
                Thanh toán
            </a>
        </div>
        
        <div class="nav-divider"></div>
        
        <div class="nav-item">
            <a href="index.php?controller=auth&action=logout" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                Đăng xuất
            </a>
        </div>
    </div>
</nav>
