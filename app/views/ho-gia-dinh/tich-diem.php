<?php
/** @var array $currentUser */
/** @var array $summary */
/** @var array $products */
/** @var array $filters */
/** @var array $earningHistory */
/** @var array $exchangeHistory */

$currentUser = $currentUser ?? [
    'full_name' => 'Hộ gia đình',
    'current_points' => 0,
];

$summary = $summary ?? [
    'current_points' => 0,
    'total_points' => 0,
    'redeemed_count' => 0,
];

$filters = $filters ?? [
    'businesses' => [
        ['value' => '', 'label' => 'Tất cả doanh nghiệp'],
    ],
    'categories' => [
        ['value' => '', 'label' => 'Tất cả danh mục'],
    ],
    'point_ranges' => [
        ['value' => '', 'label' => 'Tất cả mức điểm'],
    ],
    'availability' => [
        ['value' => '', 'label' => 'Tất cả'],
    ],
];

$products = $products ?? [];
$earningHistory = $earningHistory ?? [];
$exchangeHistory = $exchangeHistory ?? [];

function format_number($value): string {
    return number_format((float)$value, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tích điểm và đổi quà - Hộ gia đình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <?php $activeTab = 'tich-diem'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4">
        <!-- Points Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-star fa-3x mb-3"></i>
                        <h2 class="fw-bold" id="summaryCurrentPoints"><?= format_number($summary['current_points']) ?></h2>
                        <p class="mb-0">Điểm hiện có</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-history fa-3x mb-3"></i>
                        <h2 class="fw-bold"><?= format_number($summary['total_points']) ?></h2>
                        <p class="mb-0">Tổng điểm tích lũy</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-gift fa-3x mb-3"></i>
                        <h2 class="fw-bold"><?= format_number($summary['redeemed_count']) ?></h2>
                        <p class="mb-0">Sản phẩm đã đổi</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-success" role="alert">
            <div class="d-flex">
                <i class="fas fa-leaf fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-2">Ví điểm xanh hoạt động như thế nào?</h5>
                    <ul class="mb-0 ps-3">
                        <li class="mb-1">Tự giao rác đến doanh nghiệp: bạn nhận <strong>95%</strong> giá trị đơn hàng vào ví điểm, <strong>5%</strong> được trích cho quỹ vận hành.</li>
                        <li class="mb-1">Nhờ CTV thu gom: CTV <strong>ứng trước 100%</strong> số điểm. Khi doanh nghiệp xác nhận, bạn nhận <strong>60%</strong>, CTV nhận <strong>35%</strong>, nền tảng giữ <strong>5%</strong>.</li>
                        <li>Ví điểm của bạn dùng được cho <strong>mọi sản phẩm</strong> từ bất kỳ doanh nghiệp đối tác nào, không bị khóa theo nơi phát sinh đơn hàng.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Points History and Exchange Tabs -->
        <ul class="nav nav-tabs mb-4" id="pointsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                    <i class="fas fa-gift me-2"></i>Sản phẩm đổi điểm
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Lịch sử tích điểm
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="exchanges-tab" data-bs-toggle="tab" data-bs-target="#exchanges" type="button" role="tab">
                    <i class="fas fa-exchange-alt me-2"></i>Lịch sử đổi quà
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pointsTabsContent">
            <!-- Products Tab -->
            <div class="tab-pane fade show active" id="products" role="tabpanel">
                <!-- Filters -->
                <div class="row mb-4 g-3 align-items-end">
                    <div class="col-md-3">
                        <select class="form-select" id="businessFilter">
                            <?php foreach ($filters['businesses'] as $option): ?>
                                <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="categoryFilter">
                            <?php foreach ($filters['categories'] as $option): ?>
                                <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="pointsFilter">
                            <?php foreach ($filters['point_ranges'] as $option): ?>
                                <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="availabilityFilter">
                            <?php foreach ($filters['availability'] as $option): ?>
                                <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchProduct" placeholder="Tìm sản phẩm...">
                            <button class="btn btn-outline-primary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row" id="productsGrid">
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">Chưa có sản phẩm đổi điểm nào.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100" data-business-id="<?= htmlspecialchars($product['business_id'] ?? '') ?>">
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22300%22 height=%22200%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2218%22 text-anchor=%22middle%22 x=%22150%22 y=%22100%22%3ESản phẩm%3C/text%3E%3C/svg%3E') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name'] ?? 'Sản phẩm đổi điểm') ?>">
                                        <?php if (!empty($product['status_badge_label'])): ?>
                                            <div class="position-absolute top-0 start-0 m-2">
                                                <span class="badge <?= htmlspecialchars($product['status_badge_class'] ?? 'bg-success') ?>"><?= htmlspecialchars($product['status_badge_label']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($product['name'] ?? '') ?></h5>
                                        <p class="text-muted small mb-2"><?= htmlspecialchars($product['category_label'] ?? '') ?></p>
                                        <p class="card-text flex-grow-1"><?= htmlspecialchars($product['description'] ?? '') ?></p>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-primary fw-bold">
                                                    <i class="fas fa-star me-1"></i><?= htmlspecialchars($product['points_label'] ?? '') ?>
                                                </span>
                                                <?php if (!empty($product['availability_label'])): ?>
                                                    <span class="<?= htmlspecialchars($product['availability_class'] ?? 'text-success small') ?>">
                                                        <i class="<?= htmlspecialchars($product['availability_icon'] ?? 'fas fa-check-circle') ?> me-1"></i><?= htmlspecialchars($product['availability_label']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($product['progress_percent'])): ?>
                                                <div class="progress mb-2" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (int)$product['progress_percent'] ?>%" aria-valuenow="<?= (int)$product['progress_percent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($product['points_needed_label'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($product['points_needed_label']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($product['can_redeem'])): ?>
                                                <button class="btn btn-primary w-100 mt-2" onclick="exchangeProduct(<?= (int)$product['product_id'] ?>)">
                                                    <i class="fas fa-exchange-alt me-2"></i><?= htmlspecialchars($product['button_label'] ?? 'Đổi ngay') ?>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary w-100 mt-2" disabled>
                                                    <i class="fas fa-lock me-2"></i><?= htmlspecialchars($product['button_label'] ?? 'Không đủ điểm') ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Points History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lịch sử tích điểm</h5>
                        <div class="ms-3" style="min-width: 260px;">
                            <select id="historyBusinessFilter" class="form-select form-select-sm">
                                <?php foreach ($filters['businesses'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($earningHistory)): ?>
                            <div class="alert alert-info text-center mb-0">Chưa có lịch sử tích điểm.</div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($earningHistory as $item): ?>
                                    <div class="timeline-item border-start <?= htmlspecialchars($item['border_class'] ?? 'border-primary') ?> border-3 ps-3 pb-4" data-business-id="<?= htmlspecialchars($item['business_id'] ?? '') ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="fw-bold <?= htmlspecialchars($item['amount_class'] ?? 'text-primary') ?> mb-1"><?= htmlspecialchars($item['amount_label'] ?? '') ?></h6>
                                                <p class="mb-1"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                                                <small class="text-muted"><?= htmlspecialchars($item['timestamp'] ?? '') ?></small>
                                                <?php if (!empty($item['business_badge'])): ?>
                                                    <div class="mt-1"><span class="badge bg-secondary"><?= htmlspecialchars($item['business_badge']) ?></span></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($item['delta_badge_label'])): ?>
                                                <span class="badge <?= htmlspecialchars($item['delta_badge_class'] ?? 'bg-primary') ?>"><?= htmlspecialchars($item['delta_badge_label']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Exchange History Tab -->
            <div class="tab-pane fade" id="exchanges" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lịch sử đổi quà</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($exchangeHistory)): ?>
                            <div class="alert alert-info text-center mb-0">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">Chưa có đơn đổi quà nào. Hãy đổi sản phẩm ngay!</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($exchangeHistory as $exchange): ?>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <!-- Product Image -->
                                            <div class="col-md-2 text-center">
                                                <img src="<?= htmlspecialchars($exchange['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($exchange['product_name']) ?>"
                                                     class="img-fluid rounded"
                                                     style="max-height: 80px; object-fit: cover;"
                                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect fill=%22%23ddd%22 width=%2280%22 height=%2280%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2212%22 text-anchor=%22middle%22 x=%2240%22 y=%2244%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                                            </div>

                                            <!-- Product Info -->
                                            <div class="col-md-4">
                                                <h6 class="mb-1"><?= htmlspecialchars($exchange['product_name']) ?></h6>
                                                <p class="text-muted small mb-1">
                                                    <i class="fas fa-map-marker-alt"></i> Điểm đổi quà - Hệ thống
                                                </p>
                                                <p class="text-muted small mb-0">
                                                    <i class="fas fa-cubes"></i> SL: <?= $exchange['quantity'] ?>
                                                    <span class="ms-2"><i class="fas fa-coins"></i> <?= format_number($exchange['points_spent']) ?> điểm</span>
                                                </p>
                                            </div>

                                            <!-- Redemption Code -->
                                            <div class="col-md-4 col-lg-3">
                                                <p class="text-muted small mb-1"><i class="fas fa-barcode"></i> Mã đổi thưởng:</p>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" 
                                                           class="form-control font-monospace fw-bold" 
                                                           value="<?= htmlspecialchars($exchange['redemption_code']) ?>" 
                                                           id="code-<?= $exchange['redemption_id'] ?>"
                                                           readonly>
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button"
                                                            onclick="copyRedemptionCode('code-<?= $exchange['redemption_id'] ?>')"
                                                            title="Sao chép mã">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Status & Date -->
                                            <div class="col-md-4 col-lg-3">
                                                <span class="badge <?= $exchange['status_class'] ?> mb-2">
                                                    <?= $exchange['status_label'] ?>
                                                </span>
                                                <p class="text-muted small mb-0">
                                                    <i class="fas fa-calendar"></i> <?= $exchange['redeemed_date'] ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/reward-redeem.js"></script>
    <script>
        // Initialize reward redemption
        const products = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const summaryPoints = <?= (float)($summary['current_points'] ?? 0) ?>;
        console.log('Products loaded:', products.length, 'items');
        console.log('Current points:', summaryPoints);
        initRewardRedeem(products, summaryPoints);

        // Copy redemption code function
        function copyRedemptionCode(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Select and copy
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                navigator.clipboard.writeText(input.value).then(() => {
                    // Show success message
                    showToast('success', 'Đã sao chép mã!', 'Mã đổi thưởng đã được sao chép vào clipboard');
                }).catch(err => {
                    // Fallback for older browsers
                    document.execCommand('copy');
                    showToast('success', 'Đã sao chép mã!', 'Mã đổi thưởng đã được sao chép');
                });
            } catch (err) {
                console.error('Copy failed:', err);
                showToast('error', 'Lỗi', 'Không thể sao chép mã');
            }
        }

        // Toast notification helper
        function showToast(type, title, message) {
            const toastHTML = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" 
                     role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong><br>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(container);
            }
            
            container.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = container.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
    </script>
</body>
</html>
