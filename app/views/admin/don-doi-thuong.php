<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn đổi thưởng - RecyclePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/_sidebar.php'; ?>

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
                        <h1>Quản lý đơn đổi thưởng</h1>
                        <p class="header-breadcrumb">Xác nhận và giao hàng</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <a href="index.php?controller=admin_reward_product&action=index" class="btn btn-secondary">
                        <i class="fas fa-gift"></i> Quản lý sản phẩm
                    </a>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="admin-content">
        <!-- Quick Verify -->
        <div class="card mb-4 border-success shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-qrcode"></i> Xác nhận đổi thưởng - Nhập mã từ khách hàng</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> <strong>Hướng dẫn:</strong> Khi khách hàng đến điểm đổi quà, yêu cầu khách đọc mã đổi thưởng, nhập vào ô bên dưới để kiểm tra và xác nhận giao hàng.
                </div>
                <div class="row">
                    <div class="col-md-10 offset-md-1">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-success text-white">
                                <i class="fas fa-barcode fa-lg"></i>
                            </span>
                            <input type="text" 
                                   id="quickCodeInput" 
                                   class="form-control form-control-lg text-center fw-bold" 
                                   placeholder="Nhập mã đổi thưởng từ khách hàng (VD: RD20251117-ABC123)"
                                   style="text-transform: uppercase; font-family: 'Courier New', monospace; letter-spacing: 3px; font-size: 1.5rem;"
                                   autofocus>
                            <button class="btn btn-success btn-lg px-4" onclick="quickVerify()" id="verifyBtn">
                                <i class="fas fa-search"></i> Kiểm tra
                            </button>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted"><i class="fas fa-keyboard"></i> Nhấn <kbd>Enter</kbd> để tìm kiếm nhanh</small>
                        </div>
                        <!-- Inline Loading -->
                        <div id="loadingSpinner" class="text-center mt-3" style="display: none;">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Đang kiểm tra...</span>
                            </div>
                            <p class="text-muted mt-2">Đang kiểm tra mã...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Result -->
                <div id="quickResult" class="mt-4" style="display: none;"></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-list fa-2x mb-2"></i>
                        <h3><?= number_format($stats['total']) ?></h3>
                        <p class="mb-0">Tổng đơn</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h3><?= number_format($stats['pending']) ?></h3>
                        <p class="mb-0">Chờ xác nhận</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check fa-2x mb-2"></i>
                        <h3><?= number_format($stats['confirmed']) ?></h3>
                        <p class="mb-0">Đã xác nhận</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-2x mb-2"></i>
                        <h3><?= number_format($stats['delivered']) ?></h3>
                        <p class="mb-0">Đã giao hàng</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-3">
                    <input type="hidden" name="controller" value="admin_reward_product">
                    <input type="hidden" name="action" value="redemptions">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Tìm theo mã hoặc tên khách..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                            <option value="confirmed" <?= $filters['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                            <option value="delivered" <?= $filters['status'] === 'delivered' ? 'selected' : '' ?>>Đã giao hàng</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tìm kiếm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Redemptions Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Khách hàng</th>
                                <th width="30%">Sản phẩm</th>
                                <th width="10%">Điểm</th>
                                <th width="15%">Ngày đổi</th>
                                <th width="15%">Trạng thái</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($redemptions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-0">Chưa có đơn đổi thưởng nào</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $stt = 1; foreach ($redemptions as $r): ?>
                                    <tr class="align-middle">
                                        <td class="text-center text-muted"><?= $stt++ ?></td>
                                        <td>
                                            <strong class="d-block"><?= htmlspecialchars($r['household_name']) ?></strong>
                                            <small class="text-muted">
                                                <i class="fas fa-phone"></i> <?= htmlspecialchars($r['household_phone']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($r['image_url'] ?: 'https://via.placeholder.com/50') ?>" 
                                                     alt="Product" 
                                                     class="rounded me-2" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <strong class="d-block"><?= htmlspecialchars($r['product_name']) ?></strong>
                                                    <small class="text-muted">Số lượng: x<?= $r['quantity'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><strong class="text-primary"><?= number_format($r['points_spent']) ?></strong></td>
                                        <td>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($r['redeemed_at'])) ?></small><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($r['redeemed_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Chờ xác nhận</span>',
                                                'confirmed' => '<span class="badge bg-info"><i class="fas fa-check"></i> Đã xác nhận</span>',
                                                'delivered' => '<span class="badge bg-success"><i class="fas fa-box-check"></i> Đã giao</span>',
                                                'cancelled' => '<span class="badge bg-danger"><i class="fas fa-times"></i> Đã hủy</span>'
                                            ];
                                            echo $badges[$r['status']] ?? $r['status'];
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <i class="fas fa-lock text-muted" title="Mã bị ẩn để bảo mật"></i>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quick verify
        function quickVerify() {
            const code = document.getElementById('quickCodeInput').value.trim();
            if (!code) {
                alert('Vui lòng nhập mã đổi thưởng!');
                return;
            }

            // Show inline loading
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('quickResult').style.display = 'none';
            document.getElementById('verifyBtn').disabled = true;

            fetch('index.php?controller=api_reward_product&action=verify_redemption', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ redemption_code: code })
            })
            .then(response => response.json())
            .then(result => {
                // Hide loading
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('verifyBtn').disabled = false;
                
                if (result.success) {
                    showQuickResult(result.data.redemption);
                } else {
                    showQuickError(result.message);
                }
            })
            .catch(error => {
                // Hide loading on error
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('verifyBtn').disabled = false;
                showQuickError('Lỗi kết nối: ' + error.message);
            });
        }

        function showQuickResult(redemption) {
            const statusLabels = {
                'pending': '<span class="badge bg-warning text-dark fs-6"><i class="fas fa-clock"></i> Chờ xác nhận</span>',
                'confirmed': '<span class="badge bg-info fs-6"><i class="fas fa-check"></i> Đã xác nhận</span>',
                'delivered': '<span class="badge bg-success fs-6"><i class="fas fa-box-check"></i> Đã giao hàng</span>',
                'cancelled': '<span class="badge bg-danger fs-6"><i class="fas fa-times"></i> Đã hủy</span>'
            };

            let html = `
                <div class="card border-success shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Thông tin đơn đổi thưởng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="alert alert-success mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-barcode fa-3x me-3"></i>
                                        <div class="flex-grow-1">
                                            <small class="text-muted d-block">Mã đổi thưởng:</small>
                                            <h4 class="mb-0 font-monospace">${redemption.redemption_code}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-primary mb-3"><i class="fas fa-box"></i> Thông tin sản phẩm</h6>
                                    <p class="mb-2"><strong>Tên sản phẩm:</strong><br>${redemption.product_name}</p>
                                    <p class="mb-2"><strong>Số lượng:</strong> <span class="badge bg-primary">${redemption.quantity}</span></p>
                                    <p class="mb-0"><strong>Điểm sử dụng:</strong> <span class="text-danger fw-bold">${parseInt(redemption.points_spent).toLocaleString()}</span> điểm</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-primary mb-3"><i class="fas fa-user"></i> Thông tin khách hàng</h6>
                                    <p class="mb-2"><strong>Họ tên:</strong><br>${redemption.household_name}</p>
                                    <p class="mb-2"><strong>Số điện thoại:</strong><br><a href="tel:${redemption.household_phone}">${redemption.household_phone}</a></p>
                                    <p class="mb-0"><strong>Trạng thái:</strong> ${statusLabels[redemption.status] || redemption.status}</p>
                                </div>
                            </div>
                        </div>
            `;

            if (redemption.status === 'pending' || redemption.status === 'confirmed') {
                html += `
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg py-3" onclick="confirmDelivery('${redemption.redemption_code}')">
                                <i class="fas fa-check-circle fa-lg"></i> XÁC NHẬN ĐÃ GIAO HÀNG CHO KHÁCH
                            </button>
                            <button class="btn btn-outline-secondary" onclick="clearResult()">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                        </div>
                `;
            } else if (redemption.status === 'delivered') {
                html += `
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> <strong>Đơn này đã được giao hàng thành công!</strong>
                        </div>
                `;
            } else {
                html += `
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-info-circle"></i> <strong>Đơn này đã bị hủy hoặc không hợp lệ</strong>
                        </div>
                `;
            }

            html += `
                    </div>
                </div>
            `;

            document.getElementById('quickResult').innerHTML = html;
            document.getElementById('quickResult').style.display = 'block';
        }

        function showQuickError(message) {
            document.getElementById('quickResult').innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Không tìm thấy:</strong> ${message}
                    <button type="button" class="btn-close" onclick="clearResult()"></button>
                </div>
            `;
            document.getElementById('quickResult').style.display = 'block';
        }

        function clearResult() {
            document.getElementById('quickResult').style.display = 'none';
            document.getElementById('quickCodeInput').value = '';
            document.getElementById('quickCodeInput').focus();
        }

        // Confirm delivery
        // Confirm delivery
        function confirmDelivery(code) {
            if (!confirm('Xác nhận đã giao hàng cho khách hàng?')) {
                return;
            }

            // Disable button and show loading text
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';

            fetch('index.php?controller=api_reward_product&action=complete_redemption', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ redemption_code: code })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('✅ Đã xác nhận giao hàng thành công!');
                    location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    alert('❌ Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                alert('❌ Lỗi kết nối server');
            });
        }

        // Copy code
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('✅ Đã copy mã: ' + code);
            });
        }

        // Enter to submit
        document.getElementById('quickCodeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                quickVerify();
            }
        });

        // Auto focus
        document.getElementById('quickCodeInput').focus();

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</main>
</body>
</html>
