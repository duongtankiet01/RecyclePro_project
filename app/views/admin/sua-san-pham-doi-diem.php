<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật sản phẩm đổi điểm - RecyclePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/_sidebar.php'; ?>

<main class="admin-main">
    <header class="admin-header">
        <div class="header-content">
            <div class="header-title">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>Cập nhật sản phẩm đổi điểm</h1>
                    <p class="header-breadcrumb">
                        <a href="index.php?controller=admin_reward_product&action=index" class="text-decoration-none">
                            Quản lý sản phẩm
                        </a>
                        <span class="text-muted"> / Sửa sản phẩm</span>
                    </p>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php?controller=admin_reward_product&action=index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
            </div>
        </div>
    </header>

    <div class="admin-content">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="index.php?controller=admin_reward_product&action=update">
                    <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên sản phẩm *</label>
                            <input type="text"
                                   name="product_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($product['product_name']) ?>"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Danh mục *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Chọn danh mục</option>
                                <?php
                                $categories = ['Đồ gia dụng', 'Thực phẩm', 'Điện tử', 'Khác'];
                                foreach ($categories as $category): ?>
                                    <option value="<?= $category ?>" <?= $product['category'] === $category ? 'selected' : '' ?>>
                                        <?= $category ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Điểm yêu cầu *</label>
                            <input type="number"
                                   name="required_points"
                                   class="form-control"
                                   min="1"
                                   value="<?= (int)$product['required_points'] ?>"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số lượng tồn *</label>
                            <input type="number"
                                   name="stock_quantity"
                                   class="form-control"
                                   min="0"
                                   value="<?= (int)$product['stock_quantity'] ?>"
                                   required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-9 mb-3">
                            <label class="form-label">URL ảnh</label>
                            <input type="text"
                                   name="image_url"
                                   class="form-control"
                                   placeholder="https://..."
                                   value="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Trạng thái</label>
                            <div class="form-control bg-light">
                                <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= $product['status'] === 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                </span>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Bật/tắt trạng thái tại trang danh sách sản phẩm.
                            </small>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox"
                               class="form-check-input"
                               id="isFeatured"
                               name="is_featured"
                               value="1"
                            <?= (int)$product['is_featured'] === 1 ? 'checked' : '' ?>>
                        <label for="isFeatured" class="form-check-label">Đánh dấu là sản phẩm nổi bật</label>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="index.php?controller=admin_reward_product&action=index" class="btn btn-outline-secondary">
                            Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
</script>
</body>
</html>

