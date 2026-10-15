<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn CTV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar for CTV -->
    <?php
    $activeTab = 'quan-ly-don';
    require __DIR__ . '/_navbar.php';
    
    // Helper function to get full address
    function getFullAddressForGoogleMaps($address, $provinceCode, $wardCode) {
        // Chỉ cần trả về địa chỉ + Vietnam để Google Maps có thể tìm được
        $fullAddr = $address;
        if (!empty($provinceCode)) {
            // Bạn có thể thêm logic để convert province code thành tên nếu cần
            // Hiện tại chỉ thêm ", Vietnam" để cải thiện độ chính xác
        }
        return $fullAddr . ', Vietnam';
    }
    ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-tasks me-2"></i>Đơn hàng đã nhận</div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Bạn chưa nhận đơn nào. Vào <a href="index.php?controller=ctv&action=feed">Tìm đơn hàng</a> để nhận đơn mới.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Hộ gia đình</th>
                                    <th>Loại rác</th>
                                    <th>Địa chỉ hộ</th>
                                    <th>Điểm giao (DN)</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['participant_id']; ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['household_name']); ?><br>
                                            <small class="text-muted">
                                                <i class="fas fa-phone"></i> 
                                                <a href="tel:<?php echo htmlspecialchars($order['household_phone']); ?>">
                                                    <?php echo htmlspecialchars($order['household_phone']); ?>
                                                </a>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($order['waste_type']); ?></span><br>
                                            <small class="text-muted"><?php echo number_format($order['quantity'], 1); ?> kg</small>
                                        </td>
                                        <td>
                                            <?php 
                                            $googleMapsAddress = getFullAddressForGoogleMaps(
                                                $order['address'], 
                                                $order['province_code'] ?? '', 
                                                $order['ward_code'] ?? ''
                                            );
                                            ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($googleMapsAddress); ?>" 
                                               target="_blank" 
                                               class="text-decoration-none text-primary"
                                               title="Xem trên Google Maps">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <small><?php echo htmlspecialchars($order['address']); ?></small>
                                            </a>
                                        </td>
                                        <td>
                                            <?php 
                                            $businessGoogleMapsAddress = getFullAddressForGoogleMaps(
                                                $order['business_address'] ?? 'Chưa có địa chỉ', 
                                                $order['business_province_code'] ?? '', 
                                                $order['business_ward_code'] ?? ''
                                            );
                                            ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($businessGoogleMapsAddress); ?>" 
                                               target="_blank" 
                                               class="text-decoration-none text-success"
                                               title="Xem điểm giao trên Google Maps">
                                                <i class="fas fa-building me-1"></i>
                                                <small><?php echo htmlspecialchars($order['business_name']); ?></small>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="<?php echo $order['status_class']; ?>">
                                                <?php echo $order['status_label']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['can_confirm_pickup']): ?>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-success btn-confirm-pickup" 
                                                            data-id="<?php echo $order['participant_id']; ?>"
                                                            data-expected="<?php echo $order['quantity']; ?>">
                                                        <i class="fas fa-check me-1"></i>Thu gom
                                                    </button>
                                                    <button class="btn btn-danger btn-cancel-order" 
                                                            data-id="<?php echo $order['participant_id']; ?>"
                                                            title="Hủy đơn">
                                                        <i class="fas fa-times"></i> Hủy
                                                    </button>
                                                </div>
                                            <?php elseif ($order['status'] === 'confirmed'): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> Đã hoàn thành
                                                </span>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // Xử lý xác nhận thu gom
        document.querySelectorAll('.btn-confirm-pickup').forEach(btn => {
            btn.addEventListener('click', async function() {
                const participantId = this.dataset.id;
                const expectedQuantity = parseFloat(this.dataset.expected) || 0;
                
                // Prompt nhập số lượng thực tế
                const actualQuantityStr = prompt(
                    `Nhập số lượng thực tế (kg):\n\n` +
                    `Số lượng dự kiến: ${expectedQuantity.toFixed(1)} kg\n` +
                    `Lưu ý: Không được vượt quá số lượng dự kiến`,
                    expectedQuantity.toFixed(1)
                );
                
                if (actualQuantityStr === null) {
                    return; // User cancelled
                }
                
                const actualQuantity = parseFloat(actualQuantityStr);
                
                if (isNaN(actualQuantity) || actualQuantity <= 0) {
                    alert('❌ Số lượng không hợp lệ!');
                    return;
                }
                
                if (actualQuantity > expectedQuantity) {
                    alert('❌ Số lượng thực tế không được vượt quá ' + expectedQuantity.toFixed(1) + ' kg!');
                    return;
                }
                
                if (!confirm(`Xác nhận thu gom ${actualQuantity.toFixed(1)} kg rác?\n\nBạn sẽ ứng điểm cho hộ gia đình.`)) {
                    return;
                }
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...';
                
                try {
                    const response = await fetch('index.php?controller=api_collector&action=confirmPickup', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            participant_id: parseInt(participantId),
                            actual_quantity: actualQuantity
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('✅ ' + result.message);
                        window.location.reload();
                    } else {
                        alert('❌ ' + result.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-hand-holding-usd me-1"></i>Xác nhận thu gom';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('❌ Có lỗi xảy ra: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-hand-holding-usd me-1"></i>Xác nhận thu gom';
                }
            });
        });
        
        // Xử lý hủy đơn CTV
        document.querySelectorAll('.btn-cancel-order').forEach(btn => {
            btn.addEventListener('click', async function() {
                const participantId = this.dataset.id;
                
                if (!confirm('Bạn có chắc chắn muốn HỦY đơn này?\n\nĐơn sẽ trở lại trạng thái "Chờ CTV nhận" để CTV khác có thể nhận.')) {
                    return;
                }
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                try {
                    const response = await fetch('index.php?controller=api_collector&action=cancelOrder', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            participant_id: parseInt(participantId)
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('✅ ' + result.message);
                        window.location.reload();
                    } else {
                        alert('❌ ' + result.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-times"></i> Hủy';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('❌ Có lỗi xảy ra: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-times"></i> Hủy';
                }
            });
        });
    </script>
</body>
</html>
