<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Đăng ký - RecyclePro'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, rgba(25,135,84,0.15) 0%, rgba(13,110,253,0.12) 100%);
        }

        .auth-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .auth-card {
            max-width: 1080px;
            width: 100%;
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 60px -40px rgba(15,118,110,0.45);
        }

        .auth-header {
            background: linear-gradient(135deg, #146c43, #0d6efd);
            color: #fff;
            padding: 2.5rem;
        }

        .auth-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .auth-body {
            padding: 2.5rem;
            background: #fff;
        }

        .input-group-text {
            background: rgba(25,135,84,0.12);
            border: 1px solid rgba(0,0,0,0.08);
            border-right: none;
            color: #198754;
        }

        .input-group .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-white text-success rounded-pill px-3 py-2 fw-semibold">RecyclePro</span>
                        <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-semibold">Bước đầu tái chế</span>
                    </div>
                    <h1 class="mb-2">Tạo tài khoản RecyclePro</h1>
                    <p class="mb-0">Chọn vai trò phù hợp và hoàn tất thông tin cơ bản để bắt đầu.</p>
                </div>
                <div class="text-lg-end">
                    <a href="index.php?controller=auth&action=dangNhap" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại đăng nhập
                    </a>
                </div>
            </div>
            <div class="auth-body">
                <ul class="nav nav-pills justify-content-center gap-2 mb-4" id="registerRoleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="register-household-tab" data-bs-toggle="pill" data-bs-target="#register-household" type="button" role="tab" aria-controls="register-household" aria-selected="true">
                            <i class="fas fa-home me-1"></i>Hộ gia đình
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-business-tab" data-bs-toggle="pill" data-bs-target="#register-business" type="button" role="tab" aria-controls="register-business" aria-selected="false">
                            <i class="fas fa-building me-1"></i>Doanh nghiệp
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-collector-tab" data-bs-toggle="pill" data-bs-target="#register-collector" type="button" role="tab" aria-controls="register-collector" aria-selected="false">
                            <i class="fas fa-recycle me-1"></i>CTV / Thu gom
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="registerRoleContent">
                    <div class="tab-pane fade show active" id="register-household" role="tabpanel" aria-labelledby="register-household-tab">
                        <form class="row g-3 needs-validation auth-form" data-actor="households" data-mode="register" data-address-group="household" novalidate>
                            <input type="hidden" data-field="role" value="household">
                            <div class="col-12">
                                <label for="householdName" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="householdName" data-field="full_name" required>
                            </div>
                            <div class="col-12">
                                <label for="householdPhone" class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" id="householdPhone" data-field="phone_number"
                                       pattern="^0[0-9]{9}$"
                                       maxlength="10"
                                       required>
                                <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="householdPassword" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="householdPassword" data-field="password"
                                       minlength="8"
                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$"
                                       required>
                                <div class="form-text">Tối thiểu 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt (@$!%*?&#)</div>
                                <div class="invalid-feedback">Mật khẩu không đủ mạnh.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="householdPasswordConfirm" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="householdPasswordConfirm" required>
                                <div class="invalid-feedback">Mật khẩu xác nhận không khớp.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="householdProvince" class="form-label">Tỉnh/Thành</label>
                                <select class="form-select" id="householdProvince" data-field="province_code" data-address="province" required></select>
                                <input type="hidden" data-field="province_name" id="householdProvinceName">
                            </div>
                            <div class="col-md-6">
                                <label for="householdWard" class="form-label">Xã/Phường</label>
                                <select class="form-select" id="householdWard" data-field="ward_code" data-address="ward" required></select>
                                <input type="hidden" data-field="ward_name" id="householdWardName">
                            </div>
                            <div class="col-12">
                                <label for="householdAddress" class="form-label">Địa chỉ chi tiết</label>
                                <input type="text" class="form-control" id="householdAddress" data-field="address_line" placeholder="Số nhà, đường, khu..." required>
                            </div>
                            <div class="col-12">
                                <label for="householdEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="householdEmail" data-field="email" placeholder="ten@email.com" required>
                            </div>
                            <div class="col-12 d-grid mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>Đăng ký hộ gia đình
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="register-business" role="tabpanel" aria-labelledby="register-business-tab">
                        <form class="row g-3 needs-validation auth-form" data-actor="businesses" data-mode="register" data-address-group="business" novalidate>
                            <input type="hidden" data-field="role" value="business">
                            <div class="col-12">
                                <label for="businessCompany" class="form-label">Tên doanh nghiệp</label>
                                <input type="text" class="form-control" id="businessCompany" data-field="company_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="businessContact" class="form-label">Người liên hệ</label>
                                <input type="text" class="form-control" id="businessContact" data-field="contact_person" placeholder="Tên người đại diện">
                            </div>
                            <div class="col-md-6">
                                <label for="businessTax" class="form-label">Mã số thuế</label>
                                <input type="text" class="form-control" id="businessTax" data-field="tax_code" placeholder="Tuỳ chọn">
                            </div>
                            <div class="col-md-6">
                                <label for="businessPhone" class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" id="businessPhone" data-field="phone_number"
                                       pattern="^0[0-9]{9}$"
                                       maxlength="10"
                                       required>
                                <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="businessEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="businessEmail" data-field="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="businessLicense" class="form-label">Giấy phép kinh doanh</label>
                                <input type="text" class="form-control" id="businessLicense" data-field="business_license" placeholder="Số giấy phép (tuỳ chọn)">
                            </div>
                            <div class="col-md-6">
                                <label for="businessProvince" class="form-label">Tỉnh/Thành</label>
                                <select class="form-select" id="businessProvince" data-field="province_code" data-address="province" required></select>
                                <input type="hidden" data-field="province_name" id="businessProvinceName">
                            </div>
                            <div class="col-md-6">
                                <label for="businessWard" class="form-label">Xã/Phường</label>
                                <select class="form-select" id="businessWard" data-field="ward_code" data-address="ward" required></select>
                                <input type="hidden" data-field="ward_name" id="businessWardName">
                            </div>
                            <div class="col-12">
                                <label for="businessAddress" class="form-label">Địa chỉ trụ sở</label>
                                <input type="text" class="form-control" id="businessAddress" data-field="address_line" placeholder="Số nhà, đường, khu..." required>
                            </div>
                            <div class="col-md-6">
                                <label for="businessPassword" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="businessPassword" data-field="password"
                                       minlength="8"
                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$"
                                       required>
                                <div class="form-text">Tối thiểu 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt (@$!%*?&#)</div>
                                <div class="invalid-feedback">Mật khẩu không đủ mạnh.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="businessPasswordConfirm" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="businessPasswordConfirm" required>
                                <div class="invalid-feedback">Mật khẩu xác nhận không khớp.</div>
                            </div>
                            <div class="col-12 d-grid mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-building me-2"></i>Đăng ký doanh nghiệp
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="register-collector" role="tabpanel" aria-labelledby="register-collector-tab">
                        <form class="row g-3 needs-validation auth-form" data-actor="collectors" data-mode="register" data-address-group="collector" novalidate>
                            <input type="hidden" data-field="role" value="collector">
                            <div class="col-12">
                                <label for="collectorName" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="collectorName" data-field="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="collectorPhone" class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" id="collectorPhone" data-field="phone_number"
                                       pattern="^0[0-9]{9}$"
                                       maxlength="10"
                                       required>
                                <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="collectorEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="collectorEmail" data-field="email" placeholder="ten@email.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="collectorCitizen" class="form-label">CCCD/CMND</label>
                                <input type="text" class="form-control" id="collectorCitizen" data-field="citizen_id" placeholder="Số giấy tờ tuỳ thân">
                            </div>
                            <div class="col-md-6">
                                <label for="collectorVehicle" class="form-label">Phương tiện thu gom</label>
                                <select class="form-select" id="collectorVehicle" data-field="vehicle_type">
                                    <option value="">-- Chọn phương tiện --</option>
                                    <option value="motorbike">Xe máy</option>
                                    <option value="three_wheeler">Xe ba gác</option>
                                    <option value="truck">Xe tải nhỏ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="collectorProvince" class="form-label">Tỉnh/Thành hoạt động</label>
                                <select class="form-select" id="collectorProvince" data-field="province_code" data-address="province" required></select>
                                <input type="hidden" data-field="province_name" id="collectorProvinceName">
                            </div>
                            <div class="col-md-6">
                                <label for="collectorWard" class="form-label">Xã/Phường hoạt động</label>
                                <select class="form-select" id="collectorWard" data-field="ward_code" data-address="ward" required></select>
                                <input type="hidden" data-field="ward_name" id="collectorWardName">
                            </div>
                            <div class="col-12">
                                <label for="collectorAddress" class="form-label">Địa chỉ cụ thể</label>
                                <input type="text" class="form-control" id="collectorAddress" data-field="address_line" placeholder="Số nhà, đường, khu vực hoạt động..." required>
                            </div>
                            <div class="col-md-6">
                                <label for="collectorPassword" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="collectorPassword" data-field="password"
                                       minlength="8"
                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$"
                                       required>
                                <div class="form-text">Tối thiểu 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt (@$!%*?&#)</div>
                                <div class="invalid-feedback">Mật khẩu không đủ mạnh.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="collectorPasswordConfirm" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="collectorPasswordConfirm" required>
                                <div class="invalid-feedback">Mật khẩu xác nhận không khớp.</div>
                            </div>
                            <div class="col-12 d-grid mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-recycle me-2"></i>Đăng ký cộng tác viên
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>

