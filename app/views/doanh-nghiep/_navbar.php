<?php
$activeTab = $activeTab ?? '';

if (!function_exists('dn_nav_class')) {
    function dn_nav_class(string $tab, string $activeTab): string
    {
        return 'nav-link' . ($tab === $activeTab ? ' active' : '');
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php?controller=doanh_nghiep&action=quanLyDon">
            <i class="fas fa-recycle me-2"></i>RecyclePro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="<?= dn_nav_class('tao-don', $activeTab) ?>" href="index.php?controller=doanh_nghiep&action=taoDon">Tạo đơn</a>
                </li>
                <li class="nav-item">
                    <a class="<?= dn_nav_class('quan-ly-don', $activeTab) ?>" href="index.php?controller=doanh_nghiep&action=quanLyDon">Quản lý đơn</a>
                </li>
                <li class="nav-item">
                    <a class="<?= dn_nav_class('xac-nhan', $activeTab) ?>" href="index.php?controller=doanh_nghiep&action=xacNhanGiaoRac">Xác nhận</a>
                </li>
                <li class="nav-item">
                    <!-- <a class="<?= dn_nav_class('doi-diem', $activeTab) ?>" href="index.php?controller=doanh_nghiep&action=quanLyDoiDiem">Đổi điểm</a> -->
                </li>
                <li class="nav-item">
                    <a class="<?= dn_nav_class('nap-credit', $activeTab) ?>" href="index.php?controller=doanh_nghiep&action=napCredit">Nạp điểm</a>
                </li>
                <li class="nav-item">
                    <a class="<?= dn_nav_class('tai-khoan', $activeTab) ?>" href="index.php?controller=doanh_nghiep&action=quanLyTaiKhoan">Tài khoản</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span>Xin chào, <strong><?= htmlspecialchars($account['company_name'] ?? 'Doanh nghiệp') ?></strong></span>
                </div>
                <a href="index.php?controller=auth&action=logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </div>
</nav>
