<?php
declare(strict_types=1);

/**
 * API Controller for Address Data (Provinces/Wards)
 * Uses external API: provinces.open-api.vn
 */
class ApiAddressController
{
    /**
     * Get all provinces
     * GET /index.php?controller=api_address&action=provinces
     */
    public function provinces(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Fetch from external API
        $externalData = $this->fetchExternalProvinces();
        
        if ($externalData !== null) {
            echo json_encode($externalData, JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // If API fails, return error instead of fallback data
        http_response_code(503);
        echo json_encode([
            'error' => 'Không thể kết nối đến API địa chỉ. Vui lòng thử lại sau.',
            'message' => 'Service temporarily unavailable'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Get province details with districts and wards
     * GET /index.php?controller=api_address&action=province_detail&code=79
     */
    public function province_detail(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $provinceCode = $_GET['code'] ?? '';
        
        if (empty($provinceCode)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing province code'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Fetch from external API
        $externalData = $this->fetchExternalProvinceDetail($provinceCode);
        
        if ($externalData !== null) {
            echo json_encode($externalData, JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // If API fails, return error instead of fallback data
        http_response_code(503);
        echo json_encode([
            'error' => 'Không thể kết nối đến API địa chỉ. Vui lòng thử lại sau.',
            'message' => 'Service temporarily unavailable'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Fetch provinces from external API
     */
    private function fetchExternalProvinces(): ?array
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents('https://provinces.open-api.vn/api/?depth=1', false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            return is_array($data) ? $data : null;
            
        } catch (Exception $e) {
            error_log('Failed to fetch external provinces: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch province detail from external API
     */
    private function fetchExternalProvinceDetail(string $code): ?array
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ]);
            
            $url = 'https://provinces.open-api.vn/api/p/' . urlencode($code) . '?depth=3';
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            return is_array($data) ? $data : null;
            
        } catch (Exception $e) {
            error_log('Failed to fetch external province detail: ' . $e->getMessage());
            return null;
        }
    }
    
}
