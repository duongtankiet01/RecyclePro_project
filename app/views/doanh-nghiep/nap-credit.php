<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp điểm - Doanh nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php $activeTab = 'nap-credit'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="row">
            <!-- Balance and Deposit -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-wallet me-2"></i>Quản lý điểm
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h6 class="text-muted">Điểm hiện có</h6>
                            <h1 class="display-4 fw-bold text-success" id="currentBalance">
                                <span class="spinner-border spinner-border-sm"></span>
                            </h1>
                        </div>

                        <!-- Form nạp điểm -->
                        <form id="depositForm" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="depositAmount" class="form-label">Số tiền muốn nạp (VNĐ)</label>
                                <input type="number" class="form-control" id="depositAmount" placeholder="Nhập số tiền" required min="10000" step="1000">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Quy đổi: 1,000 VNĐ = 1 điểm (Tối thiểu: 10,000 VNĐ)
                                </div>
                                <div class="mt-2">
                                    <strong>Số điểm nhận được:</strong> 
                                    <span id="convertedPoints" class="text-primary fw-bold">0</span> điểm
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập số tiền hợp lệ (tối thiểu 10,000đ).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Gói nạp nhanh</label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectPackage(50000)">
                                        50,000 VNĐ = 50 điểm
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectPackage(100000)">
                                        100,000 VNĐ = 100 điểm
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectPackage(500000)">
                                        500,000 VNĐ = 500 điểm
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phương thức thanh toán</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="bankTransfer" value="bank_transfer" checked>
                                    <label class="form-check-label" for="bankTransfer">
                                        <i class="fas fa-university me-1"></i> Chuyển khoản ngân hàng
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Tạo yêu cầu nạp điểm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Transaction History / Payment Info -->
            <div class="col-lg-7">
                <!-- Lịch sử giao dịch -->
                <div class="card shadow-sm" id="transactionHistory">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-history me-2"></i>Lịch sử giao dịch
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã GD</th>
                                        <th>Thời gian</th>
                                        <th>Số tiền</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Đang tải...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Thông tin thanh toán (ẩn mặc định) -->
                <div class="card shadow-sm d-none" id="paymentInfo">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-qrcode me-2"></i>Thông tin chuyển khoản
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Quét mã QR hoặc chuyển khoản thủ công với nội dung bên dưới</strong>
                        </div>

                        <!-- QR Code -->
                        <div class="text-center mb-4">
                            <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                        </div>

                        <!-- Thông tin chuyển khoản -->
                        <div class="border rounded p-3 bg-light mb-3">
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Ngân hàng:</div>
                                <div class="col-8" id="bankName">-</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Số tài khoản:</div>
                                <div class="col-8">
                                    <span id="accountNumber">-</span>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('accountNumber')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Tên tài khoản:</div>
                                <div class="col-8" id="accountName">-</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Số tiền:</div>
                                <div class="col-8">
                                    <span id="transferAmount" class="text-danger fw-bold">-</span>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('transferAmount')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-bold">Nội dung CK:</div>
                                <div class="col-8">
                                    <code id="transferContent" class="bg-warning text-dark px-2 py-1 rounded">-</code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('transferContent')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Lưu ý:</strong> Vui lòng chuyển khoản <strong>ĐÚNG NỘI DUNG</strong> để hệ thống tự động cộng điểm.
                        </div>

                        <!-- Trạng thái -->
                        <div class="text-center">
                            <div id="paymentStatus" class="alert alert-info">
                                <div class="spinner-border spinner-border-sm me-2"></div>
                                <strong>Đang chờ thanh toán...</strong>
                                <div class="small mt-2">Hệ thống sẽ tự động cập nhật sau khi nhận được tiền</div>
                                <div class="mt-2">
                                    <strong id="countdown">Hết hạn sau: 02:00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-danger" id="cancelPaymentBtn" onclick="cancelPayment()">
                                <i class="fas fa-times me-2"></i>Hủy yêu cầu nạp tiền
                            </button>
                            <button class="btn btn-outline-secondary" onclick="backToHistory()">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại lịch sử
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        let pollingInterval = null;
        let currentTransactionCode = null;
        let countdownInterval = null;
        let timeoutTimer = null;
        const TIMEOUT_SECONDS = 120; // 2 phút

        // Load trang
        document.addEventListener('DOMContentLoaded', () => {
            loadCurrentBalance();
            loadTransactionHistory();
            
            // Form submit
            document.getElementById('depositForm').addEventListener('submit', handleDeposit);
            
            // Update điểm khi nhập số tiền
            document.getElementById('depositAmount').addEventListener('input', updateConvertedPoints);
        });

        // Chọn gói nhanh
        function selectPackage(amount) {
            document.getElementById('depositAmount').value = amount;
            updateConvertedPoints();
        }

        // Tính điểm
        function updateConvertedPoints() {
            const amount = parseFloat(document.getElementById('depositAmount').value) || 0;
            const points = Math.floor(amount / 1000);
            document.getElementById('convertedPoints').textContent = points.toLocaleString('vi-VN');
        }

        // Load số dư hiện tại
        async function loadCurrentBalance() {
            try {
                const response = await fetch('index.php?controller=api_auth&action=profile');
                const result = await response.json();
                
                if (result.success && result.data.user) {
                    const credit = result.data.user.current_credit || 0;
                    document.getElementById('currentBalance').textContent = credit.toLocaleString('vi-VN') + ' điểm';
                } else {
                    document.getElementById('currentBalance').textContent = '0 điểm';
                }
            } catch (error) {
                console.error('Error loading balance:', error);
                document.getElementById('currentBalance').textContent = '0 điểm';
            }
        }

        // Load lịch sử giao dịch
        async function loadTransactionHistory() {
            try {
                const response = await fetch('index.php?controller=api_credit&action=my_transactions&limit=10');
                const result = await response.json();
                
                const tbody = document.getElementById('transactionTableBody');
                
                if (result.success && result.data.transactions.length > 0) {
                    tbody.innerHTML = result.data.transactions.map(tx => `
                        <tr>
                            <td><code>${tx.transaction_code}</code></td>
                            <td>${new Date(tx.created_at).toLocaleString('vi-VN')}</td>
                            <td class="text-success">+${tx.amount.toLocaleString('vi-VN')} VNĐ</td>
                            <td>${getStatusBadge(tx.status, tx.status_text)}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Chưa có giao dịch nào</td></tr>';
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                document.getElementById('transactionTableBody').innerHTML = 
                    '<tr><td colspan="4" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>';
            }
        }

        // Get status badge
        function getStatusBadge(status, text) {
            const badges = {
                'pending': 'warning',
                'processing': 'info',
                'completed': 'success',
                'failed': 'danger',
                'refunded': 'secondary'
            };
            const color = badges[status] || 'secondary';
            return `<span class="badge bg-${color}">${text}</span>`;
        }

        // Xử lý form nạp điểm
        async function handleDeposit(e) {
            e.preventDefault();
            
            const form = e.target;
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const amount = parseFloat(document.getElementById('depositAmount').value);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';

            try {
                const response = await fetch('index.php?controller=api_credit&action=create_request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount: amount })
                });

                const result = await response.json();

                if (result.success) {
                    const tx = result.data.transaction;
                    currentTransactionCode = tx.transaction_code;
                    
                    // Hiển thị thông tin thanh toán
                    showPaymentInfo(tx);
                    
                    // Bắt đầu polling kiểm tra trạng thái
                    startPolling(tx.transaction_code);
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Error creating deposit:', error);
                alert('Lỗi kết nối server. Vui lòng thử lại.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // Hiển thị thông tin thanh toán
        function showPaymentInfo(transaction) {
            const bankInfo = transaction.bank_info;
            
            // Ẩn lịch sử, hiện thông tin thanh toán
            document.getElementById('transactionHistory').classList.add('d-none');
            document.getElementById('paymentInfo').classList.remove('d-none');
            
            // Fill data
            document.getElementById('qrCodeImage').src = bankInfo.qr_code_url;
            document.getElementById('bankName').textContent = bankInfo.bank_name;
            document.getElementById('accountNumber').textContent = bankInfo.account_number;
            document.getElementById('accountName').textContent = bankInfo.account_name;
            document.getElementById('transferAmount').textContent = bankInfo.amount.toLocaleString('vi-VN') + ' VNĐ';
            document.getElementById('transferContent').textContent = bankInfo.content;
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Polling kiểm tra trạng thái
        function startPolling(transactionCode) {
            // Clear polling cũ
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            if (timeoutTimer) {
                clearTimeout(timeoutTimer);
            }
            
            // Bắt đầu countdown
            let remainingSeconds = TIMEOUT_SECONDS;
            updateCountdown(remainingSeconds);
            
            countdownInterval = setInterval(() => {
                remainingSeconds--;
                updateCountdown(remainingSeconds);
                
                if (remainingSeconds <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
            
            // Set timeout để tự động hủy sau 2 phút
            timeoutTimer = setTimeout(() => {
                autoExpirePayment();
            }, TIMEOUT_SECONDS * 1000);
            
            // Poll mỗi 3 giây
            pollingInterval = setInterval(async () => {
                try {
                    const response = await fetch(`index.php?controller=api_credit&action=check_status&code=${transactionCode}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        const status = result.data.status;
                        
                        if (status === 'completed') {
                            // Thành công!
                            clearInterval(pollingInterval);
                            clearInterval(countdownInterval);
                            clearTimeout(timeoutTimer);
                            showSuccess(result.data);
                        } else if (status === 'failed' || status === 'expired') {
                            // Thất bại hoặc hết hạn
                            clearInterval(pollingInterval);
                            clearInterval(countdownInterval);
                            clearTimeout(timeoutTimer);
                            showError('Giao dịch đã hết hạn hoặc bị hủy.');
                        }
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 3000);
        }

        // Hiển thị thành công
        function showSuccess(data) {
            const statusDiv = document.getElementById('paymentStatus');
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = `
                <h5><i class="fas fa-check-circle me-2"></i>Nạp điểm thành công!</h5>
                <p class="mb-0">Bạn đã nhận được <strong>${data.credits.toLocaleString('vi-VN')} điểm</strong></p>
                <hr>
                <button class="btn btn-success" onclick="location.reload()">
                    <i class="fas fa-redo me-2"></i>Nạp tiếp
                </button>
            `;
            
            // Reload balance
            loadCurrentBalance();
        }

        // Hiển thị lỗi
        function showError(message) {
            const statusDiv = document.getElementById('paymentStatus');
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = `
                <h5><i class="fas fa-times-circle me-2"></i>Giao dịch thất bại</h5>
                <p class="mb-0">${message}</p>
            `;
        }

        // Copy text
        function copyText(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                // Show tooltip
                const btn = event.target.closest('button');
                const icon = btn.querySelector('i');
                icon.className = 'fas fa-check';
                
                setTimeout(() => {
                    icon.className = 'fas fa-copy';
                }, 1000);
            });
        }

        // Quay lại lịch sử
        function backToHistory() {
            // Clear polling
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            if (timeoutTimer) {
                clearTimeout(timeoutTimer);
                timeoutTimer = null;
            }
            
            // Ẩn thông tin thanh toán, hiện lịch sử
            document.getElementById('paymentInfo').classList.add('d-none');
            document.getElementById('transactionHistory').classList.remove('d-none');
            
            // Reload lịch sử
            loadTransactionHistory();
        }
        
        // Update countdown display
        function updateCountdown(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            const display = `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            
            const countdownEl = document.getElementById('countdown');
            if (countdownEl) {
                countdownEl.textContent = `Hết hạn sau: ${display}`;
                
                // Đổi màu khi < 30s
                if (seconds <= 30) {
                    countdownEl.classList.add('text-danger');
                } else {
                    countdownEl.classList.remove('text-danger');
                }
            }
        }
        
        // Hủy yêu cầu thanh toán (manual)
        async function cancelPayment() {
            if (!currentTransactionCode) return;
            
            if (!confirm('Bạn có chắc muốn hủy yêu cầu nạp tiền này?')) {
                return;
            }
            
            const btn = document.getElementById('cancelPaymentBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang hủy...';
            
            try {
                const response = await fetch('index.php?controller=api_credit&action=cancel_request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_code: currentTransactionCode })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear timers
                    clearInterval(pollingInterval);
                    clearInterval(countdownInterval);
                    clearTimeout(timeoutTimer);
                    
                    showError('Đã hủy yêu cầu nạp tiền');
                } else {
                    alert('Lỗi: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-times me-2"></i>Hủy yêu cầu nạp tiền';
                }
            } catch (error) {
                console.error('Cancel error:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times me-2"></i>Hủy yêu cầu nạp tiền';
            }
        }
        
        // Tự động hủy khi hết thời gian
        async function autoExpirePayment() {
            if (!currentTransactionCode) return;
            
            console.log('⏰ Payment timeout - auto expiring...');
            
            // Clear intervals
            clearInterval(pollingInterval);
            clearInterval(countdownInterval);
            
            try {
                // Call API to expire
                await fetch('index.php?controller=api_credit&action=expire_request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_code: currentTransactionCode })
                });
            } catch (error) {
                console.error('Auto expire error:', error);
            }
            
            showError('Yêu cầu nạp tiền đã hết hạn (quá 2 phút). Vui lòng tạo yêu cầu mới.');
        }
    </script>
</body>
</html>
