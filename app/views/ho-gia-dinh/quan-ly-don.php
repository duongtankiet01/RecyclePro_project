<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đã nhận - Hộ gia đình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar for Household -->
    <?php $activeTab = 'quan-ly-don'; require __DIR__ . '/_navbar.php'; ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-history me-2"></i>Lịch sử đơn hàng thu gom</div>
            <div class="card-body">
                <!-- Filter tabs -->
                <ul class="nav nav-tabs mb-3" id="orderTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-status="all">Tất cả</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-status="accepted_by_household">Đã nhận</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-status="delivered">Đã giao</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-status="confirmed">Hoàn thành</a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Doanh nghiệp</th>
                                <th>Địa chỉ giao hàng</th>
                                <th>Loại rác</th>
                                <th>Khối lượng</th>
                                <th>Trạng thái</th>
                                <th>Phần thưởng</th>
                                <th>Ngày nhận</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div id="emptyState" class="text-center py-5 d-none">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có đơn hàng nào</p>
                    <a href="index.php?controller=ho_gia_dinh&action=feed" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Tìm đơn thu gom
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStatus = 'all';

        // Load orders
        async function loadOrders(status = 'all') {
            const tbody = document.getElementById('ordersTableBody');
            const emptyState = document.getElementById('emptyState');
            
            tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary"></div></td></tr>';
            emptyState.classList.add('d-none');

            try {
                const url = `index.php?controller=api_business_order&action=household_orders&status=${status}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success && result.data.orders) {
                    const orders = result.data.orders;
                    
                    if (orders.length === 0) {
                        tbody.innerHTML = '';
                        emptyState.classList.remove('d-none');
                        return;
                    }

                    tbody.innerHTML = orders.map(order => {
                        const statusBadge = getStatusBadge(order.status);
                        const acceptedDate = order.accepted_at 
                            ? new Date(order.accepted_at).toLocaleDateString('vi-VN')
                            : '-';
                        
                        // Hiện nút Hủy nếu chưa giao hàng (pending hoặc accepted)
                        // pending: CTV chưa nhận | accepted: tự giao đang trên đường
                        const canCancel = order.status === 'pending' || order.status === 'accepted';
                        const actionButtons = canCancel 
                            ? `<button class="btn btn-danger btn-sm" onclick="cancelOrder(${order.participant_id}, '${order.order_code}', ${order.quantity})">
                                    <i class="fas fa-times"></i> Hủy đơn
                               </button>`
                            : `<span class="text-muted small">-</span>`;

                        // Tạo link Google Maps cho địa chỉ doanh nghiệp
                        const businessAddress = order.business_address || 'Chưa cập nhật';
                        const googleMapsUrl = businessAddress !== 'Chưa cập nhật' 
                            ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(businessAddress + ', Vietnam')}`
                            : '#';
                        
                        const addressCell = businessAddress !== 'Chưa cập nhật'
                            ? `<a href="${googleMapsUrl}" target="_blank" class="text-decoration-none text-success" title="Xem trên Google Maps">
                                   <i class="fas fa-map-marker-alt me-1"></i>
                                   <small>${businessAddress}</small>
                               </a>`
                            : `<small class="text-muted">
                                   <i class="fas fa-map-marker-alt me-1"></i>${businessAddress}
                               </small>`;
                        
                        return `
                            <tr>
                                <td><strong>${order.order_code}</strong></td>
                                <td>${order.business_name || 'N/A'}</td>
                                <td>${addressCell}</td>
                                <td>${order.waste_type}</td>
                                <td>${order.quantity.toFixed(1)} kg</td>
                                <td>${statusBadge}</td>
                                <td>
                                    <span class="text-primary fw-bold">
                                        <i class="fas fa-star me-1"></i>${order.total_points.toLocaleString('vi-VN')} điểm
                                    </span>
                                </td>
                                <td>${acceptedDate}</td>
                                <td>${actionButtons}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>';
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Lỗi kết nối server</td></tr>';
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-info">Chờ CTV nhận</span>',
                'accepted': '<span class="badge bg-warning">Đang giao</span>',
                'accepted_by_ctv': '<span class="badge bg-warning">CTV đang lấy</span>',
                'in_transit': '<span class="badge bg-warning">Đang chờ CTV giao</span>',
                'delivered': '<span class="badge bg-primary">Đã giao</span>',
                'confirmed': '<span class="badge bg-success">Hoàn thành</span>',
                'cancelled': '<span class="badge bg-danger">Đã hủy</span>'
            };
            return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
        }

        // Hủy đơn
        async function cancelOrder(participantId, orderCode, quantity) {
            if (!confirm(`Bạn có chắc muốn HỦY đơn ${orderCode}?\n\nSố lượng: ${quantity} kg\n\nSau khi hủy, đơn sẽ quay lại feed cho hộ khác nhận.`)) {
                return;
            }

            try {
                const response = await fetch('index.php?controller=api_business_order&action=cancel_participation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        participant_id: participantId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Đã hủy đơn ${orderCode} thành công!\n\nSố lượng ${result.data.cancelled_quantity} kg đã được trả lại.`);
                    // Reload danh sách
                    loadOrders(currentStatus);
                } else {
                    alert('❌ Lỗi: ' + (result.message || 'Không thể hủy đơn'));
                }
            } catch (error) {
                console.error('Cancel order error:', error);
                alert('❌ Lỗi kết nối: ' + error.message);
            }
        }

        // Tab switching
        document.querySelectorAll('#orderTabs .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active tab
                document.querySelectorAll('#orderTabs .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Load orders with filter
                currentStatus = this.dataset.status;
                loadOrders(currentStatus);
            });
        });

        // Load on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadOrders('all');
        });
    </script>
</body>
</html>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function viewOrderDetails(orderId) {
            // TODO: Show order details modal or navigate to details page
            console.log('Viewing details for order:', orderId);
            alert('Chức năng xem chi tiết đơn hàng đang được phát triển');
        }

        // TODO: API Integration Points for Controller
        /*
        1. Orders Management APIs
           - GET /api/household/orders - Get user's order history
           - PUT /api/household/orders/{id}/cancel - Cancel pending order
           - POST /api/household/orders/{id}/confirm-delivery - Confirm delivery

        2. Rewards APIs
           - GET /api/household/rewards/summary - Get total points and credits earned
           - GET /api/household/points/balance - Get current points balance

        3. Order Details APIs
           - GET /api/household/orders/{id} - Get detailed order information
        */
    </script>
</body>
</html>
