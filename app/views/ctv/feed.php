<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng CTV - Cộng tác viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .order-card {
            border: 1px solid rgba(13, 110, 253, 0.15);
            border-radius: 20px;
            padding: 1.35rem;
            background: #fff;
            box-shadow: 0 1rem 2rem rgba(15, 118, 110, 0.08);
            height: 100%;
        }
        .badge-chip {
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .metric-card {
            background: #f8fafb;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .metric-label {
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.72rem;
            color: #6c757d;
            margin-bottom: .15rem;
        }
        .metric-value {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .contact-avatar {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13,110,253,.12);
            color: #0d6efd;
            font-size: 1.1rem;
        }
        .order-actions .btn {
            border-radius: 999px;
            padding: 0.65rem 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar for CTV -->
    <?php $activeTab = 'feed'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4">
        <h3 class="mb-1">Tìm đơn thu gom cần CTV</h3>
        <p class="text-muted mb-4">Những đơn hộ gia đình cần CTV thu gom và ứng điểm.</p>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form data-address-group="feed">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="province" class="form-label">Tỉnh/Thành</label>
                            <select class="form-select" id="province" data-address="province" data-prefill-code="<?= htmlspecialchars($currentUser['address']['province_code'] ?? '') ?>"></select>
                        </div>
                        <div class="col-md-3">
                            <label for="ward" class="form-label">Xã/Phường</label>
                            <select class="form-select" id="ward" data-address="ward" data-prefill-code="<?= htmlspecialchars($currentUser['address']['ward_code'] ?? '') ?>"></select>
                        </div>
                    <div class="col-md-3">
                        <label for="wasteType" class="form-label">Loại rác</label>
                        <select class="form-select" id="wasteType">
                            <option selected value="">Tất cả loại rác</option>
                            <option>Nhựa PET</option>
                            <option>Giấy carton</option>
                            <option>Kim loại</option>
                            <option>Chai thủy tinh</option>
                            <option>Nhựa tổng hợp</option>
                            <option>Giấy văn phòng</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" id="applyFilters"><i class="fas fa-search me-2"></i>Lọc đơn</button>
                    </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order List -->
        <div id="ordersContainer" class="row g-4"></div>
        <div id="emptyState" class="text-center py-5 d-none">
            <div class="mb-3"><i class="fas fa-motorcycle fa-3x text-muted"></i></div>
            <h5>Hiện chưa có đơn nào cần CTV thu gom.</h5>
            <p class="text-muted mb-0">Hãy thử chọn khu vực khác hoặc quay lại sau.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        console.log('🚀 Starting CTV feed...');
        
        // Initial data from PHP (converted to JSON)
        const initialOrders = <?php echo json_encode($orders ?? []); ?>;
        console.log('📦 Initial orders:', initialOrders);
        
        // Render function
        function renderOrders(ordersList) {
            const container = document.getElementById('ordersContainer');
            const emptyState = document.getElementById('emptyState');
            
            console.log('🎨 Rendering', ordersList.length, 'CTV orders');
            
            if (!ordersList || ordersList.length === 0) {
                container.classList.add('d-none');
                emptyState?.classList.remove('d-none');
                return;
            }

            emptyState?.classList.add('d-none');
            container.classList.remove('d-none');

            const html = ordersList.map(order => {
                const quantity = parseFloat(order.quantity || 0).toFixed(1);
                const advancePoints = parseFloat(order.advance_amount || 0);
                const commissionPoints = parseFloat(order.your_commission || 0);
                const commissionCash = commissionPoints * 1000;
                const pointsPerKg = parseFloat(order.points_per_kg || 0);
                return `
                    <div class="col-xl-4 col-lg-6">
                        <div class="order-card d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <span class="badge badge-chip bg-light text-primary">
                                    <i class="fas fa-recycle me-1"></i>${order.waste_type || 'Chưa rõ'}
                                </span>
                                <span class="badge badge-chip bg-light text-dark">
                                    <i class="fas fa-weight-hanging me-1"></i>~${quantity} kg
                                </span>
                            </div>

                            <div class="mb-2">
                                <h5 class="mb-1">Hộ: ${order.household_name || 'Không rõ'}</h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                    ${order.address || 'Chưa cập nhật'}
                                </p>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="metric-card h-100">
                                        <div class="metric-label">Ứng cho hộ</div>
                                        <div class="metric-value text-danger">${advancePoints.toLocaleString('vi-VN')} điểm</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-card h-100">
                                        <div class="metric-label">Hoa hồng</div>
                                        <div class="metric-value text-success">${commissionCash.toLocaleString('vi-VN')} đ</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="metric-card h-100">
                                        <div class="metric-label">Điểm thưởng</div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="metric-value">${(pointsPerKg).toLocaleString('vi-VN')} điểm/kg</span>
                                            <small class="text-muted">Tổng: ${(order.total_points || 0).toLocaleString('vi-VN')} điểm</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="contact-avatar">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">${order.household_phone || '---'}</div>
                                    <small class="text-muted">Liên hệ & xác nhận thời gian</small>
                                </div>
                            </div>

                            <div class="order-actions mt-auto">
                                <a href="index.php?controller=ctv&action=chiTietDon&id=${order.participant_id}"
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-route me-2"></i>Xem & Nhận đơn
                                </a>
                                <div class="text-muted small mt-2">
                                    <i class="far fa-clock me-1"></i>Nhận lúc: ${order.accepted_at || 'Chưa xác định'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
            console.log('✅ Rendered successfully');
        }

        // Initial render
        renderOrders(initialOrders);
    </script>
</body>
</html>
