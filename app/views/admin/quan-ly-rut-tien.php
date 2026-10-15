<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý rút tiền - RecyclePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            background: #ffffff;
            border-right: 1px solid #e9ecef;
        }
        .admin-sidebar .sidebar-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .admin-sidebar .sidebar-header h4 {
            color: #28a745;
        }
        .admin-sidebar .sidebar-header p {
            color: #6c757d;
        }
        .admin-sidebar .nav-link {
            color: #495057;
        }
        .admin-sidebar .nav-link:hover {
            background: #f8f9fa;
            color: #28a745;
        }
        .admin-sidebar .nav-link.active {
            background: #e8f5e9;
            color: #28a745;
            font-weight: 600;
        }
        .admin-sidebar .nav-link.active i {
            color: #28a745;
        }
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.danger { border-left-color: #dc3545; }
    </style>
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
                <a href="index.php?controller=admin&action=quanLyRutTien" class="nav-link active" data-page="withdrawals">
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
                    <div>
                        <h1>Quản lý rút tiền</h1>
                        <p class="header-breadcrumb">Xử lý các yêu cầu rút tiền từ người dùng</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Làm mới
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="admin-content">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card warning shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">Đang chờ xử lý</h6>
                            <h3 class="mb-1"><?php echo number_format($stats['totalPending'] ?? 0); ?></h3>
                            <small class="text-muted"><?php echo number_format($stats['pendingAmount'] ?? 0); ?>₫</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card success shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">Đã hoàn thành</h6>
                            <h3 class="mb-1"><?php echo number_format($stats['totalCompleted'] ?? 0); ?></h3>
                            <small class="text-muted"><?php echo number_format($stats['completedAmount'] ?? 0); ?>₫</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card danger shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">Đã từ chối</h6>
                            <h3 class="mb-1"><?php echo number_format($stats['totalRejected'] ?? 0); ?></h3>
                            <small class="text-muted">-</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Withdrawals Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Danh sách yêu cầu rút tiền</h5>
                        <?php 
                        $pendingCount = 0;
                        foreach ($withdrawals ?? [] as $w) {
                            if ($w['status'] === 'pending') $pendingCount++;
                        }
                        if ($pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark ms-2"><?php echo $pendingCount; ?> chờ xử lý</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($withdrawals)): ?>
                        <div class="alert alert-info text-center m-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Chưa có yêu cầu rút tiền nào
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã YC</th>
                                        <th>Hộ gia đình</th>
                                        <th>SĐT</th>
                                        <th>Số tiền</th>
                                        <th>Ngân hàng</th>
                                        <th>Số TK</th>
                                        <th>Chủ TK</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($withdrawals as $w): ?>
                                        <tr id="row-<?php echo $w['request_id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($w['request_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($w['household_name'] ?? 'Cộng tác viên'); ?></td>
                                            <td><?php echo htmlspecialchars($w['phone_number']); ?></td>
                                            <td class="fw-bold text-success"><?php echo number_format($w['amount_requested'], 0, ',', '.'); ?>₫</td>
                                            <td><?php echo htmlspecialchars($w['bank_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($w['bank_account_number']); ?></code></td>
                                            <td><?php echo htmlspecialchars($w['account_holder_name']); ?></td>
                                            <td>
                                                <?php if ($w['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Chờ xử lý</span>
                                                <?php elseif ($w['status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Đã chuyển</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Từ chối</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($w['requested_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($w['status'] === 'pending'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-primary" onclick="showQRCode(<?php echo $w['request_id']; ?>, '<?php echo htmlspecialchars($w['request_code']); ?>', '<?php echo htmlspecialchars($w['bank_account_number']); ?>', '<?php echo htmlspecialchars($w['bank_name']); ?>', <?php echo $w['amount_requested']; ?>, '<?php echo htmlspecialchars($w['account_holder_name']); ?>')" title="Quét QR để chuyển tiền">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                        <button class="btn btn-success" onclick="confirmTransfer(<?php echo $w['request_id']; ?>, '<?php echo htmlspecialchars($w['request_code']); ?>')" title="Xác nhận đã chuyển">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-danger" onclick="rejectRequest(<?php echo $w['request_id']; ?>, '<?php echo htmlspecialchars($w['request_code']); ?>')" title="Từ chối">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i>Quét QR để chuyển tiền
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-mobile-alt me-2"></i>
                        <strong>Hướng dẫn:</strong> Mở app ngân hàng → Quét QR → Xác nhận chuyển khoản
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div id="qrCodeContainer" class="mb-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tạo QR...</span>
                                </div>
                            </div>
                            
                            <div id="transferInfo" class="text-start">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted" width="140">Mã yêu cầu:</td>
                                        <td><strong id="qr-request-code"></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Số tài khoản:</td>
                                        <td><code id="qr-account-number"></code></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Ngân hàng:</td>
                                        <td id="qr-bank-name"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Chủ tài khoản:</td>
                                        <td id="qr-account-holder"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Số tiền:</td>
                                        <td class="fw-bold text-success" id="qr-amount"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nội dung CK:</td>
                                        <td><code id="qr-content"></code></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mb-0" id="pollingStatus">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span class="visually-hidden">Đang chờ...</span>
                            </div>
                            <div>
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Đang chờ thanh toán...</strong>
                                <div class="small mt-1">Hệ thống sẽ tự động xác nhận sau khi nhận được tiền</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeQRModal">Đóng</button>
                    <button type="button" class="btn btn-success d-none" id="manualConfirmBtn">
                        <i class="fas fa-check me-2"></i>Xác nhận thủ công
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let pollingInterval = null;
    let currentRequestId = null;
    
    // Mapping tên ngân hàng → Mã BIN cho VietQR
    const bankCodeMap = {
        'Vietcombank': 'VCB',
        'VietinBank': 'CTG',
        'BIDV': 'BIDV',
        'Agribank': 'VBA',
        'Techcombank': 'TCB',
        'ACB': 'ACB',
        'VPBank': 'VPB',
        'TPBank': 'TPB',
        'Sacombank': 'STB',
        'MB': 'MB',
        'MBBank': 'MB',
        'SHB': 'SHB',
        'VIB': 'VIB',
        'SeABank': 'SEAB',
        'OCB': 'OCB',
        'MSB': 'MSB',
        'HDBank': 'HDB'
    };
    
    function getBankCode(bankName) {
        if (bankCodeMap[bankName]) {
            return bankCodeMap[bankName];
        }
        for (const [key, value] of Object.entries(bankCodeMap)) {
            if (bankName.includes(key) || key.includes(bankName)) {
                return value;
            }
        }
        return 'VCB';
    }
    
    function showQRCode(requestId, requestCode, accountNumber, bankName, amount, accountHolder) {
        const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
        modal.show();
        
        // Lưu request ID để polling
        currentRequestId = requestId;
        
        document.getElementById('qr-request-code').textContent = requestCode;
        document.getElementById('qr-account-number').textContent = accountNumber;
        document.getElementById('qr-bank-name').textContent = bankName;
        document.getElementById('qr-account-holder').textContent = accountHolder;
        document.getElementById('qr-amount').textContent = amount.toLocaleString('vi-VN') + ' VNĐ';
        document.getElementById('qr-content').textContent = requestCode;
        
        const bankCode = getBankCode(bankName);
        const qrUrl = `https://img.vietqr.io/image/${bankCode}-${accountNumber}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(requestCode)}&accountName=${encodeURIComponent(accountHolder)}`;
        
        const container = document.getElementById('qrCodeContainer');
        container.innerHTML = `<img src="${qrUrl}" alt="QR Code" class="img-fluid" style="max-width: 300px; border: 2px solid #ddd; border-radius: 8px;">`;
        
        // Bắt đầu polling để check trạng thái
        startPolling(requestId);
        
        // Show nút xác nhận thủ công sau 30 giây
        setTimeout(() => {
            document.getElementById('manualConfirmBtn').classList.remove('d-none');
        }, 30000);
    }
    
    function startPolling(requestId) {
        // Clear polling cũ nếu có
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        console.log('🔄 Starting polling for request_id:', requestId);
        
        // Poll mỗi 5 giây
        pollingInterval = setInterval(async () => {
            try {
                const url = `index.php?controller=api_withdrawal&action=check_status&request_id=${requestId}`;
                console.log('📡 Polling:', url);
                
                const response = await fetch(url);
                const result = await response.json();
                
                console.log('📥 Response:', result);
                
                if (result.success) {
                    const status = result.data.status;
                    console.log('✅ Status:', status);
                    
                    if (status === 'completed') {
                        // Thanh toán thành công!
                        console.log('🎉 Payment completed!');
                        clearInterval(pollingInterval);
                        showPaymentSuccess();
                    } else if (status === 'rejected' || status === 'cancelled') {
                        // Bị từ chối/hủy
                        console.log('❌ Payment rejected/cancelled');
                        clearInterval(pollingInterval);
                        showPaymentFailed('Yêu cầu đã bị từ chối hoặc hủy');
                    } else {
                        console.log('⏳ Still pending...');
                    }
                } else {
                    console.error('❌ API error:', result.message);
                }
            } catch (error) {
                console.error('❌ Polling error:', error);
            }
        }, 5000);
    }
    
    function showPaymentSuccess() {
        const statusDiv = document.getElementById('pollingStatus');
        statusDiv.className = 'alert alert-success mb-0';
        statusDiv.innerHTML = `
            <div class="text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="mb-2">✅ Thanh toán thành công!</h5>
                <p class="mb-3">Hệ thống đã xác nhận giao dịch</p>
                <button class="btn btn-success" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Tải lại trang
                </button>
            </div>
        `;
        
        // Ẩn nút đóng và xác nhận thủ công
        document.getElementById('closeQRModal').classList.add('d-none');
        document.getElementById('manualConfirmBtn').classList.add('d-none');
    }
    
    function showPaymentFailed(message) {
        const statusDiv = document.getElementById('pollingStatus');
        statusDiv.className = 'alert alert-danger mb-0';
        statusDiv.innerHTML = `
            <div class="text-center">
                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                <h5 class="mb-2">❌ Giao dịch thất bại</h5>
                <p class="mb-0">${message}</p>
            </div>
        `;
    }
    
    // Dọn dẹp khi đóng modal
    document.getElementById('qrCodeModal').addEventListener('hidden.bs.modal', function() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        currentRequestId = null;
    });
    
    // Xác nhận thủ công
    document.getElementById('manualConfirmBtn').addEventListener('click', function() {
        if (currentRequestId) {
            const requestCode = document.getElementById('qr-request-code').textContent;
            confirmTransfer(currentRequestId, requestCode);
        }
    });

    async function confirmTransfer(requestId, requestCode) {
        const note = prompt(`Xác nhận đã chuyển tiền cho yêu cầu ${requestCode}?\n\nGhi chú (tùy chọn):`, 'Đã chuyển tiền thành công');
        
        if (note === null) return;
        
        try {
            const response = await fetch('index.php?controller=api_withdrawal&action=confirm_transfer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, note: note })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
                location.reload();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    async function rejectRequest(requestId, requestCode) {
        const reason = prompt(`Từ chối yêu cầu ${requestCode}?\n\nLý do từ chối:`, '');
        
        if (!reason || reason.trim() === '') {
            alert('Vui lòng nhập lý do từ chối');
            return;
        }
        
        if (!confirm(`Xác nhận từ chối yêu cầu ${requestCode}?\nĐiểm sẽ được hoàn lại cho hộ gia đình.`)) {
            return;
        }
        
        try {
            const response = await fetch('index.php?controller=api_withdrawal&action=reject', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, reason: reason })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
                location.reload();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Có lỗi xảy ra. Vui lòng thử lại.');
        }
    }
    
    function logout() {
        if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
            localStorage.removeItem('adminToken');
            localStorage.removeItem('adminName');
            window.location.href = 'index.php?controller=auth&action=dangNhap';
        }
    }

    // Sidebar toggle for mobile
    document.addEventListener('DOMContentLoaded', () => {
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
