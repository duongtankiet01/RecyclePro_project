<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/CollectorProfile.php';
require_once __DIR__ . '/../../models/OrderParticipant.php';
require_once __DIR__ . '/../../models/Withdrawal.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/UserAddress.php';

class CtvController extends BaseController
{
    private Order $orderModel;
    private CollectorProfile $collectorProfileModel;
    private OrderParticipant $participantModel;
    private Withdrawal $withdrawalModel;
    private User $userModel;
    private UserAddress $addressModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->collectorProfileModel = new CollectorProfile();
        $this->participantModel = new OrderParticipant();
        $this->withdrawalModel = new Withdrawal();
        
        // Khởi tạo User model để load full profile
        $db = Database::getInstance();
        $this->userModel = new User($db);
        $this->addressModel = new UserAddress();
    }

    /**
     * Override getCurrentUser để load đầy đủ thông tin bao gồm address từ database
     */
    protected function getCurrentUser(): array
    {
        if (!isset($_SESSION['user_id'])) {
            return [
                'id' => null,
                'full_name' => 'Khách',
                'email' => '',
                'role' => 'guest',
            ];
        }

        $userId = (int)$_SESSION['user_id'];
        
        // Sử dụng User::getFullProfile() để lấy đầy đủ thông tin bao gồm address
        $fullProfile = $this->userModel->getFullProfile($userId);
        
        if ($fullProfile) {
            return $fullProfile;
        }

        // Fallback về session data nếu không load được
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'full_name' => $_SESSION['user_name'] ?? 'Người dùng',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'guest',
        ];
    }

    public function feed(): void
    {
        // ✅ SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        // Use OrderParticipant model Ä'á»ƒ láº¥y Ä'Æ¡n available cho collectors
        $results = $this->participantModel->getAvailableForCollectors(['limit' => 50]);
        
        $orders = array_map(function($row) {
            $totalPoints = (float)$row['quantity'] * (float)$row['points_per_kg'];
            
            // Logic đúng: CTV ứng 60% cho hộ, sau đó nhận 95% từ DN
            $advanceAmount = $totalPoints * 0.60; // CTV ứng 60% cho hộ
            $ctvReceiveFromDN = $totalPoints * 0.95; // CTV nhận 95% từ DN
            $ctvProfit = $ctvReceiveFromDN - $advanceAmount; // CTV lời ròng = 95% - 60% = 35%
            
            return [
                'participant_id' => (int)$row['participant_id'],
                'household_name' => $row['household_name'] ?? 'N/A',
                'household_phone' => $row['household_phone'] ?? 'N/A',
                'waste_type' => $row['waste_type'],
                'quantity' => (float)$row['quantity'],
                'points_per_kg' => (float)$row['points_per_kg'],
                'total_points' => $totalPoints,
                'advance_amount' => $advanceAmount,
                'your_commission' => $ctvProfit,
                'address' => $row['address_line'] ?? 'Chưa có địa chỉ',
                'accepted_at' => isset($row['accepted_at']) ? date('d/m/Y H:i', strtotime($row['accepted_at'])) : 'N/A',
            ];
        }, $results);

        $this->render('ctv/feed', [
            'currentUser' => $this->getCurrentUser(),
            'orders'      => $orders,
        ]);
    }

    public function dangNhap(): void
    {
        // Redirect to unified auth page
        header('Location: index.php?controller=auth&action=dangNhap');
        exit;
    }

    public function quanLyDon(): void
    {
        // ✅ SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        $currentUser = $this->getCurrentUser();
        $collectorId = (int)$currentUser['id'];
        
        // Use OrderParticipant model Ä'á»ƒ láº¥y Ä'Æ¡n cá»§a collector
        $results = $this->participantModel->findByCollector($collectorId, ['limit' => 50]);
        
        $orders = array_map(function($row) {
            $totalPoints = (float)$row['quantity'] * (float)$row['points_per_kg'];
            
            // Logic đúng: CTV lời ròng = 95% - 60% = 35%
            $ctvProfit = ($totalPoints * 0.95) - ($totalPoints * 0.60);
            $ctvCommission = $ctvProfit;
            
            // XÃ¡c Ä‘á»‹nh tráº¡ng thÃ¡i vÃ  action
            $statusLabel = '';
            $statusClass = '';
            $canConfirmPickup = false;
            
            $status = strtolower(trim($row['status']));
            
            switch ($status) {
                case 'pending':
                    $statusLabel = 'Chưa nhận';
                    $statusClass = 'badge bg-secondary';
                    break;
                case 'accepted':
                    $statusLabel = 'Chờ thu gom';
                    $statusClass = 'badge bg-warning text-dark';
                    $canConfirmPickup = true;
                    break;
                case 'in_transit':
                    $statusLabel = 'Đang vận chuyển';
                    $statusClass = 'badge bg-info';
                    break;
                case 'delivered':
                    $statusLabel = 'Chờ DN xác nhận';
                    $statusClass = 'badge bg-primary';
                    break;
                case 'confirmed':
                    $statusLabel = 'Hoàn thành';
                    $statusClass = 'badge bg-success';
                    break;
                case 'cancelled':
                    $statusLabel = 'Đã hủy';
                    $statusClass = 'badge bg-danger';
                    break;
                default:
                    $statusLabel = ucfirst($status) . ' (?)';
                    $statusClass = 'badge bg-secondary';
            }
            
            // Ghép địa chỉ đầy đủ bằng cách tra cứu province và ward
            $fullAddress = $row['address_line'] ?? 'Chưa có địa chỉ';
            $provinceCode = $row['province_code'] ?? '';
            $wardCode = $row['ward_code'] ?? '';
            
            // Địa chỉ doanh nghiệp
            $businessAddress = $row['business_address_line'] ?? 'Chưa có địa chỉ';
            $businessProvinceCode = $row['business_province_code'] ?? '';
            $businessWardCode = $row['business_ward_code'] ?? '';
            
            return [
                'participant_id' => (int)$row['participant_id'],
                'household_name' => $row['household_name'],
                'household_phone' => $row['household_phone'],
                'waste_type' => $row['waste_type'],
                'quantity' => (float)$row['quantity'],
                'address' => $fullAddress,
                'province_code' => $provinceCode,
                'ward_code' => $wardCode,
                'business_name' => $row['company_name'] ?? $row['business_name'] ?? 'N/A',
                'business_address' => $businessAddress,
                'business_province_code' => $businessProvinceCode,
                'business_ward_code' => $businessWardCode,
                'status' => $row['status'],
                'status_raw' => $row['status'], // For debug
                'status_label' => $statusLabel ?: 'N/A',
                'status_class' => $statusClass ?: 'badge bg-secondary',
                'advance_given' => (float)$row['advance_given'],
                'your_commission' => $ctvCommission,
                'can_confirm_pickup' => $canConfirmPickup,
                'accepted_at' => $row['accepted_at'],
            ];
        }, $results);

        $this->render('ctv/quan-ly-don', [
            'currentUser' => $currentUser,
            'orders' => $orders,
        ]);
    }

    public function chiTietDon(): void
    {
        //  SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        $participantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($participantId <= 0) {
            http_response_code(404);
            echo 'Đơn hàng không tồn tại.';
            return;
        }
        
        // Use OrderParticipant model Ä‘á»ƒ láº¥y thÃ´ng tin Ä‘Æ¡n
        $row = $this->participantModel->find($participantId);
        
        if (!$row) {
            http_response_code(404);
            echo 'Đơn hàng không tồn tại.';
            return;
        }
        
        // Tính toán điểm
        $totalPoints = (float)$row['quantity'] * (float)$row['points_per_kg'];
        
        // Logic đúng: CTV ứng 60% cho hộ, sau đó nhận 95% từ DN
        $advanceAmount = $totalPoints * 0.60; // CTV ứng 60% cho hộ
        $ctvReceiveFromDN = $totalPoints * 0.95; // CTV nhận 95% từ DN
        $ctvCommission = $ctvReceiveFromDN - $advanceAmount; // CTV lời ròng = 35%
        
        // Kiá»ƒm tra Ä'Æ¡n Ä'Ã£ Ä'Æ°á»£c nháº­n chÆ°a
        $isAvailable = ($row['status'] === 'pending' && $row['collector_id'] === null);
        
        // Format địa chỉ hộ gia đình đầy đủ
        $householdAddress = 'Chưa có địa chỉ';
        if (!empty($row['address_line'])) {
            $householdAddress = $row['address_line'];
            
            // Lấy thông tin tỉnh và wards
            if (!empty($row['province_code'])) {
                try {
                    $apiUrl = "https://provinces.open-api.vn/api/p/" . $row['province_code'] . "?depth=3";
                    $provinceData = @file_get_contents($apiUrl);
                    
                    if ($provinceData) {
                        $province = json_decode($provinceData, true);
                        
                        // Tìm ward/district nếu có ward_code
                        if (!empty($row['ward_code']) && isset($province['districts'])) {
                            foreach ($province['districts'] as $district) {
                                if (isset($district['wards'])) {
                                    foreach ($district['wards'] as $ward) {
                                        if ($ward['code'] == $row['ward_code']) {
                                            $householdAddress .= ', ' . $ward['name'];
                                            $householdAddress .= ', ' . $district['name'];
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Thêm tên tỉnh
                        if (isset($province['name'])) {
                            $householdAddress .= ', ' . $province['name'];
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error fetching province data: ' . $e->getMessage());
                }
            }
        }
        
        // Format địa chỉ doanh nghiệp đầy đủ
        $businessAddress = 'Chưa có địa chỉ';
        if (!empty($row['business_address'])) {
            $businessAddress = $row['business_address'];
            
            // Lấy thông tin tỉnh và wards cho doanh nghiệp
            if (!empty($row['business_province_code'])) {
                try {
                    $apiUrl = "https://provinces.open-api.vn/api/p/" . $row['business_province_code'] . "?depth=3";
                    $provinceData = @file_get_contents($apiUrl);
                    
                    if ($provinceData) {
                        $province = json_decode($provinceData, true);
                        
                        // Tìm ward/district nếu có ward_code
                        if (!empty($row['business_ward_code']) && isset($province['districts'])) {
                            foreach ($province['districts'] as $district) {
                                if (isset($district['wards'])) {
                                    foreach ($district['wards'] as $ward) {
                                        if ($ward['code'] == $row['business_ward_code']) {
                                            $businessAddress .= ', ' . $ward['name'];
                                            $businessAddress .= ', ' . $district['name'];
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Thêm tên tỉnh
                        if (isset($province['name'])) {
                            $businessAddress .= ', ' . $province['name'];
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error fetching business province data: ' . $e->getMessage());
                }
            }
        }
        
        $this->render('ctv/chi-tiet-don', [
            'currentUser' => $this->getCurrentUser(),
            'order' => [
                'participant_id'    => (int)$row['participant_id'],
                'waste_type'        => $row['waste_type'],
                'household_name'    => $row['household_name'],
                'household_phone'   => $row['household_phone'],
                'address'           => $householdAddress,
                'quantity'          => (float)$row['quantity'],
                'points_per_kg'     => (float)$row['points_per_kg'],
                'total_points'      => $totalPoints,
                'advance_amount'    => $advanceAmount,
                'your_commission'   => $ctvCommission,
                'status'            => $row['status'],
                'is_available'      => $isAvailable,
                'accepted_at'       => $row['accepted_at'],
                'business_name'     => $row['company_name'] ?? $row['business_name'] ?? 'Chưa có tên',
                'business_phone'    => $row['business_phone'] ?? 'Chưa có số điện thoại',
                'business_email'    => $row['business_email'] ?? null,
                'contact_person'    => $row['contact_person'] ?? null,
                'business_address'  => $businessAddress,
                'order_notes'       => $row['order_notes'] ?? null
            ],
        ]);
    }

    public function thuGom(): void
    {
        // ✅ SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $order = $id ? $this->orderModel->find($id) : null;
        if ($order === null) {
            http_response_code(404);
            echo 'Đơn hàng không tồn tại.';
            return;
        }

        $this->render('ctv/thu-gom', [
            'currentUser' => $this->getCurrentUser(),
            'order'       => [
                'id'                  => $order['id'],
                'code'                => $order['code'] ?? '',
                'can_mark_received'   => !empty($order['can_confirm_pickup']),
                'can_mark_delivered'  => !empty($order['can_confirm_delivery']),
            ],
        ]);
    }

    public function viDiem(): void
    {
        // ✅ SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        $this->render('ctv/vi-diem', [
            'currentUser' => $this->getCurrentUser(),
            'wallet'      => $this->getWallet(),
            'history'     => $this->getWalletHistory(),
        ]);
    }

    public function napDiem(): void
    {
        // ✅ SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        $currentUser = $this->getCurrentUser();
        
        // Use CollectorProfile model Ä'á»ƒ láº¥y Ä'iá»ƒm hiá»‡n táº¡i
        $currentPoints = $this->collectorProfileModel->getCurrentPoints($currentUser['id']);
        
        $this->render('ctv/nap-diem', [
            'currentUser'    => $currentUser,
            'currentPoints'  => $currentPoints,
        ]);
    }

    public function quanLyTaiKhoan(): void
    {
        // ✅ SECURITY: Chỉ CTV mới được truy cập
        $this->requireRole('collector');
        
        $this->render('ctv/quan-ly-tai-khoan', [
            'currentUser'     => $this->getCurrentUser(),
            'wallet'          => $this->getWallet(),
            'locationOptions' => $this->getLocationOptions(),
        ]);
    }

    /**
     * Get wallet info
     * REFACTORED: Sá»­ dá»¥ng CollectorProfile Model
     */
    private function getWallet(): array
    {
        $user = $this->getCurrentUser();
        if (!$user['id']) {
            return [
                'available_points' => 0,
                'pending_points'   => 0,
                'total_earned'     => 0,
                'total_used'       => 0,
                'status_badge'     => 'ChÆ°a kÃ­ch hoáº¡t',
            ];
        }

        try {
            // Sá»­ dá»¥ng CollectorProfile Model
            $wallet = $this->collectorProfileModel->getWallet((int)$user['id']);
            
            return [
                'available_points' => $wallet['available_points'],
                'pending_points'   => $wallet['pending_points'],
                'total_earned'     => $wallet['total_earned'],
                'total_used'       => 0, // TODO: Calculate from transactions
                'total_orders'     => 0, // TODO: Get from profile
                'status_badge'     => 'Hoáº¡t Ä‘á»™ng',
            ];
            
        } catch (Exception $e) {
            error_log("Error loading wallet: " . $e->getMessage());
            return [
                'available_points' => 0,
                'pending_points'   => 0,
                'total_earned'     => 0,
                'total_used'       => 0,
                'status_badge'     => 'Lá»—i',
            ];
        }
    }

    /**
     * Get wallet history
     * REFACTORED: Sá»­ dá»¥ng CollectorProfile Model
     */
    private function getWalletHistory(): array
    {
        $user = $this->getCurrentUser();
        if (!$user['id']) {
            return [];
        }

        try {
            // Sá»­ dá»¥ng CollectorProfile Model
            $transactions = $this->collectorProfileModel->getPointTransactions(
                (int)$user['id'], 
                ['limit' => 50]
            );
            
            $history = [];
            foreach ($transactions as $row) {
                $amount = (float)$row['amount'];
                $balanceAfter = (float)$row['balance_after'];
                
                $history[] = [
                    'id' => $row['transaction_id'],
                    'type' => $row['transaction_type'],
                    'amount' => $amount,
                    'balance_after' => $balanceAfter,
                    'description' => $row['description'],
                    'created_at' => $row['created_at'],
                    'amount_formatted' => ($amount > 0 ? '+' : '') . number_format($amount, 0, ',', '.') . ' điểm',
                    'amount_class' => $amount > 0 ? 'text-success' : 'text-danger',
                ];
            }
            
            return $history;
            
        } catch (Exception $e) {
            error_log("Error loading wallet history: " . $e->getMessage());
            return [];
        }
    }

    private function getLocationOptions(): array
    {
        // TODO: Load provinces/districts/wards from database or service.
        return [
            'provinces' => [],
            'districts' => [],
            'wards'     => [],
        ];
    }
}

