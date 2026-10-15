<!DOCTYPE html>
<htm                    <li class="nav-item"><a class="nav-link" href="index.php?controller=ctv&action=quanLyTaiKhoan">Tài khoản</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="me-3"><span>Xin chào, <strong><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Người dùng'); ?></strong></span></div>
                    <a href="index.php?controller=auth&action=logout" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thu gom - Cộng tác viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar for CTV -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-recycle me-2"></i>RecyclePro (CTV)</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php?controller=ctv&action=feed">Tìm đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?controller=ctv&action=quanLyDon">Đơn đã nhận</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?controller=ctv&action=viDiem">Ví điểm</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?controller=ctv&action=napDiem">Nạp điểm</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="me-3"><span>Xin chào, <strong>Trần Văn B</strong></span></div>
                    <a href="index.php?controller=auth&action=logout" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><i class="fas fa-truck-loading me-2"></i>Thu gom đơn #CTV-GIAY-002</div>
                    <div class="card-body">
                        <!-- Placeholder for Controller: Load order state and allow updates -->
                        <p class="text-muted">Vui lòng xác nhận khi đã nhận rác từ hộ gia đình và khi đã giao cho doanh nghiệp.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-info btn-lg icon-text" id="btnReceived"><i class="fas fa-box"></i> Đã nhận rác từ hộ</button>
                            <button class="btn btn-success btn-lg icon-text" id="btnDelivered"><i class="fas fa-truck"></i> Đã giao cho DN</button>
                            <a href="index.php?controller=ctv&action=quanLyDon" class="btn btn-outline-secondary">Quay lại</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Placeholder for Controller: Hook up events to update status via API
        document.getElementById('btnReceived').addEventListener('click', function(){
            alert('Đã đánh dấu: Đã nhận rác từ hộ.');
        });
        document.getElementById('btnDelivered').addEventListener('click', function(){
            alert('Đã đánh dấu: Đã giao cho doanh nghiệp.');
        });
    </script>
</body>
</html>
