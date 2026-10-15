<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn - Hộ gia đình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar for Household -->
    <?php require __DIR__ . '/_navbar.php'; ?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Chi tiết đơn hàng <?php echo htmlspecialchars($order['code'] ?? '#N/A'); ?>
                    </div>
                    <div class="card-body">
                        <!-- Placeholder for Controller: Populate order details -->
                        <div class="mb-3">
                            <h4 id="orderTitle"><?php echo htmlspecialchars($order['title'] ?? 'Đơn hàng'); ?></h4>
                            <p class="text-muted mb-0" id="companyName">Đăng bởi: <?php echo htmlspecialchars($order['company_name'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item px-0">
                                        <strong id="priceLabel">Điểm/kg:</strong> 
                                        <span id="priceValue" class="text-primary fw-bold"><?php echo htmlspecialchars($order['points_per_kg'] ?? '0 điểm/kg'); ?></span>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <strong>Số lượng tối đa:</strong> 
                                        <span id="maxQuantity" class="fw-bold"><?php echo htmlspecialchars($order['max_quantity'] ?? '0 kg'); ?></span>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <strong>Còn lại:</strong> 
                                        <span id="remainingQuantity" class="text-warning fw-bold"><?php echo htmlspecialchars($order['remaining_quantity'] ?? '0 kg'); ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item px-0">
                                        <strong>Liên hệ:</strong> 
                                        <span id="contactName"><?php echo htmlspecialchars($order['contact_name'] ?? 'N/A'); ?></span>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <strong>Điện thoại:</strong> 
                                        <span id="contactPhone" class="text-primary"><?php echo htmlspecialchars($order['contact_phone'] ?? 'N/A'); ?></span>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <strong>Địa chỉ:</strong> 
                                        <span id="orderAddress"><?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></span>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <strong>Ghi chú:</strong> 
                                        <span id="orderNotes"><?php echo htmlspecialchars($order['notes'] ?? 'Không có ghi chú'); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <form class="needs-validation" novalidate>
                            <!-- Placeholder for Controller: Handle order acceptance -->
                             <div class="mb-3">
                                <label for="quantity" class="form-label">Khối lượng bạn có (kg)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="quantity" placeholder="Nhập số kg bạn có" required min="0.1" max="<?php echo $order['remaining_quantity_raw'] ?? 0; ?>" step="0.1" oninput="calculateEarnings()">
                                    <span class="input-group-text">kg</span>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    Tối đa: <strong><?php echo htmlspecialchars($order['max_quantity'] ?? '0 kg'); ?></strong> | 
                                    Còn lại của đơn: <strong class="text-warning"><?php echo htmlspecialchars($order['remaining_quantity'] ?? '0 kg'); ?></strong>
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập khối lượng hợp lệ (0.1 - <?php echo $order['remaining_quantity_raw'] ?? 0; ?> kg).</div>
                            </div>

                            <!-- Earnings Calculator -->
                            <div class="mb-4">
                                <!-- Money calculator removed (points-only flow) -->

                                <!-- Points Calculator -->
                                <div id="pointsCalculator" class="card bg-light border-primary">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">
                                            <i class="fas fa-star me-2"></i>Tính toán tích điểm
                                        </h6>
                                        <div class="row text-center mb-3">
                                            <div class="col-4">
                                                <div class="border-end">
                                                    <div class="text-muted small">Điểm/kg</div>
                                                    <div class="fw-bold text-primary" id="pointsPrice">50 điểm/kg</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="border-end">
                                                    <div class="text-muted small">Số lượng</div>
                                                    <div class="fw-bold" id="calculatedQuantityPoints">0 kg</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-muted small">Tổng điểm</div>
                                                <div class="fw-bold text-primary" id="totalPoints">0 điểm</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Thông tin điểm -->
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Quy tắc:</strong> 
                                            <br>• <strong>Hộ tự giao:</strong> Bạn nhận 95% điểm sau khi DN xác nhận, hệ thống giữ 5% phí.
                                            <br>• <strong>Hộ nhờ CTV:</strong> CTV ứng 60% điểm cho bạn ngay; sau khi DN xác nhận, CTV nhận 95% từ hệ thống (lời ròng 35%), hệ thống giữ 5% phí.
                                            <br><small>Điểm có thể dùng để đổi sản phẩm hoặc rút tiền (1 điểm = 1,000&nbsp;VNĐ).</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Hình thức giao rác</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="deliveryType" id="self-delivery" value="self" checked required onchange="calculateEarnings()">
                                    <label class="form-check-label" for="self-delivery">
                                        <strong>Tự giao đến điểm thu gom</strong>
                                        <small class="d-block text-muted">Bạn sẽ tự mang rác đến địa chỉ của doanh nghiệp.</small>
                                    </label>
                                </div>
                                <div class="form-check mt-2" id="ctvDeliveryOption">
                                    <input class="form-check-input" type="radio" name="deliveryType" id="ctv-delivery" value="ctv" required onchange="calculateEarnings()">
                                    <label class="form-check-label" for="ctv-delivery">
                                        <strong>Nhờ Cộng tác viên (CTV) đến lấy</strong>
                                        <small class="d-block text-muted">CTV ứng 60% cho bạn ngay; sau DN xác nhận, CTV nhận 95%, lời ròng 35%, hệ thống giữ 5%.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check-circle me-2"></i>Nhận đơn</button>
                                <a href="feed.html" class="btn btn-outline-secondary">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Get remaining quantity from PHP
        const remainingQuantity = parseFloat('<?php echo str_replace([' kg', ',', '.'], ['', '', '.'], $order['remaining_quantity'] ?? '0'); ?>') || 0;
        const pointsPerKg = parseFloat('<?php echo str_replace([' điểm/kg', ',', '.'], ['', '', '.'], $order['points_per_kg'] ?? '0'); ?>') || 0;

        function calculateEarnings() {
            const quantityInput = document.getElementById('quantity');
            const quantity = parseFloat(quantityInput.value) || 0;
            
            // Validation
            if (quantity > remainingQuantity) {
                quantityInput.setCustomValidity(`Không được vượt quá ${remainingQuantity} kg`);
                quantityInput.classList.add('is-invalid');
            } else if (quantity < 0.1 && quantity > 0) {
                quantityInput.setCustomValidity('Khối lượng tối thiểu là 0.1 kg');
                quantityInput.classList.add('is-invalid');
            } else {
                quantityInput.setCustomValidity('');
                quantityInput.classList.remove('is-invalid');
                if (quantity > 0) {
                    quantityInput.classList.add('is-valid');
                }
            }

            // Update points calculator  
            document.getElementById('calculatedQuantityPoints').textContent = quantity.toFixed(1) + ' kg';
            
            const totalPoints = quantity * pointsPerKg;
            const deliveryType = document.querySelector('input[name="deliveryType"]:checked')?.value || 'self';
            
            // Chỉ hiển thị số điểm hộ nhận
            let householdReceive;
            if (deliveryType === 'self') {
                // Tự giao: nhận 95%
                householdReceive = Math.floor(totalPoints * 0.95);
            } else {
                // CTV: nhận 60% ngay
                householdReceive = Math.floor(totalPoints * 0.60);
            }
            
            document.getElementById('totalPoints').textContent = `${householdReceive.toLocaleString('vi-VN')} điểm`;
        }

        // Form submission handler
        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const quantity = parseFloat(document.getElementById('quantity').value);
            
            if (!quantity || quantity <= 0 || quantity > remainingQuantity) {
                alert('Vui lòng nhập khối lượng hợp lệ!');
                return;
            }

            // Show confirmation
            const deliveryType = document.querySelector('input[name="deliveryType"]:checked')?.value || 'self';
            const totalPoints = quantity * pointsPerKg;
            
            let earningsText, confirmDetails;
            if (deliveryType === 'self') {
                // Tự giao: nhận 95%
                const householdPoints = Math.floor(totalPoints * 0.95);
                earningsText = `${householdPoints.toLocaleString('vi-VN')} điểm (95%)`;
                confirmDetails = `- Phí hệ thống: 5%\n- Điểm được cộng sau khi DN xác nhận`;
            } else {
                // CTV: CTV ứng 60% ngay, sau đó nhận 95% từ DN
                const householdPortion = Math.floor(totalPoints * 0.60); // Hộ nhận
                const ctvReceiveFromDN = Math.floor(totalPoints * 0.95); // CTV nhận từ DN
                const ctvProfit = ctvReceiveFromDN - householdPortion; // CTV lời ròng
                const systemFee = Math.floor(totalPoints * 0.05);
                
                earningsText = `Nhận ngay ${householdPortion.toLocaleString('vi-VN')} điểm (CTV ứng)`;
                confirmDetails = `Cách hoạt động:\n` +
                    `- CTV ứng trước: ${householdPortion.toLocaleString('vi-VN')} điểm cho bạn ngay\n` +
                    `- Sau DN xác nhận, CTV nhận lại: ${ctvReceiveFromDN.toLocaleString('vi-VN')} điểm (95%)\n` +
                    `- CTV lời ròng: ${ctvProfit.toLocaleString('vi-VN')} điểm\n` +
                    `- Phí hệ thống: ${systemFee.toLocaleString('vi-VN')} điểm (5%)`;
            }
            
            const deliveryText = deliveryType === 'self' ? 'Tự giao đến DN' : 'CTV đến lấy';
            const confirmMessage = `Xác nhận nhận đơn:\n\n` +
                `- Khối lượng: ${quantity} kg\n` +
                `- Điểm từ DN: ${totalPoints.toLocaleString('vi-VN')} điểm\n` +
                `- Bạn nhận: ${earningsText}\n` +
                `- Hình thức: ${deliveryText}\n\n` +
                `${confirmDetails}\n\n` +
                `Bạn có chắc chắn?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }

            // Get order ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = parseInt(urlParams.get('id'));

            if (!orderId) {
                alert('Không tìm thấy mã đơn hàng!');
                return;
            }

            try {
                // Call API to accept order
                const response = await fetch('index.php?controller=api_business_order&action=accept_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderId: orderId,
                        quantity: quantity,
                        deliveryType: deliveryType
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert('✅ Đã nhận đơn thành công! Vui lòng kiểm tra trong "Quản lý đơn".');
                    window.location.href = 'index.php?controller=ho_gia_dinh&action=quanLyDon';
                } else {
                    alert('❌ Lỗi: ' + (result.message || 'Không thể nhận đơn'));
                }
            } catch (error) {
                console.error('Error accepting order:', error);
                alert('❌ Lỗi kết nối server. Vui lòng thử lại!');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set max attribute for quantity input
            document.getElementById('quantity').setAttribute('max', remainingQuantity);
            
            // Update form text with actual values
            const formText = document.querySelector('.form-text');
            formText.innerHTML = `<i class="fas fa-info-circle text-primary me-1"></i>Tối đa: <strong>${remainingQuantity} kg</strong> | Còn lại của đơn: <strong>${remainingQuantity} kg</strong>`;
            
            // Update calculator display
            document.getElementById('pointsPrice').textContent = `${pointsPerKg} điểm/kg`;
            
            calculateEarnings();
        });
    </script>
</body>
</html>
