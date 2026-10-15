'use strict';

// Household Feed Controller: filter recycling orders by household address
(function () {
    const ordersContainer = document.getElementById('ordersContainer');
    if (!ordersContainer) return;

    const emptyState = document.getElementById('emptyState');
    const provinceSelect = document.getElementById('province');
    const wardSelect = document.getElementById('ward');
    const provinceHint = document.getElementById('provinceHint');
    const wardHint = document.getElementById('wardHint');
    const wasteTypeSelect = document.getElementById('wasteType');
    const applyBtn = document.getElementById('applyFilters');

    const defaultProvinceCode = ordersContainer.dataset.householdProvince || '';
    const defaultWardName = ordersContainer.dataset.householdWard || '';

    console.log('🏠 Household default address:', {
        provinceCode: defaultProvinceCode,
        wardCode: defaultWardName,
        provincePrefill: provinceSelect?.dataset.prefillCode,
        wardPrefill: wardSelect?.dataset.prefillCode
    });

    const ORDERS = Array.isArray(window.HOUSEHOLD_ORDERS) ? window.HOUSEHOLD_ORDERS : [];

    const formatNumber = (value) => Number(value || 0).toLocaleString('vi-VN');

    const getOrderTypeMeta = (order) => {
        if (order.type === 'cash') {
            return {
                label: 'Đơn nhận tiền',
                headerClass: 'border-success',
                badgeClass: 'bg-success',
                icon: 'fas fa-money-bill-wave',
                headerBg: 'bg-success',
                theme: 'success'
            };
        }
        return {
            label: 'Đơn tích điểm',
            headerClass: 'border-primary',
            badgeClass: 'bg-primary',
            icon: 'fas fa-star',
            headerBg: 'bg-primary',
            theme: 'primary'
        };
    };

    const buildCard = (order) => {
        const meta = getOrderTypeMeta(order);
        const totalPoints = order.type === 'points' ? order.pointsPerKg * order.quantityKg : null;
        const totalCash = order.type === 'cash' ? order.cashRate * order.quantityKg : null;
        const remainingPercent = order.quantityKg
            ? Math.max(0, Math.min(100, Math.round(((order.quantityKg - order.remainingKg) / order.quantityKg) * 100)))
            : 0;

        const themeStyles = meta.theme === 'success'
            ? { bg: 'rgba(25,135,84,0.12)', color: '#198754' }
            : { bg: 'rgba(13,110,253,0.12)', color: '#0d6efd' };

        const rewardUnit = order.type === 'points'
            ? `${formatNumber(order.pointsPerKg)} điểm/kg`
            : `${formatNumber(order.cashRate)} đ/kg`;

        const rewardTotal = order.type === 'points'
            ? `${formatNumber(totalPoints || 0)} điểm`
            : `${formatNumber(totalCash || 0)} đ`;

        const rewardIcon = order.type === 'points' ? 'fa-coins text-warning' : 'fa-money-bill-wave text-success';

        return `
            <div class="col-xl-4 col-lg-6">
                <div class="order-card">

                    <div class="mb-2">
                        <h5 class="mb-1">${order.company || 'Doanh nghiệp đang ẩn danh'}</h5>
                        <p class="text-muted mb-0">
                            <i class="fas fa-location-dot text-danger me-2"></i>${order.wardDisplay || order.wardName || 'Đang cập nhật khu vực'}
                        </p>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="metric-card">
                                <div class="metric-label">Khối lượng tối đa</div>
                                <div class="metric-value">${formatNumber(order.quantityKg)} kg</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="metric-card">
                                <div class="metric-label">Phần thưởng</div>
                                <div class="metric-value">${rewardUnit}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="metric-card d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-label">Tổng thưởng</div>
                                    <div class="metric-value">${rewardTotal}</div>
                                </div>
                                <span class="badge badge-chip bg-white text-muted border">
                                    <i class="fas ${rewardIcon} me-2"></i>${order.type === 'points' ? 'Điểm thưởng' : 'Tiền mặt'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Đã nhận: ${formatNumber(order.quantityKg - order.remainingKg)} kg</span>
                            <span>${remainingPercent}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-${meta.theme}" role="progressbar" style="width: ${remainingPercent}%;"></div>
                        </div>
                    </div>

                    <div class="order-actions mt-auto">
                        <a href="index.php?controller=ho_gia_dinh&action=chiTietDon&id=${order.orderId || order.id}" class="btn ${order.type === 'cash' ? 'btn-success' : 'btn-primary'} w-100">
                            <i class="fas fa-hand-holding-heart me-2"></i>Tham gia đơn
                        </a>
                        <div class="text-muted small mt-2">
                            <i class="far fa-clock me-1"></i>Hạn chót: ${new Date(order.deadline).toLocaleDateString('vi-VN')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    const renderOrders = (list) => {
        ordersContainer.innerHTML = '';
        if (!list.length) {
            ordersContainer.classList.add('d-none');
            emptyState?.classList.remove('d-none');
            return;
        }
        emptyState?.classList.add('d-none');
        ordersContainer.classList.remove('d-none');
        const html = list.map(buildCard).join('');
        ordersContainer.insertAdjacentHTML('beforeend', html);
    };

    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .toLowerCase();

    const getSelectText = (select) => {
        if (!select) return '';
        const index = select.selectedIndex;
        if (index >= 0) {
            const option = select.options[index];
            return option ? option.textContent : '';
        }
        return '';
    };

    const getFallbackValue = (id) => {
        const input = document.querySelector(`[data-fallback-for="${id}"]`);
        return input ? input.value : '';
    };

    const filterOrders = () => {
        // Lấy orders mới nhất từ window (có thể đã được cập nhật từ API)
        const currentOrders = Array.isArray(window.HOUSEHOLD_ORDERS) ? window.HOUSEHOLD_ORDERS : ORDERS;
        
        const provinceValue = (provinceSelect && provinceSelect.style.display !== 'none' && provinceSelect.value)
            ? provinceSelect.value
            : (getFallbackValue('province') || defaultProvinceCode);
        
        // Lấy ward code và ward name từ select
        let wardCode = '';
        let wardName = '';
        if (wardSelect && wardSelect.style.display !== 'none' && wardSelect.value) {
            wardCode = wardSelect.value; // ward code từ API
            const selectedOption = wardSelect.options[wardSelect.selectedIndex];
            wardName = selectedOption ? selectedOption.textContent.trim() : '';
        } else {
            const fallbackWard = getFallbackValue('ward') || defaultWardName;
            wardCode = fallbackWard;
            wardName = fallbackWard;
        }
        
        const wasteTypeValue = wasteTypeSelect?.value || '';

        console.log('🔍 Filter criteria:');
        console.log('  - provinceValue:', provinceValue);
        console.log('  - wardCode:', wardCode);
        console.log('  - wardName:', wardName);
        console.log('  - wasteTypeValue:', wasteTypeValue);
        console.log('📋 Total orders before filter:', currentOrders.length);

        const filtered = currentOrders.filter(order => {
            console.log('🔎 Checking order:', order.orderCode || order.orderId);
            
            // So sánh province code
            if (provinceValue && String(order.provinceCode || '') !== String(provinceValue)) {
                console.log('  ❌ Province mismatch:', order.provinceCode, '!==', provinceValue);
                return false;
            }
            
            // So sánh ward: ưu tiên so sánh ward code, nếu không có thì so sánh ward name
            if (wardCode) {
                const orderWardCode = String(order.wardCode || '').trim();
                const orderWardName = normalize(order.wardName || '');
                const orderWardDisplay = normalize(order.wardDisplay || '');
                const targetWardCode = String(wardCode).trim();
                const targetWardName = normalize(wardName);
                
                console.log('  🏘️ Ward check:');
                console.log('    - Target code:', targetWardCode, '(type:', typeof targetWardCode, ')');
                console.log('    - Target name (normalized):', targetWardName);
                console.log('    - Order wardCode:', orderWardCode, '(type:', typeof orderWardCode, ')');
                console.log('    - Order wardName (normalized):', orderWardName);
                console.log('    - Order wardDisplay (normalized):', orderWardDisplay);
                
                let wardMatch = false;
                
                // So sánh ward code trước (chính xác hơn) - so sánh cả string và number
                if (orderWardCode && targetWardCode) {
                    // So sánh string
                    if (orderWardCode === targetWardCode) {
                        wardMatch = true;
                        console.log('    ✅ Match by ward code (string)');
                    } 
                    // So sánh number (nếu cả 2 đều là số)
                    else if (!isNaN(orderWardCode) && !isNaN(targetWardCode) && 
                             parseInt(orderWardCode) === parseInt(targetWardCode)) {
                        wardMatch = true;
                        console.log('    ✅ Match by ward code (number)');
                    }
                }
                
                // Nếu không match bằng code, thử match bằng name
                if (!wardMatch && targetWardName) {
                    if (orderWardName && orderWardName === targetWardName) {
                        wardMatch = true;
                        console.log('    ✅ Match by ward name');
                    } else if (orderWardDisplay && orderWardDisplay === targetWardName) {
                        wardMatch = true;
                        console.log('    ✅ Match by ward display');
                    }
                }
                
                if (!wardMatch) {
                    console.log('  ❌ Ward mismatch - no match found');
                    return false;
                }
            }
            
            if (wasteTypeValue && normalize(order.wasteType || '') !== normalize(wasteTypeValue)) {
                console.log('  ❌ Waste type mismatch');
                return false;
            }
            
            console.log('  ✅ Order passed filter');
            return true;
        });

        console.log('✅ Filtered orders:', filtered.length);

        if (provinceHint) {
            const text = provinceSelect && provinceSelect.style.display !== 'none'
                ? getSelectText(provinceSelect)
                : (getFallbackValue('province') || defaultProvinceCode || '');
            provinceHint.textContent = text ? `Khu vực lọc: ${text}` : 'Khu vực lọc: toàn quốc';
        }
        if (wardHint) {
            const text = wardSelect && wardSelect.style.display !== 'none'
                ? getSelectText(wardSelect)
                : (getFallbackValue('ward') || '');
            wardHint.textContent = text ? `Phường/Xã: ${text}` : '';
        }

        renderOrders(filtered);
    };

    const bootstrapListeners = () => {
        applyBtn?.addEventListener('click', filterOrders);
        provinceSelect?.addEventListener('change', () => {
            // Không tự động filter khi đổi province
            // Chỉ clear ward hint để người dùng biết cần chọn lại ward
            if (wardHint) wardHint.textContent = '';
        });
        wardSelect?.addEventListener('change', filterOrders);
        wasteTypeSelect?.addEventListener('change', filterOrders);
    };

    const waitUntilAddressReady = () => {
        const ready = () => {
            const provinceReady = provinceSelect ? provinceSelect.options.length > 1 : true;
            const wardReady = wardSelect ? wardSelect.options.length > 1 : true;
            return provinceReady && wardReady;
        };
        if (ready()) {
            console.log('✅ Address selects already ready, filtering...');
            filterOrders();
            return;
        }
        console.log('⏳ Waiting for address selects to be populated...');
        const observer = new MutationObserver(() => {
            if (ready()) {
                console.log('✅ Address selects now ready');
                observer.disconnect();
                // Đảm bảo province và ward được set đúng giá trị
                if (provinceSelect && defaultProvinceCode) {
                    console.log('🔧 Setting province to:', defaultProvinceCode);
                    if (provinceSelect.value !== defaultProvinceCode) {
                        provinceSelect.value = defaultProvinceCode;
                    }
                }
                if (wardSelect && defaultWardName) {
                    console.log('🔧 Setting ward to:', defaultWardName);
                    // defaultWardName là ward_code từ controller
                    if (wardSelect.value !== defaultWardName) {
                        wardSelect.value = defaultWardName;
                    }
                }
                filterOrders();
            }
        });
        if (provinceSelect) observer.observe(provinceSelect, { childList: true });
        if (wardSelect) observer.observe(wardSelect, { childList: true });
        // fallback in case MutationObserver misses (e.g. prefilled already)
        setTimeout(() => {
            if (ready()) {
                console.log('⏰ Timeout reached, forcing filter');
                observer.disconnect();
                filterOrders();
            }
        }, 2000);
    };

    bootstrapListeners();
    
    // Listen for orders loaded event
    window.addEventListener('ordersLoaded', (e) => {
        console.log('🔔 ordersLoaded event received:', e.detail.orders.length, 'orders');
        // Update ORDERS reference
        window.HOUSEHOLD_ORDERS = e.detail.orders;
        // Wait for address selects to be ready, then filter
        waitUntilAddressReady();
    });
    
    // Also run on page load in case orders already loaded
    // Check if orders are already in window
    if (Array.isArray(window.HOUSEHOLD_ORDERS) && window.HOUSEHOLD_ORDERS.length > 0) {
        console.log('📦 Orders already loaded, filtering...');
        waitUntilAddressReady();
    } else {
        waitUntilAddressReady();
    }
})();
