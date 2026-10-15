<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận giao rác - Doanh nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <!-- In a real MVC app, this would be a partial view -->
        <?php $activeTab = 'xac-nhan'; require __DIR__ . '/_navbar.php'; ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-check-circle me-2"></i>Danh sách chờ xác nhận
            </div>
            <div class="card-body">
                <!-- Rule note -->
                <div class="alert alert-info">
                    <i class="fas fa-circle-info me-2"></i>
                    Khi xác nhận, hệ thống trừ điểm trong ví DN đúng bằng tổng điểm của đơn và phân bổ theo hình thức hộ đã chọn:<br>
                    <strong>Hộ tự giao:</strong> 95% điểm chuyển cho hộ, 5% phí hệ thống.<br>
                    <strong>Hộ nhờ CTV:</strong> hộ nhận 60%, CTV nhận 35% (hoàn điểm đã ứng cho hộ), hệ thống thu 5% phí.<br>
                    <small>CTV chỉ nhận đơn khi ví còn đủ điểm để ứng 100% cho hộ ngay lúc nhận.</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Người giao</th>
                                <th>Loại rác</th>
                                <th>KL dự kiến (kg)</th>
                                <th>KL thực tế (kg)</th>
                                <th>Điểm/kg</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="deliveriesTableBody">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        let deliveries = [];

        // Load deliveries from API
        async function loadDeliveries() {
            try {
                const response = await fetch('index.php?controller=api_business_order&action=pending_deliveries');
                const result = await response.json();

                if (result.success) {
                    deliveries = result.data.deliveries;
                    renderDeliveries();
                } else {
                    showError(result.message || 'Không thể tải danh sách');
                }
            } catch (error) {
                console.error('Load deliveries error:', error);
                showError('Lỗi kết nối: ' + error.message);
            }
        }

        function renderDeliveries() {
            const tbody = document.getElementById('deliveriesTableBody');
            
            if (deliveries.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            Không có đơn nào chờ xác nhận
                        </td>
                    </tr>
                `;
                return;
            }

            const html = deliveries.map(delivery => {
                const participantId = delivery.participant_id;
                const hasCTV = delivery.collector_id !== null;
                const deliveryPerson = hasCTV 
                    ? `<span class="badge bg-info">CTV</span> ${delivery.collector_name || 'N/A'}`
                    : `<span class="badge bg-secondary">Tự giao</span> ${delivery.household_name}`;
                const deliveryPhone = hasCTV ? delivery.collector_phone : delivery.household_phone;
                const statusBadge = delivery.status === 'in_transit' 
                    ? '<span class="badge bg-primary">Đang giao đến</span>' 
                    : '<span class="badge bg-warning">Chờ giao</span>';
                
                // ✅ FIX: Safe access với fallback values
                const expectedQty = delivery.expected_quantity || 0;
                const pointsPerKg = delivery.points_per_kg || 0;
                
                return `
                    <tr data-participant-id="${participantId}">
                        <td>
                            <strong>${delivery.order_code}</strong><br>
                            ${statusBadge}
                        </td>
                        <td>
                            ${deliveryPerson}<br>
                            <small class="text-muted">${deliveryPhone || ''}</small>
                        </td>
                        <td>${delivery.waste_type || 'N/A'}</td>
                        <td>
                            <strong>${expectedQty.toLocaleString('vi-VN')} kg</strong>
                            <br><small class="text-muted">(Tối đa)</small>
                        </td>
                        <td>
                            <input type="number" 
                                   class="form-control form-control-sm actual-weight" 
                                   style="width: 120px;" 
                                   placeholder="Nhập KL thực tế" 
                                   required 
                                   min="0.1" 
                                   max="${expectedQty}"
                                   step="0.1"
                                   data-participant-id="${participantId}"
                                   data-expected="${expectedQty}"
                                   value="${expectedQty}">
                            <small class="text-muted">Tối đa: ${expectedQty} kg</small>
                        </td>
                        <td>
                            <span class="badge bg-primary points-per-kg" 
                                  data-participant-id="${participantId}" 
                                  data-ppk="${pointsPerKg}">
                                ${pointsPerKg.toLocaleString('vi-VN')} điểm/kg
                            </span>
                        </td>
                        <td>
                            <div class="small mb-2" id="calc-${participantId}">
                                <strong>Tổng điểm: 0</strong>
                            </div>
                            <button class="btn btn-success btn-sm" 
                                    onclick="confirmDelivery(${participantId})">
                                <i class="fas fa-check"></i> Xác nhận
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = html;

            // Attach event listeners
            document.querySelectorAll('.actual-weight').forEach(input => {
                input.addEventListener('input', function(e) {
                    updateCalculation(e.target);
                });
                // Trigger initial calculation
                updateCalculation(input);
            });
        }

        function updateCalculation(input) {
            if (!input) return;
            const participantId = input.dataset.participantId;
            const delivery = deliveries.find(d => d.participant_id == participantId);
            
            if (!delivery) return;

            // ✅ FIX: Safe parsing với fallback
            const actualQuantity = parseFloat(input.value) || 0;
            const expectedQuantity = parseFloat(input.dataset.expected) || 0;
            
            // Validation: không cho vượt quá số lượng đăng ký
            if (actualQuantity > expectedQuantity) {
                input.value = expectedQuantity;
                input.classList.add('is-invalid');
                setTimeout(() => {
                    alert(`⚠️ Khối lượng thực tế không được vượt quá ${expectedQuantity} kg!\n\nHộ gia đình chỉ đăng ký ${expectedQuantity} kg nên DN chỉ có credit cho số lượng này.`);
                    input.focus();
                }, 100);
                return;
            } else {
                input.classList.remove('is-invalid');
            }
            
            // ✅ FIX: Safe access với fallback
            const pointsPerKg = delivery.points_per_kg || 0;
            const totalPoints = Math.floor(actualQuantity * pointsPerKg);
            
            // Tính điểm sau khi trừ phí
            const deliveryMethod = delivery.delivery_method || 'self';
            let householdPoints, platformFee, ctvPoints;
            
            if (deliveryMethod === 'self') {
                // Tự giao: Hộ 95%, Hệ thống 5%
                householdPoints = Math.floor(totalPoints * 0.95);
                platformFee = totalPoints - householdPoints;
                ctvPoints = 0;
            } else {
                // CTV: Hộ 60%, CTV 35%, Hệ thống 5%
                householdPoints = Math.floor(totalPoints * 0.60);
                ctvPoints = Math.floor(totalPoints * 0.35);
                platformFee = Math.floor(totalPoints * 0.05);
            }

            const calcEl = document.getElementById(`calc-${participantId}`);
            if (calcEl) {
                calcEl.innerHTML = `
                    <strong>Tổng điểm từ DN: ${totalPoints.toLocaleString('vi-VN')}</strong><br>
                    <small class="text-success">→ Hộ nhận: ${householdPoints.toLocaleString('vi-VN')} điểm</small>
                    ${ctvPoints > 0 ? `<br><small class="text-info">→ CTV nhận: ${ctvPoints.toLocaleString('vi-VN')} điểm</small>` : ''}
                    <br><small class="text-muted">→ Phí HT: ${platformFee.toLocaleString('vi-VN')} điểm</small>
                `;
            }
        }

        async function confirmDelivery(participantId) {
            const input = document.querySelector(`.actual-weight[data-participant-id="${participantId}"]`);
            const actualQuantity = parseFloat(input.value);
            const expectedQuantity = parseFloat(input.dataset.expected) || 0;

            if (!actualQuantity || actualQuantity <= 0) {
                alert('Vui lòng nhập số lượng thực tế hợp lệ');
                return;
            }

            // Validation: không cho vượt quá
            if (actualQuantity > expectedQuantity) {
                alert(`⚠️ Khối lượng thực tế (${actualQuantity} kg) không được vượt quá khối lượng đăng ký (${expectedQuantity} kg)!\n\nHộ gia đình chỉ đăng ký ${expectedQuantity} kg nên DN chỉ có credit cho số lượng này.`);
                input.focus();
                return;
            }

            const delivery = deliveries.find(d => d.participant_id == participantId);
            if (!delivery) return;

            // ✅ FIX: Safe access với fallback
            const pointsPerKg = delivery.points_per_kg || 0;
            const totalPoints = Math.floor(actualQuantity * pointsPerKg);
            const deliveryMethod = delivery.delivery_method || 'self';
            
            // Tính điểm sau phí
            let householdPoints, ctvPoints;
            if (deliveryMethod === 'self') {
                householdPoints = Math.floor(totalPoints * 0.95);
                ctvPoints = 0;
            } else {
                householdPoints = Math.floor(totalPoints * 0.60);
                ctvPoints = Math.floor(totalPoints * 0.35);
            }
            
            const confirmMsg = `Xác nhận giao rác?\n\n` +
                `Hộ gia đình: ${delivery.household_name}\n` +
                `Số lượng: ${actualQuantity} kg\n` +
                `Tổng điểm từ DN: ${totalPoints.toLocaleString('vi-VN')} điểm\n\n` +
                `Phân bổ:\n` +
                `  • Hộ nhận: ${householdPoints.toLocaleString('vi-VN')} điểm (${deliveryMethod === 'self' ? '95%' : '60%'})\n` +
                (ctvPoints > 0 ? `  • CTV nhận: ${ctvPoints.toLocaleString('vi-VN')} điểm (35%)\n` : '') +
                `  • Phí hệ thống: ${totalPoints - householdPoints - ctvPoints} điểm (5%)`;
            
            if (!confirm(confirmMsg)) {
                return;
            }

            try {
                const response = await fetch('index.php?controller=api_business_order&action=confirm_delivery', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        participant_id: participantId,
                        actual_quantity: actualQuantity
                    })
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    
                    // ✅ FIX: Sử dụng đúng tên field từ API và check null
                    const pointsFromBusiness = data.points_from_business || 0;
                    const householdPoints = data.household_points_final || data.household_points_earned || 0;
                    const collectorPoints = data.collector_points_final || 0;
                    const platformFee = data.platform_fee || 0;
                    const deliveryMethod = data.delivery_method || 'self';
                    
                    let successMsg = `✅ Xác nhận thành công!\n\n`;
                    successMsg += `Tổng điểm từ DN: ${pointsFromBusiness.toLocaleString('vi-VN')} điểm\n\n`;
                    successMsg += `Phân bổ:\n`;
                    successMsg += `  • Hộ nhận: ${householdPoints.toLocaleString('vi-VN')} điểm\n`;
                    
                    if (deliveryMethod !== 'self' && collectorPoints > 0) {
                        successMsg += `  • CTV nhận: ${collectorPoints.toLocaleString('vi-VN')} điểm\n`;
                    }
                    
                    successMsg += `  • Phí hệ thống: ${platformFee.toLocaleString('vi-VN')} điểm`;
                    
                    alert(successMsg);
                    // Reload danh sách
                    loadDeliveries();
                } else {
                    alert('❌ Lỗi: ' + (result.message || 'Không thể xác nhận'));
                }
            } catch (error) {
                console.error('Confirm delivery error:', error);
                alert('❌ Lỗi kết nối: ' + error.message);
            }
        }

        function showError(message) {
            const tbody = document.getElementById('deliveriesTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                        ${message}
                    </td>
                </tr>
            `;
        }

        // Load on page ready
        document.addEventListener('DOMContentLoaded', loadDeliveries);
    </script>
</body>
</html>
