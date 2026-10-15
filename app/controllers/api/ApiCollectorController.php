<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/CollectorProfile.php';
require_once __DIR__ . '/../../models/OrderParticipant.php';
require_once __DIR__ . '/../../models/BusinessOrder.php';

class ApiCollectorController extends BaseController
{
    private CollectorProfile $collectorProfileModel;
    private OrderParticipant $orderParticipantModel;
    private BusinessOrder $businessOrderModel;

    public function __construct()
    {
        $this->collectorProfileModel = new CollectorProfile();
        $this->orderParticipantModel = new OrderParticipant();
        $this->businessOrderModel = new BusinessOrder();
    }

    /**
     * API: Láº¥y danh sÃ¡ch Ä‘Æ¡n chá» CTV nháº­n
     * GET /index.php?controller=api_collector&action=available_orders
     * 
     * REFACTORED: Sá»­ dá»¥ng OrderParticipant Model
     */
    public function availableOrders(): void
    {
        header('Content-Type: application/json');
        
        try {
            // Sá»­ dá»¥ng OrderParticipant Model
            $participants = $this->orderParticipantModel->getAvailableForCollectors();
            
            $orders = array_map(function($row) {
                $totalPoints = (float)$row['quantity'] * (float)$row['points_per_kg'];
                $advanceAmount = $totalPoints; // 100%
                $ctvCommission = $totalPoints * 0.35; // 35%
                
                return [
                    'participant_id' => (int)$row['participant_id'],
                    'household_name' => $row['household_name'],
                    'household_phone' => $row['household_phone'],
                    'waste_type' => $row['waste_type'],
                    'quantity' => (float)$row['quantity'],
                    'points_per_kg' => (float)$row['points_per_kg'],
                    'total_points' => $totalPoints,
                    'advance_amount' => $advanceAmount,
                    'your_commission' => $ctvCommission,
                    'address' => $row['address_line'] ?? 'ChÆ°a cÃ³ Ä‘á»‹a chá»‰',
                    'accepted_at' => $row['accepted_at'],
                ];
            }, $participants);
            
            echo json_encode([
                'success' => true,
                'data' => ['orders' => $orders]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lá»—i táº£i danh sÃ¡ch Ä‘Æ¡n: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: CTV nháº­n Ä‘Æ¡n (chÆ°a á»©ng Ä‘iá»ƒm)
     * POST /index.php?controller=api_collector&action=acceptOrder
     * Body: {participant_id: 123}
     * 
     * REFACTORED: Sá»­ dá»¥ng OrderParticipant Model
     */
    public function acceptOrder(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lÃ²ng Ä‘Äƒng nháº­p vá»›i tÃ i khoáº£n CTV']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $participantId = (int)($input['participant_id'] ?? 0);
            $collectorId = (int)$_SESSION['user_id'];
            
            if ($participantId <= 0) {
                throw new Exception('participant_id không hợp lệ');
            }
            
            // Lấy thông tin đơn để tính số điểm cần ứng
            $participant = $this->orderParticipantModel->find($participantId);
            if (!$participant) {
                throw new Exception('Không tìm thấy đơn hàng');
            }
            
            // Tính số điểm cần ứng trước (60% tổng điểm)
            $totalPoints = (float)$participant['quantity'] * (float)$participant['points_per_kg'];
            $advancePoints = $totalPoints * 0.60; // CTV ứng 60% cho hộ gia đình
            
            // Kiểm tra CTV có đủ điểm không
            $currentPoints = $this->collectorProfileModel->getCurrentPoints($collectorId);
            
            if ($currentPoints < $advancePoints) {
                $shortfall = $advancePoints - $currentPoints;
                throw new Exception(
                    sprintf(
                        'Bạn không đủ điểm để nhận đơn này. Cần: %.0f điểm, Hiện có: %.0f điểm, Thiếu: %.0f điểm. Vui lòng nạp thêm điểm!',
                        $advancePoints,
                        $currentPoints,
                        $shortfall
                    )
                );
            }
            
            // Sử dụng OrderParticipant Model để nhận đơn
            $result = $this->orderParticipantModel->acceptByCollector($participantId, $collectorId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã nhận đơn thành công! Hãy đến địa chỉ để xem rác và xác nhận thu gom.',
                    'data' => [
                        'participant_id' => $participantId,
                        'advance_points' => $advancePoints,
                        'remaining_points' => $currentPoints - $advancePoints
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Không thể nhận đơn. Đơn có thể đã được CTV khác nhận.');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: CTV hủy đơn đã nhận
     * POST /index.php?controller=api_collector&action=cancelOrder
     * Body: {participant_id: 123}
     * 
     * REFACTORED: Sử dụng OrderParticipant Model
     */
    public function cancelOrder(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập với tài khoản CTV']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $participantId = (int)($input['participant_id'] ?? 0);
            $collectorId = (int)$_SESSION['user_id'];
            
            if ($participantId <= 0) {
                throw new Exception('Thiếu participant_id');
            }
            
            // Sử dụng OrderParticipant Model
            $this->orderParticipantModel->cancelByCollector($participantId, $collectorId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã hủy đơn thành công. Đơn đã trở lại trạng thái "Chờ CTV nhận".'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: CTV xÃ¡c nháº­n thu gom vÃ  á»©ng Ä‘iá»ƒm
     * POST /index.php?controller=api_collector&action=confirmPickup
     * Body: {participant_id: 123, actual_quantity: 15.5}
     * 
     * REFACTORED: Sá»­ dá»¥ng OrderParticipant Model
     */
    public function confirmPickup(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lÃ²ng Ä‘Äƒng nháº­p vá»›i tÃ i khoáº£n CTV']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $participantId = (int)($input['participant_id'] ?? 0);
            $actualQuantity = (float)($input['actual_quantity'] ?? 0);
            $collectorId = (int)$_SESSION['user_id'];
            
            if ($participantId <= 0) {
                throw new Exception('participant_id khÃ´ng há»£p lá»‡');
            }
            
            if ($actualQuantity <= 0) {
                throw new Exception('Sá»‘ lÆ°á»£ng thá»±c táº¿ pháº£i lá»›n hÆ¡n 0');
            }
            
            // Sá»­ dá»¥ng OrderParticipant Model
            $result = $this->orderParticipantModel->confirmPickup(
                $participantId,
                $collectorId,
                $actualQuantity
            );
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã xác nhận thu gom ' . number_format($actualQuantity, 1) . ' kg! Ứng ' . number_format($result['advance_given'], 0) . ' điểm cho hộ gia đình. Hãy mang rác về doanh nghiệp.',
                    'data' => [
                        'participant_id' => $participantId,
                        'actual_quantity' => $actualQuantity,
                        'advance_given' => $result['advance_given']
                    ]
                ]);
            } else {
                throw new Exception('Không thể xác nhận thu gom');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Táº¡o yÃªu cáº§u náº¡p Ä‘iá»ƒm
     * POST /index.php?controller=api_collector&action=createTopup
     * Body: {amount: 500000}
     * 
     * REFACTORED: Sá»­ dá»¥ng CollectorProfile Model
     */
    public function createTopup(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lÃ²ng Ä‘Äƒng nháº­p vá»›i tÃ i khoáº£n CTV']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $amountVnd = (float)($input['amount'] ?? 0);
            $collectorId = (int)$_SESSION['user_id'];
            
            // Validate amount (min 10,000 VND = 10 Ä‘iá»ƒm)
            if ($amountVnd < 10000) {
                throw new Exception('Sá»‘ tiá»n náº¡p tá»‘i thiá»ƒu lÃ  10,000 VND (10 Ä‘iá»ƒm)');
            }
            
            if ($amountVnd > 50000000) {
                throw new Exception('Sá»‘ tiá»n náº¡p tá»‘i Ä‘a lÃ  50,000,000 VND (50,000 Ä‘iá»ƒm)');
            }
            
            // Sá»­ dá»¥ng CollectorProfile Model
            $result = $this->collectorProfileModel->createTopup($collectorId, $amountVnd);
            
            // Generate QR code URL
            $transactionCode = $result['transaction_code'];
            $qrUrl = 'https://img.vietqr.io/image/970422-0373784694-compact2.jpg?' . http_build_query([
                'amount' => $result['amount_vnd'],
                'addInfo' => $transactionCode,
                'accountName' => 'LE THANH NHAN'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tạo yêu cầu nạp điểm thành công',
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'topup_id' => $result['topup_id'],
                    'transaction_code' => $transactionCode,
                    'topup_code' => $transactionCode, // Backward compatibility
                    'amount' => $result['amount_vnd'],
                    'amount_vnd' => $result['amount_vnd'],
                    'credits' => $result['credits'],
                    'points_to_receive' => $result['points_to_receive'], // Backward compatibility
                    'bank_info' => [
                        'account_number' => '0373784694',
                        'account_name' => 'LE THANH NHAN',
                        'bank_name' => 'MB Bank',
                        'content' => $transactionCode,
                        'amount' => $result['amount_vnd'],
                        'qr_code_url' => $qrUrl
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Kiá»ƒm tra tráº¡ng thÃ¡i náº¡p Ä‘iá»ƒm
     * GET /index.php?controller=api_collector&action=checkTopupStatus&code=CTV123
     */
    public function checkTopupStatus(): void
    {
        header('Content-Type: application/json');
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lÃ²ng Ä‘Äƒng nháº­p']);
            return;
        }
        
        try {
            $topupCode = $_GET['code'] ?? '';
            $userId = (int)$_SESSION['user_id'];
            
            if (empty($topupCode)) {
                throw new Exception('Thiáº¿u mÃ£ topup');
            }
            
            $topup = $this->collectorProfileModel->getTopupByCode($userId, $topupCode);
            
            if (!$topup) {
                throw new Exception('KhÃ´ng tÃ¬m tháº¥y giao dá»‹ch');
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'status' => $topup['status'],
                    'points' => (float)$topup['points_received'],
                    'confirmed_at' => $topup['confirmed_at']
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Láº¥y lá»‹ch sá»­ náº¡p Ä‘iá»ƒm
     * GET /index.php?controller=api_collector&action=getTopupHistory
     */
    public function getTopupHistory(): void
    {
        header('Content-Type: application/json');
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Vui lòng đăng nhập',
                'debug' => [
                    'has_session' => isset($_SESSION['user_id']),
                    'role' => $_SESSION['user_role'] ?? 'none'
                ]
            ]);
            return;
        }
        
        try {
            $userId = (int)$_SESSION['user_id'];
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $history = $this->collectorProfileModel->getTopupHistory($userId, ['limit' => $limit]);
            
            echo json_encode([
                'success' => true,
                'data' => ['history' => $history],
                'debug' => [
                    'user_id' => $userId,
                    'count' => count($history)
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi tải lịch sử: ' . $e->getMessage(),
                'debug' => [
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }
    
    /**
     * API: Láº¥y danh sÃ¡ch Ä‘Æ¡n CTV Ä‘Ã£ nháº­n
     * GET /index.php?controller=api_collector&action=my_orders
     * 
     * REFACTORED: Sá»­ dá»¥ng OrderParticipant Model
     */
    public function myOrders(): void
    {
        header('Content-Type: application/json');
        
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lÃ²ng Ä‘Äƒng nháº­p']);
            return;
        }
        
        try {
            $collectorId = (int)$_SESSION['user_id'];
            
            // Sá»­ dá»¥ng OrderParticipant Model
            $participants = $this->orderParticipantModel->getByCollector($collectorId);
            
            $orders = array_map(function($row) {
                return [
                    'participant_id' => (int)$row['participant_id'],
                    'household_name' => $row['household_name'],
                    'waste_type' => $row['waste_type'],
                    'quantity' => (float)$row['quantity'],
                    'status' => $row['status'],
                    'advance_given' => (float)$row['advance_given'],
                    'final_points' => $row['final_collector_points'] ? (float)$row['final_collector_points'] : null,
                    'address' => $row['address_line'] ?? 'ChÆ°a cÃ³ Ä‘á»‹a chá»‰',
                    'accepted_at' => $row['accepted_at'],
                    'delivered_at' => $row['delivered_at'],
                ];
            }, $participants);
            
            echo json_encode([
                'success' => true,
                'data' => ['orders' => $orders]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lá»—i táº£i danh sÃ¡ch Ä‘Æ¡n: ' . $e->getMessage()
            ]);
        }
    }
}
