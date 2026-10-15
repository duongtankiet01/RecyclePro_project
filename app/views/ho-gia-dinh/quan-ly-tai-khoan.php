<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tài khoản của tôi - Hộ gia đình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <!-- Navbar Household with Tài khoản active -->
    <?php $activeTab = 'tai-khoan'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="fas fa-user-cog me-2"></i>
                        <strong>Thông tin tài khoản</strong>
                    </div>
                    <div class="card-body">
                        <form id="householdProfileForm" class="needs-validation" data-address-group novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="fullName" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="fullName" placeholder="Đang tải..." required>
                                    <div class="invalid-feedback">Vui lòng nhập họ tên.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email (không đổi)</label>
                                    <input type="email" class="form-control" id="email" placeholder="Đang tải..." disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="tel" pattern="^0[0-9]{9}$" class="form-control" id="phone" placeholder="Đang tải..." required>
                                    <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="points" class="form-label">Điểm hiện có</label>
                                    <input type="text" class="form-control" id="points" value="0" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="province" class="form-label">Tỉnh/Thành</label>
                                    <select id="province" class="form-select" data-address="province" data-field="province_code" required></select>
                                    <div class="invalid-feedback">Chọn tỉnh/thành.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="ward" class="form-label">Xã/Phường</label>
                                    <select id="ward" class="form-select" data-address="ward" data-field="ward_code" required></select>
                                    <div class="invalid-feedback">Chọn xã/phường.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="addressDetail" class="form-label">Địa chỉ chi tiết</label>
                                    <input type="text" class="form-control" id="addressDetail" placeholder="Số nhà, đường..." required>
                                    <div class="invalid-feedback">Nhập địa chỉ chi tiết.</div>
                                </div>
                                <div class="col-12">
                                    <label for="note" class="form-label">Ghi chú (không bắt buộc)</label>
                                    <textarea id="note" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <hr class="my-4" />
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0"><i class="fas fa-key me-2 text-warning"></i>Đổi mật khẩu</h6>
                                <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#householdPasswordCollapse" aria-expanded="false" aria-controls="householdPasswordCollapse">
                                    <i class="fas fa-lock me-1"></i>Bật form đổi mật khẩu
                                </button>
                            </div>
                            <div class="collapse" id="householdPasswordCollapse">
                                <p class="text-muted small mb-3">Chỉ cần điền khi bạn muốn đổi mật khẩu.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="currentPassword" class="form-label">Mật khẩu hiện tại</label>
                                        <input type="password" class="form-control" id="currentPassword" minlength="8">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="newPassword" class="form-label">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="newPassword" minlength="8">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="confirmPassword" class="form-label">Nhập lại mật khẩu</label>
                                        <input type="password" class="form-control" id="confirmPassword" minlength="8">
                                        <div class="invalid-feedback" id="passwordMismatch">Mật khẩu không khớp.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary"><i class="fas fa-undo me-2"></i>Hủy</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast container (dynamic) -->
        <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1080"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        (function() {
            const form = document.getElementById('householdProfileForm');
            
            // Load thông tin user khi trang load
            async function loadUserProfile() {
                try {
                    const response = await fetch('index.php?controller=api_auth&action=profile');
                    const result = await response.json();
                    
                    if (result.success && result.data.user) {
                        const user = result.data.user;
                        
                        // Điền thông tin cơ bản
                        document.getElementById('fullName').value = user.full_name || '';
                        document.getElementById('email').value = user.email || '';
                        document.getElementById('phone').value = user.phone_number || '';
                        
                        // Điền điểm (nếu là household)
                        if (user.current_points !== undefined) {
                            document.getElementById('points').value = user.current_points.toLocaleString('vi-VN');
                        }
                        
                        // Điền địa chỉ (nếu có)
                        if (user.address) {
                            const addr = user.address;
                            
                            if (addr.province_code) {
                                try {
                                    // Try external API, fallback to local
                                    let provinces = [];
                                    try {
                                        const provincesRes = await fetch('https://provinces.open-api.vn/api/?depth=1');
                                        provinces = await provincesRes.json();
                                    } catch (extError) {
                                        const localRes = await fetch('/KLTN_2025/index.php?controller=api_address&action=provinces');
                                        provinces = await localRes.json();
                                    }
                                    
                                    const provinceSelect = document.getElementById('province');
                                    
                                    // Populate dropdown tỉnh
                                    provinceSelect.innerHTML = '<option value="">Chọn tỉnh/thành...</option>';
                                    provinces.forEach(prov => {
                                        const opt = document.createElement('option');
                                        opt.value = prov.code;
                                        opt.textContent = prov.name;
                                        provinceSelect.appendChild(opt);
                                    });
                                    
                                    // Set giá trị đã lưu
                                    provinceSelect.value = addr.province_code;
                                    
                                    // Load phường/xã theo tỉnh
                                    if (addr.ward_code) {
                                        let provinceDetail = null;
                                        try {
                                            const wardsRes = await fetch(`https://provinces.open-api.vn/api/p/${addr.province_code}?depth=3`);
                                            provinceDetail = await wardsRes.json();
                                        } catch (extError) {
                                            const localRes = await fetch(`/KLTN_2025/index.php?controller=api_address&action=province_detail&code=${addr.province_code}`);
                                            provinceDetail = await localRes.json();
                                        }
                                        const wards = (provinceDetail.districts || []).flatMap(d => d.wards || []);
                                        
                                        const wardSelect = document.getElementById('ward');
                                        
                                        // Populate dropdown xã/phường
                                        wardSelect.innerHTML = '<option value="">Chọn xã/phường...</option>';
                                        wards.forEach(ward => {
                                            const opt = document.createElement('option');
                                            opt.value = ward.code;
                                            opt.textContent = ward.name;
                                            wardSelect.appendChild(opt);
                                        });
                                        
                                        // Set giá trị đã lưu
                                        wardSelect.value = addr.ward_code;
                                    }
                                } catch (error) {
                                    console.error('❌ Lỗi khi load địa chỉ:', error);
                                    showToast('Không thể tải danh sách tỉnh/xã từ API', 'warning');
                                }
                            }
                            
                            document.getElementById('addressDetail').value = addr.address_line || '';
                            if (document.getElementById('note')) {
                                document.getElementById('note').value = addr.note || '';
                            }
                        }
                    } else {
                        showToast('Không thể tải thông tin tài khoản', 'warning');
                    }
                } catch (error) {
                    console.error('Error loading profile:', error);
                    showToast('Lỗi khi tải thông tin tài khoản', 'danger');
                }
            }
            
            // Load profile khi trang load
            loadUserProfile();
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                // Bootstrap validation
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }
                // Password change validation (only if any password field filled)
                const current = document.getElementById('currentPassword').value.trim();
                const newer = document.getElementById('newPassword').value.trim();
                const confirm = document.getElementById('confirmPassword').value.trim();
                if (current || newer || confirm) {
                    if (!current || !newer || !confirm) {
                        showToast('Vui lòng điền đủ 3 trường mật khẩu nếu muốn đổi.', 'warning');
                        return;
                    }
                    if (newer !== confirm) {
                        document.getElementById('confirmPassword').classList.add('is-invalid');
                        showToast('Mật khẩu xác nhận không khớp.', 'danger');
                        return;
                    } else {
                        document.getElementById('confirmPassword').classList.remove('is-invalid');
                    }
                }

                // Prepare data to send
                const formData = {
                    full_name: document.getElementById('fullName').value.trim(),
                    phone_number: document.getElementById('phone').value.trim(),
                    province_code: document.getElementById('province').value,
                    ward_code: document.getElementById('ward').value,
                    address_line: document.getElementById('addressDetail').value.trim(),
                    note: document.getElementById('note').value.trim()
                };

                // Add password change data if provided
                if (current) {
                    formData.current_password = current;
                    formData.new_password = newer;
                    formData.confirm_password = confirm;
                }

                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...';

                try {
                    const response = await fetch('index.php?controller=api_auth&action=update_profile', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message || 'Cập nhật thành công!', 'success');
                        form.classList.remove('was-validated');
                        
                        // Clear password fields
                        document.getElementById('currentPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                        
                        // Update displayed data with the returned user data
                        if (result.data && result.data.user) {
                            const user = result.data.user;
                            // Update points display if changed
                            if (user.points !== undefined) {
                                document.getElementById('points').value = user.points;
                            }
                        }
                    } else {
                        showToast(result.message || 'Có lỗi xảy ra khi cập nhật', 'danger');
                    }
                } catch (error) {
                    console.error('Error updating profile:', error);
                    showToast('Không thể kết nối đến server', 'danger');
                } finally {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });

            function showToast(message, type='info') {
                const id = 't' + Date.now();
                const html = `\n<div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">\n  <div class="d-flex">\n    <div class="toast-body"><i class="fas fa-circle-info me-2"></i>${message}</div>\n    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>\n  </div>\n</div>`;
                const container = document.getElementById('toastContainer');
                container.insertAdjacentHTML('beforeend', html);
                const toastEl = document.getElementById(id);
                const t = new bootstrap.Toast(toastEl, { delay: 3000 });
                t.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            }
        })();
    </script>
</body>
</html>
