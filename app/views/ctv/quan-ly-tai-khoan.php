<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tài khoản CTV - Cộng tác viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <!-- Navbar CTV with Tài khoản active -->
    <?php $activeTab = 'tai-khoan'; require __DIR__ . '/_navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="fas fa-user-edit me-2"></i>
                        <strong>Thông tin cá nhân CTV</strong>
                    </div>
                    <div class="card-body">
                        <form id="collectorProfileForm" class="needs-validation" data-address-group novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="fullName" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="fullName" placeholder="Đang tải..." required>
                                    <div class="invalid-feedback">Nhập họ tên.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" class="form-control" placeholder="Đang tải..." disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="tel" id="phone" class="form-control" pattern="^0[0-9]{9}$" placeholder="Đang tải..." required>
                                    <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="vehicleType" class="form-label">Phương tiện</label>
                                    <select id="vehicleType" class="form-select" required>
                                        <option value="">Chọn...</option>
                                        <option value="motorbike">Xe máy</option>
                                        <option value="three_wheeler">Xe ba gác</option>
                                        <option value="truck">Xe bán tải</option>
                                        <option value="other">Khác</option>
                                    </select>
                                    <div class="invalid-feedback">Chọn phương tiện.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="maxCapacity" class="form-label">Tải tối đa (kg)</label>
                                    <input type="number" min="5" max="1000" step="5" id="maxCapacity" class="form-control" placeholder="Đang tải..." required>
                                    <div class="invalid-feedback">Nhập tải hợp lệ.</div>
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
                                    <input type="text" id="addressDetail" class="form-control" placeholder="Số nhà, đường..." required>
                                    <div class="invalid-feedback">Nhập địa chỉ.</div>
                                </div>
                                <div class="col-12">
                                    <label for="serviceArea" class="form-label">Khu vực hoạt động (ghi chú)</label>
                                    <textarea id="serviceArea" rows="2" class="form-control" placeholder="Ví dụ: Ưu tiên các quận nội thành, nhận đơn sau 18h..."></textarea>
                                </div>
                            </div>
                            <hr class="my-4" />
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0"><i class="fas fa-key me-2 text-warning"></i>Đổi mật khẩu</h6>
                                <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collectorPasswordCollapse" aria-expanded="false" aria-controls="collectorPasswordCollapse">
                                    <i class="fas fa-lock me-1"></i>Bật form đổi mật khẩu
                                </button>
                            </div>
                            <div class="collapse" id="collectorPasswordCollapse">
                                <p class="text-muted small mb-3">Chỉ cần điền khi bạn thực sự muốn đổi mật khẩu.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="currentPassword">Mật khẩu hiện tại</label>
                                        <input type="password" id="currentPassword" class="form-control" minlength="8">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="newPassword">Mật khẩu mới</label>
                                        <input type="password" id="newPassword" class="form-control" minlength="8">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="confirmPassword">Nhập lại mật khẩu</label>
                                        <input type="password" id="confirmPassword" class="form-control" minlength="8">
                                        <div class="invalid-feedback">Không khớp.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary"><i class="fas fa-undo me-2"></i>Hủy</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Lưu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1080"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        (function(){
            const form = document.getElementById('collectorProfileForm');
            
            // Load thông tin user khi trang load
            async function loadUserProfile() {
                try {
                    const response = await fetch('index.php?controller=api_auth&action=profile');
                    const result = await response.json();
                    
                    console.log('CTV Profile loaded:', result);
                    
                    if (result.success && result.data.user) {
                        const user = result.data.user;
                        
                        console.log('CTV User data:', user);
                        console.log('CTV Address data:', user.address);
                        
                        // Điền thông tin cơ bản
                        document.getElementById('fullName').value = user.full_name || '';
                        document.getElementById('email').value = user.email || '';
                        document.getElementById('phone').value = user.phone_number || '';
                        
                        // Điền thông tin collector
                        if (user.vehicle_type) {
                            document.getElementById('vehicleType').value = user.vehicle_type;
                        }
                        
                        if (user.max_capacity) {
                            document.getElementById('maxCapacity').value = user.max_capacity;
                        }
                        
                        // Điền địa chỉ (nếu có) - sử dụng data attributes để main.js xử lý
                        if (user.address && user.address.province_code) {
                            const provinceSelect = document.getElementById('province');
                            const wardSelect = document.getElementById('ward');
                            
                            // Set prefill data attributes
                            provinceSelect.dataset.prefillCode = user.address.province_code;
                            wardSelect.dataset.prefillCode = user.address.ward_code;
                            
                            document.getElementById('addressDetail').value = user.address.address_line || '';
                            if (document.getElementById('serviceArea')) {
                                document.getElementById('serviceArea').value = user.address.note || '';
                            }
                        }
                    } else {
                        toast('Không thể tải thông tin tài khoản', 'warning');
                    }
                } catch (error) {
                    console.error('Error loading profile:', error);
                    toast('Lỗi khi tải thông tin tài khoản', 'danger');
                }
            }
            
            // Load profile khi trang load
            loadUserProfile();
            
            form.addEventListener('submit', async function(e){
                e.preventDefault();
                if(!form.checkValidity()) { e.stopPropagation(); form.classList.add('was-validated'); return; }
                const c = currentPassword.value.trim(), n = newPassword.value.trim(), cf = confirmPassword.value.trim();
                if(c||n||cf){
                    if(!c||!n||!cf){ toast('Điền đủ 3 trường mật khẩu nếu muốn đổi.', 'warning'); return; }
                    if(n!==cf){ confirmPassword.classList.add('is-invalid'); toast('Mật khẩu xác nhận không khớp.', 'danger'); return; } else {confirmPassword.classList.remove('is-invalid');}
                }

                // Prepare data to send
                const formData = {
                    full_name: document.getElementById('fullName').value.trim(),
                    phone_number: document.getElementById('phone').value.trim(),
                    province_code: document.getElementById('province').value,
                    ward_code: document.getElementById('ward').value,
                    address_line: document.getElementById('addressDetail').value.trim(),
                    note: document.getElementById('serviceArea').value.trim(),
                    vehicle_type: document.getElementById('vehicleType').value
                };

                // Add password change data if provided
                if (c) {
                    formData.current_password = c;
                    formData.new_password = n;
                    formData.confirm_password = cf;
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
                        toast(result.message || 'Cập nhật thành công!', 'success');
                        form.classList.remove('was-validated');
                        
                        // Clear password fields
                        document.getElementById('currentPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                    } else {
                        toast(result.message || 'Có lỗi xảy ra khi cập nhật', 'danger');
                    }
                } catch (error) {
                    console.error('Error updating profile:', error);
                    toast('Không thể kết nối đến server', 'danger');
                } finally {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
            function toast(msg,type='info'){
                const id='t'+Date.now();
                const html=`<div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert"><div class="d-flex"><div class="toast-body"><i class='fas fa-circle-info me-2'></i>${msg}</div><button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast'></button></div></div>`;
                toastContainer.insertAdjacentHTML('beforeend', html); const el=document.getElementById(id); const t=new bootstrap.Toast(el,{delay:3000}); t.show(); el.addEventListener('hidden.bs.toast',()=>el.remove());
            }
        })();
    </script>
</body>
</html>
