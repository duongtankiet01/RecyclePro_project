<?php
declare(strict_types=1);

/**
 * User Model - Quản lý users và authentication
 * 
 * Xử lý:
 * - Authentication (login, register, password verification)
 * - User CRUD operations
 * - Profile management (role-specific)
 * - Session data preparation
 */
class User
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Tìm user theo email
     * 
     * @param string $email Email cần tìm (case-insensitive)
     * @return array|null Thông tin user hoặc null nếu không tồn tại
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT user_id, full_name, email, phone_number, password_hash, password_algo, role, status, created_at, last_login_at
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }

    /**
     * Tìm user theo số điện thoại
     * 
     * @param string $phone Số điện thoại
     * @return array|null Thông tin user hoặc null nếu không tồn tại
     */
    public function findByPhone(string $phone): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, full_name, email, phone_number, password_hash, role, status
                FROM users 
                WHERE phone_number = :phone
            ");
            $stmt->execute(['phone' => trim($phone)]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: null;
        } catch (PDOException $e) {
            // Nếu cột phone_number chưa tồn tại, return null
            if (str_contains($e->getMessage(), '1054')) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Kiểm tra email đã tồn tại chưa
     * 
     * @param string $email Email cần kiểm tra
     * @return bool True nếu email đã tồn tại
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        
        return (bool)$stmt->fetch();
    }

    /**
     * Kiểm tra số điện thoại đã tồn tại chưa
     * 
     * @param string $phone Số điện thoại cần kiểm tra
     * @return bool True nếu số điện thoại đã tồn tại
     */
    public function phoneExists(string $phone): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM users WHERE phone_number = :phone LIMIT 1');
            $stmt->execute(['phone' => trim($phone)]);
            
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            // Nếu cột phone_number chưa tồn tại, return false
            if (str_contains($e->getMessage(), '1054')) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Verify password của user
     * 
     * @param string $password Password cần verify
     * @param string $passwordHash Hash trong database
     * @return bool True nếu password đúng
     */
    public function verifyPassword(string $password, string $passwordHash): bool
    {
        return password_verify($password, $passwordHash);
    }

    /**
     * Tạo user mới (chỉ bảng users, không include profiles)
     * 
     * @param array $data Dữ liệu user ['full_name', 'email', 'phone_number', 'password', 'role']
     * @return int User ID vừa tạo
     * @throws Exception Nếu dữ liệu không hợp lệ
     */
    public function create(array $data): int
    {
        // Validation
        $role = trim($data['role'] ?? '');
        $email = mb_strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $fullName = trim($data['full_name'] ?? '');
        $phone = trim($data['phone_number'] ?? '');

        if ($email === '' || $password === '' || $role === '') {
            throw new Exception('Thiếu email, password, hoặc role');
        }

        if (!in_array($role, ['household', 'business', 'collector', 'admin'], true)) {
            throw new Exception('Role không hợp lệ');
        }

        // Check duplicates
        if ($this->emailExists($email)) {
            throw new Exception('Email đã tồn tại');
        }

        if ($phone !== '' && $this->phoneExists($phone)) {
            throw new Exception('Số điện thoại đã tồn tại');
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert
        $stmt = $this->db->prepare("
            INSERT INTO users (full_name, email, phone_number, password_hash, password_algo, role, status) 
            VALUES (:full_name, :email, :phone, :hash, 'bcrypt', :role, 'active')
        ");

        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Cập nhật last_login_at và last_login_ip
     * 
     * @param int $userId User ID
     * @param string $ipAddress IP address
     * @return void
     */
    public function updateLastLogin(int $userId, string $ipAddress): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login_at = NOW(), last_login_ip = :ip 
            WHERE user_id = :id
        ");
        $stmt->execute(['ip' => $ipAddress, 'id' => $userId]);
    }

    /**
     * Lấy thông tin đầy đủ của user bao gồm profile theo role và địa chỉ
     * 
     * @param int $userId User ID
     * @return array|null Thông tin user đầy đủ hoặc null nếu không tồn tại
     */
    public function getFullProfile(int $userId): ?array
    {
        // Lấy thông tin user cơ bản
        $stmt = $this->db->prepare("
            SELECT user_id, full_name, email, phone_number, role, status, created_at, last_login_at
            FROM users 
            WHERE user_id = :uid
        ");
        $stmt->execute(['uid' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $profile = [
            'id' => (int)$user['user_id'],
            'full_name' => $user['full_name'] ?? '',
            'email' => $user['email'],
            'phone_number' => $user['phone_number'] ?? '',
            'role' => $user['role'],
            'status' => $user['status'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'],
        ];

        // Lấy thông tin profile theo role
        try {
            switch ($user['role']) {
                case 'household':
                    $stmt = $this->db->prepare("SELECT * FROM household_profiles WHERE user_id = :uid");
                    $stmt->execute(['uid' => $userId]);
                    $hp = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($hp) {
                        $profile['current_points'] = isset($hp['current_points']) ? (int)$hp['current_points'] : 0;
                    } else {
                        $profile['current_points'] = 0;
                    }
                    break;

                case 'business':
                    $stmt = $this->db->prepare("SELECT * FROM business_profiles WHERE user_id = :uid");
                    $stmt->execute(['uid' => $userId]);
                    $bp = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($bp) {
                        $profile['company_name'] = $bp['company_name'] ?? '';
                        $profile['tax_code'] = $bp['tax_code'] ?? '';
                        $profile['business_license'] = $bp['business_license'] ?? '';
                        $profile['contact_person'] = $bp['contact_person'] ?? '';
                        $profile['current_credit'] = isset($bp['current_credit']) ? (float)$bp['current_credit'] : 0.00;
                    } else {
                        $profile['current_credit'] = 0.00;
                    }
                    break;

                case 'collector':
                    $stmt = $this->db->prepare("SELECT * FROM collector_profiles WHERE user_id = :uid");
                    $stmt->execute(['uid' => $userId]);
                    $cp = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($cp) {
                        $profile['citizen_id'] = $cp['citizen_id'] ?? '';
                        $profile['vehicle_type'] = $cp['vehicle_type'] ?? '';
                        $profile['wallet_balance'] = isset($cp['wallet_balance']) ? (float)$cp['wallet_balance'] : 0.00;
                        $profile['current_points'] = isset($cp['current_points']) ? (int)$cp['current_points'] : 0;
                    } else {
                        $profile['wallet_balance'] = 0.00;
                        $profile['current_points'] = 0;
                    }
                    break;
            }
        } catch (PDOException $e) {
            // Nếu có lỗi query (cột không tồn tại), bỏ qua
            error_log('User profile query error: ' . $e->getMessage());
        }

        // Lấy địa chỉ chính (is_primary = 1)
        try {
            $stmt = $this->db->prepare("
                SELECT address_id, province_code, ward_code, address_line, note
                FROM user_addresses
                WHERE user_id = :uid AND is_primary = 1
                LIMIT 1
            ");
            $stmt->execute(['uid' => $userId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($address) {
                $profile['address'] = [
                    'address_id' => (int)$address['address_id'],
                    'province_code' => $address['province_code'],
                    'ward_code' => $address['ward_code'],
                    'address_line' => $address['address_line'] ?? '',
                    'note' => $address['note'] ?? '',
                ];
            }
        } catch (PDOException $e) {
            // Bảng user_addresses chưa tồn tại - bỏ qua
            error_log('Address query error: ' . $e->getMessage());
        }

        return $profile;
    }

    /**
     * Lấy display name của user (cho business: lấy company_name, khác: full_name)
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @return string Display name
     */
    public function getDisplayName(int $userId, string $role): string
    {
        if ($role === 'business') {
            $stmt = $this->db->prepare('SELECT company_name FROM business_profiles WHERE user_id = :uid');
            $stmt->execute(['uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['company_name'])) {
                return $row['company_name'];
            }
        }

        $stmt = $this->db->prepare('SELECT full_name FROM users WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['full_name'])) {
            return $row['full_name'];
        }

        // Fallback for admin
        if ($role === 'admin') {
            return 'Administrator';
        }

        return 'Unknown User';
    }

    /**
     * Chuẩn bị dữ liệu session sau khi login thành công
     * 
     * @param array $user User data từ database
     * @return array Session data với redirect URL
     */
    public function prepareSessionData(array $user): array
    {
        $userId = (int)$user['user_id'];
        $role = $user['role'];

        // Lấy display name
        $displayName = $this->getDisplayName($userId, $role);

        // Xác định redirect URL theo role
        $redirectMap = [
            'household' => 'index.php?controller=ho_gia_dinh&action=feed',
            'business' => 'index.php?controller=doanh_nghiep&action=taoDon',
            'collector' => 'index.php?controller=ctv&action=feed',
            'admin' => 'index.php?controller=admin&action=dashboard',
        ];
        $redirect = $redirectMap[$role] ?? 'index.php';

        return [
            'id' => $userId,
            'role' => $role,
            'name' => $displayName,
            'email' => $user['email'],
            'phone_number' => $user['phone_number'] ?? '',
            'redirect' => $redirect,
        ];
    }

    /**
     * Thực hiện đăng nhập với email và password
     * 
     * @param string $email Email
     * @param string $password Password
     * @return array|null Session data nếu thành công, null nếu thất bại
     * @throws Exception Nếu tài khoản không hoạt động
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return null;
        }

        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return null;
        }

        if ($user['status'] !== 'active') {
            throw new Exception('Tài khoản không hoạt động');
        }

        // Update last login
        $this->updateLastLogin((int)$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '');

        // Prepare session data
        return $this->prepareSessionData($user);
    }

    /**
     * Tạo user với profile và address (transaction-safe)
     * 
     * @param array $data Toàn bộ dữ liệu registration
     * @return array User info với id, role, email, name, address_saved, address_error
     * @throws Exception Nếu có lỗi trong quá trình tạo
     */
    public function createWithProfileAndAddress(array $data): array
    {
        $role = trim($data['role'] ?? '');
        $email = mb_strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $phone = trim($data['phone_number'] ?? $data['phone'] ?? '');
        $fullName = trim($data['full_name'] ?? '');

        // Validation
        if ($email === '' || $password === '' || $role === '') {
            throw new Exception('Thiếu email, password, hoặc role');
        }

        if (!in_array($role, ['household', 'business', 'collector', 'admin'], true)) {
            throw new Exception('Role không hợp lệ');
        }

        // Role-specific validation
        if ($role === 'household' && $fullName === '') {
            throw new Exception('full_name là bắt buộc cho hộ gia đình');
        }
        if ($role === 'business' && empty($data['company_name'])) {
            throw new Exception('company_name là bắt buộc cho doanh nghiệp');
        }
        if ($role === 'collector' && $fullName === '') {
            throw new Exception('full_name là bắt buộc cho cộng tác viên');
        }

        // Check duplicates
        if ($this->emailExists($email)) {
            throw new Exception('Email đã tồn tại');
        }
        if ($phone !== '' && $this->phoneExists($phone)) {
            throw new Exception('Số điện thoại đã tồn tại');
        }

        // Begin transaction
        $this->db->beginTransaction();
        try {
            // Insert base user
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("
                INSERT INTO users (full_name, email, phone_number, password_hash, password_algo, role, status) 
                VALUES (:full_name, :email, :phone, :hash, 'bcrypt', :role, 'active')
            ");

            $stmt->execute([
                'full_name' => ($role === 'business' ? ($data['contact_person'] ?? $fullName) : $fullName),
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'hash' => $passwordHash,
                'role' => $role,
            ]);

            $userId = (int)$this->db->lastInsertId();

            // Create role-specific profile
            $displayName = $this->createRoleProfile($userId, $role, $data, $fullName);

            // Optional address insertion
            $addressSaved = false;
            $addressError = null;

            // Debug log để kiểm tra data
            error_log('Address data check for role=' . $role . ': province_code=' . ($data['province_code'] ?? 'empty') . ', ward_code=' . ($data['ward_code'] ?? 'empty') . ', address_line=' . ($data['address_line'] ?? 'empty'));

            if (!empty($data['province_code']) && !empty($data['ward_code']) && !empty($data['address_line'])) {
                try {
                    $isHeadOffice = ($role === 'business') ? 1 : 0;

                    $stmt = $this->db->prepare("
                        INSERT INTO user_addresses 
                        (user_id, province_code, ward_code, address_line, is_primary, is_head_office) 
                        VALUES (:uid, :province, :ward, :addr, 1, :is_head_office)
                    ");
                    $stmt->execute([
                        'uid' => $userId,
                        'province' => $data['province_code'],
                        'ward' => $data['ward_code'],
                        'addr' => $data['address_line'],
                        'is_head_office' => $isHeadOffice,
                    ]);
                    $addressSaved = true;
                    error_log('Address saved successfully for user_id=' . $userId);
                } catch (Exception $ae) {
                    $addressError = $ae->getMessage();
                    error_log('Address save error: ' . $addressError);
                }
            } else {
                error_log('Address validation failed: missing province_code, ward_code, or address_line');
            }

            $this->db->commit();

            return [
                'id' => $userId,
                'role' => $role,
                'email' => $email,
                'name' => $displayName,
                'address_saved' => $addressSaved,
                'address_error' => $addressError,
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tạo profile theo role (private helper)
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param array $data Registration data
     * @param string $fullName Full name fallback
     * @return string Display name
     * @throws Exception Nếu role không hợp lệ
     */
    private function createRoleProfile(int $userId, string $role, array $data, string $fullName): string
    {
        switch ($role) {
            case 'household':
                $stmt = $this->db->prepare("INSERT INTO household_profiles (user_id) VALUES (:uid)");
                $stmt->execute(['uid' => $userId]);
                return $fullName;

            case 'business':
                $company = $data['company_name'];
                $stmt = $this->db->prepare("
                    INSERT INTO business_profiles (user_id, company_name, tax_code, business_license, contact_person) 
                    VALUES (:uid, :company, :tax, :license, :contact)
                ");
                $stmt->execute([
                    'uid' => $userId,
                    'company' => $company,
                    'tax' => $data['tax_code'] ?? null,
                    'license' => $data['business_license'] ?? null,
                    'contact' => $data['contact_person'] ?? null,
                ]);
                return $company;

            case 'collector':
                $stmt = $this->db->prepare("
                    INSERT INTO collector_profiles (user_id, citizen_id, vehicle_type) 
                    VALUES (:uid, :citizen, :vehicle)
                ");
                $stmt->execute([
                    'uid' => $userId,
                    'citizen' => $data['citizen_id'] ?? null,
                    'vehicle' => $data['vehicle_type'] ?? null,
                ]);
                return $fullName;

            case 'admin':
                return $fullName !== '' ? $fullName : 'Administrator';

            default:
                throw new Exception('Role không hỗ trợ');
        }
    }

    /**
     * Lấy tổng số user
     */
    public function getTotalCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE status != 'deleted'");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy danh sách tất cả users (không bao gồm admin)
     */
    public function getAll(array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        $role = $options['role'] ?? '';
        $status = $options['status'] ?? '';
        $search = $options['search'] ?? '';
        
        $sql = "
            SELECT 
                user_id,
                full_name,
                email,
                phone_number,
                role,
                status,
                DATE_FORMAT(created_at, '%d/%m/%Y') as created
            FROM users
            WHERE role != 'admin'
        ";
        
        $params = [];
        
        // Lọc theo role
        if ($role !== '') {
            $sql .= " AND role = :role";
            $params['role'] = $role;
        }
        
        // Lọc theo status
        if ($status !== '') {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        // Tìm kiếm theo tên, email, phone
        if ($search !== '' && trim($search) !== '') {
            $searchValue = "%" . trim($search) . "%";
            $sql .= " AND (
                full_name LIKE :search1
                OR email LIKE :search2
                OR phone_number LIKE :search3
            )";
            $params['search1'] = $searchValue;
            $params['search2'] = $searchValue;
            $params['search3'] = $searchValue;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        // Debug
        error_log("=== USER SEARCH DEBUG ===");
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));
        error_log("Search term: [" . $search . "]");
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters với kiểu dữ liệu rõ ràng
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Results count: " . count($results));
        
        return $results;
    }

    /**
     * Cập nhật trạng thái user
     */
    public function updateStatus(int $userId, string $status): bool
    {
        $allowedStatuses = ['active', 'pending', 'suspended', 'rejected', 'deleted'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Trạng thái không hợp lệ');
        }

        $stmt = $this->db->prepare("
            UPDATE users 
            SET status = :status, updated_at = NOW()
            WHERE user_id = :user_id
        ");
        
        return $stmt->execute([
            'status' => $status,
            'user_id' => $userId
        ]);
    }

    /**
     * Admin cập nhật thông tin user (tên, SĐT, trạng thái và thông tin theo role)
     * 
     * @param int $userId
     * @param array $data
     * @return array Thông tin user đã cập nhật
     * @throws Exception
     */
    public function updateByAdmin(int $userId, array $data): array
    {
        $user = $this->getFullProfile($userId);
        if (!$user) {
            throw new Exception('Không tìm thấy tài khoản');
        }

        $allowedStatuses = ['active', 'pending', 'suspended', 'rejected', 'deleted'];
        $updateParts = [];
        $params = [':user_id' => $userId];

        if (isset($data['full_name'])) {
            $fullName = trim($data['full_name']);
            if ($fullName === '') {
                throw new Exception('Họ tên không được để trống');
            }
            $updateParts[] = 'full_name = :full_name';
            $params[':full_name'] = $fullName;
        }

        if (isset($data['status'])) {
            $status = $data['status'];
            if (!in_array($status, $allowedStatuses, true)) {
                throw new Exception('Trạng thái không hợp lệ');
            }
            $updateParts[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (isset($data['phone_number'])) {
            $phone = trim($data['phone_number']);
            if ($phone === '') {
                throw new Exception('Số điện thoại không được để trống');
            }
            if (!preg_match('/^0[0-9]{9}$/', $phone)) {
                throw new Exception('Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.');
            }
            $stmt = $this->db->prepare("
                SELECT user_id FROM users 
                WHERE phone_number = :phone AND user_id != :user_id
                LIMIT 1
            ");
            $stmt->execute([
                ':phone' => $phone,
                ':user_id' => $userId
            ]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception('Số điện thoại đã tồn tại');
            }
            $updateParts[] = 'phone_number = :phone_number';
            $params[':phone_number'] = $phone;
        }

        if (!empty($updateParts)) {
            $sql = "UPDATE users SET " . implode(', ', $updateParts) . ", updated_at = NOW() WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        // Role-specific updates
        switch ($user['role']) {
            case 'business':
                $companyName = trim($data['company_name'] ?? '');
                if ($companyName === '') {
                    throw new Exception('Tên doanh nghiệp không được để trống');
                }
                $taxCode = trim($data['tax_code'] ?? '');
                $contactPerson = trim($data['contact_person'] ?? '');

                $exists = $this->recordExists('business_profiles', $userId);
                if ($exists) {
                    $stmt = $this->db->prepare("
                        UPDATE business_profiles
                        SET company_name = :company_name,
                            tax_code = :tax_code,
                            contact_person = :contact_person
                        WHERE user_id = :user_id
                    ");
                } else {
                    $stmt = $this->db->prepare("
                        INSERT INTO business_profiles (user_id, company_name, tax_code, contact_person)
                        VALUES (:user_id, :company_name, :tax_code, :contact_person)
                    ");
                }
                $stmt->execute([
                    ':user_id' => $userId,
                    ':company_name' => $companyName,
                    ':tax_code' => $taxCode !== '' ? $taxCode : null,
                    ':contact_person' => $contactPerson !== '' ? $contactPerson : null,
                ]);
                break;

            case 'collector':
                $vehicleType = trim($data['vehicle_type'] ?? '');
                if ($vehicleType === '') {
                    throw new Exception('Vui lòng chọn phương tiện hoạt động');
                }
                $exists = $this->recordExists('collector_profiles', $userId);
                if ($exists) {
                    $stmt = $this->db->prepare("
                        UPDATE collector_profiles
                        SET vehicle_type = :vehicle_type
                        WHERE user_id = :user_id
                    ");
                } else {
                    $stmt = $this->db->prepare("
                        INSERT INTO collector_profiles (user_id, vehicle_type)
                        VALUES (:user_id, :vehicle_type)
                    ");
                }
                $stmt->execute([
                    ':user_id' => $userId,
                    ':vehicle_type' => $vehicleType
                ]);
                break;
        }

        return $this->getFullProfile($userId);
    }

    private function recordExists(string $table, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$table} WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        return (bool)$stmt->fetchColumn();
    }
}
