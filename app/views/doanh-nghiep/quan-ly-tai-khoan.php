<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tài khoản doanh nghiệp - RecyclePro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <?php $activeTab = 'tai-khoan'; require __DIR__ . '/_navbar.php'; ?>

  <div class="container mt-4 mb-5">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-building me-2"></i>
            <strong>Hồ sơ doanh nghiệp</strong>
          </div>
          <div class="card-body">
            <form id="businessProfileForm" class="needs-validation" data-address-group novalidate>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="companyName">Tên doanh nghiệp</label>
                  <input type="text" id="companyName" class="form-control" placeholder="Đang tải..." required>
                  <div class="invalid-feedback">Nhập tên doanh nghiệp.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="taxCode">Mã số thuế</label>
                  <input type="text" id="taxCode" pattern="^[0-9A-Za-z\-]{8,15}$" class="form-control" placeholder="Đang tải..." required>
                  <div class="invalid-feedback">MST 8-15 ký tự.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="contactPerson">Người liên hệ</label>
                  <input type="text" id="contactPerson" class="form-control" placeholder="Đang tải..." required>
                  <div class="invalid-feedback">Nhập người liên hệ.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="email">Email (không đổi)</label>
                  <input type="email" id="email" class="form-control" placeholder="Đang tải..." disabled>
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="phone">Số điện thoại</label>
                  <input type="tel" id="phone" pattern="^0[0-9]{9}$" class="form-control" placeholder="Đang tải..." required>
                  <div class="invalid-feedback">Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="province">Tỉnh/Thành</label>
                  <select id="province" class="form-select" data-address="province" data-field="province_code" required></select>
                  <div class="invalid-feedback">Chọn tỉnh.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="ward">Xã/Phường</label>
                  <select id="ward" class="form-select" data-address="ward" data-field="ward_code" required></select>
                  <div class="invalid-feedback">Chọn xã/phường.</div>
                </div>
                <div class="col-md-12">
                  <label class="form-label" for="addressDetail">Địa chỉ chi tiết</label>
                  <input type="text" id="addressDetail" class="form-control" placeholder="Số, đường, khu..." required>
                  <div class="invalid-feedback">Nhập địa chỉ.</div>
                </div>
                <div class="col-md-12">
                  <label class="form-label" for="businessDesc">Mô tả hoạt động</label>
                  <textarea id="businessDesc" class="form-control" rows="3" placeholder="Ví dụ: Thu gom xử lý nhựa, kim loại..." ></textarea>
                </div>
              </div>
              <hr class="my-4" />
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0"><i class="fas fa-key me-2 text-warning"></i>Đổi mật khẩu</h6>
                <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#businessPasswordCollapse" aria-expanded="false" aria-controls="businessPasswordCollapse">
                    <i class="fas fa-lock me-1"></i>Bật form đổi mật khẩu
                </button>
              </div>
              <div class="collapse" id="businessPasswordCollapse">
                <p class="text-muted small mb-3">Chỉ điền khi bạn muốn đổi mật khẩu đăng nhập.</p>
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Lưu thay đổi</button>
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
      const form = document.getElementById('businessProfileForm');
      
      // Load thông tin user khi trang load
      async function loadUserProfile() {
          try {
              const response = await fetch('index.php?controller=api_auth&action=profile');
              const result = await response.json();
              
              if (result.success && result.data.user) {
                  const user = result.data.user;
                  
                  // Điền thông tin doanh nghiệp
                  if (user.company_name) {
                      document.getElementById('companyName').value = user.company_name || '';
                  }
                  document.getElementById('taxCode').value = user.tax_code || '';
                  document.getElementById('contactPerson').value = user.contact_person || user.full_name || '';
                  document.getElementById('email').value = user.email || '';
                  document.getElementById('phone').value = user.phone_number || '';
                  
                  // Điền địa chỉ (nếu có) - Giống Hộ Gia Đình
                  if (user.address) {
                      const addr = user.address;
                      console.log('📍 Địa chỉ từ API:', addr);
                      
                      if (addr.province_code) {
                          try {
                              // Try external API, fallback to local
                              console.log('🔄 Đang load danh sách tỉnh từ API...');
                              let provinces = [];
                              try {
                                  const provincesRes = await fetch('https://provinces.open-api.vn/api/?depth=1');
                                  provinces = await provincesRes.json();
                              } catch (extError) {
                                  console.warn('External API failed, using local fallback');
                                  const localRes = await fetch('/KLTN_2025/index.php?controller=api_address&action=provinces');
                                  provinces = await localRes.json();
                              }
                              console.log('✅ Loaded', provinces.length, 'tỉnh/thành');
                              
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
                              console.log('🏙️ Đã chọn tỉnh:', provinceSelect.options[provinceSelect.selectedIndex]?.text);
                              
                              // Load phường/xã theo tỉnh
                              if (addr.ward_code) {
                                  console.log('🔄 Đang load danh sách xã/phường cho tỉnh:', addr.province_code);
                                  let provinceDetail = null;
                                  try {
                                      const wardsRes = await fetch(`https://provinces.open-api.vn/api/p/${addr.province_code}?depth=3`);
                                      provinceDetail = await wardsRes.json();
                                  } catch (extError) {
                                      console.warn('External API failed, using local fallback');
                                      const localRes = await fetch(`/KLTN_2025/index.php?controller=api_address&action=province_detail&code=${addr.province_code}`);
                                      provinceDetail = await localRes.json();
                                  }
                                  const wards = (provinceDetail.districts || []).flatMap(d => d.wards || []);
                                  console.log('✅ Loaded', wards.length, 'xã/phường');
                                  
                                  const wardSelect = document.getElementById('ward');
                                  wardSelect.innerHTML = '<option value="">Chọn xã/phường...</option>';
                                  wards.forEach(ward => {
                                      const opt = document.createElement('option');
                                      opt.value = ward.code;
                                      opt.textContent = ward.name;
                                      wardSelect.appendChild(opt);
                                  });
                                  
                                  // Set giá trị đã lưu
                                  wardSelect.value = addr.ward_code;
                                  console.log('🏘️ Đã chọn xã:', wardSelect.options[wardSelect.selectedIndex]?.text);
                              }
                          } catch (error) {
                              console.error('❌ Lỗi load địa chỉ:', error);
                          }
                      }
                      
                      // Điền địa chỉ chi tiết
                      document.getElementById('addressDetail').value = addr.address_line || '';
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
        if(!form.checkValidity()){ e.stopPropagation(); form.classList.add('was-validated'); return; }
        const c=currentPassword.value.trim(), n=newPassword.value.trim(), cf=confirmPassword.value.trim();
        if(c||n||cf){
          if(!c||!n||!cf){ toast('Điền đủ 3 trường mật khẩu để đổi.', 'warning'); return; }
          if(n!==cf){ confirmPassword.classList.add('is-invalid'); toast('Mật khẩu xác nhận không khớp.', 'danger'); return; } else { confirmPassword.classList.remove('is-invalid'); }
        }

        // Prepare data to send
        const formData = {
          full_name: document.getElementById('contactPerson').value.trim(),
          phone_number: document.getElementById('phone').value.trim(),
          province_code: document.getElementById('province').value,
          ward_code: document.getElementById('ward').value,
          address_line: document.getElementById('addressDetail').value.trim(),
          note: document.getElementById('businessDesc').value.trim(),
          company_name: document.getElementById('companyName').value.trim(),
          tax_code: document.getElementById('taxCode').value.trim()
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
        const html=`<div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert"><div class=\"d-flex\"><div class=\"toast-body\"><i class='fas fa-circle-info me-2'></i>${msg}</div><button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast'></button></div></div>`;
        toastContainer.insertAdjacentHTML('beforeend', html); const el=document.getElementById(id); const t=new bootstrap.Toast(el,{delay:3200}); t.show(); el.addEventListener('hidden.bs.toast',()=>el.remove());
      }
    })();
  </script>
</body>
</html>
