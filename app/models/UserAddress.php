<?php
declare(strict_types=1);

/**
 * UserAddress Model - Quản lý địa chỉ của users
 * 
 * Xử lý:
 * - CRUD địa chỉ
 * - Validation province/ward codes
 * - Quản lý địa chỉ chính (is_primary)
 * - Quản lý head office (cho business)
 */
class UserAddress
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Tạo địa chỉ mới cho user
     * 
     * @param int $userId User ID
     * @param array $data Dữ liệu địa chỉ ['province_code', 'ward_code', 'address_line', 'note', 'is_primary', 'is_head_office']
     * @return int Address ID vừa tạo
     * @throws Exception Nếu dữ liệu không hợp lệ
     */
    public function create(int $userId, array $data): int
    {
        // Validation
        $provinceCode = trim($data['province_code'] ?? '');
        $wardCode = trim($data['ward_code'] ?? '');
        $addressLine = trim($data['address_line'] ?? '');

        if ($provinceCode === '' || $wardCode === '' || $addressLine === '') {
            throw new Exception('Thiếu thông tin địa chỉ bắt buộc');
        }

        // Validate province and ward codes
        $this->validateProvinceAndWard($provinceCode, $wardCode);

        $isPrimary = $data['is_primary'] ?? 0;
        $isHeadOffice = $data['is_head_office'] ?? 0;
        $note = trim($data['note'] ?? '');

        // Nếu đặt is_primary = 1, cần unset các địa chỉ primary cũ
        if ($isPrimary) {
            $this->unsetPrimaryAddresses($userId);
        }

        // Insert
        $stmt = $this->db->prepare("
            INSERT INTO user_addresses 
            (user_id, province_code, ward_code, address_line, note, is_primary, is_head_office) 
            VALUES (:uid, :province, :ward, :addr, :note, :is_primary, :is_head_office)
        ");

        $stmt->execute([
            'uid' => $userId,
            'province' => $provinceCode,
            'ward' => $wardCode,
            'addr' => $addressLine,
            'note' => $note !== '' ? $note : null,
            'is_primary' => $isPrimary ? 1 : 0,
            'is_head_office' => $isHeadOffice ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Lấy địa chỉ chính của user
     * 
     * @param int $userId User ID
     * @return array|null Thông tin địa chỉ chính hoặc null nếu chưa có
     */
    public function findPrimaryByUser(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT address_id, user_id, province_code, ward_code, address_line, note, is_primary, is_head_office, created_at
                FROM user_addresses
                WHERE user_id = :uid AND is_primary = 1
                LIMIT 1
            ");
            $stmt->execute(['uid' => $userId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);

            return $address ?: null;
        } catch (PDOException $e) {
            // Bảng chưa tồn tại
            error_log('UserAddress query error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy tất cả địa chỉ của user
     * 
     * @param int $userId User ID
     * @return array Danh sách địa chỉ
     */
    public function findAllByUser(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT address_id, user_id, province_code, ward_code, address_line, note, is_primary, is_head_office, created_at
                FROM user_addresses
                WHERE user_id = :uid
                ORDER BY is_primary DESC, created_at DESC
            ");
            $stmt->execute(['uid' => $userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('UserAddress query error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cập nhật địa chỉ
     * 
     * @param int $addressId Address ID
     * @param array $data Dữ liệu cần update
     * @return bool True nếu thành công
     * @throws Exception Nếu dữ liệu không hợp lệ
     */
    public function update(int $addressId, array $data): bool
    {
        // Build update query dynamically
        $allowedFields = ['province_code', 'ward_code', 'address_line', 'note', 'is_primary', 'is_head_office'];
        $updateFields = [];
        $params = ['address_id' => $addressId];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false; // Không có gì để update
        }

        // Validate province/ward nếu có update
        if (isset($data['province_code']) || isset($data['ward_code'])) {
            // Lấy thông tin hiện tại
            $current = $this->findById($addressId);
            if (!$current) {
                throw new Exception('Địa chỉ không tồn tại');
            }

            $provinceCode = $data['province_code'] ?? $current['province_code'];
            $wardCode = $data['ward_code'] ?? $current['ward_code'];

            $this->validateProvinceAndWard($provinceCode, $wardCode);
        }

        // Nếu set is_primary = 1, unset các primary khác
        if (isset($data['is_primary']) && $data['is_primary']) {
            $current = $this->findById($addressId);
            if ($current) {
                $this->unsetPrimaryAddresses((int)$current['user_id'], $addressId);
            }
        }

        $sql = "UPDATE user_addresses SET " . implode(', ', $updateFields) . " WHERE address_id = :address_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Xóa địa chỉ
     * 
     * @param int $addressId Address ID
     * @return bool True nếu thành công
     */
    public function delete(int $addressId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_addresses WHERE address_id = :id");
        return $stmt->execute(['id' => $addressId]);
    }

    /**
     * Tìm địa chỉ theo ID
     * 
     * @param int $addressId Address ID
     * @return array|null Thông tin địa chỉ hoặc null
     */
    public function findById(int $addressId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT address_id, user_id, province_code, ward_code, address_line, note, is_primary, is_head_office, created_at
            FROM user_addresses
            WHERE address_id = :id
        ");
        $stmt->execute(['id' => $addressId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        return $address ?: null;
    }

    /**
     * Validate mã tỉnh và phường/xã có tồn tại trong database không
     * 
     * @param string $provinceCode Mã tỉnh
     * @param string $wardCode Mã phường/xã
     * @return bool True nếu hợp lệ
     * @throws Exception Nếu mã không hợp lệ
     */
    public function validateProvinceAndWard(string $provinceCode, string $wardCode): bool
    {
        // Kiểm tra province_code tồn tại
        $stmt = $this->db->prepare('SELECT 1 FROM vn_provinces WHERE province_code = :p LIMIT 1');
        $stmt->execute(['p' => $provinceCode]);
        if (!$stmt->fetch()) {
            throw new Exception('Mã tỉnh/thành phố không tồn tại');
        }

        // Kiểm tra ward_code tồn tại và thuộc province_code
        $stmt = $this->db->prepare('SELECT 1 FROM vn_wards WHERE ward_code = :w AND province_code = :p LIMIT 1');
        $stmt->execute(['w' => $wardCode, 'p' => $provinceCode]);
        if (!$stmt->fetch()) {
            throw new Exception('Mã phường/xã không tồn tại hoặc không thuộc tỉnh/thành phố này');
        }

        return true;
    }

    /**
     * Unset tất cả địa chỉ primary của user (trước khi set primary mới)
     * 
     * @param int $userId User ID
     * @param int|null $exceptAddressId Address ID ngoại trừ (để không unset chính nó)
     * @return void
     */
    private function unsetPrimaryAddresses(int $userId, ?int $exceptAddressId = null): void
    {
        if ($exceptAddressId !== null) {
            $stmt = $this->db->prepare("
                UPDATE user_addresses 
                SET is_primary = 0 
                WHERE user_id = :uid AND address_id != :except
            ");
            $stmt->execute(['uid' => $userId, 'except' => $exceptAddressId]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE user_addresses 
                SET is_primary = 0 
                WHERE user_id = :uid
            ");
            $stmt->execute(['uid' => $userId]);
        }
    }

    /**
     * Lấy thông tin tỉnh/thành phố
     * 
     * @param string $provinceCode Mã tỉnh
     * @return array|null Thông tin tỉnh
     */
    public function getProvinceInfo(string $provinceCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT province_code, province_name
            FROM vn_provinces
            WHERE province_code = :p
        ");
        $stmt->execute(['p' => $provinceCode]);
        $province = $stmt->fetch(PDO::FETCH_ASSOC);

        return $province ?: null;
    }

    /**
     * Lấy thông tin phường/xã
     * 
     * @param string $wardCode Mã phường/xã
     * @return array|null Thông tin phường/xã
     */
    public function getWardInfo(string $wardCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT ward_code, ward_name, province_code
            FROM vn_wards
            WHERE ward_code = :w
        ");
        $stmt->execute(['w' => $wardCode]);
        $ward = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ward ?: null;
    }

    /**
     * Lấy địa chỉ đầy đủ với tên tỉnh và phường/xã
     * 
     * @param int $addressId Address ID
     * @return array|null Địa chỉ với province_name và ward_name
     */
    public function getFullAddress(int $addressId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ua.address_id,
                ua.user_id,
                ua.address_line,
                ua.note,
                ua.is_primary,
                ua.is_head_office,
                ua.created_at,
                vp.province_code,
                vp.province_name,
                vw.ward_code,
                vw.ward_name
            FROM user_addresses ua
            LEFT JOIN vn_provinces vp ON ua.province_code = vp.province_code
            LEFT JOIN vn_wards vw ON ua.ward_code = vw.ward_code
            WHERE ua.address_id = :id
        ");
        $stmt->execute(['id' => $addressId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        return $address ?: null;
    }
}
