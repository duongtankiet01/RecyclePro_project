<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/BusinessOrder.php';
require_once __DIR__ . '/../../models/OrderParticipant.php';
require_once __DIR__ . '/../../models/User.php';

class DoanhNghiepController extends BaseController
{
    private BusinessOrder $businessOrderModel;
    private OrderParticipant $participantModel;
    private User $userModel;
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
        $this->participantModel = new OrderParticipant();
        $this->userModel = new User();
    }

    public function index(): void
    {
        // Redirect to quan ly don by default
        header('Location: index.php?controller=doanh_nghiep&action=quanLyDon');
        exit;
    }

    public function dangNhap(): void
    {
        // Redirect to unified auth page
        header('Location: index.php?controller=auth&action=dangNhap');
        exit;
    }

    public function quanLyDon(): void
    {
        // ✅ SECURITY: Chỉ doanh nghiệp mới được truy cập
        $this->requireRole('business');
        
        $this->render('doanh-nghiep/quan-ly-don', [
            'orders'   => $this->getBusinessOrders(),
            'filters'  => $this->getOrderFilters(),
            'account'  => $this->getAccountProfile(),
        ]);
    }

    public function taoDon(): void
    {
        // ✅ SECURITY: Chỉ doanh nghiệp mới được truy cập
        $this->requireRole('business');
        
        $this->render('doanh-nghiep/tao-don', [
            'materials' => $this->getMaterialOptions(),
            'provinces' => $this->getProvinceOptions(),
            'account'   => $this->getAccountProfile(),
        ]);
    }

    public function quanLyDoiDiem(): void
    {
        $this->requireRole('business');
        header('Location: index.php?controller=doanh_nghiep&action=quanLyTaiKhoan');
        exit;
    }

    public function xacNhanGiaoRac(): void
    {
        // ✅ SECURITY: Chỉ doanh nghiệp mới được truy cập
        $this->requireRole('business');
        
        $this->render('doanh-nghiep/xac-nhan-giao-rac', [
            'pendingDeliveries' => $this->getPendingDeliveries(),
            'account'           => $this->getAccountProfile(),
        ]);
    }

    public function quanLyTaiKhoan(): void
    {
        // ✅ SECURITY: Chỉ doanh nghiệp mới được truy cập
        $this->requireRole('business');
        
        $this->render('doanh-nghiep/quan-ly-tai-khoan', [
            'account' => $this->getAccountProfile(),
        ]);
    }

    public function napCredit(): void
    {
        // ✅ SECURITY: Chỉ doanh nghiệp mới được truy cập
        $this->requireRole('business');
        
        $this->render('doanh-nghiep/nap-credit', [
            'creditPackages' => $this->getCreditPackages(),
            'account'        => $this->getAccountProfile(),
        ]);
    }

    private function getBusinessOrders(): array
    {
        $user = parent::getCurrentUser();
        
        if (!$user['id']) {
            return [];
        }
        
        // Use BusinessOrder model để lấy đơn thực
        try {
            $orders = $this->businessOrderModel->findByBusiness($user['id'], ['limit' => 50]);
            
            $statusLabels = [
                'open' => 'Mới',
                'active' => 'Đang thu gom',
                'completed' => 'Hoàn tất',
                'cancelled' => 'Đã hủy',
            ];
            
            return array_map(function($order) use ($statusLabels) {
                $participantCount = (int)($order['participant_count'] ?? 0);
                return [
                    'code' => 'DN-ORD-' . str_pad((string)$order['order_id'], 6, '0', STR_PAD_LEFT),
                    'material' => $order['waste_type'],
                    'weight' => number_format((float)$order['quantity'], 1, '.', ',') . ' kg',
                    'collector' => $participantCount > 0 ? $participantCount . ' CTV' : 'Chưa có',
                    'status' => $statusLabels[$order['status']] ?? $order['status'],
                    'order_id' => $order['order_id'],
                ];
            }, $orders);
        } catch (Exception $e) {
            error_log('Error loading business orders: ' . $e->getMessage());
            return [];
        }
    }

    private function getOrderFilters(): array
    {
        return [
            'statuses' => ['Tất cả', 'Mới', 'Đang thu gom', 'Chờ xác nhận', 'Hoàn tất'],
            'materials'=> ['Tất cả', 'Nhựa PET', 'Giấy carton', 'Kim loại', 'Thủy tinh'],
        ];
    }

    private function getMaterialOptions(): array
    {
        return ['Nhựa PET', 'Giấy', 'Kim loại', 'Thủy tinh', 'Rác hữu cơ'];
    }

    private function getProvinceOptions(): array
    {
        // TODO: Replace with actual province data from ApiAddressController
        return [];
    }

    private function getPendingDeliveries(): array
    {
        $user = parent::getCurrentUser();
        if (!$user['id']) {
            return [];
        }

        try {
            // Use OrderParticipant model để lấy pending deliveries
            $deliveries = $this->participantModel->getPendingDeliveriesForBusiness($user['id']);
            
            return array_map(function($delivery) {
                return [
                    'participant_id' => $delivery['participant_id'],
                    'order_code' => 'ORD-' . $delivery['participant_id'],
                    'household_name' => $delivery['household_name'] ?? 'N/A',
                    'household_phone' => $delivery['household_phone'] ?? '',
                    'household_address' => $delivery['household_address'] ?? '',
                    'collector_name' => $delivery['collector_name'] ?? 'Tự giao',
                    'collector_phone' => $delivery['collector_phone'] ?? '',
                    'waste_type' => $delivery['waste_type'],
                    'expected_quantity' => (float)$delivery['expected_quantity'],
                    'points_per_kg' => (float)$delivery['points_per_kg'],
                    'delivery_method' => $delivery['delivery_method'],
                    'advance_given' => (float)$delivery['advance_given'],
                    'status' => $delivery['status'],
                    'status_label' => $delivery['status'] === 'in_transit' ? 'Đang giao đến' : 'Chờ giao',
                    'collected_at' => $delivery['collected_at'] ?? null,
                ];
            }, $deliveries);
            
        } catch (Exception $e) {
            error_log("Error loading pending deliveries: " . $e->getMessage());
            return [];
        }
    }

    private function getAccountProfile(): array
    {
        // Gọi parent để lấy thông tin cơ bản từ session
        $user = parent::getCurrentUser();
        
        // Nếu user đã đăng nhập, lấy thêm thông tin business từ User model
        if ($user['id']) {
            try {
                $fullProfile = $this->userModel->getFullProfile($user['id']);
                
                if ($fullProfile && $fullProfile['role'] === 'business') {
                    return [
                        'id' => $fullProfile['id'],
                        'company_name' => $fullProfile['company_name'] ?? '',
                        'email' => $fullProfile['email'],
                        'phone' => $fullProfile['phone_number'] ?? '',
                        'tax_code' => $fullProfile['tax_code'] ?? '',
                        'business_license' => $fullProfile['business_license'] ?? '',
                        'contact_person' => $fullProfile['contact_person'] ?? '',
                        'current_credit' => (int)($fullProfile['current_credit'] ?? 0),
                        'address' => $fullProfile['address']['address_line'] ?? '',
                        'province_code' => $fullProfile['address']['province_code'] ?? '',
                        'ward_code' => $fullProfile['address']['ward_code'] ?? '',
                    ];
                }
            } catch (Exception $e) {
                error_log('Error loading business profile: ' . $e->getMessage());
            }
        }
        
        return [
            'id' => $user['id'] ?? null,
            'company_name' => $user['full_name'] ?? 'Doanh nghiệp',
            'email' => $user['email'] ?? '',
            'phone' => '',
            'address' => '',
            'tax_code' => '',
            'current_credit' => 0,
        ];
    }

    private function getCreditPackages(): array
    {
        return [
            ['credits' => 10000, 'price' => 990000],
            ['credits' => 25000, 'price' => 2290000],
            ['credits' => 50000, 'price' => 4390000],
        ];
    }

    /**
     * Trang quét QR để xác nhận giao hàng
     */
}
