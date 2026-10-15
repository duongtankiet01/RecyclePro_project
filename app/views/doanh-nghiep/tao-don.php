<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo đơn thu gom - Doanh nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
        <?php $activeTab = 'tao-don'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header" id="order-header">
                        <i class="fas fa-plus-circle me-2" id="header-icon"></i><span id="header-text">Tạo đơn thu gom mới</span>
                    </div>
                    <div class="card-body">
                        <form class="row g-3 needs-validation" novalidate>
                            <!-- Placeholder for Controller: Handle form submission -->
                            <div class="col-md-6">
                                <label for="wasteType" class="form-label">Loại rác tái chế</label>
                                <select class="form-select" id="wasteType" required>
                                    <option selected disabled value="">Chọn loại rác...</option>
                                    <option value="Nhựa PET">Nhựa PET</option>
                                    <option value="Giấy carton">Giấy carton</option>
                                    <option value="Kim loại (lon nhôm)">Kim loại (lon nhôm)</option>
                                    <option value="Thủy tinh">Thủy tinh</option>
                                    <option value="__custom__">Khác (tùy chỉnh)</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn loại rác.</div>
                            </div>
                            <div class="col-md-6 d-none" id="customWasteTypeField">
                                <label for="customWasteType" class="form-label">Nhập loại rác tùy chỉnh</label>
                                <input type="text" class="form-control" id="customWasteType" placeholder="Ví dụ: Pin cũ, Điện tử..." maxlength="50">
                                <div class="invalid-feedback">Vui lòng nhập loại rác tùy chỉnh.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">KL/SL cần thu gom (kg/cái)</label>
                                <input type="number" class="form-control" id="quantity" placeholder="Ví dụ: 100" required min="1">
                                <div class="invalid-feedback">Vui lòng nhập khối lượng hợp lệ.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Hình thức trả thưởng</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentType" id="points" value="points" checked>
                                    <label class="form-check-label" for="points">
                                        <strong>Trả bằng điểm tích lũy</strong>
                                        <div class="text-muted small mt-1">
Quy đổi: 1,000 VNĐ = 1 điểm (DN nạp tiền → hệ thống chuyển thành điểm).<br>
                                            - Hộ tự giao: Sau xác nhận, hệ thống trừ điểm DN và chuyển 95% cho hộ, 5% phí hệ thống.<br>
                                            - Hộ nhờ CTV: CTV phải có sẵn điểm để ứng 100% cho hộ. Khi DN xác nhận, hệ thống trừ điểm DN và phân bổ 60% cho hộ, 35% cho CTV, 5% phí hệ thống.
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Fields for Points -->
                            <div class="col-md-6" id="points-field">
                                <label for="pointsPerKg" class="form-label">Điểm tích lũy (điểm/kg)</label>
                                <input type="number" class="form-control" id="pointsPerKg" placeholder="Ví dụ: 100" min="1" step="1" required>
                                <div class="invalid-feedback">Vui lòng nhập số điểm tích lũy.</div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Điểm trả cho hộ được tính theo quy tắc: tự giao 95% điểm; nhờ CTV hộ nhận 100% điểm ngay.
                                </div>
                            </div>
                            
                            <div class="col-md-6 d-none" id="deadline-field">
                                <label for="deadline" class="form-label">Hạn chót nhận đơn</label>
                                <input type="date" class="form-control" id="deadline">
                                <div class="invalid-feedback">Vui lòng chọn hạn chót.</div>
                            </div>

                            <!-- Display estimated rewards -->
                            <div class="col-12" id="reward-estimate">
                                <label class="form-label">Dự kiến thưởng (cho 100kg)</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div id="points-estimate" class="reward-display">
                                                <i class="fas fa-star text-warning me-2"></i>
                                                <span id="points-amount">10,000 điểm</span>
                                                <div class="small text-muted mt-2">
                                                    - Hộ tự giao: Hộ nhận 95% điểm, hệ thống thu 5% phí.<br>
                                                    - Hộ nhờ CTV: Hộ nhận 60%, CTV nhận 35% (hoàn điểm đã ứng), hệ thống thu 5% phí.
                                                </div>
                                            </div>
</div>
                                    </div>
                            </div>

                             <div class="col-12" id="notes-field">
                                <label for="notes" class="form-label">Ghi chú thêm</label>
                                <textarea class="form-control" id="notes" rows="3" placeholder="Yêu cầu thêm (nếu có)"></textarea>
                            </div>

                            <div class="col-12 d-grid" id="submit-field">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Tạo đơn
                                </button>
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
        class OrderCreationManager {
            constructor() {
                console.log('🚀 OrderCreationManager constructor called');
                this.initializeEventListeners();
                // Points-only: show points fields by default
                this.updateHeader('points', document.getElementById('header-icon'), document.getElementById('header-text'), document.getElementById('order-header'));
                this.updateRewardEstimate();
                console.log('✅ OrderCreationManager initialized');
            }

            initializeEventListeners() {
                console.log('🔧 Initializing event listeners...');
                
                // Input change listeners for reward estimation (points only)
                ['quantity', 'pointsPerKg'].forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        console.log(`✅ Found element: ${id}`);
                        element.addEventListener('input', () => this.updateRewardEstimate());
                    } else {
                        console.error(`❌ Element not found: ${id}`);
                    }
                });

                // Waste type change listener - show/hide custom input
                const wasteTypeSelect = document.getElementById('wasteType');
                const customWasteTypeField = document.getElementById('customWasteTypeField');
                const customWasteTypeInput = document.getElementById('customWasteType');
                
                if (wasteTypeSelect && customWasteTypeField && customWasteTypeInput) {
                    wasteTypeSelect.addEventListener('change', () => {
                        if (wasteTypeSelect.value === '__custom__') {
                            customWasteTypeField.classList.remove('d-none');
                            customWasteTypeInput.required = true;
                        } else {
                            customWasteTypeField.classList.add('d-none');
                            customWasteTypeInput.required = false;
                            customWasteTypeInput.value = '';
                        }
                    });
                }

                // Form submission
                const form = document.querySelector('form');
                if (form) {
                    console.log('✅ Form found, attaching submit listener');
                    form.addEventListener('submit', (e) => {
                        console.log('📤 Form submitted!');
                        this.handleFormSubmission(e);
                    });
                } else {
                    console.error('❌ Form not found!');
                }
            }

            // Points-only; no toggle needed

            updateHeader(selectedPayment, headerIcon, headerText, orderHeader) {
                headerIcon.className = 'fas fa-plus-circle me-2';
                headerText.textContent = 'Tạo đơn thu gom mới';
                orderHeader.className = 'card-header';
            }
updateRewardEstimate() {
                const quantity = parseFloat(document.getElementById('quantity').value) || 100; // Default 100kg
                const pointsPerKg = parseFloat(document.getElementById('pointsPerKg').value) || 100;
                const totalPoints = quantity * pointsPerKg;
                document.getElementById('points-amount').innerHTML = `
                    <div>${totalPoints.toLocaleString('vi-VN')} điểm</div>
                    <small class="text-muted">Tự giao: hộ nhận ${Math.floor(totalPoints*0.95).toLocaleString('vi-VN')} điểm, hệ thống giữ ${(totalPoints*0.05).toLocaleString('vi-VN')} điểm phí • Nhờ CTV: hộ nhận ${(totalPoints*0.6).toLocaleString('vi-VN')} điểm, CTV nhận ${(totalPoints*0.35).toLocaleString('vi-VN')} điểm, hệ thống giữ ${(totalPoints*0.05).toLocaleString('vi-VN')} điểm phí</small>
                `;
            }

            async handleFormSubmission(event) {
                console.log('📤 handleFormSubmission called');
                event.preventDefault();
                console.log('✅ preventDefault called');
                
                const form = event.target;
                console.log('📋 Form:', form);
                
                // Get waste type - handle custom input
                const wasteTypeSelect = document.getElementById('wasteType');
                let wasteType = wasteTypeSelect.value;
                
                // If custom is selected, use custom input value
                if (wasteType === '__custom__') {
                    const customWasteType = document.getElementById('customWasteType').value.trim();
                    if (!customWasteType) {
                        // Show validation error for custom input
                        const customField = document.getElementById('customWasteType');
                        customField.setCustomValidity('Vui lòng nhập loại rác tùy chỉnh');
                        customField.reportValidity();
                        form.classList.add('was-validated');
                        return;
                    }
                    wasteType = customWasteType;
                }
                
                // Log all field values
                const quantity = document.getElementById('quantity').value;
                const pointsPerKg = document.getElementById('pointsPerKg').value;
                
                console.log('🔍 Field values:');
                console.log('  - wasteType:', wasteType, '(empty?', wasteType === '', ')');
                console.log('  - quantity:', quantity, '(valid?', quantity > 0, ')');
                console.log('  - pointsPerKg:', pointsPerKg, '(valid?', pointsPerKg > 0, ')');
                
                console.log('✔️ Form validity:', form.checkValidity());
                
                if (!form.checkValidity()) {
                    console.log('❌ Form validation FAILED');
                    
                    // Log which fields are invalid
                    const invalidFields = form.querySelectorAll(':invalid');
                    console.log('❌ Invalid fields:', invalidFields.length);
                    invalidFields.forEach(field => {
                        console.log('  -', field.id || field.name, ':', field.validationMessage);
                    });
                    
                    form.classList.add('was-validated');
                    return;
                }

                console.log('✅ Form validation PASSED');

                // Collect form data
                const formData = {
                    wasteType: wasteType,
                    quantity: parseFloat(document.getElementById('quantity').value),
                    pointsPerKg: parseFloat(document.getElementById('pointsPerKg').value),
                    notes: document.getElementById('notes').value
                };

                console.log('📦 Form data:', formData);

                // Disable submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo đơn...';

                try {
                    console.log('🌐 Sending request to API...');
                    
                    // Call API
                    const response = await fetch('index.php?controller=api_business_order&action=create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    console.log('📥 Response status:', response.status);
                    console.log('📥 Response OK:', response.ok);

                    const result = await response.json();
                    console.log('📄 Response data:', result);

                    if (result.success) {
                        console.log('✅ Success! Order:', result.data.order);
                        // Show success message
                        this.showSuccessMessage(result.data.order);
                    } else {
                        console.log('❌ Failed:', result.message);
                        // Show error
                        this.showErrorMessage(result.message || 'Có lỗi xảy ra khi tạo đơn');
                    }
                } catch (error) {
                    console.error('❌ Error creating order:', error);
                    this.showErrorMessage('Không thể kết nối đến server. Vui lòng thử lại.');
                } finally {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }

            showSuccessMessage(order) {
                const orderCode = order.order_code || 'RC' + order.order_id;
                const totalPoints = parseFloat(order.total_points);
                
                const alertHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-check-circle me-2"></i>Tạo đơn thành công!
                        </h5>
                        <p class="mb-2"><strong>Mã đơn:</strong> ${orderCode}</p>
                        <p class="mb-2"><strong>Loại rác:</strong> ${order.waste_type}</p>
                        <p class="mb-2"><strong>Khối lượng:</strong> ${order.quantity} kg</p>
                        <p class="mb-2"><strong>Thưởng:</strong> ${totalPoints.toLocaleString('vi-VN')} điểm</p>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0">Đơn hàng đã được tạo và đang chờ hộ gia đình nhận.</p>
                            <div class="d-flex gap-2">
                                <a href="index.php?controller=doanh_nghiep&action=quanLyDon" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list me-2"></i>Xem danh sách đơn
                                </a>
                                <button type="button" class="btn btn-sm btn-success" onclick="location.reload()">
                                    <i class="fas fa-plus me-2"></i>Tạo đơn mới
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

                // Insert alert at top of form
                const cardBody = document.querySelector('.card-body');
                cardBody.insertAdjacentHTML('afterbegin', alertHTML);

                // Reset form
                document.querySelector('form').reset();
                document.querySelector('form').classList.remove('was-validated');
                
                // Hide custom waste type field
                const customWasteTypeField = document.getElementById('customWasteTypeField');
                const customWasteTypeInput = document.getElementById('customWasteType');
                if (customWasteTypeField) {
                    customWasteTypeField.classList.add('d-none');
                }
                if (customWasteTypeInput) {
                    customWasteTypeInput.required = false;
                }
                
                // Points-only reset
                this.updateHeader('points', document.getElementById('header-icon'), document.getElementById('header-text'), document.getElementById('order-header'));
                this.updateRewardEstimate();

                // Scroll to top
                cardBody.scrollIntoView({ behavior: 'smooth' });
            }

            showErrorMessage(message) {
                const alertHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-circle me-2"></i>Lỗi tạo đơn
                        </h5>
                        <p class="mb-0">${message}</p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

                // Insert alert at top of form
                const cardBody = document.querySelector('.card-body');
                cardBody.insertAdjacentHTML('afterbegin', alertHTML);

                // Scroll to top
                cardBody.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            console.log('📄 DOM Content Loaded');
            console.log('🔍 Creating OrderCreationManager instance...');
            new OrderCreationManager();
        });
    </script>
</body>
</html>