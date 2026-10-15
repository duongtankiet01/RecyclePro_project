<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn CTV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-info-circle me-2"></i>Chi tiết đơn CTV #<?php echo $order['participant_id']; ?>
                    </div>
                    <div class="card-body">
                        <?php if (!$order['is_available']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Đơn hàng này đã được CTV khác nhận hoặc đã hoàn thành.</strong>
                            </div>
                        <?php endif; ?>
                        
                        <h4 class="mb-4">Thu gom <strong><?php echo htmlspecialchars($order['waste_type']); ?></strong></h4>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light text-dark">
                                <i class="fas fa-building me-2"></i>Thông tin doanh nghiệp
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Tên doanh nghiệp:</strong> 
                                    <span class="float-end"><?php echo htmlspecialchars($order['business_name']); ?></span>
                                </li>
                                <?php if (!empty($order['contact_person'])): ?>
                                <li class="list-group-item">
                                    <strong>Người liên hệ:</strong> 
                                    <span class="float-end"><?php echo htmlspecialchars($order['contact_person']); ?></span>
                                </li>
                                <?php endif; ?>
                                <li class="list-group-item">
                                    <strong>Số điện thoại:</strong> 
                                    <span class="float-end">
                                        <a href="tel:<?php echo htmlspecialchars($order['business_phone']); ?>" class="text-decoration-none">
                                            <i class="fas fa-phone text-success me-1"></i>
                                            <?php echo htmlspecialchars($order['business_phone']); ?>
                                        </a>
                                    </span>
                                </li>
                                <?php if (!empty($order['business_email'])): ?>
                                <li class="list-group-item">
                                    <strong>Email:</strong> 
                                    <span class="float-end">
                                        <a href="mailto:<?php echo htmlspecialchars($order['business_email']); ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope text-primary me-1"></i>
                                            <?php echo htmlspecialchars($order['business_email']); ?>
                                        </a>
                                    </span>
                                </li>
                                <?php endif; ?>
                                <li class="list-group-item">
                                    <strong>Địa chỉ:</strong><br>
                                    <span class="text-muted">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        <?php echo htmlspecialchars($order['business_address']); ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light text-dark">
                                <i class="fas fa-home me-2"></i>Thông tin hộ gia đình
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Tên hộ:</strong> 
                                    <span class="float-end"><?php echo htmlspecialchars($order['household_name']); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Số điện thoại:</strong> 
                                    <span class="float-end">
                                        <a href="tel:<?php echo htmlspecialchars($order['household_phone']); ?>" class="text-decoration-none">
                                            <i class="fas fa-phone text-success me-1"></i>
                                            <?php echo htmlspecialchars($order['household_phone']); ?>
                                        </a>
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Địa chỉ:</strong><br>
                                    <span class="text-muted">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        <?php echo htmlspecialchars($order['address']); ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light text-dark">
                                <i class="fas fa-boxes me-2"></i>Thông tin đơn hàng
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Loại rác:</strong> 
                                    <span class="float-end badge bg-info"><?php echo htmlspecialchars($order['waste_type']); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Khối lượng dự kiến:</strong> 
                                    <span class="float-end"><strong>~<?php echo number_format($order['quantity'], 1); ?> kg</strong></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Đơn giá:</strong> 
                                    <span class="float-end"><?php echo number_format($order['points_per_kg'], 0); ?> điểm/kg</span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Tổng điểm:</strong> 
                                    <span class="float-end"><strong><?php echo number_format($order['total_points'], 0); ?> điểm</strong></span>
                                </li>
                                <?php if (!empty($order['order_notes'])): ?>
                                <li class="list-group-item">
                                    <strong>Ghi chú từ doanh nghiệp:</strong><br>
                                    <span class="text-muted">
                                        <i class="fas fa-sticky-note text-warning me-1"></i>
                                        <?php echo nl2br(htmlspecialchars($order['order_notes'])); ?>
                                    </span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="card mb-4 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <i class="fas fa-hand-holding-usd me-2"></i>Chi tiết thanh toán
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Số điểm bạn phải ứng:</strong> 
                                    <span class="float-end text-danger fw-bold">
                                        <?php echo number_format($order['advance_amount'], 0); ?> điểm
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Tiền công bạn nhận (lời ròng):</strong> 
                                    <span class="float-end text-success fw-bold">
                                        <?php echo number_format($order['your_commission'] * 1000, 0); ?> VND
                                    </span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong><br>
                            • Bạn sẽ ứng <strong><?php echo number_format($order['advance_amount'], 0); ?> điểm</strong> cho hộ gia đình ngay khi nhận đơn<br>
                            • Sau khi DN xác nhận thu gom, bạn nhận <strong><?php echo number_format($order['total_points'] * 0.95, 0); ?> điểm</strong> từ hệ thống (lời ròng: <strong><?php echo number_format($order['your_commission'], 0); ?> điểm</strong>)
                        </div>
                        
                        <div class="d-grid gap-2">
                            <?php if ($order['is_available']): ?>
                                <button id="btnAcceptOrder" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i>Nhận đơn này
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg" disabled>
                                    <i class="fas fa-lock me-2"></i>Đơn không khả dụng
                                </button>
                            <?php endif; ?>
                            <a href="index.php?controller=ctv&action=feed" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <?php if (isset($order) && $order['is_available']): ?>
    <script>
        document.getElementById('btnAcceptOrder').addEventListener('click', async function() {
            const participantId = <?php echo $order['participant_id']; ?>;
            
            if (!confirm('Xác nhận nhận đơn này?\n\nBạn sẽ đến địa chỉ để xem rác và xác nhận thu gom.')) {
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            
            try {
                const response = await fetch('index.php?controller=api_collector&action=acceptOrder', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({participant_id: participantId})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    window.location.href = 'index.php?controller=ctv&action=quanLyDon';
                } else {
                    alert('❌ ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Nhận đơn này';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Có lỗi xảy ra: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Nhận đơn này';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
