<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ví điểm - Cộng tác viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stat { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; border-radius:.5rem; background:#f8f9fa; }
        .stat .icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:50%; }
        .stat .value { font-weight:700; font-size:1.25rem; }
    </style>
    
    <script>
        const maxPoints = <?php echo floor($wallet['available_points']); ?>;
        
        function vnd(n){ return (n||0).toLocaleString('vi-VN') + ' VNĐ'; }
        
        function updateCashFromPoints(){
            const pts = parseInt(document.getElementById('withdrawPoints').value||'0',10);
            document.getElementById('calcCash').textContent = vnd(pts*1000);
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('withdrawForm');
            
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (!form.checkValidity()) { 
                    form.classList.add('was-validated'); 
                    return; 
                }
                
                const pts = parseInt(document.getElementById('withdrawPoints').value||'0',10);
                const accountNumber = document.getElementById('bankAccountNumber').value.trim();
                const bankName = document.getElementById('bankName').value;
                const accountHolder = document.getElementById('accountHolderName').value.trim();
                
                if (pts < 50) {
                    alert('Số điểm tối thiểu là 50 điểm');
                    return;
                }
                
                if (pts > maxPoints) {
                    alert(`Bạn chỉ có ${maxPoints.toLocaleString('vi-VN')} điểm`);
                    return;
                }
                
                const amount = pts * 1000;
                
                if (!confirm(`Xác nhận rút tiền?\n\nSố điểm: ${pts.toLocaleString('vi-VN')} điểm\nSố tiền: ${amount.toLocaleString('vi-VN')} VNĐ\n\nThông tin nhận tiền:\nSố TK: ${accountNumber}\nNgân hàng: ${bankName}\nChủ TK: ${accountHolder}`)) {
                    return;
                }
                
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
                
                try {
                    const response = await fetch('index.php?controller=api_withdrawal&action=create_request_collector', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            points: pts,
                            bank_account_number: accountNumber,
                            bank_name: bankName,
                            account_holder_name: accountHolder
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(`✅ ${result.message}\n\nMã yêu cầu: ${result.data.request_code}\nĐiểm đã trừ: ${result.data.points_deducted.toLocaleString('vi-VN')} điểm\nSố tiền: ${result.data.amount_requested.toLocaleString('vi-VN')} VNĐ\nĐiểm còn lại: ${result.data.remaining_points.toLocaleString('vi-VN')} điểm\n\nAdmin sẽ xử lý và chuyển tiền trong thời gian sớm nhất.`);
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
            });
        });
    </script>
</head>
<body>
    <!-- Navbar for CTV -->
    <?php $activeTab = 'vi-diem'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="fas fa-star me-2"></i>Ví điểm CTV</div>
                    <div class="card-body">
                        <div class="stat mb-3">
                            <div class="icon bg-warning text-dark"><i class="fas fa-coins"></i></div>
                            <div>
                                <div class="text-muted">Điểm khả dụng</div>
                                <div class="value" id="availablePoints">
                                    <?php echo number_format($wallet['available_points'], 0, ',', '.'); ?> điểm
                                </div>
                            </div>
                        </div>
                        <div class="stat mb-4">
                            <div class="icon bg-info text-white"><i class="fas fa-hourglass-half"></i></div>
                            <div>
                                <div class="text-muted">Điểm chờ xác nhận</div>
                                <div class="value" id="pendingPoints">
                                    <?php echo number_format($wallet['pending_points'], 0, ',', '.'); ?> điểm
                                </div>
                                <small class="text-muted">Điểm đã ứng, chờ DN xác nhận để nhận hoa hồng.</small>
                            </div>
                        </div>

                        <h6 class="mb-2">Rút điểm về tiền</h6>
                        <form id="withdrawForm" class="needs-validation" novalidate>
                            <div class="mb-2">
                                <label class="form-label" for="withdrawPoints">Số điểm cần rút</label>
                                <input type="number" id="withdrawPoints" class="form-control" 
                                       min="50" max="<?php echo floor($wallet['available_points']); ?>"
                                       placeholder="Tối thiểu 50 điểm" required oninput="updateCashFromPoints()">
                                <div class="form-text">Tỉ lệ: 1 điểm = 1,000 VNĐ | Khả dụng: <?php echo number_format($wallet['available_points'], 0, ',', '.'); ?> điểm</div>
                                <div class="invalid-feedback">Số điểm không hợp lệ (tối thiểu 50 điểm)</div>
                            </div>
                            <div class="alert alert-secondary mb-2">
                                <i class="fas fa-equals me-2"></i>Số tiền tương ứng: <strong id="calcCash">0 VNĐ</strong>
                            </div>
                            
                            <hr class="my-3">
                            <h6 class="mb-2">Thông tin nhận tiền</h6>
                            
                            <div class="mb-2">
                                <label for="bankAccountNumber" class="form-label">Số tài khoản</label>
                                <input type="text" class="form-control" id="bankAccountNumber" placeholder="Nhập số TK" required>
                                <div class="invalid-feedback">Vui lòng nhập số tài khoản</div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="bankName" class="form-label">Ngân hàng</label>
                                <select class="form-select" id="bankName" required>
                                    <option value="">-- Chọn ngân hàng --</option>
                                    <option value="Vietcombank">Vietcombank</option>
                                    <option value="VietinBank">VietinBank</option>
                                    <option value="BIDV">BIDV</option>
                                    <option value="Agribank">Agribank</option>
                                    <option value="Techcombank">Techcombank</option>
                                    <option value="ACB">ACB</option>
                                    <option value="VPBank">VPBank</option>
                                    <option value="TPBank">TPBank</option>
                                    <option value="MB">MB</option>
                                    <option value="Sacombank">Sacombank</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn ngân hàng</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="accountHolderName" class="form-label">Tên chủ TK</label>
                                <input type="text" class="form-control" id="accountHolderName" placeholder="NGUYEN VAN A" required style="text-transform: uppercase">
                                <div class="form-text">Nhập chính xác (KHÔNG DẤU, IN HOA)</div>
                                <div class="invalid-feedback">Vui lòng nhập tên chủ tài khoản</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu rút tiền</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="fas fa-history me-2"></i>Lịch sử điểm</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Biến động</th>
                                        <th>Nội dung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($history)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                Chưa có giao dịch nào
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($history as $item): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></small>
                                                </td>
                                                <td class="<?php echo $item['amount_class']; ?>">
                                                    <strong><?php echo $item['amount_formatted']; ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($item['description']); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Số dư sau: <?php echo number_format($item['balance_after'], 0, ',', '.'); ?> điểm
                                                    </small>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
