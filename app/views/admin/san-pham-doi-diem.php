<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm đổi điểm - RecyclePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-content">
                <div class="header-title">
                    <button class="btn btn-link d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1>Quản lý sản phẩm đổi điểm</h1>
                        <p class="header-breadcrumb">Sản phẩm thưởng cho hộ gia đình</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <a href="index.php?controller=admin_reward_product&action=redemptions" class="btn btn-success me-2">
                        <i class="fas fa-list"></i> Đơn đổi thưởng
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="admin-content">
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-2x mb-2"></i>
                        <h3><?= number_format($stats['total_products']) ?></h3>
                        <p class="mb-0">Tổng sản phẩm</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?= number_format($stats['active_products']) ?></h3>
                        <p class="mb-0">Đang hoạt động</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-warehouse fa-2x mb-2"></i>
                        <h3><?= number_format($stats['total_stock']) ?></h3>
                        <p class="mb-0">Tồn kho</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                        <h3><?= number_format($stats['total_redeemed']) ?></h3>
                        <p class="mb-0">Đã đổi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-3">
                    <input type="hidden" name="controller" value="admin_reward_product">
                    <input type="hidden" name="action" value="index">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Tìm sản phẩm..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Tất cả danh mục</option>
                            <option value="Đồ gia dụng" <?= $filters['category'] === 'Đồ gia dụng' ? 'selected' : '' ?>>Đồ gia dụng</option>
                            <option value="Thực phẩm" <?= $filters['category'] === 'Thực phẩm' ? 'selected' : '' ?>>Thực phẩm</option>
                            <option value="Điện tử" <?= $filters['category'] === 'Điện tử' ? 'selected' : '' ?>>Điện tử</option>
                            <option value="Khác" <?= $filters['category'] === 'Khác' ? 'selected' : '' ?>>Khác</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tìm kiếm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Điểm</th>
                                <th>Kho</th>
                                <th>Đã đổi</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Chưa có sản phẩm nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/50') ?>" 
                                                 alt="<?= htmlspecialchars($p['product_name']) ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover;" 
                                                 class="rounded">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['product_name']) ?></strong>
                                            <?php if ($p['is_featured']): ?>
                                                <span class="badge bg-warning text-dark ms-1">Nổi bật</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(mb_substr($p['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td><?= htmlspecialchars($p['category']) ?></td>
                                        <td><strong><?= number_format($p['required_points']) ?></strong> điểm</td>
                                        <td>
                                            <span class="badge bg-<?= $p['stock_quantity'] > 0 ? 'success' : 'danger' ?>">
                                                <?= number_format($p['stock_quantity']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($p['total_redeemed']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= $p['status'] === 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editProduct(<?= $p['product_id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                    onclick="openDeleteProductModal(<?= $p['product_id'] ?>, '<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

    <!-- Delete Product Confirm Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductLabel">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Xóa sản phẩm
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc muốn xóa sản phẩm <strong id="deleteProductName">này</strong>?<br>
                    Hành động này không thể hoàn tác.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteProductBtn">
                        <i class="fas fa-trash me-1"></i>Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="index.php?controller=admin_reward_product&action=store">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm sản phẩm đổi điểm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên sản phẩm *</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Danh mục *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Chọn danh mục</option>
                                    <option value="Đồ gia dụng">Đồ gia dụng</option>
                                    <option value="Thực phẩm">Thực phẩm</option>
                                    <option value="Điện tử">Điện tử</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Điểm yêu cầu *</label>
                                <input type="number" name="required_points" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số lượng *</label>
                                <input type="number" name="stock_quantity" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL ảnh</label>
                                <input type="text" name="image_url" class="form-control" placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                            <label class="form-check-label" for="isFeatured">Đánh dấu là sản phẩm nổi bật</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(id) {
            window.location.href = 'index.php?controller=admin_reward_product&action=edit&id=' + id;
        }

        let deleteModal;
        let selectedProductId = null;

        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.getElementById('deleteProductModal');
            if (modalElement) {
                deleteModal = new bootstrap.Modal(modalElement);
            }

            const confirmBtn = document.getElementById('confirmDeleteProductBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    if (!selectedProductId) return;
                    submitDeleteForm(selectedProductId);
                });
            }
        });

        function openDeleteProductModal(id, name) {
            selectedProductId = id;
            document.getElementById('deleteProductName').textContent = name || 'này';
            deleteModal?.show();
        }

        function submitDeleteForm(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?controller=admin_reward_product&action=delete';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_id';
            input.value = id;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }

        <?php if (isset($_SESSION['success_message'])): ?>
            alert('<?= $_SESSION['success_message'] ?>');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            alert('<?= $_SESSION['error_message'] ?>');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</main>
</body>
</html>
