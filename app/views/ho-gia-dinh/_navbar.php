<!-- Navbar for Household -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand <?php echo ($activeTab ?? '') === 'feed' ? 'active' : ''; ?>" 
           href="index.php?controller=ho_gia_dinh&action=feed">
            <i class="fas fa-recycle me-2"></i>RecyclePro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab ?? '') === 'quan-ly-don' ? 'active' : ''; ?>" 
                       href="index.php?controller=ho_gia_dinh&action=quanLyDon">
                        Xem đơn
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab ?? '') === 'tich-diem' ? 'active' : ''; ?>" 
                       href="index.php?controller=ho_gia_dinh&action=tichDiem">
                        Đổi sản phẩm
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab ?? '') === 'rut-tien' ? 'active' : ''; ?>" 
                       href="index.php?controller=ho_gia_dinh&action=rutTien">
                        Đổi tiền
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab ?? '') === 'tai-khoan' ? 'active' : ''; ?>" 
                       href="index.php?controller=ho_gia_dinh&action=quanLyTaiKhoan">
                        Tài khoản
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span>Xin chào, <strong><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Người dùng'); ?></strong></span>
                </div>
                <a href="index.php?controller=auth&action=logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </div>
</nav>

