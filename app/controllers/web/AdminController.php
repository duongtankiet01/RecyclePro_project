<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/Withdrawal.php';
require_once __DIR__ . '/../../models/BusinessOrder.php';
require_once __DIR__ . '/../../models/OrderParticipant.php';
require_once __DIR__ . '/../../models/User.php';

class AdminController extends BaseController
{
    private Withdrawal $withdrawalModel;
    private BusinessOrder $businessOrderModel;
    private OrderParticipant $participantModel;
    private User $userModel;

    public function __construct()
    {
        $this->withdrawalModel = new Withdrawal();
        $this->businessOrderModel = new BusinessOrder();
        $this->participantModel = new OrderParticipant();
        $this->userModel = new User();
    }

    public function dangNhap(): void
    {
        // Redirect to unified auth page
        header('Location: index.php?controller=auth&action=dangNhap');
        exit;
    }

    public function dashboard(): void
    {
        // ✅ SECURITY: Chỉ admin mới được truy cập
        $this->requireRole('admin');
        
        $this->render('admin/dashboard', [
            'summary'          => $this->getDashboardSummary(),
            'recentActivities' => $this->getRecentActivities(),
            'systemHealth'     => $this->getSystemHealth(),
        ]);
    }

    public function quanLyTaiKhoan(): void
    {
        // ✅ SECURITY: Chỉ admin mới được truy cập
        $this->requireRole('admin');
        
        // Lấy filter từ GET params
        $filters = [
            'role' => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $this->render('admin/quan-ly-tai-khoan', [
            'accounts' => $this->getAllAccounts($filters),
            'filters' => $filters
        ]);
    }

    public function quanLyRutTien(): void
    {
        // ✅ SECURITY: Chỉ admin mới được truy cập
        $this->requireRole('admin');
        
        $this->render('admin/quan-ly-rut-tien', [
            'withdrawals' => $this->getWithdrawalRequests(),
            'stats'       => $this->getWithdrawalStats(),
        ]);
    }

    private function getDashboardSummary(): array
    {
        try {
            // Lấy tổng số user
            $totalUsers = $this->userModel->getTotalCount();
            
            // Lấy tổng số đơn hôm nay
            $todayOrders = $this->businessOrderModel->getTodayCount();
            
            // Lấy số yêu cầu rút tiền chờ xử lý
            $pendingWithdrawals = $this->withdrawalModel->getPendingCount();
            
            // Lấy doanh thu hôm nay (tổng tiền các đơn hoàn thành hôm nay)
            $todayRevenue = $this->businessOrderModel->getTodayRevenue();
            
            return [
                'totalUsers' => $totalUsers,
                'todayOrders' => $todayOrders,
                'pendingWithdrawals' => $pendingWithdrawals,
                'todayRevenue' => $todayRevenue,
                'totalOrders' => $this->businessOrderModel->getTotalCount(),
                'totalRevenue' => $this->businessOrderModel->getTotalRevenue(),
            ];
        } catch (Exception $e) {
            error_log("Error fetching dashboard summary: " . $e->getMessage());
            return [
                'totalUsers' => 0,
                'todayOrders' => 0,
                'pendingWithdrawals' => 0,
                'todayRevenue' => 0,
                'totalOrders' => 0,
                'totalRevenue' => 0,
            ];
        }
    }

    private function getRecentActivities(): array
    {
        // TODO: Implement real-time activity feed from database
        // Could query from order_participants, withdrawals, user_activities tables
        return [];
    }

    private function getSystemHealth(): array
    {
        // TODO: Implement real system health monitoring
        // Could check database connection, API response times, queue status
        return [
            'apiLatency'    => 0,
            'uptimePercent' => 0,
            'queueBacklog'  => 0,
        ];
    }

    private function getAllAccounts(array $filters = []): array
    {
        try {
            return $this->userModel->getAll([
                'limit' => 100,
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
                'search' => $filters['search'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error fetching accounts: " . $e->getMessage());
            return [];
        }
    }

    private function getPendingApprovals(): array
    {
        try {
            $pendingUsers = $this->userModel->getAll([
                'status' => 'pending',
                'limit' => 50
            ]);
            
            $approvals = [];
            foreach ($pendingUsers as $user) {
                $type = match($user['role']) {
                    'collector' => 'CTV',
                    'business' => 'Doanh nghiệp',
                    'household' => 'Hộ gia đình',
                    default => ucfirst($user['role'])
                };
                
                $approvals[] = [
                    'id' => match($user['role']) {
                        'collector' => 'CTV-' . str_pad((string)$user['user_id'], 3, '0', STR_PAD_LEFT),
                        'business' => 'DN-' . str_pad((string)$user['user_id'], 3, '0', STR_PAD_LEFT),
                        default => 'HGD-' . str_pad((string)$user['user_id'], 3, '0', STR_PAD_LEFT)
                    },
                    'name' => $user['full_name'] ?? 'N/A',
                    'type' => $type,
                    'submitted_at' => $user['created'] ?? date('d/m/Y H:i'),
                ];
            }
            
            return $approvals;
        } catch (Exception $e) {
            error_log("Error fetching pending approvals: " . $e->getMessage());
            return [];
        }
    }

    private function getWithdrawalRequests(): array
    {
        try {
            // Use Withdrawal model để lấy tất cả requests
            return $this->withdrawalModel->getAll(['limit' => 100]);
        } catch (Exception $e) {
            error_log("Error fetching withdrawal requests: " . $e->getMessage());
            return [];
        }
    }

    private function getWithdrawalStats(): array
    {
        try {
            // Use Withdrawal model để lấy statistics
            $stats = $this->withdrawalModel->getStatistics();
            
            // Map keys to match view expectations
            return [
                'totalPending' => $stats['pending_count'] ?? 0,
                'totalCompleted' => $stats['completed_count'] ?? 0,
                'totalRejected' => $stats['rejected_count'] ?? 0,
                'pendingAmount' => $stats['pending_amount'] ?? 0,
                'completedAmount' => $stats['total_amount_paid'] ?? 0,
            ];
        } catch (Exception $e) {
            error_log("Error fetching withdrawal stats: " . $e->getMessage());
            return [
                'totalPending' => 0,
                'totalCompleted' => 0,
                'totalRejected' => 0,
                'pendingAmount' => 0,
                'completedAmount' => 0,
            ];
        }
    }
}
