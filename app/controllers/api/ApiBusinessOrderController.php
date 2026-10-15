<?php
declare(strict_types=1);

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../../database/Database.php';
require_once __DIR__ . '/../../models/BusinessOrder.php';
require_once __DIR__ . '/../../models/OrderParticipant.php';
require_once __DIR__ . '/../../models/UserAddress.php';

class ApiBusinessOrderController extends BaseController
{
    private PDO $db;
    private BusinessOrder $businessOrderModel;
    private OrderParticipant $participantModel;
    private UserAddress $userAddressModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
        $this->businessOrderModel = new BusinessOrder();
        $this->participantModel = new OrderParticipant();
        $this->userAddressModel = new UserAddress();
    }

    /**
     * API tạo đơn thu gom rác
     * POST /index.php?controller=api_business_order&action=create
     * 
     * REFACTORED: Sử dụng BusinessOrder Model
     */
    public function create(): void
    {
        try {
            $businessId = $this->requireAuthAndRole('business', 'Chỉ doanh nghiệp mới được tạo đơn');
            if ($businessId === null) {
                return;
            }
            $data = $this->getJsonInput();

            // Validate input
            $wasteType = trim($data['wasteType'] ?? '');
            $quantity = (float)($data['quantity'] ?? 0);
            $pointsPerKg = (float)($data['pointsPerKg'] ?? 0);
            $notes = trim($data['notes'] ?? '');

            if (empty($wasteType)) {
                $this->jsonError('Vui lòng chọn loại rác', 422);
                return;
            }

            if ($quantity <= 0) {
                $this->jsonError('Khối lượng phải lớn hơn 0', 422);
                return;
            }

            if ($pointsPerKg <= 0) {
                $this->jsonError('Điểm tích lũy phải lớn hơn 0', 422);
                return;
            }

            // Sử dụng BusinessOrder Model để tạo đơn
            $orderId = $this->businessOrderModel->create($businessId, [
                'waste_type' => $wasteType,
                'quantity' => $quantity,
                'points_per_kg' => $pointsPerKg,
                'notes' => $notes ?: null
            ]);

            // Lấy thông tin đơn vừa tạo
            $order = $this->businessOrderModel->find($orderId);

            $this->jsonSuccess('Tạo đơn thành công', [
                'order' => [
                    'order_id' => (int)$order['order_id'],
                    'order_code' => $this->formatOrderCode((int)$order['order_id']),
                    'waste_type' => $order['waste_type'],
                    'quantity' => (float)$order['quantity'],
                    'points_per_kg' => (float)$order['points_per_kg'],
                    'total_points' => (float)$order['total_points'],
                    'status' => $order['status'],
                    'notes' => $order['notes'],
                    'created_at' => $order['created_at']
                ]
            ]);

        } catch (Exception $e) {
            error_log('Create order error: ' . $e->getMessage());
            $statusCode = $this->getStatusCodeFromException($e);
            $this->jsonError($e->getMessage(), $statusCode);
        }
    }

    /**
     * API cập nhật đơn thu gom
     * POST /index.php?controller=api_business_order&action=update
     */
    public function update(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Method not allowed', 405);
                return;
            }

            $businessId = $this->requireAuthAndRole('business', 'Chỉ doanh nghiệp mới được chỉnh sửa đơn');
            if ($businessId === null) {
                return;
            }

            $data = $this->getJsonInput();
            $orderId = (int)($data['order_id'] ?? 0);
            $wasteType = trim($data['waste_type'] ?? '');
            $quantity = (float)($data['quantity'] ?? 0);
            $pointsPerKg = (float)($data['points_per_kg'] ?? 0);
            $notes = $data['notes'] ?? null;

            if ($orderId <= 0) {
                $this->jsonError('Order ID không hợp lệ', 422);
                return;
            }

            if ($wasteType === '' || $quantity <= 0 || $pointsPerKg <= 0) {
                $this->jsonError('Vui lòng nhập đầy đủ thông tin hợp lệ', 422);
                return;
            }

            $updatedOrder = $this->businessOrderModel->updateForBusiness($orderId, $businessId, [
                'waste_type' => $wasteType,
                'quantity' => $quantity,
                'points_per_kg' => $pointsPerKg,
                'notes' => $notes
            ]);

            $this->jsonSuccess('Cập nhật đơn thành công', [
                'order' => [
                    'order_id' => (int)$updatedOrder['order_id'],
                    'order_code' => $this->formatOrderCode((int)$updatedOrder['order_id']),
                    'waste_type' => $updatedOrder['waste_type'],
                    'quantity' => (float)$updatedOrder['quantity'],
                    'points_per_kg' => (float)$updatedOrder['points_per_kg'],
                    'total_points' => (float)$updatedOrder['total_points'],
                    'notes' => $updatedOrder['notes'],
                    'status' => $updatedOrder['status']
                ]
            ]);

        } catch (Exception $e) {
            error_log('Update order error: ' . $e->getMessage());
            $statusCode = $this->getStatusCodeFromException($e);
            $this->jsonError($e->getMessage(), $statusCode);
        }
    }

    /**
     * API xóa/hủy đơn thu gom
     * POST /index.php?controller=api_business_order&action=delete
     */
    public function delete(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Method not allowed', 405);
                return;
            }

            $businessId = $this->requireAuthAndRole('business', 'Chỉ doanh nghiệp mới được xóa đơn');
            if ($businessId === null) {
                return;
            }

            $input = $this->getJsonInput();
            // Trường hợp submit form thường
            if (empty($input) && !empty($_POST)) {
                $input = $_POST;
            }

            $orderId = (int)($input['order_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');

            if ($orderId <= 0) {
                $this->jsonError('Order ID không hợp lệ', 422);
                return;
            }

            $this->businessOrderModel->cancelByBusiness($orderId, $businessId, $reason);

            $this->jsonSuccess('Đã xóa đơn thành công');

        } catch (Exception $e) {
            error_log('Delete order error: ' . $e->getMessage());
            $statusCode = $this->getStatusCodeFromException($e);
            $this->jsonError($e->getMessage(), $statusCode);
        }
    }

    /**
     * API lấy danh sách đơn hàng của doanh nghiệp
     * GET /index.php?controller=api_business_order&action=my_orders
     * 
     * REFACTORED: Sử dụng BusinessOrder Model
     */
    public function my_orders(): void
    {
        try {
            $businessId = $this->requireAuthAndRole('business', 'Chỉ doanh nghiệp mới được xem đơn');
            if ($businessId === null) {
                return;
            }

            // Lấy filter từ query params
            $filters = [
                'status' => $_GET['status'] ?? 'all',
                'page' => max(1, (int)($_GET['page'] ?? 1)),
                'limit' => min(50, max(1, (int)($_GET['limit'] ?? 20)))
            ];

            // Sử dụng BusinessOrder Model
            $orders = $this->businessOrderModel->findByBusiness($businessId, $filters);

            // Format response
            $formattedOrders = array_map(function($row) {
                return [
                    'order_id' => (int)$row['order_id'],
                    'order_code' => $this->formatOrderCode((int)$row['order_id']),
                    'waste_type' => $row['waste_type'],
                    'quantity' => (float)$row['quantity'],
                    'quantity_accepted' => (float)($row['quantity_accepted'] ?? 0),
                    'quantity_remaining' => (float)($row['quantity_remaining'] ?? $row['quantity']),
                    'points_per_kg' => (float)$row['points_per_kg'],
                    'total_points' => (float)$row['total_points'],
                    'delivery_method' => $row['delivery_method'],
                    'status' => $row['status'],
                    'status_text' => $this->getStatusText($row['status']),
                    'notes' => $row['notes'],
                    'participant_count' => (int)($row['participant_count'] ?? 0),
                    'household_names' => $row['household_names'] ?? null,
                    'household_name' => $row['household_names'] ?? null, // Backward compatibility
                    'collector_name' => null,
                    'created_at' => $row['created_at'],
                    'household_accepted_at' => $row['household_accepted_at'],
                    'ctv_accepted_at' => $row['ctv_accepted_at'],
                    'delivered_at' => $row['delivered_at'],
                    'confirmed_at' => $row['confirmed_at']
                ];
            }, $orders);

            // Count total
            $totalFilters = ['status' => $filters['status']];
            $allOrders = $this->businessOrderModel->findByBusiness($businessId, $totalFilters);
            $total = count($allOrders);

            $this->jsonSuccess('Lấy danh sách đơn thành công', [
                'orders' => $formattedOrders,
                'pagination' => [
                    'page' => $filters['page'],
                    'limit' => $filters['limit'],
                    'total' => $total,
                    'total_pages' => (int)ceil($total / $filters['limit'])
                ]
            ]);

        } catch (Exception $e) {
            error_log('Get orders error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * API lấy danh sách đơn available cho hộ gia đình
     * GET /index.php?controller=api_business_order&action=available_orders
     */
    public function available_orders(): void
    {
        try {
            // Lấy địa chỉ của hộ gia đình đang đăng nhập (nếu có)
            $householdProvinceCode = null;
            $householdWardCode = null;

            if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'household') {
                $householdAddr = $this->userAddressModel->findPrimaryByUser((int)$_SESSION['user_id']);

                if ($householdAddr) {
                    $householdProvinceCode = $householdAddr['province_code'];
                    $householdWardCode = $householdAddr['ward_code'];
                }
            }            // Lấy các đơn còn số lượng (quantity_remaining > 0)
            $orders = $this->businessOrderModel->getAvailableOrders();

            // Format orders và thêm thông tin khoảng cách
            $formattedOrders = array_map(function($order) use ($householdProvinceCode, $householdWardCode) {
                // Tính mức độ ưu tiên theo địa chỉ
                $priorityScore = 0;
                
                if ($householdProvinceCode && $householdWardCode) {
                    $orderProvince = $order['province_code'];
                    $orderWard = $order['ward_code'];
                    
                    // Cùng ward (phường/xã): ưu tiên cao nhất
                    if ($orderWard == $householdWardCode && $orderProvince == $householdProvinceCode) {
                        $priorityScore = 3;
                    }
                    // Cùng province (tỉnh/thành): ưu tiên trung bình
                    elseif ($orderProvince == $householdProvinceCode) {
                        $priorityScore = 2;
                    }
                    // Khác tỉnh: ưu tiên thấp
                    else {
                        $priorityScore = 1;
                    }
                }
                
                return [
                    'orderId' => (int)$order['order_id'],
                    'orderCode' => $this->formatOrderCode((int)$order['order_id']),
                    'type' => 'points',
                    'wasteType' => $order['waste_type'],
                    'company' => $order['company_name'] ?: $order['business_name'],
                    'quantityKg' => (float)$order['quantity'],
                    'quantityAccepted' => (float)$order['quantity_accepted'],
                    'remainingKg' => (float)$order['quantity_remaining'],
                    'pointsPerKg' => (float)$order['points_per_kg'],
                    'totalPoints' => (float)$order['total_points'],
                    'provinceCode' => $order['province_code'] ?? '79',
                    'districtCode' => '760', // TODO: Thêm district_code vào bảng user_addresses
                    'wardCode' => $order['ward_code'] ?? '26734',
                    'wardName' => $order['ward_code'] ? 'Ward ' . $order['ward_code'] : 'Chưa cập nhật',
                    'wardDisplay' => $order['business_address'] ?? ($order['address_line'] ?? 'TP. Hồ Chí Minh'),
                    'status' => $order['status'],
                    'statusText' => $this->getStatusText($order['status']),
                    'notes' => $order['notes'],
                    'createdAt' => $order['created_at'],
                    'deadline' => date('Y-m-d', strtotime('+7 days', strtotime($order['created_at']))),
                    'priorityScore' => $priorityScore // Thêm để sort
                ];
            }, $orders);

            // Sắp xếp theo priorityScore giảm dần (gần nhất trước), sau đó theo created_at mới nhất
            usort($formattedOrders, function($a, $b) {
                // Ưu tiên theo địa chỉ trước
                if ($a['priorityScore'] !== $b['priorityScore']) {
                    return $b['priorityScore'] - $a['priorityScore']; // Giảm dần
                }
                // Nếu cùng mức ưu tiên, sắp xếp theo thời gian tạo (mới nhất trước)
                return strtotime($b['createdAt']) - strtotime($a['createdAt']);
            });

            $this->jsonSuccess('Lấy danh sách đơn thành công', [
                'orders' => $formattedOrders,
                'total' => count($formattedOrders)
            ]);

        } catch (PDOException $e) {
            error_log('Get available orders error: ' . $e->getMessage());
            $this->jsonError('Lỗi database: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API hộ gia đình nhận đơn
     * POST /index.php?controller=api_business_order&action=accept_order
     * 
     * REFACTORED: Sử dụng OrderParticipant Model
     */
    public function accept_order(): void
    {
        try {
            $householdId = $this->requireAuthAndRole('household', 'Chỉ hộ gia đình mới được nhận đơn');
            if ($householdId === null) {
                return;
            }
            $data = $this->getJsonInput();

            // Validate input
            $orderId = (int)($data['orderId'] ?? 0);
            $quantity = (float)($data['quantity'] ?? 0);
            $deliveryType = trim($data['deliveryType'] ?? 'self');

            if ($orderId <= 0) {
                $this->jsonError('Order ID không hợp lệ', 422);
                return;
            }

            if ($quantity <= 0) {
                $this->jsonError('Khối lượng phải lớn hơn 0', 422);
                return;
            }

            if (!in_array($deliveryType, ['self', 'ctv'])) {
                $this->jsonError('Hình thức giao không hợp lệ', 422);
                return;
            }

            // Sử dụng OrderParticipant Model
            $participantId = $this->participantModel->acceptByHousehold(
                $orderId,
                $householdId,
                $quantity,
                $deliveryType
            );

            // Lấy thông tin còn lại
            $order = $this->businessOrderModel->find($orderId);
            $remaining = (float)$order['quantity_remaining'];

            $this->jsonSuccess('Nhận đơn thành công', [
                'participant_id' => $participantId,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'remaining' => max(0, $remaining),
                'is_full' => $remaining <= 0
            ]);

        } catch (Exception $e) {
            error_log('Accept order error: ' . $e->getMessage());
            $statusCode = $this->getStatusCodeFromException($e);
            $this->jsonError($e->getMessage(), $statusCode);
        }
    }

    /**
     * API lấy danh sách đơn của hộ gia đình
     * GET /index.php?controller=api_business_order&action=household_orders
     */
    public function household_orders(): void
    {
        try {
            $householdId = $this->requireAuthAndRole('household', 'Chỉ hộ gia đình mới được xem đơn');
            if ($householdId === null) {
                return;
            }
            
            // Lấy filter
            $status = $_GET['status'] ?? 'all';
            
            // Build filters for model
            $filters = ['exclude_cancelled' => true];
            
            if ($status !== 'all') {
                // Map status từ frontend sang participant status
                if ($status === 'accepted_by_household') {
                    $filters['status'] = 'pending';
                } else {
                    $filters['status'] = $status;
                }
            }

            // Lấy orders từ model
            $participants = $this->participantModel->findByHousehold($householdId, $filters);

            $orders = [];
            foreach ($participants as $row) {
                // Get business order details
                $businessOrder = $this->businessOrderModel->find((int)$row['order_id']);
                if (!$businessOrder) {
                    continue;
                }
                // Tính điểm từ doanh nghiệp (trước phí hệ thống)
                $pointsFromBusiness = (float)$row['quantity'] * (float)$businessOrder['points_per_kg'];
                
                // Tính điểm thực nhận (sau khi trừ phí hệ thống)
                $deliveryMethod = $row['delivery_method'] ?? 'self';
                if ($deliveryMethod === 'self') {
                    // Tự giao: 95%
                    $householdPoints = floor($pointsFromBusiness * 0.95);
                } else {
                    // CTV thu gom: 60%
                    $householdPoints = floor($pointsFromBusiness * 0.60);
                }
                
                $orders[] = [
                    'order_id' => (int)$businessOrder['order_id'],
                    'participant_id' => (int)$row['participant_id'],
                    'order_code' => $this->formatOrderCode((int)$businessOrder['order_id']),
                    'waste_type' => $businessOrder['waste_type'],
                    'total_quantity' => (float)$businessOrder['quantity'],
                    'quantity' => (float)$row['quantity'],
                    'points_per_kg' => (float)$businessOrder['points_per_kg'],
                    'points_from_business' => $pointsFromBusiness,
                    'total_points' => $householdPoints,
                    'delivery_method' => $row['delivery_method'],
                    'status' => $row['status'],
                    'status_text' => $this->getParticipantStatusText($row['status']),
                    'order_status' => $businessOrder['status'],
                    'notes' => $businessOrder['notes'],
                    'business_name' => $row['company_name'] ?: $row['business_name'],
                    'business_address' => $row['business_address'] ?? 'Chưa cập nhật',
                    'created_at' => $businessOrder['created_at'],
                    'accepted_at' => $row['accepted_at'],
                    'delivered_at' => $row['delivered_at'],
                    'cancelled_at' => $row['cancelled_at']
                ];
            }

            $this->jsonSuccess('Lấy danh sách đơn thành công', [
                'orders' => $orders,
                'total' => count($orders)
            ]);

        } catch (PDOException $e) {
            error_log('Get household orders error: ' . $e->getMessage());
            $this->jsonError('Lỗi database: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API hủy đơn nhận (cho hộ gia đình)
     * POST /index.php?controller=api_business_order&action=cancel_participation
     * Body: {participant_id}
     * 
     * REFACTORED: Sử dụng OrderParticipant Model
     */
    public function cancel_participation(): void
    {
        try {
            $householdId = $this->requireAuthAndRole('household', 'Chỉ hộ gia đình mới được hủy đơn');
            if ($householdId === null) {
                return;
            }

            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            $participantId = (int)($data['participant_id'] ?? 0);

            if ($participantId <= 0) {
                $this->jsonError('Participant ID không hợp lệ', 400);
                return;
            }

            // Sử dụng OrderParticipant Model
            $this->participantModel->cancelByHousehold($participantId, $householdId);

            // Lấy thông tin participant để trả về
            $participant = $this->participantModel->find($participantId);
            $order = $this->businessOrderModel->find((int)$participant['order_id']);

            $this->jsonSuccess('Hủy đơn thành công', [
                'participant_id' => $participantId,
                'order_id' => (int)$participant['order_id'],
                'cancelled_quantity' => (float)$participant['quantity'],
                'remaining_after_cancel' => (float)$order['quantity_remaining']
            ]);

        } catch (Exception $e) {
            error_log('Cancel participation error: ' . $e->getMessage());
            $statusCode = $this->getStatusCodeFromException($e);
            $this->jsonError($e->getMessage(), $statusCode);
        }
    }

    /**
     * API lấy danh sách đơn chờ xác nhận giao rác (cho doanh nghiệp)
     * GET /index.php?controller=api_business_order&action=pending_deliveries
     * 
     * REFACTORED: Sử dụng BusinessOrder Model
     */
    public function pending_deliveries(): void
    {
        try {
            $businessId = $this->requireAuthAndRole('business', 'Chỉ doanh nghiệp mới được xem');
            if ($businessId === null) {
                return;
            }

            // Sử dụng BusinessOrder Model
            $deliveriesData = $this->businessOrderModel->getPendingDeliveries($businessId);

            // Format response
            $deliveries = array_map(function($row) {
                return [
                    'participant_id' => (int)$row['participant_id'],
                    'order_id' => (int)$row['order_id'],
                    'order_code' => $this->formatOrderCode((int)$row['order_id']),
                    'household_id' => (int)$row['household_id'],
                    'household_name' => $row['household_name'],
                    'household_phone' => $row['household_phone'],
                    'collector_id' => $row['collector_id'] ? (int)$row['collector_id'] : null,
                    'collector_name' => $row['collector_name'] ?? null,
                    'collector_phone' => $row['collector_phone'] ?? null,
                    'waste_type' => $row['waste_type'],
                    'expected_quantity' => (float)$row['expected_quantity'],
                    'points_per_kg' => (float)$row['points_per_kg'],
                    'delivery_method' => $row['delivery_method'],
                    'status' => $row['participant_status'],
                    'advance_given' => (float)($row['advance_given'] ?? 0),
                    'accepted_at' => $row['accepted_at'],
                    'collected_at' => $row['collected_at']
                ];
            }, $deliveriesData);

            $this->jsonSuccess('Lấy danh sách thành công', [
                'deliveries' => $deliveries,
                'total' => count($deliveries)
            ]);

        } catch (Exception $e) {
            error_log('Get pending deliveries error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * API xác nhận giao rác và cộng điểm cho hộ gia đình
     * POST /index.php?controller=api_business_order&action=confirm_delivery
     * Body: {participant_id, actual_quantity}
     * 
     * REFACTORED: Sử dụng OrderParticipant Model
     */
    public function confirm_delivery(): void
    {
        try {
            $businessId = $this->requireAuthAndRole('business', 'Chỉ doanh nghiệp mới được xác nhận');
            if ($businessId === null) {
                return;
            }

            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            $participantId = (int)($data['participant_id'] ?? 0);
            $actualQuantity = (float)($data['actual_quantity'] ?? 0);

            // Validate
            if ($participantId <= 0) {
                $this->jsonError('Participant ID không hợp lệ', 400);
                return;
            }

            if ($actualQuantity <= 0) {
                $this->jsonError('Số lượng thực tế phải lớn hơn 0', 400);
                return;
            }

            // Sử dụng OrderParticipant Model
            $result = $this->participantModel->confirmDelivery(
                $participantId,
                $businessId,
                $actualQuantity
            );

            $this->jsonSuccess('Xác nhận giao rác thành công', $result);

        } catch (Exception $e) {
            error_log('Confirm delivery error: ' . $e->getMessage());
            $statusCode = $this->getStatusCodeFromException($e);
            $this->jsonError($e->getMessage(), $statusCode);
        }
    }

    // ==================== PROTECTED HELPERS ====================

    /**
     * Kiểm tra đăng nhập và role
     * @param string $requiredRole Role yêu cầu ('business', 'household', etc.)
     * @param string $errorMessage Message lỗi nếu không đúng role
     * @return int|null User ID nếu hợp lệ, null nếu không
     */
    protected function requireAuthAndRole(string $requiredRole, string $errorMessage): ?int
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonError('Chưa đăng nhập', 401);
            return null;
        }

        if ($_SESSION['user_role'] !== $requiredRole) {
            $this->jsonError($errorMessage, 403);
            return null;
        }

        return (int)$_SESSION['user_id'];
    }

    /**
     * Format order code từ order_id
     */
    protected function formatOrderCode(int $orderId): string
    {
        return 'RC' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Xác định HTTP status code từ exception message
     */
    protected function getStatusCodeFromException(Exception $e): int
    {
        $message = $e->getMessage();
        
        if (strpos($message, 'Không tìm thấy') !== false || 
            strpos($message, 'không tồn tại') !== false) {
            return 404;
        }
        
        if (strpos($message, 'không hợp lệ') !== false || 
            strpos($message, 'không thể') !== false ||
            strpos($message, 'không còn') !== false ||
            strpos($message, 'đã nhận') !== false ||
            strpos($message, 'không được') !== false ||
            strpos($message, 'vượt quá') !== false ||
            strpos($message, 'Không đủ credit') !== false) {
            return 400;
        }
        
        return 500;
    }

    /**
     * Helper: Get status text in Vietnamese
     */
    private function getParticipantStatusText(string $status): string
    {
        $statusMap = [
            'pending' => 'Đã nhận - Chờ giao',
            'delivered' => 'Đã giao rác',
            'confirmed' => 'Đã xác nhận',
            'cancelled' => 'Đã hủy'
        ];
        return $statusMap[$status] ?? $status;
    }

    private function getStatusText(string $status): string
    {
        $statusMap = [
            'pending' => 'Chờ hộ nhận',
            'accepted_by_household' => 'Hộ đã nhận',
            'accepted_by_ctv' => 'CTV đã nhận',
            'collected' => 'Đã thu gom',
            'delivered' => 'Đã giao rác',
            'confirmed' => 'Đã xác nhận',
            'cancelled' => 'Đã hủy'
        ];
        return $statusMap[$status] ?? $status;
    }
}
