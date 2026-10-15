<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/UserAddress.php';

class ApiAuthController extends BaseController
{
    private PDO $db;
    private User $userModel;
    private UserAddress $addressModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Database::getInstance() already returns a PDO instance
        $this->db = Database::getInstance();
        $this->userModel = new User($this->db);
        $this->addressModel = new UserAddress($this->db);
        header('Content-Type: application/json; charset=UTF-8');
    }

    /**
     * API endpoint đăng ký unified
     * POST /index.php?controller=api_auth&action=register
     */
    public function register(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // Chuẩn hóa data format
            if (isset($data['phone']) && !isset($data['phone_number'])) {
                $data['phone_number'] = $data['phone'];
            }

            // Delegate toàn bộ logic sang User model
            $user = $this->userModel->createWithProfileAndAddress($data);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];

            $this->jsonSuccess('Đăng ký thành công', ['user' => $user]);

        } catch (PDOException $e) {
            $mapped = $this->mapDuplicateMessage($e);
            if ($mapped !== null) {
                $this->jsonError($mapped['message'], $mapped['code']);
                return;
            }
            $msg = $e->getMessage();
            if (str_contains($msg, 'cannot be null')) {
                $this->jsonError('Thiếu dữ liệu bắt buộc (NOT NULL)', 422);
                return;
            }
            if (str_contains($msg, 'foreign key constraint fails')) {
                $this->jsonError('Tham chiếu dữ liệu không tồn tại (foreign key)', 422);
                return;
            }
            $this->jsonError('Lỗi CSDL: ' . $msg, 500);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * API endpoint đăng nhập unified
     * POST /index.php?controller=api_auth&action=login
     */
    public function login(): void
    {
        try {
            $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
            $data = $this->getJsonInput();
            $email = mb_strtolower(trim($data['email'] ?? ''));
            $password = $data['password'] ?? '';

            if ($email === '' || $password === '') {
                throw new Exception('Thiếu email hoặc mật khẩu');
            }

            // Delegate sang User model
            $result = $this->userModel->attemptLogin($email, $password);

            if (!$result) {
                $this->jsonError('Email hoặc mật khẩu không đúng', 401);
                return;
            }

            // Set session
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_role'] = $result['role'];
            $_SESSION['user_email'] = $result['email'];
            $_SESSION['user_name'] = $result['name'];

            // Cho phép redirect server-side nếu request là form POST thường hoặc có tham số force_redirect=1
            $forceRedirect = isset($_REQUEST['force_redirect']) && $_REQUEST['force_redirect'] == '1';
            $wantsRedirect = !$isJson || $forceRedirect;

            if ($wantsRedirect) {
                header('Location: ' . $result['redirect']);
                // Äá»“ng thá»i tráº£ JSON phÃ²ng trÆ°á»ng há»£p fetch mong Ä‘á»£i
                echo json_encode([
                    'success' => true,
                    'title' => 'Đăng nhập thành công',
                    'message' => 'Đăng nhập thành công',
                    'user' => $result,
                    'redirect' => $result['redirect']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $this->jsonSuccess('Đăng nhập thành công', [
                'user' => $result,
                'redirect' => $result['redirect'],
            ]);

        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * API endpoint lấy thông tin user hiện tại
     * GET /index.php?controller=api_auth&action=profile
     */
    public function profile(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('Chưa đăng nhập', 401);
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            
            // Delegate sang User model
            $userInfo = $this->userModel->getFullProfile($userId);

            if (!$userInfo) {
                $this->jsonError('Không tìm thấy thông tin người dùng', 404);
                return;
            }

            $this->jsonSuccess('Lấy thông tin thành công', ['user' => $userInfo]);

        } catch (PDOException $e) {
            error_log('Profile API DB Error: ' . $e->getMessage());
            $this->jsonError('Lỗi truy vấn database: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('Profile API Error: ' . $e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 400);
        }
    }

    /**
     * API endpoint cập nhật thông tin user
     * POST /index.php?controller=api_auth&action=update_profile
     */
    public function update_profile(): void
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonError('Chưa đăng nhập', 401);
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $data = $this->getJsonInput();

            // Validation
            if (empty($data['full_name']) || trim($data['full_name']) === '') {
                $this->jsonError('Họ tên không được để trống', 422);
                return;
            }

            if (empty($data['phone_number']) || trim($data['phone_number']) === '') {
                $this->jsonError('Số điện thoại không được để trống', 422);
                return;
            }

            // Validate phone number format (same như lúc đăng ký)
            $rawPhone = trim($data['phone_number']);
            if (!preg_match('/^0[0-9]{9}$/', $rawPhone)) {
                $this->jsonError('Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.', 422);
                return;
            }
            $phone = $rawPhone;

            // Không cho trùng số điện thoại với user khác
            $phoneStmt = $this->db->prepare("SELECT user_id FROM users WHERE phone_number = :phone LIMIT 1");
            $phoneStmt->execute([':phone' => $phone]);
            $existingOwner = $phoneStmt->fetchColumn();
            if ($existingOwner && (int)$existingOwner !== $userId) {
                $this->jsonError('Số điện thoại đã tồn tại', 409);
                return;
            }

            $userRole = $_SESSION['user_role'] ?? '';

            // Validate address nếu cung cấp
            if (!empty($data['province_code']) && empty($data['ward_code'])) {
                $this->jsonError('Vui lòng chọn phường/xã', 422);
                return;
            }
            if (!empty($data['province_code']) && !empty($data['ward_code'])) {
                $addressLine = trim($data['address_line'] ?? '');
                if ($addressLine === '') {
                    $this->jsonError('Vui lòng nhập địa chỉ chi tiết.', 422);
                    return;
                }
            }

            if ($userRole === 'business' && empty(trim($data['company_name'] ?? ''))) {
                $this->jsonError('Tên doanh nghiệp không được để trống', 422);
                return;
            }
            if ($userRole === 'collector' && empty(trim($data['vehicle_type'] ?? ''))) {
                $this->jsonError('Vui lòng chọn phương tiện hoạt động', 422);
                return;
            }

            $this->db->beginTransaction();

            try {
                // Cập nhật thông tin cơ bản trong bảng users
                $updateUserSql = "UPDATE users SET full_name = :full_name, phone_number = :phone_number";
                $params = [
                    ':full_name' => trim($data['full_name']),
                    ':phone_number' => $phone,
                    ':user_id' => $userId
                ];

                // Nếu có đổi mật khẩu
                if (!empty($data['current_password']) && !empty($data['new_password'])) {
                    // Kiểm tra mật khẩu hiện tại
                    $checkSql = "SELECT password_hash FROM users WHERE user_id = :user_id";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->execute([':user_id' => $userId]);
                    $currentHash = $checkStmt->fetchColumn();

                    if (!password_verify($data['current_password'], $currentHash)) {
                        throw new Exception('Mật khẩu hiện tại không đúng');
                    }

                    // Validate new password (giống đăng ký)
                    $newPassword = $data['new_password'];
                    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';
                    if (!preg_match($passwordPattern, $newPassword)) {
                        throw new Exception('Mật khẩu mới phải tối thiểu 8 ký tự và bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt (@$!%*?&#).');
                    }

                    if ($data['new_password'] !== $data['confirm_password']) {
                        throw new Exception('Mật khẩu xác nhận không khớp');
                    }

                    $updateUserSql .= ", password_hash = :password_hash";
                    $params[':password_hash'] = password_hash($data['new_password'], PASSWORD_DEFAULT);
                }

                $updateUserSql .= " WHERE user_id = :user_id";
                $stmt = $this->db->prepare($updateUserSql);
                $stmt->execute($params);

                // Cập nhật địa chỉ trong bảng user_addresses
                if (!empty($data['province_code']) && !empty($data['ward_code'])) {
                    // Kiểm tra xem đã có địa chỉ chính chưa
                    $checkAddressSql = "SELECT address_id FROM user_addresses WHERE user_id = :user_id AND is_primary = 1";
                    $checkAddrStmt = $this->db->prepare($checkAddressSql);
                    $checkAddrStmt->execute([':user_id' => $userId]);
                    $addressId = $checkAddrStmt->fetchColumn();

                    $addressParams = [
                        ':user_id' => $userId,
                        ':province_code' => trim($data['province_code']),
                        ':ward_code' => trim($data['ward_code']),
                        ':address_line' => trim($data['address_line'] ?? ''),
                        ':note' => trim($data['note'] ?? '')
                    ];

                    if ($addressId) {
                        // Update existing address
                        $updateAddressSql = "UPDATE user_addresses 
                                            SET province_code = :province_code,
                                                ward_code = :ward_code,
                                                address_line = :address_line,
                                                note = :note
                                            WHERE user_id = :user_id AND is_primary = 1";
                        $addrStmt = $this->db->prepare($updateAddressSql);
                        $addrStmt->execute($addressParams);
                    } else {
                        // Insert new address
                        $insertAddressSql = "INSERT INTO user_addresses 
                                            (user_id, province_code, ward_code, address_line, note, is_primary)
                                            VALUES (:user_id, :province_code, :ward_code, :address_line, :note, 1)";
                        $addrStmt = $this->db->prepare($insertAddressSql);
                        $addrStmt->execute($addressParams);
                    }
                }

                // Cập nhật thông tin collector (nếu là CTV)
                if (!empty($data['vehicle_type'])) {
                    $checkCollectorSql = "SELECT collector_id FROM collector_profiles WHERE user_id = :user_id";
                    $checkCollStmt = $this->db->prepare($checkCollectorSql);
                    $checkCollStmt->execute([':user_id' => $userId]);
                    $collectorExists = $checkCollStmt->fetchColumn();

                    if ($collectorExists) {
                        $updateCollectorSql = "UPDATE collector_profiles SET vehicle_type = :vehicle_type WHERE user_id = :user_id";
                        $collStmt = $this->db->prepare($updateCollectorSql);
                        $collStmt->execute([
                            ':vehicle_type' => $data['vehicle_type'],
                            ':user_id' => $userId
                        ]);
                    }
                }

                // Cập nhật thông tin business (nếu là doanh nghiệp)
                if ($userRole === 'business') {
                    $checkBusinessSql = "SELECT business_id FROM business_profiles WHERE user_id = :user_id";
                    $checkBizStmt = $this->db->prepare($checkBusinessSql);
                    $checkBizStmt->execute([':user_id' => $userId]);
                    $businessExists = $checkBizStmt->fetchColumn();

                    if ($businessExists) {
                        $updateBusinessSql = "UPDATE business_profiles 
                                             SET company_name = :company_name,
                                                 tax_code = :tax_code,
                                                 contact_person = :contact_person
                                             WHERE user_id = :user_id";
                        $bizStmt = $this->db->prepare($updateBusinessSql);
                        $bizStmt->execute([
                            ':company_name' => trim($data['company_name']),
                            ':tax_code' => trim($data['tax_code'] ?? ''),
                            ':contact_person' => trim($data['full_name']),
                            ':user_id' => $userId
                        ]);
                    }
                }

                // Commit transaction
                $this->db->commit();

                // Cập nhật session nếu cần
                $_SESSION['user_name'] = trim($data['full_name']);

                // Lấy lại thông tin đã cập nhật
                $updatedUser = $this->userModel->getFullProfile($userId);

                $this->jsonSuccess('Cập nhật thông tin thành công', ['user' => $updatedUser]);

            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (PDOException $e) {
            error_log('Update Profile API DB Error: ' . $e->getMessage());
            
            // Kiểm tra lỗi duplicate phone
            $mapped = $this->mapDuplicateMessage($e);
            if ($mapped !== null) {
                $this->jsonError($mapped['message'], $mapped['code']);
                return;
            }
            
            $this->jsonError('Lỗi cập nhật database: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('Update Profile API Error: ' . $e->getMessage());
            $this->jsonError($e->getMessage(), 400);
        }
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Phân tích lỗi duplicate MySQL để trả về thông điệp rõ hơn.
     * MySQL 1062 dạng: Duplicate entry 'value' for key 'constraint'
     */
    private function mapDuplicateMessage(PDOException $e): ?array
    {
        $msg = $e->getMessage();
        $sqlState = $e->getCode();
        if (!str_contains($msg, 'Duplicate') && $sqlState !== '23000') { return null; }
        if (preg_match("/Duplicate entry '([^']+)' for key '([^']+)'/", $msg, $m)) {
            $value = $m[1] ?? '';
            $key = $m[2] ?? '';
            $map = [
                'uk_user_email' => 'Email đã tồn tại',
                'uk_user_phone' => 'Số điện thoại đã tồn tại',
                'uk_business_tax_code' => 'Mã số thuế đã tồn tại',
                'uk_collector_citizen' => 'CMND/CCCD đã tồn tại',
            ];
            $human = $map[$key] ?? 'Dữ liệu đã tồn tại hoặc không hợp lệ';
            if ((isset($_GET['debug']) && $_GET['debug'] == '1') || (!empty($_POST['debug']))) {
                $human .= ' (key='.$key.' value='.$value.')';
            }
            return ['message' => $human, 'code' => 409];
        }
        return ['message' => 'Dữ liệu đã tồn tại hoặc không hợp lệ', 'code' => 409];
    }
}


