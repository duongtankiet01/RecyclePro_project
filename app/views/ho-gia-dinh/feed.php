<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đơn thu gom - Hộ gia đình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .order-card {
            border: 1px solid rgba(25, 135, 84, 0.16);
            border-radius: 22px;
            padding: 1.4rem;
            background: #fff;
            box-shadow: 0 1rem 2rem rgba(25, 135, 84, 0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .badge-chip {
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .metric-card {
            background: #f7fbf9;
            border-radius: 16px;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(0,0,0,0.03);
            height: 100%;
        }
        .metric-label {
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.72rem;
            color: #6c757d;
        }
        .metric-value {
            font-size: 1.2rem;
            font-weight: 700;
        }
        .progress-badge {
            background: rgba(255,193,7,.12);
            color: #d97706;
            border-radius: 12px;
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
        }
        .order-actions .btn {
            border-radius: 999px;
            padding: 0.6rem 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar for Household -->
    <?php $activeTab = 'feed'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4">
    <h3 class="mb-1">Tìm đơn thu gom gần bạn</h3>
    <p class="text-muted mb-4">Hệ thống chỉ hiển thị những đơn có cùng Tỉnh/Thành và Xã/Phường với địa chỉ chính của bạn.</p>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form data-address-group="feed">
                    <div class="row g-3 align-items-end">
                        <!-- Placeholder for Controller: Use API for address filtering -->
                        <div class="col-md-4">
                            <label for="province" class="form-label">Tỉnh/Thành</label>
                            <select class="form-select" id="province" data-address="province" data-prefill-code="<?= htmlspecialchars($householdAddress['province_code'] ?? '') ?>"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="ward" class="form-label">Xã/Phường</label>
                            <select class="form-select" id="ward" data-address="ward" data-prefill-code="<?= htmlspecialchars($householdAddress['ward_code'] ?? '') ?>"></select>
                        </div>
                        <div class="col-md-2">
                            <label for="wasteType" class="form-label">Loại rác</label>
                            <select class="form-select" id="wasteType">
                                <option selected value="">Tất cả</option>
                                <option>Nhựa PET</option>
                                <option>Giấy carton</option>
                                <option>Kim loại</option>
                                <option>Chai thủy tinh</option>
                                <option>Nhựa tổng hợp</option>
                                <option>Giấy văn phòng</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" id="applyFilters"><i class="fas fa-search me-2"></i>Lọc</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order List -->
        <div id="ordersContainer" class="row g-4" 
             data-household-province="<?= htmlspecialchars($householdAddress['province_code'] ?? '') ?>" 
             data-household-ward="<?= htmlspecialchars($householdAddress['ward_code'] ?? '') ?>"></div>
        <div id="emptyState" class="text-center py-5 d-none">
            <div class="mb-3"><i class="fas fa-map-marker-alt fa-3x text-muted"></i></div>
            <h5>Hiện chưa có đơn nào trong khu vực của bạn.</h5>
            <p class="text-muted mb-0">Hãy thử chọn khu vực khác hoặc quay lại sau.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/household-feed.js"></script>
    <script>
        console.log('🚀 Starting orders feed...');
        
        // Load orders from API
        fetch('index.php?controller=api_business_order&action=available_orders')
            .then(r => r.json())
            .then(data => {
                console.log('📥 API response:', data);
                
                if (data.success) {
                    const orders = data.data.orders;
                    console.log('✅ Loaded', orders.length, 'orders');
                    console.log('📦 Orders:', orders);
                    
                    // Lưu vào window để household-feed.js có thể filter
                    window.HOUSEHOLD_ORDERS = orders;
                    
                    // Dispatch event để household-feed.js biết orders đã load và tự động filter/render
                    window.dispatchEvent(new CustomEvent('ordersLoaded', { detail: { orders } }));
                } else {
                    console.error('❌ API error:', data.message);
                    window.HOUSEHOLD_ORDERS = [];
                }
            })
            .catch(err => {
                console.error('❌ Network error:', err);
                window.HOUSEHOLD_ORDERS = [];
            });
    </script>
</body>
</html>
