<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ví tiền & Rút tiền - Hộ gia đình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
     <!-- Navbar for Household -->
    <?php $activeTab = 'rut-tien'; require __DIR__ . '/_navbar.php'; ?>
    <div class="container mt-4">
        <!-- The layout is similar to the enterprise credit page -->
         <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="fas fa-wallet me-2"></i>Ví điểm của bạn</div>
                    <div class="card-body">
                         <div class="text-center mb-4">
                            <div class="mb-2">
                                <h6 class="text-muted mb-1">Điểm hiện có</h6>
                                <h2 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-star me-2"></i><?php echo number_format($wallet['points'] ?? 0, 0, ',', '.'); ?> điểm
                                </h2>
                            </div>
                            <hr>
                            <div>
                                <h6 class="text-muted mb-1">Tương đương</h6>
                                <h1 class="display-4 fw-bold text-success"><?php echo number_format($wallet['balance'] ?? 0, 0, ',', '.'); ?>đ</h1>
                                <small class="text-muted">Tỷ lệ: 1 điểm = <?php echo number_format($wallet['conversion_rate'] ?? 100, 0, ',', '.'); ?> VNĐ</small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Số tiền rút tối thiểu là 50,000đ (50 điểm)
                        </div>
                        
                        <form id="withdrawForm" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="withdrawPoints" class="form-label">Số điểm muốn đổi</label>
                                <input type="number" class="form-control" id="withdrawPoints" required min="50" max="<?php echo $wallet['points'] ?? 0; ?>" step="1" oninput="calculateAmount()">
                                <div class="form-text">
                                    Tối thiểu: 50 điểm | Tối đa: <?php echo number_format($wallet['points'] ?? 0, 0, ',', '.'); ?> điểm
                                </div>
                                <div class="invalid-feedback">Số điểm không hợp lệ.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Số tiền nhận được</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg fw-bold text-success" id="withdrawAmount" readonly value="0">
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h6 class="mb-3">Thông tin tài khoản nhận tiền</h6>
                            
                            <div class="mb-3">
                                <label for="bankAccountNumber" class="form-label">Số tài khoản</label>
                                <input type="text" class="form-control" id="bankAccountNumber" placeholder="Ví dụ: 0123456789" required>
                                <div class="invalid-feedback">Vui lòng nhập số tài khoản.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bankName" class="form-label">Tên ngân hàng</label>
                                <select class="form-select" id="bankName" required>
                                    <option value="">-- Chọn ngân hàng --</option>
                                    <option value="Vietcombank">Vietcombank - Ngân hàng TMCP Ngoại thương Việt Nam</option>
                                    <option value="VietinBank">VietinBank - Ngân hàng TMCP Công thương Việt Nam</option>
                                    <option value="BIDV">BIDV - Ngân hàng TMCP Đầu tư và Phát triển Việt Nam</option>
                                    <option value="Agribank">Agribank - Ngân hàng Nông nghiệp và Phát triển Nông thôn</option>
                                    <option value="Techcombank">Techcombank - Ngân hàng TMCP Kỹ thương Việt Nam</option>
                                    <option value="ACB">ACB - Ngân hàng TMCP Á Châu</option>
                                    <option value="VPBank">VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng</option>
                                    <option value="TPBank">TPBank - Ngân hàng TMCP Tiên Phong</option>
                                    <option value="Sacombank">Sacombank - Ngân hàng TMCP Sài Gòn Thương Tín</option>
                                    <option value="MB">MB - Ngân hàng TMCP Quân đội</option>
                                    <option value="SHB">SHB - Ngân hàng TMCP Sài Gòn - Hà Nội</option>
                                    <option value="VIB">VIB - Ngân hàng TMCP Quốc tế Việt Nam</option>
                                    <option value="SeABank">SeABank - Ngân hàng TMCP Đông Nam Á</option>
                                    <option value="OCB">OCB - Ngân hàng TMCP Phương Đông</option>
                                    <option value="MSB">MSB - Ngân hàng TMCP Hàng Hải</option>
                                    <option value="HDBank">HDBank - Ngân hàng TMCP Phát triển TP.HCM</option>
                                    <option value="Other">Ngân hàng khác</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn ngân hàng.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="accountHolderName" class="form-label">Tên chủ tài khoản</label>
                                <input type="text" class="form-control" id="accountHolderName" placeholder="Ví dụ: NGUYEN VAN A" required style="text-transform: uppercase">
                                <div class="form-text">Nhập chính xác tên chủ tài khoản (KHÔNG DẤU, IN HOA)</div>
                                <div class="invalid-feedback">Vui lòng nhập tên chủ tài khoản.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu rút tiền</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="fas fa-history me-2"></i>Lịch sử rút tiền</div>
                     <div class="card-body">
                         <?php if (empty($history)): ?>
                            <div class="alert alert-info text-center mb-0">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Chưa có lịch sử rút tiền
                            </div>
                         <?php else: ?>
                         <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead><tr><th>Mã GD</th><th>Thời gian</th><th>Số điểm</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                                <tbody>
                                    <?php foreach ($history as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['code'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['date'] ?? ''); ?></td>
                                        <td class="text-danger">-<?php echo number_format($item['points'] ?? 0, 0, ',', '.'); ?> điểm</td>
                                        <td class="text-success">+<?php echo number_format($item['amount'] ?? 0, 0, ',', '.'); ?>đ</td>
                                        <td><?php echo $item['status'] ?? ''; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const conversionRate = <?php echo $wallet['conversion_rate'] ?? 100; ?>;
        const maxPoints = <?php echo $wallet['points'] ?? 0; ?>;
        
        function calculateAmount() {
            const points = parseFloat(document.getElementById('withdrawPoints').value) || 0;
            const amount = points * conversionRate;
            document.getElementById('withdrawAmount').value = amount.toLocaleString('vi-VN');
        }
        
        document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const form = this;
            
            // Check HTML5 validation
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            const points = parseInt(document.getElementById('withdrawPoints').value);
            const accountNumber = document.getElementById('bankAccountNumber').value.trim();
            const bankName = document.getElementById('bankName').value;
            const accountHolder = document.getElementById('accountHolderName').value.trim();
            
            // Additional validation
            if (points < 50) {
                alert('Số điểm tối thiểu là 50 điểm');
                return;
            }
            
            if (points > maxPoints) {
                alert(`Bạn chỉ có ${maxPoints.toLocaleString('vi-VN')} điểm`);
                return;
            }
            
            const amount = points * conversionRate;
            
            if (!confirm(`Xác nhận rút tiền?\n\nSố điểm: ${points.toLocaleString('vi-VN')} điểm\nSố tiền: ${amount.toLocaleString('vi-VN')} VNĐ\n\nThông tin nhận tiền:\nSố TK: ${accountNumber}\nNgân hàng: ${bankName}\nChủ TK: ${accountHolder}`)) {
                return;
            }
            
            // Disable submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            
            try {
                const response = await fetch('index.php?controller=api_withdrawal&action=create_request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        points: points,
                        bank_account_number: accountNumber,
                        bank_name: bankName,
                        account_holder_name: accountHolder
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ ${result.message}\n\nMã yêu cầu: ${result.data.request_code}\nĐiểm đã trừ: ${result.data.points_deducted.toLocaleString('vi-VN')} điểm\nSố tiền: ${result.data.amount_requested.toLocaleString('vi-VN')} VNĐ\nĐiểm còn lại: ${result.data.remaining_points.toLocaleString('vi-VN')} điểm\n\nAdmin sẽ xử lý và chuyển tiền trong thời gian sớm nhất.`);
                    
                    // Reload page to update balance and history
                    window.location.reload();
                } else {
                    alert('❌ ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Có lỗi xảy ra. Vui lòng thử lại sau.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }, false);
    </script>
</body>
</html>
