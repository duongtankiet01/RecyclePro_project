<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn thu gom - Doanh nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php $activeTab = 'quan-ly-don'; require __DIR__ . '/_navbar.php'; ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tasks me-2"></i>Danh sách đơn đã tạo</span>
                <a href="index.php?controller=doanh_nghiep&action=taoDon" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-2"></i>Tạo đơn mới
                </a>
            </div>
            <div class="card-body">
                <div id="orderAlert"></div>
                <!-- Filter tabs -->
                <ul class="nav nav-tabs mb-3" id="orderTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-status="pending">
                            <i class="fas fa-clock me-1"></i>Chờ nhận
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-status="active">
                            <i class="fas fa-spinner me-1"></i>Đang xử lý
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-status="completed">
                            <i class="fas fa-check-circle me-1"></i>Hoàn thành
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-status="cancelled">
                            <i class="fas fa-times-circle me-1"></i>Đã hủy
                        </a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Loại rác</th>
                                <th>Khối lượng</th>
                                <th>Điểm/kg</th>
                                <th>Tổng điểm</th>
                                <th>Hộ gia đình</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <tr>
                                <td colspan="8" class="text-center">
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
                    <a href="index.php?controller=doanh_nghiep&action=taoDon" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tạo đơn mới
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStatus = 'pending';

        // Load orders
        async function loadOrders(status = 'pending') {
            const tbody = document.getElementById('ordersTableBody');
            const emptyState = document.getElementById('emptyState');
            
            tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary"></div></td></tr>';
            emptyState.classList.add('d-none');

            try {
                const url = `index.php?controller=api_business_order&action=my_orders&status=${status}`;
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
                        const createdDate = new Date(order.created_at).toLocaleDateString('vi-VN');
                        
                        // Hiển thị thông tin hộ gia đình
                        let householdDisplay = '<span class="text-muted">Chưa có hộ nhận</span>';
                        if (order.participant_count > 0) {
                            householdDisplay = `
                                <span class="badge bg-success">${order.participant_count} hộ</span><br>
                                <small class="text-muted">${order.household_names || ''}</small>
                            `;
                        }
                        
                        // Hiển thị số lượng
                        const quantityDisplay = `
                            <strong>${order.quantity.toFixed(1)} kg</strong><br>
                            <small class="text-muted">Đã nhận: ${order.quantity_accepted.toFixed(1)} kg</small><br>
                            <small class="text-success">Còn lại: ${order.quantity_remaining.toFixed(1)} kg</small>
                        `;

                        const canModify = order.status === 'pending' && order.participant_count === 0;
                        let actionButtons = '<span class="text-muted">Không khả dụng</span>';
                        if (order.status === 'pending') {
                            const encodedOrder = encodeURIComponent(JSON.stringify(order));
                            actionButtons = `
                                <button class="btn btn-sm btn-outline-primary me-2 btn-edit-order" 
                                        data-order="${encodedOrder}">
                                    <i class="fas fa-edit me-1"></i>Sửa
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-order" 
                                        data-order-id="${order.order_id}" 
                                        data-order-code="${order.order_code}"
                                        data-order="${encodedOrder}">
                                    <i class="fas fa-trash me-1"></i>Xóa
                                </button>
                            `;
                        }

                        return `
                            <tr>
                                <td><strong>${order.order_code}</strong></td>
                                <td>${order.waste_type}</td>
                                <td>${quantityDisplay}</td>
                                <td class="text-primary fw-bold">${order.points_per_kg.toFixed(0)}</td>
                                <td class="text-primary fw-bold">${order.total_points.toLocaleString('vi-VN')}</td>
                                <td>${householdDisplay}</td>
                                <td>${statusBadge}</td>
                                <td>${createdDate}</td>
                                <td>${actionButtons}</td>
                            </tr>
                        `;
                    }).join('');

                    attachOrderActionHandlers();
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>';
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Lỗi kết nối server</td></tr>';
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Chờ hộ nhận</span>',
                'active': '<span class="badge bg-info">Đang xử lý</span>',
                'completed': '<span class="badge bg-success">Hoàn thành</span>',
                'cancelled': '<span class="badge bg-danger">Đã hủy</span>'
            };
            return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
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
            loadOrders('pending'); // Load pending orders by default to match active tab
            const editOrderForm = document.getElementById('editOrderForm');
            if (editOrderForm) {
                editOrderForm.addEventListener('submit', handleEditFormSubmit);
            }
            const deleteConfirmBtn = document.getElementById('confirmDeleteOrderBtn');
            if (deleteConfirmBtn) {
                deleteConfirmBtn.addEventListener('click', handleDeleteConfirm);
            }
            editOrderModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
            deleteOrderModal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
        });

        let editOrderModal;
        let deleteOrderModal;
        let currentOrderData = null;
        let deletingOrderId = null;

        function attachOrderActionHandlers() {
            document.querySelectorAll('.btn-edit-order').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const data = JSON.parse(decodeURIComponent(btn.dataset.order));
                        openEditOrderModal(data);
                    } catch (error) {
                        console.error('Failed to parse order data', error);
                    }
                });
            });

            document.querySelectorAll('.btn-delete-order').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const orderId = parseInt(btn.dataset.orderId, 10);
                        const orderCode = btn.dataset.orderCode;
                        const orderData = btn.dataset.order ? JSON.parse(decodeURIComponent(btn.dataset.order)) : null;
                        openDeleteOrderModal(orderId, orderCode, orderData);
                    } catch (error) {
                        console.error('Failed to parse order data', error);
                    const orderId = parseInt(btn.dataset.orderId, 10);
                    const orderCode = btn.dataset.orderCode;
                        openDeleteOrderModal(orderId, orderCode, null);
                    }
                });
            });
        }

        function openEditOrderModal(order) {
            currentOrderData = order;
            
            document.getElementById('editOrderId').value = order.order_id;
            document.getElementById('editWasteType').value = order.waste_type;
            document.getElementById('editQuantity').value = order.quantity.toFixed(1);
            document.getElementById('editPointsPerKg').value = order.points_per_kg.toFixed(0);
            document.getElementById('editNotes').value = order.notes || '';
            document.getElementById('editOrderCode').textContent = order.order_code;
            
            // Hide warning alert when opening modal
            const warningAlert = document.getElementById('editWarningAlert');
            if (warningAlert) {
                warningAlert.classList.add('d-none');
            }
            
            editOrderModal.show();
        }

        async function handleEditFormSubmit(event) {
            event.preventDefault();
            
            // Check if order has participants
            if (currentOrderData && currentOrderData.participant_count > 0) {
                const warningAlert = document.getElementById('editWarningAlert');
                if (warningAlert) {
                    warningAlert.classList.remove('d-none');
                    warningAlert.innerHTML = `
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Không thể chỉnh sửa:</strong> Đơn này đã có ${currentOrderData.participant_count} hộ gia đình đang nhận. 
                        Vui lòng liên hệ với các hộ để hủy đơn trước khi chỉnh sửa.
                    `;
                }
                return;
            }
            
            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';

            const payload = {
                order_id: parseInt(document.getElementById('editOrderId').value, 10),
                waste_type: document.getElementById('editWasteType').value,
                quantity: parseFloat(document.getElementById('editQuantity').value),
                points_per_kg: parseFloat(document.getElementById('editPointsPerKg').value),
                notes: document.getElementById('editNotes').value.trim()
            };

            try {
                const response = await fetch('index.php?controller=api_business_order&action=update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    showAlert(result.message || 'Cập nhật đơn thành công', 'success');
                    editOrderModal.hide();
                    loadOrders(currentStatus);
                } else {
                    showAlert(result.message || 'Có lỗi xảy ra khi cập nhật đơn', 'danger');
                }
            } catch (error) {
                console.error('Update order error:', error);
                showAlert('Không thể kết nối tới server', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Lưu thay đổi';
            }
        }

        function openDeleteOrderModal(orderId, orderCode, orderData = null) {
            deletingOrderId = orderId;
            currentOrderData = orderData; // Store order data for later check
            document.getElementById('deleteOrderCode').textContent = orderCode;
            document.getElementById('deleteReason').value = '';
            
            // Hide warning alert when opening modal
            const warningAlert = document.getElementById('deleteWarningAlert');
            if (warningAlert) {
                warningAlert.classList.add('d-none');
            }
            
            deleteOrderModal.show();
        }

        async function handleDeleteConfirm() {
            if (!deletingOrderId) return;
            
            // Check if order has participants
            const participantCount = currentOrderData ? (currentOrderData.participant_count || 0) : 0;
            if (participantCount > 0) {
                const warningAlert = document.getElementById('deleteWarningAlert');
                if (warningAlert) {
                    warningAlert.classList.remove('d-none');
                    warningAlert.innerHTML = `
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Không thể xóa:</strong> Đơn này đã có ${participantCount} hộ gia đình đang nhận. 
                        Vui lòng liên hệ với các hộ để hủy đơn trước khi xóa.
                    `;
                }
                return;
            }
            
            const confirmBtn = document.getElementById('confirmDeleteOrderBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xóa...';

            const payload = {
                order_id: deletingOrderId,
                reason: document.getElementById('deleteReason').value.trim()
            };

            try {
                const response = await fetch('index.php?controller=api_business_order&action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    showAlert(result.message || 'Đã xóa đơn thành công', 'success');
                    deleteOrderModal.hide();
                    loadOrders(currentStatus);
                } else {
                    showAlert(result.message || 'Không thể xóa đơn', 'danger');
                }
            } catch (error) {
                console.error('Delete order error:', error);
                showAlert('Không thể kết nối tới server', 'danger');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Xóa đơn';
            }
        }

        function showAlert(message, type = 'success') {
            const placeholder = document.getElementById('orderAlert');
            placeholder.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }
    </script>

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderModalLabel">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa đơn <span id="editOrderCode"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editOrderForm">
                    <div class="modal-body">
                        <input type="hidden" id="editOrderId">
                        <div class="mb-3">
                            <label class="form-label">Loại rác *</label>
                            <select class="form-select" id="editWasteType" required>
                                <option value="Nhựa PET">Nhựa PET</option>
                                <option value="Giấy carton">Giấy carton</option>
                                <option value="Kim loại">Kim loại</option>
                                <option value="Thủy tinh">Thủy tinh</option>
                                <option value="Rác hữu cơ">Rác hữu cơ</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Khối lượng (kg) *</label>
                                <input type="number" class="form-control" id="editQuantity" min="0.1" step="0.1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Điểm / kg *</label>
                                <input type="number" class="form-control" id="editPointsPerKg" min="1" step="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="editNotes" rows="3"></textarea>
                        </div>
                        <div class="alert alert-danger d-none mb-0" id="editWarningAlert" role="alert">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Order Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteOrderModalLabel">
                        <i class="fas fa-trash me-2 text-danger"></i>Xóa đơn <span id="deleteOrderCode"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa đơn này? Hành động này sẽ hoàn credit về tài khoản nếu đơn chưa có hộ nhận.</p>
                    <div class="alert alert-danger d-none mb-3" id="deleteWarningAlert" role="alert">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do (không bắt buộc)</label>
                        <textarea class="form-control" id="deleteReason" rows="3" placeholder="Ví dụ: nhập sai thông tin đơn..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteOrderBtn">
                        <i class="fas fa-trash me-2"></i>Xóa đơn
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
