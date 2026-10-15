<?php
declare(strict_types=1);

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../../database/Database.php';
require_once __DIR__ . '/../../models/CreditTransaction.php';

class ApiCreditController extends BaseController
{
    private PDO $db;
    private CreditTransaction $creditModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->creditModel = new CreditTransaction();
    }

    /**
     * Táº¡o yÃªu cáº§u náº¡p Ä‘iá»ƒm (táº¡o mÃ£ giao dá»‹ch)
     * POST /index.php?controller=api_credit&action=create_request
     */
    public function create_request(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('Chưa đăng nhập', 401);
                return;
            }

            if ($_SESSION['user_role'] !== 'business') {
                $this->jsonError('Chỉ doanh nghiệp mới được nạp điểm', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            // Validate input
            $amount = (float)($data['amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'bank_transfer';

            // Create transaction using model
            $result = $this->creditModel->create($businessId, $amount, $paymentMethod);

            $this->jsonSuccess('Tạo yêu cầu nạp điểm thành công', [
                'transaction' => $result
            ]);

        } catch (Exception $e) {
            error_log('Create credit request error: ' . $e->getMessage());
            $this->jsonError('Lỗi database: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Láº¥y danh sÃ¡ch giao dá»‹ch náº¡p Ä‘iá»ƒm cá»§a doanh nghiá»‡p
     * GET /index.php?controller=api_credit&action=my_transactions
     */
    public function my_transactions(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('ChÆ°a Ä‘Äƒng nháº­p', 401);
                return;
            }

            if ($_SESSION['user_role'] !== 'business') {
                $this->jsonError('KhÃ´ng cÃ³ quyá»n truy cáº­p', 403);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $status = $_GET['status'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Build filters
            $filters = [
                'limit' => $limit,
                'offset' => $offset
            ];

            if ($status !== '' && $status !== 'all') {
                $filters['status'] = $status;
            }

            // Get transactions using model
            $transactions = $this->creditModel->getByBusiness($businessId, $filters);

            // Format response
            $formattedTransactions = array_map(function($row) {
                return [
                    'transaction_id' => (int)$row['transaction_id'],
                    'transaction_code' => $row['transaction_code'],
                    'amount' => (float)$row['amount'],
                    'credits' => (float)$row['credits'],
                    'payment_method' => $row['payment_method'],
                    'status' => $row['status'],
                    'status_text' => $this->creditModel->getStatusText($row['status']),
                    'bank_transaction_id' => $row['bank_transaction_id'],
                    'payment_reference' => $row['payment_reference'],
                    'completed_at' => $row['completed_at'],
                    'failed_at' => $row['failed_at'],
                    'failure_reason' => $row['failure_reason'],
                    'created_at' => $row['created_at']
                ];
            }, $transactions);

            // Count total
            $countFilters = [];
            if ($status !== '' && $status !== 'all') {
                $countFilters['status'] = $status;
            }
            $total = $this->creditModel->count(array_merge(['business_id' => $businessId], $countFilters));

            $this->jsonSuccess('Lấy danh sách giao dịch thành công', [
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int)ceil($total / $limit)
                ]
            ]);

        } catch (Exception $e) {
            error_log('Get transactions error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Kiá»ƒm tra tráº¡ng thÃ¡i giao dá»‹ch
     * GET /index.php?controller=api_credit&action=check_status&code=TXN123456
     */
    public function check_status(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('ChÆ°a Ä‘Äƒng nháº­p', 401);
                return;
            }

            $businessId = (int)$_SESSION['user_id'];
            $code = $_GET['code'] ?? '';

            if (empty($code)) {
                $this->jsonError('Thiếu mã giao dịch', 422);
                return;
            }

            // Get transaction using model
            $transaction = $this->creditModel->findByCode($code);

            if (!$transaction) {
                $this->jsonError('Không tìm thấy giao dịch', 404);
                return;
            }

            // Verify ownership
            if ((int)$transaction['business_id'] !== $businessId) {
                $this->jsonError('Không có quyền truy cập giao dịch này', 403);
                return;
            }

            $this->jsonSuccess('Láº¥y tráº¡ng thÃ¡i giao dá»‹ch thÃ nh cÃ´ng', [
                'status' => $transaction['status'],
                'status_text' => $this->creditModel->getStatusText($transaction['status']),
                'completed_at' => $transaction['completed_at'],
                'credits' => (float)$transaction['credits'],
                'amount' => (float)$transaction['amount']
            ]);

        } catch (Exception $e) {
            error_log('Check status error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Hủy yêu cầu nạp điểm (manual cancel)
     * POST /index.php?controller=api_credit&action=cancel_request
     */
    public function cancel_request(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('Chưa đăng nhập', 401);
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];
            $data = $this->getJsonInput();
            $transactionCode = $data['transaction_code'] ?? '';

            if (empty($transactionCode)) {
                $this->jsonError('Thiếu mã giao dịch', 422);
                return;
            }

            // Get transaction
            $transaction = $this->creditModel->findByCode($transactionCode);

            if (!$transaction) {
                $this->jsonError('Không tìm thấy giao dịch', 404);
                return;
            }

            // Verify ownership (support both business and collector)
            if ($userRole === 'business') {
                if ((int)$transaction['business_id'] !== $userId) {
                    $this->jsonError('Không có quyền hủy giao dịch này', 403);
                    return;
                }
            } elseif ($userRole === 'collector') {
                // For collector, business_id stores collector's user_id
                if ((int)$transaction['business_id'] !== $userId) {
                    $this->jsonError('Không có quyền hủy giao dịch này', 403);
                    return;
                }
            } else {
                $this->jsonError('Vai trò không hợp lệ', 403);
                return;
            }

            // Only allow cancel pending transactions
            if ($transaction['status'] !== CreditTransaction::STATUS_PENDING) {
                $this->jsonError('Chỉ có thể hủy giao dịch đang chờ thanh toán', 400);
                return;
            }

            // Cancel transaction
            $this->creditModel->updateStatus(
                (int)$transaction['transaction_id'], 
                CreditTransaction::STATUS_FAILED, 
                null, 
                'Người dùng hủy yêu cầu'
            );

            $this->jsonSuccess('Đã hủy yêu cầu nạp điểm', [
                'transaction_code' => $transactionCode
            ]);

        } catch (Exception $e) {
            error_log('Cancel request error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Tự động hết hạn yêu cầu nạp điểm (timeout)
     * POST /index.php?controller=api_credit&action=expire_request
     */
    public function expire_request(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('Chưa đăng nhập', 401);
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];
            $data = $this->getJsonInput();
            $transactionCode = $data['transaction_code'] ?? '';

            if (empty($transactionCode)) {
                $this->jsonError('Thiếu mã giao dịch', 422);
                return;
            }

            // Get transaction
            $transaction = $this->creditModel->findByCode($transactionCode);

            if (!$transaction) {
                $this->jsonError('Không tìm thấy giao dịch', 404);
                return;
            }

            // Verify ownership (support both business and collector)
            if ($userRole === 'business') {
                if ((int)$transaction['business_id'] !== $userId) {
                    $this->jsonError('Không có quyền thao tác giao dịch này', 403);
                    return;
                }
            } elseif ($userRole === 'collector') {
                if ((int)$transaction['business_id'] !== $userId) {
                    $this->jsonError('Không có quyền thao tác giao dịch này', 403);
                    return;
                }
            } else {
                $this->jsonError('Vai trò không hợp lệ', 403);
                return;
            }

            // Only expire pending transactions
            if ($transaction['status'] !== CreditTransaction::STATUS_PENDING) {
                // Already processed, no need to expire
                $this->jsonSuccess('Giao dịch đã được xử lý', [
                    'status' => $transaction['status']
                ]);
                return;
            }

            // Expire transaction
            $this->creditModel->updateStatus(
                (int)$transaction['transaction_id'], 
                CreditTransaction::STATUS_FAILED, 
                null, 
                'Hết thời gian chờ thanh toán (2 phút)'
            );

            $this->jsonSuccess('Đã hết hạn yêu cầu nạp điểm', [
                'transaction_code' => $transactionCode
            ]);

        } catch (Exception $e) {
            error_log('Expire request error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }
}
