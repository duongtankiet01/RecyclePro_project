<?php
declare(strict_types=1);

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../../database/Database.php';
require_once __DIR__ . '/../../models/CollectorProfile.php';
require_once __DIR__ . '/../../models/Withdrawal.php';
require_once __DIR__ . '/../../models/CreditTransaction.php';

/**
 * Webhook Controller nháº­n thÃ´ng bÃ¡o tá»« SePay
 * URL: /index.php?controller=webhook_sepay&action=handle
 */
class WebhookSepayController extends BaseController
{
    private PDO $db;
    private CollectorProfile $collectorModel;
    private Withdrawal $withdrawalModel;
    private CreditTransaction $creditModel;
    
    private const API_KEY = 'AKNUM1O2N0QMUYQYGZJWMCFULLFHU5VKEPHFIXOGTI4SD93RWJCRTGJERJCZH9A0';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->collectorModel = new CollectorProfile();
        $this->withdrawalModel = new Withdrawal();
        $this->creditModel = new CreditTransaction();
    }

    /**
     * Endpoint nháº­n webhook tá»« SePay
     * POST /index.php?controller=webhook_sepay&action=handle
     */
    public function handle(): void
    {
        try {
            // Log request Ä‘á»ƒ debug
            $this->logWebhook('Received webhook request');

            // Verify API Key tá»« header (support multiple header formats)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? 
                          $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 
                          (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? '') : '');
            
            if (!$this->verifyApiKey($authHeader)) {
                $this->logWebhook('Invalid API Key: ' . $authHeader);
                $this->respondError('Unauthorized', 401);
                return;
            }

            // Láº¥y dá»¯ liá»‡u tá»« SePay
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data) {
                $this->logWebhook('Invalid JSON data');
                $this->respondError('Invalid JSON', 400);
                return;
            }

            $this->logWebhook('Webhook data: ' . $json);

            // Parse dá»¯ liá»‡u tá»« SePay
            $sepayId = (int)($data['id'] ?? 0);
            $gateway = $data['gateway'] ?? '';
            $transactionDate = $data['transactionDate'] ?? '';
            $accountNumber = $data['accountNumber'] ?? '';
            $code = $data['code'] ?? null; // MÃ£ code thanh toÃ¡n (VD: TXN123456)
            $content = $data['content'] ?? '';
            $transferType = $data['transferType'] ?? 'in';
            $transferAmount = (float)($data['transferAmount'] ?? 0);
            $referenceCode = $data['referenceCode'] ?? '';

            // Validate dá»¯ liá»‡u cÆ¡ báº£n
            if ($sepayId <= 0 || $transferAmount <= 0) {
                $this->logWebhook('Invalid transaction data');
                $this->respondError('Invalid transaction data', 400);
                return;
            }

            // Xá»­ lÃ½ theo loáº¡i giao dá»‹ch
            if ($transferType === 'in') {
                // TIá»€N VÃ€O: Náº¡p credit cho doanh nghiá»‡p
                $this->handleIncomingTransaction($sepayId, $content, $code, $transferAmount, $json);
            } elseif ($transferType === 'out') {
                // TIá»€N RA: XÃ¡c nháº­n rÃºt tiá»n cho há»™ gia Ä‘Ã¬nh
                $this->handleOutgoingTransaction($sepayId, $content, $code, $transferAmount, $json);
            } else {
                $this->logWebhook("Unknown transfer type: {$transferType}");
                $this->respondSuccess('Ignored (unknown transfer type)');
                return;
            }

        } catch (PDOException $e) {
            error_log('Webhook DB error: ' . $e->getMessage());
            $this->respondError('Database error', 500);
        } catch (Exception $e) {
            error_log('Webhook error: ' . $e->getMessage());
            $this->respondError('Internal error', 500);
        }
    }

    /**
     * Xá»­ lÃ½ giao dá»‹ch TIá»€N VÃ€O (Náº¡p credit cho doanh nghiá»‡p HOáº¶C Ä‘iá»ƒm CTV)
     */
    private function handleIncomingTransaction(
        int $sepayId,
        string $content,
        ?string $code,
        float $transferAmount,
        string $json
    ): void {
        // Kiá»ƒm tra trÃ¹ng láº·p credit_transactions
        $stmt = $this->db->prepare("
            SELECT transaction_id, status 
            FROM credit_transactions 
            WHERE bank_transaction_id = :sepay_id
        ");
        $stmt->execute(['sepay_id' => (string)$sepayId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $this->logWebhook("Duplicate credit transaction detected: {$sepayId}");
            $this->respondSuccess('Transaction already processed (duplicate)');
            return;
        }
        
        // Kiá»ƒm tra trÃ¹ng láº·p collector_topup_transactions
        $stmt = $this->db->prepare("
            SELECT topup_id, status 
            FROM collector_topup_transactions 
            WHERE bank_trace = :sepay_id
        ");
        $stmt->execute(['sepay_id' => (string)$sepayId]);
        $existingTopup = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingTopup) {
            $this->logWebhook("Duplicate collector topup detected: {$sepayId}");
            $this->respondSuccess('Topup already processed (duplicate)');
            return;
        }

        // Extract mÃ£ giao dá»‹ch (TXN... hoáº·c CTV...)
        $transactionCode = $this->extractTransactionCode($content, $code);

        if (!$transactionCode) {
            $this->logWebhook('No transaction code found in content: ' . $content);
            $this->respondSuccess('No transaction code found, ignored');
            return;
        }

        // Kiểm tra xem là nạp credit DN hay nạp điểm CTV
        if (strpos($transactionCode, 'CTV') === 0 || strpos($transactionCode, 'TOP') === 0) {
            // Xử lý nạp điểm CTV
            $this->handleCollectorTopup($sepayId, $transactionCode, $transferAmount, $content, $json);
            return;
        }

        // Use CreditTransaction model to find and complete transaction
        try {
            $transaction = $this->creditModel->findByCode($transactionCode);

            if (!$transaction) {
                $this->logWebhook("Transaction code {$transactionCode} not found");
                $this->respondSuccess('Transaction not found');
                return;
            }

            if ($transaction['status'] !== 'pending') {
                $this->logWebhook("Transaction {$transactionCode} already processed (status: {$transaction['status']})");
                $this->respondSuccess('Transaction already processed');
                return;
            }

            // Kiá»ƒm tra sá»‘ tiá»n khá»›p (tolerance 1%)
            $expectedAmount = (float)$transaction['amount'];
            $amountDiff = abs($transferAmount - $expectedAmount);
            $tolerance = $expectedAmount * 0.01;

            if ($amountDiff > $tolerance) {
                $this->logWebhook("Amount mismatch. Expected: {$expectedAmount}, Received: {$transferAmount}");
                $this->creditModel->fail(
                    (int)$transaction['transaction_id'],
                    "Sá»‘ tiá»n khÃ´ng khá»›p. Mong Ä‘á»£i: {$expectedAmount} VNÄ, Nháº­n Ä‘Æ°á»£c: {$transferAmount} VNÄ"
                );
                $this->respondSuccess('Amount mismatch, transaction marked as failed');
                return;
            }

            // Complete transaction using model (auto adds credits to business)
            $this->creditModel->complete((int)$transaction['transaction_id'], (string)$sepayId);

            $this->logWebhook("âœ… Credit transaction {$transactionCode} completed. Added {$transaction['credits']} credits to business {$transaction['business_id']}");
            $this->respondSuccess('Transaction processed successfully', 201);

        } catch (Exception $e) {
            $this->logWebhook('Error completing transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xá»­ lÃ½ giao dá»‹ch TIá»€N RA (XÃ¡c nháº­n rÃºt tiá»n cho há»™ gia Ä‘Ã¬nh)
     */
    private function handleOutgoingTransaction(
        int $sepayId,
        string $content,
        ?string $code,
        float $transferAmount,
        string $json
    ): void {
        $this->logWebhook("Processing OUTGOING transaction (withdrawal confirmation)");

        // Extract mÃ£ rÃºt tiá»n (WD...)
        $withdrawalCode = $this->extractWithdrawalCode($content, $code);

        if (!$withdrawalCode) {
            $this->logWebhook('No withdrawal code found in content: ' . $content);
            $this->respondSuccess('No withdrawal code found, ignored');
            return;
        }

        // TÃ¬m yÃªu cáº§u rÃºt tiá»n pending
        $stmt = $this->db->prepare("
            SELECT 
                wr.request_id,
                wr.household_id,
                wr.amount_requested,
                wr.points_deducted,
                wr.status,
                wr.bank_account_number,
                u.full_name as user_name
            FROM withdrawal_requests wr
            JOIN users u ON wr.household_id = u.user_id
            WHERE wr.request_code = :code 
              AND wr.status = 'pending'
            LIMIT 1
        ");
        $stmt->execute(['code' => $withdrawalCode]);
        $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$withdrawal) {
            $this->logWebhook("Withdrawal code {$withdrawalCode} not found or not pending");
            $this->respondSuccess('Withdrawal not found or already processed');
            return;
        }

        // Kiá»ƒm tra sá»‘ tiá»n khá»›p (tolerance 1%)
        // Validate amount (1% tolerance)
        $expectedAmount = (float)$withdrawal['amount_requested'];
        $amountDiff = abs($transferAmount - $expectedAmount);
        $tolerance = max($expectedAmount * 0.01, 1000); // Min 1,000 VNÄ tolerance

        if ($amountDiff > $tolerance) {
            $this->logWebhook("Amount mismatch. Expected: {$expectedAmount}, Transferred: {$transferAmount}");
            $this->respondSuccess('Amount mismatch, manual review required');
            return;
        }

        // Complete withdrawal using model (admin_id = 0 for webhook auto-confirm)
        $note = "Tá»± Ä‘á»™ng xÃ¡c nháº­n bá»Ÿi Sepay Webhook. Giao dá»‹ch #{$sepayId}. ÄÃ£ chuyá»ƒn " . 
                number_format($transferAmount, 0, ',', '.') . " VNÄ vÃ o STK {$withdrawal['bank_account_number']}";
        
        $result = $this->withdrawalModel->complete((int)$withdrawal['request_id'], 0, $note);

        if ($result) {
            $this->logWebhook("âœ… Withdrawal {$withdrawalCode} auto-confirmed. Transferred " . 
                number_format($transferAmount, 0, ',', '.') . 
                " VNÄ to {$withdrawal['user_name']} (ID: {$withdrawal['user_id']})");

            $this->respondSuccess('Withdrawal confirmed successfully', 201);
        } else {
            $this->logWebhook("Failed to complete withdrawal {$withdrawalCode}");
            $this->respondError('Failed to complete withdrawal', 500);
        }
    }

    /**
     * Verify API Key tá»« header
     */
    private function verifyApiKey(string $authHeader): bool
    {
        // Format: "Apikey YOUR_API_KEY"
        if (preg_match('/^Apikey\s+(.+)$/i', $authHeader, $matches)) {
            $receivedKey = trim($matches[1]);
            return $receivedKey === self::API_KEY;
        }
        return false;
    }

    /**
     * Extract transaction code tá»« ná»™i dung chuyá»ƒn khoáº£n (cho náº¡p credit)
     */
    private function extractTransactionCode(string $content, ?string $sepayCode): ?string
    {
        // Náº¿u SePay Ä‘Ã£ nháº­n diá»‡n Ä‘Æ°á»£c code
        if (!empty($sepayCode)) {
            return $sepayCode;
        }

        // Tìm pattern: NAP TXN20251012123456 hoặc TXN20251012123456
        if (preg_match('/\b(TXN\d{14})\b/i', $content, $matches)) {
            return strtoupper($matches[1]);
        }

        // Tìm pattern CTV topup: TOP251102135413539
        if (preg_match('/\b(TOP\d{11,15})\b/i', $content, $matches)) {
            return strtoupper($matches[1]);
        }

        // Tìm pattern khác nếu cần
        if (preg_match('/\bNAP\s+([A-Z0-9]+)\b/i', $content, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Extract withdrawal code tá»« ná»™i dung chuyá»ƒn khoáº£n (cho rÃºt tiá»n)
     */
    private function extractWithdrawalCode(string $content, ?string $sepayCode): ?string
    {
        // Náº¿u SePay Ä‘Ã£ nháº­n diá»‡n Ä‘Æ°á»£c code
        if (!empty($sepayCode)) {
            return $sepayCode;
        }

        // TÃ¬m pattern: RUT WD12345678901234567, WD12345678901234567
        // Match WD + 17 chữ số (khớp với Sepay config)
        if (preg_match('/\b(WD\d{17})\b/i', $content, $matches)) {
            return strtoupper($matches[1]);
        }

        // TÃ¬m pattern khÃ¡c: RUT TIEN WD12345678901234567
        if (preg_match('/RUT(?:\s+TIEN)?\s+([A-Z0-9]+)\b/i', $content, $matches)) {
            $code = strtoupper($matches[1]);
            // Kiá»ƒm tra format WD + 17 chữ số
            if (preg_match('/^WD\d{17}$/', $code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Xử lý nạp điểm cho CTV
     * REFACTORED: Dùng CreditTransaction (chung với doanh nghiệp)
     */
    private function handleCollectorTopup(
        int $sepayId,
        string $topupCode,
        float $transferAmount,
        string $content,
        string $json
    ): void {
        // TÃ¬m giao dá»‹ch náº¡p Ä‘iá»ƒm CTV pending
        $stmt = $this->db->prepare("
            SELECT topup_id, collector_id, amount_vnd, points_received, status
            FROM collector_topup_transactions
            WHERE topup_code = :code 
              AND status = 'pending'
            LIMIT 1
        ");
        $stmt->execute(['code' => $topupCode]);
        $topup = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$topup) {
            $this->logWebhook("Topup code {$topupCode} not found or not pending");
            $this->respondSuccess('Topup not found or already processed');
            return;
        }

        // Kiá»ƒm tra sá»‘ tiá»n khá»›p (tolerance 1%)
        $expectedAmount = (float)$topup['amount_vnd'];
        $amountDiff = abs($transferAmount - $expectedAmount);
        $tolerance = $expectedAmount * 0.01;

        if ($amountDiff > $tolerance) {
            $this->logWebhook("Topup amount mismatch. Expected: {$expectedAmount}, Received: {$transferAmount}");
            
            // Update topup status to failed
            $stmt = $this->db->prepare("
                UPDATE collector_topup_transactions 
                SET status = 'failed',
                    notes = :notes,
                    bank_trace = :trace
                WHERE topup_id = :topup_id
            ");
            $stmt->execute([
                'notes' => "Sá»‘ tiá»n khÃ´ng khá»›p. Mong Ä‘á»£i: {$expectedAmount} VNÄ, Nháº­n Ä‘Æ°á»£c: {$transferAmount} VNÄ",
                'trace' => (string)$sepayId,
                'topup_id' => $topup['topup_id']
            ]);
            
            $this->respondSuccess('Topup amount mismatch, marked as failed');
            return;
        }

        // Complete topup using CollectorProfile model (auto adds points)
        try {
            $result = $this->collectorModel->confirmTopup((int)$topup['topup_id']);

            $this->logWebhook("âœ… CTV topup {$topupCode} completed. Added {$result['points_received']} points to collector {$topup['collector_id']}");
            $this->respondSuccess('Topup processed successfully');

        } catch (Exception $e) {
            $this->logWebhook("Error processing CTV topup: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log webhook activity
     */
    private function logWebhook(string $message): void
    {
        $logFile = __DIR__ . '/../../logs/sepay_webhook.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Respond success theo format SePay yÃªu cáº§u
     */
    private function respondSuccess(string $message = 'OK', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Respond error
     */
    private function respondError(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
