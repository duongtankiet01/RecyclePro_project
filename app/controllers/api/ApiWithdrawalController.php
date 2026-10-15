<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../database/Database.php';
require_once __DIR__ . '/../../models/Withdrawal.php';

/**
 * API Controller for Household Withdrawal Requests
 */
class ApiWithdrawalController
{
    private PDO $db;
    private Withdrawal $withdrawalModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->withdrawalModel = new Withdrawal();
    }

    /**
     * Create a new withdrawal request
     * POST /api/withdrawal/create
     */
    public function create_request()
    {
        header('Content-Type: application/json');

        // Check authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'household') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        $points = (float)($input['points'] ?? 0);
        $bankInfo = [
            'account_number' => trim($input['bank_account_number'] ?? ''),
            'bank_name' => trim($input['bank_name'] ?? ''),
            'account_holder' => trim($input['account_holder_name'] ?? '')
        ];

        try {
            // Validate minimum points
            $minPoints = $this->withdrawalModel->getMinWithdrawalPoints();
            if ($points < $minPoints) {
                throw new Exception("Số điểm tối thiểu là {$minPoints} điểm");
            }

            // Check if user can withdraw
            if (!$this->withdrawalModel->canWithdraw($userId, 'household', $points)) {
                $maxPoints = $this->withdrawalModel->getMaxWithdrawalPoints($userId, 'household');
                throw new Exception('Số điểm không đủ. Bạn chỉ có ' . number_format($maxPoints, 0, ',', '.') . ' điểm');
            }

            // Create withdrawal request using model
            $result = $this->withdrawalModel->create($userId, 'household', $points, $bankInfo);

            echo json_encode([
                'success' => true,
                'message' => 'Yêu cầu rút tiền đã được gửi thành công',
                'data' => [
                    'request_id' => $result['request_id'],
                    'request_code' => $result['request_code'],
                    'points_deducted' => $result['points_deducted'],
                    'amount_requested' => $result['amount_vnd'],
                    'status' => 'pending',
                    'remaining_points' => $result['remaining_points']
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
     * Create a new withdrawal request for COLLECTOR
     * POST /api/withdrawal/create_request_collector
     */
    public function create_request_collector()
    {
        header('Content-Type: application/json');

        // Check authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collector') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Chi CTV moi duoc rut tien']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        $points = (float)($input['points'] ?? 0);
        $bankInfo = [
            'account_number' => trim($input['bank_account_number'] ?? ''),
            'bank_name' => trim($input['bank_name'] ?? ''),
            'account_holder' => trim($input['account_holder_name'] ?? '')
        ];

        try {
            // Validate minimum points
            $minPoints = $this->withdrawalModel->getMinWithdrawalPoints();
            if ($points < $minPoints) {
                throw new Exception("So diem toi thieu la {$minPoints} diem");
            }

            // Check if user can withdraw
            if (!$this->withdrawalModel->canWithdraw($userId, 'collector', $points)) {
                $maxPoints = $this->withdrawalModel->getMaxWithdrawalPoints($userId, 'collector');
                throw new Exception('So diem khong du. Ban chi co ' . number_format($maxPoints, 0, ',', '.') . ' diem');
            }

            // Create withdrawal request using model
            $result = $this->withdrawalModel->create($userId, 'collector', $points, $bankInfo);

            echo json_encode([
                'success' => true,
                'message' => 'Yeu cau rut tien da duoc gui thanh cong',
                'data' => [
                    'request_id' => $result['request_id'],
                    'request_code' => $result['request_code'],
                    'points_deducted' => $result['points_deducted'],
                    'amount_requested' => $result['amount_vnd'],
                    'status' => 'pending',
                    'remaining_points' => $result['remaining_points']
                ]
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
     * Get withdrawal history for household
     * GET /api/withdrawal/history
     */
    public function get_history()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'household') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];

        try {
            // Get withdrawal history using model
            $requests = $this->withdrawalModel->getByUser($userId, 'household', ['limit' => 50]);

            echo json_encode([
                'success' => true,
                'data' => $requests
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Admin confirms transfer completed
     * POST /api/withdrawal/confirm_transfer
     */
    public function confirm_transfer()
    {
        header('Content-Type: application/json');

        // Check admin authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $adminId = (int)$_SESSION['user_id'];
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requestId = (int)($input['request_id'] ?? 0);
        $transactionRef = trim($input['transaction_ref'] ?? '');

        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
            return;
        }

        try {
            // Complete withdrawal using model
            $this->withdrawalModel->complete($requestId, $adminId, $transactionRef);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xác nhận chuyển tiền thành công'
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
     * Admin rejects withdrawal request
     * POST /api/withdrawal/reject
     */
    public function reject()
    {
        header('Content-Type: application/json');

        // Check admin authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $adminId = (int)$_SESSION['user_id'];
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requestId = (int)($input['request_id'] ?? 0);
        $reason = trim($input['reason'] ?? '');

        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
            return;
        }

        if (empty($reason)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do từ chối']);
            return;
        }

        try {
            // Reject withdrawal using model (auto refund points)
            $this->withdrawalModel->reject($requestId, $adminId, $reason);

            echo json_encode([
                'success' => true,
                'message' => 'Đã từ chối yêu cầu và hoàn lại điểm'
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
     * Check withdrawal request status
     * GET /api/withdrawal/check_status?request_id=123
     */
    public function check_status()
    {
        header('Content-Type: application/json');

        // Check admin authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $requestId = (int)($_GET['request_id'] ?? 0);

        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
            return;
        }

        try {
            // Get withdrawal request
            $stmt = $this->db->prepare("
                SELECT 
                    wr.request_id,
                    wr.status,
                    wr.amount_requested,
                    wr.points_deducted,
                    wr.request_code,
                    wr.requested_at,
                    wr.completed_at,
                    wr.rejected_at
                FROM withdrawal_requests wr
                WHERE wr.request_id = :request_id
            ");
            
            $stmt->execute(['request_id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu']);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'request_id' => (int)$request['request_id'],
                    'request_code' => $request['request_code'],
                    'status' => $request['status'],
                    'amount' => (float)$request['amount_requested'],
                    'points_deducted' => (float)$request['points_deducted'],
                    'requested_at' => $request['requested_at'],
                    'completed_at' => $request['completed_at'],
                    'rejected_at' => $request['rejected_at']
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ]);
        }
    }
}
