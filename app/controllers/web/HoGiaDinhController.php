<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/BusinessOrder.php';
require_once __DIR__ . '/../../models/HouseholdProfile.php';
require_once __DIR__ . '/../../models/OrderParticipant.php';
require_once __DIR__ . '/../../models/Withdrawal.php';
require_once __DIR__ . '/../../models/RewardProduct.php';
require_once __DIR__ . '/../../models/RewardRedemption.php';

class HoGiaDinhController extends BaseController
{
    private BusinessOrder $businessOrderModel;
    private HouseholdProfile $householdModel;
    private OrderParticipant $participantModel;
    private Withdrawal $withdrawalModel;
    private RewardProduct $rewardProductModel;
    private RewardRedemption $redemptionModel;
    private PDO $db;

    public function __construct()
    {
        // Tạo database connection
        $config = require __DIR__ . '/../../../config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']}";
        $this->db = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        $this->businessOrderModel = new BusinessOrder();
        $this->householdModel = new HouseholdProfile();
        $this->participantModel = new OrderParticipant();
        $this->withdrawalModel = new Withdrawal();
        $this->rewardProductModel = new RewardProduct($this->db);
        $this->redemptionModel = new RewardRedemption($this->db);
    }

    public function feed(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        // Use BusinessOrder model for available orders
        $availableOrders = $this->businessOrderModel->getAvailableOrders();
        $orders = $this->mapOrdersForFeed($availableOrders);

        $this->render('ho-gia-dinh/feed', [
            'currentUser'      => $this->getCurrentUser(),
            'householdAddress' => $this->getHouseholdAddress(),
            'filterOptions'    => [
                'order_types' => [
                    ['value' => '', 'label' => 'Táº¥t cáº£ loáº¡i Ä‘Æ¡n'],
                    ['value' => 'cash', 'label' => 'ÄÆ¡n nháº­n tiá»n'],
                    ['value' => 'points', 'label' => 'ÄÆ¡n tÃ­ch Ä‘iá»ƒm'],
                ],
                'waste_types' => $this->getWasteTypes(),
            ],
            'orders'           => $orders,
        ]);
    }

    public function tichDiem(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        $currentUser = $this->getCurrentUser();
        
        // Use HouseholdProfile model for current points
        $currentPoints = $this->householdModel->getCurrentPoints($currentUser['id']);
        $currentUser['current_points'] = $currentPoints;

        // Get point transactions history using model
        $pointTransactions = $this->householdModel->getPointTransactions($currentUser['id'], ['limit' => 50]);
        
        // Calculate total earned points
        $totalEarned = 0;
        foreach ($pointTransactions as $transaction) {
            if ((float)$transaction['amount'] > 0) {
                $totalEarned += (float)$transaction['amount'];
            }
        }

        // Format earning history for view
        $earningHistory = $this->formatEarningHistory($pointTransactions);

        // Lấy danh sách sản phẩm đổi điểm
        $products = $this->rewardProductModel->findAllActive();
        $formattedProducts = $this->formatProductsForView($products, $currentPoints);
        
        // Lấy lịch sử đổi thưởng - sử dụng RewardRedemption Model
        $redemptions = $this->redemptionModel->findByHousehold($currentUser['id'], ['limit' => 10]);

        $exchangeHistory = array_map(function($r) {
            // Generate mã đổi thưởng từ redemption_id (format: RD + 8 số)
            $redemptionCode = 'RD' . str_pad((string)$r['redemption_id'], 8, '0', STR_PAD_LEFT);
            
            return [
                'redemption_id' => $r['redemption_id'],
                'redemption_code' => $redemptionCode, // Thêm mã vào array
                'product_name' => $r['product_name'],
                'image_url' => $r['image_url'] ?? 'https://via.placeholder.com/100',
                'business_name' => 'Hệ thống',
                'quantity' => $r['quantity'],
                'points_spent' => $r['points_spent'],
                'status' => $r['status'],
                'status_label' => $this->getRedemptionStatusLabel($r['status']),
                'status_class' => $this->getRedemptionStatusBadge($r['status']),
                'redeemed_date' => date('d/m/Y H:i', strtotime($r['redeemed_at'])),
                'delivered_date' => $r['delivered_at'] ? date('d/m/Y H:i', strtotime($r['delivered_at'])) : null,
            ];
        }, $redemptions);

        $this->render('ho-gia-dinh/tich-diem', [
            'currentUser'     => $currentUser,
            'summary'         => [
                'current_points' => $currentPoints,
                'total_points' => $totalEarned,
                'redeemed_count' => count($redemptions),
            ],
            'filters'         => $this->getProductFilters($products),
            'products'        => $formattedProducts,
            'earningHistory'  => $earningHistory,
            'exchangeHistory' => $exchangeHistory,
        ]);
    }

    private function formatEarningHistory(array $transactions): array
    {
        if (empty($transactions)) {
            return [];
        }
        
        $history = [];
        foreach ($transactions as $transaction) {
            $amount = (float)$transaction['amount'];
            
            // Only show positive transactions (earned points)
            if ($amount > 0) {
                $history[] = [
                    'business_id' => '',
                    'amount_label' => '+' . number_format($amount, 0, ',', '.') . ' điểm',
                    'amount_class' => 'text-success',
                    'description' => $transaction['description'] ?? 'Tích điểm',
                    'timestamp' => date('d/m/Y H:i', strtotime($transaction['created_at'])),
                    'business_badge' => '',
                    'delta_badge_label' => number_format($amount, 0, ',', '.') . ' điểm',
                    'delta_badge_class' => 'bg-success',
                    'border_class' => 'border-success'
                ];
            }
        }
        
        return $history;
    }

    public function dangNhap(): void
    {
        // Redirect to unified auth page
        header('Location: index.php?controller=auth&action=dangNhap');
        exit;
    }

    public function quanLyDon(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        $this->render('ho-gia-dinh/quan-ly-don', [
            'currentUser' => $this->getCurrentUser(),
            'orders'      => $this->getHouseholdOrders(),
        ]);
    }

    public function chiTietDon(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        // Há»— trá»£ cáº£ code vÃ  id
        $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        // Náº¿u cÃ³ id, láº¥y order theo id, náº¿u khÃ´ng thÃ¬ dÃ¹ng code
        if ($orderId > 0) {
            $order = $this->getHouseholdOrderDetailById($orderId);
        } else {
            $order = $this->getHouseholdOrderDetail($code);
        }

        $this->render('ho-gia-dinh/chi-tiet-don', [
            'currentUser' => $this->getCurrentUser(),
            'order'       => $order,
        ]);
    }

    public function giaoRac(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
        $order = $this->getHouseholdOrderDetail($code);

        $this->render('ho-gia-dinh/giao-rac', [
            'currentUser' => $this->getCurrentUser(),
            'order'       => $order,
            'checklist'   => $this->getDeliveryChecklist(),
        ]);
    }

    public function rutTien(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        $currentUser = $this->getCurrentUser();
        
        // Use HouseholdProfile model for current points
        $currentPoints = $this->householdModel->getCurrentPoints($currentUser['id']);
        
        // Tá»· lá»‡ quy Ä‘á»•i: 1 Ä‘iá»ƒm = 1,000 VNÄ
        $conversionRate = $this->withdrawalModel->getConversionRate();
        $currentBalance = $currentPoints * $conversionRate;
        
        // Use Withdrawal model for history
        $withdrawalRequests = $this->withdrawalModel->getByUser($currentUser['id'], 'household', ['limit' => 20]);
        $withdrawalHistory = $this->formatWithdrawalHistory($withdrawalRequests);
        
        $this->render('ho-gia-dinh/rut-tien', [
            'currentUser' => $currentUser,
            'wallet' => [
                'balance' => $currentBalance,
                'points' => $currentPoints,
                'conversion_rate' => $conversionRate
            ],
            'history' => $withdrawalHistory,
        ]);
    }
    
    private function formatWithdrawalHistory(array $requests): array
    {
        if (empty($requests)) {
            return [];
        }
        
        // Map raw status -> localized badge markup
        $statusLabels = [
            'pending' => '<span class="badge bg-warning text-dark">Chờ xử lý</span>',
            'completed' => '<span class="badge bg-success">Thành công</span>',
            'rejected' => '<span class="badge bg-danger">Từ chối</span>',
            'cancelled' => '<span class="badge bg-secondary">Đã hủy</span>'
        ];
        
        return array_map(function($row) use ($statusLabels) {
            return [
                'code' => $row['request_code'],
                'date' => date('d/m/Y H:i', strtotime($row['requested_at'])),
                'points' => $row['points_deducted'] ?? 0,
                'amount' => $row['amount_requested'] ?? 0,
                'status' => $statusLabels[$row['status']] ?? $row['status']
            ];
        }, $requests);
    }

    public function quanLyTaiKhoan(): void
    {
        // ✅ SECURITY: Chỉ hộ gia đình mới được truy cập
        $this->requireRole('household');
        
        $this->render('ho-gia-dinh/quan-ly-tai-khoan', [
            'currentUser'     => $this->getCurrentUser(),
            'addresses'       => $this->getHouseholdAddressOptions(),
            'securitySummary' => $this->getSecuritySummary(),
        ]);
    }

    /**
     * Get badge class for status
     */
    private function getRedemptionStatusBadge(string $status): string
    {
        $badges = [
            'pending' => 'bg-warning text-dark',
            'confirmed' => 'bg-info',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger'
        ];
        return $badges[$status] ?? 'bg-secondary';
    }

    private function mapOrdersForFeed(array $orders): array
    {
        return array_map(function (array $order): array {
            return [
                'id'            => $order['code'] ?? '',
                'type'          => $order['household_type'] ?? 'points',
                'company'       => $order['company_name'] ?? '',
                'wasteType'     => $order['material'] ?? '',
                'pointsPerKg'   => $order['points_per_kg'] ?? null,
                'cashRate'      => $order['cash_rate'] ?? null,
                'quantityKg'    => $order['quantity_kg'] ?? null,
                'remainingKg'   => $order['remaining_kg'] ?? null,
                'deadline'      => $order['deadline'] ?? '',
                'provinceCode'  => $order['province_code'] ?? '',
                'provinceName'  => $order['province_name'] ?? '',
                'wardName'      => $order['ward_name'] ?? '',
                'wardDisplay'   => $order['ward_display'] ?? '',
                'highlight'     => $order['highlight_label'] ?? '',
            ];
        }, $orders);
    }

    protected function getCurrentUser(): array
    {
        // Gọi parent để lấy thông tin cơ bản từ session
        $user = parent::getCurrentUser();
        
        // Nếu user đã đăng nhập, lấy đầy đủ thông tin từ database
        if ($user['id']) {
            try {
                // Sử dụng User model để lấy full profile (bao gồm cả địa chỉ)
                require_once __DIR__ . '/../../models/User.php';
                $userModel = new User();
                $fullProfile = $userModel->getFullProfile($user['id']);
                
                if ($fullProfile) {
                    // Merge full profile với thông tin từ session
                    $user = array_merge($user, $fullProfile);
                }
                
                // Lấy thêm thông tin points từ household model
                $currentPoints = $this->householdModel->getCurrentPoints($user['id']);
                $user['current_points'] = $currentPoints;
            } catch (Exception $e) {
                // Nếu có lỗi, vẫn trả về user từ session
                error_log('Error loading full user profile: ' . $e->getMessage());
                $user['current_points'] = 0;
            }
        }
        
        return $user;
    }

    private function getHouseholdAddress(): array
    {
        // Lấy địa chỉ từ profile của user thay vì session
        $currentUser = $this->getCurrentUser();
        
        return [
            'province_code' => $currentUser['address']['province_code'] ?? '',
            'ward_code'     => $currentUser['address']['ward_code'] ?? '',
        ];
    }

    private function getWasteTypes(): array
    {
        // TODO: Pull list from database.
        return [];
    }

    private function getHouseholdOrders(): array
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser['id']) {
            return [];
        }
        
        // Lấy đơn từ OrderParticipant Model
        $orders = $this->participantModel->getHouseholdOrdersWithBusinessInfo($currentUser['id']);
        
        // Format data cho view
        $statusLabels = [
            'pending' => 'Chờ CTV nhận',
            'accepted' => 'Đã chấp nhận',
            'collected' => 'Đã thu gom',
            'in_transit' => 'CTV đang đến lấy',
            'confirmed' => 'Đã thu gom',
            'cancelled' => 'Đã hủy',
        ];
        
        return array_map(function($order) use ($statusLabels) {
            // Tính điểm từ quantity * points_per_kg
            $pointsFromBusiness = (float)($order['quantity'] ?? 0) * (float)($order['points_per_kg'] ?? 0);
            
            // Tính điểm thực nhận (sau khi trừ phí hệ thống)
            $deliveryMethod = $order['delivery_method'] ?? 'collector';
            if ($deliveryMethod === 'self') {
                // Tự giao: 95%
                $expectedPoints = floor($pointsFromBusiness * 0.95);
            } else {
                // CTV thu gom: 60%
                $expectedPoints = floor($pointsFromBusiness * 0.60);
            }
            
            $reward = number_format($expectedPoints, 0, ',', '.') . ' điểm';
            
            // Nếu có participant_id thì dùng, không thì dùng order_id
            $displayId = $order['participant_id'] ?? $order['order_id'];
            
            return [
                'code' => 'RC' . str_pad((string)$displayId, 6, '0', STR_PAD_LEFT),
                'material' => $order['waste_type'] ?? 'N/A',
                'weight' => number_format((float)$order['quantity'], 1, '.', ',') . ' kg',
                'status' => $statusLabels[$order['status']] ?? $order['status'],
                'reward' => $reward,
                'order_id' => $order['order_id'],
                'participant_id' => $order['participant_id'],
            ];
        }, $orders);
    }

    private function getHouseholdOrderDetailById(int $orderId): array
    {
        // Use BusinessOrder model Ä‘á»ƒ láº¥y thÃ´ng tin Ä‘Æ¡n
        $order = $this->businessOrderModel->find($orderId);
        
        if (!$order) {
            return [
                'code' => 'N/A',
                'title' => 'ÄÆ¡n khÃ´ng tá»“n táº¡i',
                'company_name' => 'N/A',
                'type_label' => 'N/A',
                'points_per_kg' => '0 Ä‘iá»ƒm/kg',
                'max_quantity' => '0 kg',
                'remaining_quantity' => '0 kg',
                'address' => 'N/A',
                'pickup_window' => 'N/A',
                'contact_name' => 'N/A',
                'contact_phone' => 'N/A',
                'notes' => 'ÄÆ¡n hÃ ng khÃ´ng tá»“n táº¡i hoáº·c Ä‘Ã£ bá»‹ xÃ³a',
            ];
        }
        
        // Format Ä‘á»‹a chá»‰ Ä‘áº§y Ä‘á»§
        $fullAddress = '';
        if (!empty($order['address_line'])) {
            $fullAddress = $order['address_line'];
            
            // Láº¥y tÃªn tá»‰nh/phÆ°á»ng tá»« API
            if (!empty($order['province_code'])) {
                try {
                    // Gá»i API Ä'á»ƒ láº¥y thÃ´ng tin tá»‰nh vÃ  wards
                    $apiUrl = "https://provinces.open-api.vn/api/p/" . $order['province_code'] . "?depth=3";
                    $provinceData = @file_get_contents($apiUrl);
                    
                    if ($provinceData) {
                        $province = json_decode($provinceData, true);
                        
                        // ThÃªm tÃªn phÆ°á»ng/xÃ£ náº¿u cÃ³
                        if (!empty($order['ward_code']) && isset($province['districts'])) {
                            foreach ($province['districts'] as $district) {
                                if (isset($district['wards'])) {
                                    foreach ($district['wards'] as $ward) {
                                        if ($ward['code'] == $order['ward_code']) {
                                            $fullAddress .= ', ' . $ward['name'];
                                            $fullAddress .= ', ' . $district['name'];
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // ThÃªm tÃªn tá»‰nh
                        if (isset($province['name'])) {
                            $fullAddress .= ', ' . $province['name'];
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error fetching province data: ' . $e->getMessage());
                    // Fallback náº¿u API lá»—i
                    if (!empty($order['ward_code'])) {
                        $fullAddress .= ', PhÆ°á»ng/XÃ£ ' . $order['ward_code'];
                    }
                }
            }
        } else {
            $fullAddress = 'Chưa cập nhật địa chỉ';
        }
        
        // Get actual numeric values
        $quantityTotal = (float)($order['quantity'] ?? 0);
        $quantityAccepted = (float)($order['quantity_accepted'] ?? 0);
        $quantityRemaining = (float)($order['quantity_remaining'] ?? $quantityTotal);
        $pointsPerKg = (float)($order['points_per_kg'] ?? 0);
        
        return [
            'code' => 'RC' . str_pad((string)$order['order_id'], 6, '0', STR_PAD_LEFT),
            'title' => 'Thu gom ' . $order['waste_type'],
            'company_name' => $order['company_name'] ?? 'N/A',
            'type_label' => 'Đơn tích điểm',
            'points_per_kg' => number_format($pointsPerKg, 0, '.', ',') . ' điểm/kg',
            'max_quantity' => number_format($quantityTotal, 1, '.', ',') . ' kg',
            'quantity_accepted' => number_format($quantityAccepted, 1, '.', ',') . ' kg',
            'remaining_quantity' => number_format($quantityRemaining, 1, '.', ',') . ' kg',
            'remaining_quantity_raw' => $quantityRemaining, // For JS validation
            'address' => $fullAddress,
            'pickup_window' => 'Liên hệ',
            'contact_name' => $order['contact_person'] ?? $order['company_name'] ?? $order['business_name'] ?? 'N/A',
            'contact_phone' => $order['business_phone'] ?? 'N/A',
            'notes' => $order['notes'] ?? 'Không có ghi chú',
        ];
    }

    private function getHouseholdOrderDetail(string $code): array
    {
        // TODO: Replace with actual order lookup from database
        // Should use BusinessOrder model to find order by code
        return [
            'code'              => $code,
            'title'             => 'Đơn hàng không tồn tại',
            'company_name'      => 'N/A',
            'type_label'        => 'N/A',
            'points_per_kg'     => '0 điểm/kg',
            'max_quantity'      => '0 kg',
            'remaining_quantity'=> '0 kg',
            'address'           => 'N/A',
            'pickup_window'     => 'N/A',
            'contact_name'      => 'N/A',
            'contact_phone'     => 'N/A',
            'notes'             => 'Đơn hàng không tồn tại hoặc đã bị xóa',
        ];
    }

    private function getDeliveryChecklist(): array
    {
        return [
            'Đóng gói chắc chắn, tránh rò rỉ nước',
            'Đính kèm mã đơn và thông tin liên hệ',
            'Chụp ảnh tình trạng rác trước khi giao',
            'Mang theo giấy tờ tùy thân để đối chiếu',
            'Kiểm tra lại số lượng và phân loại trước khi lên xe',
        ];
    }

    private function getHouseholdAddressOptions(): array
    {
        return [
            'provinces' => ['HÃ  Ná»™i', 'Há»“ ChÃ­ Minh'],
            'districts' => ['HoÃ n Kiáº¿m', 'Ba ÄÃ¬nh', 'Quáº­n 1'],
            'wards'     => ['PhÆ°á»ng TrÃ ng Tiá»n', 'PhÆ°á»ng Cá»­a ÄÃ´ng', 'PhÆ°á»ng Báº¿n NghÃ©'],
        ];
    }

    private function getSecuritySummary(): array
    {
        // TODO: Replace with actual security data from user sessions and security settings
        return [
            'last_password_change' => 'N/A',
            'two_factor_enabled'   => false,
            'devices'              => [],
        ];
    }

    private function getLoyaltySummary(int $currentPoints): array
    {
        // TODO: Replace with actual loyalty data from RewardRedemption model
        return [
            'current_points'  => $currentPoints,
            'total_points'    => $currentPoints,
            'redeemed_count'  => 0,
        ];
    }

    private function getLoyaltyFilters(): array
    {
        return [
            'businesses' => [
                ['value' => '', 'label' => 'Tất cả doanh nghiệp'],
                ['value' => 'vib', 'label' => 'Ngân hàng VIB'],
                ['value' => 'vietnamairlines', 'label' => 'Vietnam Airlines'],
                ['value' => 'grab', 'label' => 'Grab'],
            ],
            'categories' => [
                ['value' => '', 'label' => 'Tất cả danh mục'],
                ['value' => 'finance', 'label' => 'Ngân hàng & Ví điện tử'],
                ['value' => 'travel', 'label' => 'Du lịch & Di chuyển'],
                ['value' => 'utilities', 'label' => 'Dịch vụ tiện ích'],
            ],
            'point_ranges' => [
                ['value' => '', 'label' => 'Tất cả mức điểm'],
                ['value' => '0-20000', 'label' => 'Dưới 20.000 điểm'],
                ['value' => '20000-50000', 'label' => '20.000 - 50.000 điểm'],
                ['value' => '50000+', 'label' => 'Trên 50.000 điểm'],
            ],
            'availability' => [
                ['value' => '', 'label' => 'Tất cả'],
                ['value' => 'available', 'label' => 'Còn đổi được'],
                ['value' => 'limited', 'label' => 'Sắp hết'],
                ['value' => 'unavailable', 'label' => 'Hết quà'],
            ],
        ];
    }
    /**
     * Format products cho view
     */
    private function formatProductsForView(array $products, float $currentPoints): array
    {
        $categoryLabels = [
            'household' => 'Đồ gia dụng',
            'food' => 'Thực phẩm',
            'personal' => 'Chăm sóc cá nhân',
            'eco' => 'Sản phẩm xanh',
            'other' => 'Khác'
        ];

        return array_map(function($product) use ($categoryLabels, $currentPoints) {
            $requiredPoints = (int)$product['required_points'];
            $canRedeem = $currentPoints >= $requiredPoints;
            
            return [
                'product_id' => $product['product_id'],
                'name' => $product['product_name'],
                'description' => $product['description'] ?? '',
                'enterprise' => 'Hệ thống',
                'business_id' => $product['business_id'],
                'category_label' => $categoryLabels[$product['category']] ?? 'Khác',
                'points_label' => number_format($requiredPoints, 0, ',', '.') . ' điểm',
                'required_points' => $requiredPoints,
                'availability_label' => $product['stock_quantity'] . ' sản phẩm',
                'availability_class' => $product['stock_quantity'] > 10 ? 'text-success small' : 'text-warning small',
                'availability_icon' => 'fas fa-box',
                'image_url' => $product['image_url'] ?? 'https://via.placeholder.com/300x200?text=' . urlencode($product['product_name']),
                'status_badge_label' => $product['is_featured'] ? 'Nổi bật' : '',
                'status_badge_class' => 'bg-warning text-dark',
                'can_redeem' => $canRedeem,
                'button_class' => $canRedeem ? 'btn-primary' : 'btn-secondary disabled',
                'button_label' => $canRedeem ? 'Đổi ngay' : 'Không đủ điểm'
            ];
        }, $products);
    }

    /**
     * Tạo filter options cho products
     */
    private function getProductFilters(array $products): array
    {
        // Giờ chỉ có admin quản lý, không cần filter theo doanh nghiệp
        return [
            'businesses' => [], // Không cần filter business nữa
            'categories' => [
                ['value' => '', 'label' => 'Tất cả danh mục'],
                ['value' => 'Đồ gia dụng', 'label' => 'Đồ gia dụng'],
                ['value' => 'Thực phẩm', 'label' => 'Thực phẩm'],
                ['value' => 'Điện tử', 'label' => 'Điện tử'],
                ['value' => 'Khác', 'label' => 'Khác']
            ]
        ];
    }

    /**
     * Chi tiết đơn đổi thưởng với QR code
     */
    public function chiTietDoiThuong(): void
    {
        $this->requireRole('household');
        // Trang chi tiết đã được gộp về mục lịch sử đổi quà
        header('Location: index.php?controller=ho_gia_dinh&action=tichDiem');
        exit;
    }

    /**
     * Lấy label cho trạng thái đơn
     */
    private function getRedemptionStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy'
        ];

        return $labels[$status] ?? 'Không xác định';
    }

    /**
     * Lấy class cho badge trạng thái
     */
    private function getRedemptionStatusClass(string $status): string
    {
        $classes = [
            'pending' => 'badge bg-warning',
            'confirmed' => 'badge bg-info',
            'delivered' => 'badge bg-success',
            'cancelled' => 'badge bg-danger'
        ];

        return $classes[$status] ?? 'badge bg-secondary';
    }
}
