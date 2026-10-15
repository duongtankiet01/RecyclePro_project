/*
==============================================
PureAI - Recycling Collection Management System
JavaScript - KLTN2025
==============================================
*/

document.addEventListener('DOMContentLoaded', () => {
    const basePath = detectAppBasePath();
    setupFormValidation();
    setupPasswordConfirmation();
    setupAuthForms(basePath);
    setupAddressCascade();
});

function detectAppBasePath() {
    try {
        const script = document.currentScript
            || Array.from(document.querySelectorAll('script')).find((s) => /\/assets\/js\/main\.js/.test(s.getAttribute('src') || s.src || ''));
        if (!script) return '';
        const src = script.src || script.getAttribute('src');
        const url = new URL(src, window.location.origin);
        return url.pathname.replace(/\/assets\/js\/main\.js$/, '');
    } catch (error) {
        console.warn('Không xác định được base path, dùng rỗng.', error);
        return '';
    }
}

function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

function setupPasswordConfirmation() {
    document.querySelectorAll('.auth-form').forEach((form) => {
        const passwordInput = form.querySelector('input[data-field="password"]');
        const confirmInput = form.querySelector('input[id*="Confirm"]');
        if (!passwordInput || !confirmInput) return;

        const validate = () => {
            if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                confirmInput.setCustomValidity('Mật khẩu xác nhận không khớp');
                confirmInput.classList.add('is-invalid');
            } else {
                confirmInput.setCustomValidity('');
                confirmInput.classList.remove('is-invalid');
            }
        };

        ['input', 'change', 'blur'].forEach((evt) => {
            passwordInput.addEventListener(evt, validate);
            confirmInput.addEventListener(evt, validate);
        });

        form.addEventListener('submit', validate);
    });
}

function setupAuthForms(basePath) {
    const forms = document.querySelectorAll('.auth-form');
    if (!forms.length) return;

    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            form.classList.add('was-validated');
            if (!form.checkValidity()) {
                showFormAlert(form, 'warning', 'Vui lòng kiểm tra lại các trường bắt buộc.');
                return;
            }

            const mode = form.dataset.mode === 'login' ? 'login' : 'register';
            const payload = gatherPayload(form);
            
            setFormLoading(form, true);
            
            try {
                const apiUrl = mode === 'login' 
                    ? 'index.php?controller=api_auth&action=login'
                    : 'index.php?controller=api_auth&action=register';
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    showFormAlert(form, 'success', result.message || 'Thành công!');
                    
                    // Redirect sau khi đăng nhập/đăng ký thành công
                    if (result.data && result.data.redirect) {
                        setTimeout(() => {
                            window.location.href = result.data.redirect;
                        }, 1000);
                    } else if (mode === 'register') {
                        showFormAlert(form, 'success', 'Đăng ký thành công! Đang chuyển sang đăng nhập...');
                        setTimeout(() => {
                            const loginUrl = new URL('index.php?controller=auth&action=dangNhap', window.location.href);
                            if (payload.email) {
                                loginUrl.searchParams.set('prefill_email', payload.email);
                            }
                            window.location.href = loginUrl.toString();
                        }, 1500);
                    }
                } else {
                    showFormAlert(form, 'danger', result.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                }
            } catch (error) {
                console.error('API Error:', error);
                showFormAlert(form, 'danger', 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
            } finally {
                setFormLoading(form, false);
            }
        });
    });
}

function gatherPayload(form) {
    const payload = {};
    const elements = form.querySelectorAll('[data-field]');
    elements.forEach((el) => {
        const field = el.dataset.field;
        if (!field) return;

        let value;
        if (el.type === 'checkbox') {
            value = el.checked;
        } else if (el.tagName === 'SELECT') {
            value = el.value;
        } else {
            value = el.value != null ? el.value.trim() : '';
        }

        if (value === '' && el.required === false) {
            return;
        }

        payload[field] = value;
    });
    return payload;
}

function setFormLoading(form, isLoading) {
    const submitButton = form.querySelector('[type="submit"]');
    if (!submitButton) return;

    if (isLoading) {
        submitButton.dataset.originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý';
        submitButton.disabled = true;
    } else {
        submitButton.innerHTML = submitButton.dataset.originalText || submitButton.innerHTML;
        submitButton.disabled = false;
    }
}

function showFormAlert(form, type, message, extraData = null) {
    if (!form) return;

    let container = form.querySelector('.form-alert-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'form-alert-container my-3';
        form.prepend(container);
    }

    container.innerHTML = '';
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.role = 'alert';
    alert.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-times-circle' : 'fa-info-circle'} me-2"></i>${message}`;

    if (extraData && typeof extraData === 'object' && Object.keys(extraData).length > 0) {
        const list = document.createElement('ul');
        list.className = 'mt-2 small ps-3';
        Object.entries(extraData).forEach(([key, value]) => {
            const item = document.createElement('li');
            const formattedValue = typeof value === 'object' ? JSON.stringify(value) : value;
            item.innerHTML = `<strong>${key}:</strong> ${formattedValue}`;
            list.appendChild(item);
        });
        alert.appendChild(list);
    }

    container.appendChild(alert);
}

function handleLoginSuccess(payload, basePath) {
    if (!payload || typeof payload !== 'object') return;

    const redirects = {
        household: '/ho-gia-dinh/feed.html',
        business: '/doanh-nghiep/dashboard.html',
        collector: '/ctv/feed.html',
    };

    const role = payload.role;
    const token = payload.token;
    const redirectPath = redirects[role];
    if (token) {
        try {
            localStorage.setItem('greenbin_token', token);
            localStorage.setItem('greenbin_role', role || '');
            localStorage.setItem('greenbin_user', JSON.stringify({
                id: payload.user_id,
                name: payload.full_name,
                email: payload.email,
                phone: payload.phone_number,
                role,
            }));
        } catch (error) {
            console.warn('Không thể lưu token vào localStorage', error);
        }
    }

    if (redirectPath) {
        const normalizedBase = basePath || '';
        const url = normalizedBase === '/' ? redirectPath : `${normalizedBase}${redirectPath}`;
        setTimeout(() => {
            window.location.href = url;
        }, 600);
    }
}

function switchToTab(selector) {
    if (!selector) return;
    const trigger = document.querySelector(selector);
    if (!trigger) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        const tab = new bootstrap.Tab(trigger);
        tab.show();
    } else {
        trigger.click();
    }
}

function setupAddressCascade() {
    const addressForms = document.querySelectorAll('form[data-address-group]');
    if (!addressForms.length) return;

    addressForms.forEach((form) => {
        const provinceSelect = form.querySelector('[data-address="province"]');
        const wardSelect = form.querySelector('[data-address="ward"]');
        const provinceNameInput = form.querySelector('input[data-field="province_name"]') || null;
        const wardNameInput = form.querySelector('input[data-field="ward_name"]') || null;
        const trackasiaPlaceInput = form.querySelector('[name="trackasiaPlaceId"]') || document.getElementById('trackasiaPlaceId');

        if (!provinceSelect && !wardSelect) return;

        const PROVINCES_API = 'https://provinces.open-api.vn/api';
        const LOCAL_API = '/KLTN_2025/index.php?controller=api_address';
        const cache = {
            provinces: null,
        };

        const setPlaceholder = (select, text) => {
        if (!select) return;
        select.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = text || 'Chọn...';
        opt.disabled = true;
        opt.selected = true;
        select.appendChild(opt);
    };

        const enable = (select, enabled) => {
        if (!select) return;
        select.disabled = !enabled;
    };

        let addressAlertShown = false;
        const showInlineAlert = (anchorEl, message, type = 'warning') => {
        try {
            if (!anchorEl || addressAlertShown) return;
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show mt-2`;
            alert.role = 'alert';
            alert.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            const parent = anchorEl.parentElement || document.body;
            parent.appendChild(alert);
            addressAlertShown = true;
        } catch (error) {
            console.warn('Không thể hiển thị cảnh báo địa chỉ', error);
        }
        };

        const syncSelectLabel = (select, targetInput) => {
        if (!select || !targetInput) return;
        const option = select.options[select.selectedIndex];
        targetInput.value = option ? option.textContent.trim() : '';
    };

        const syncFallbackLabel = (select, input) => {
        if (!select || !input) return;
    const field = select.dataset.field === 'ward_code' ? 'ward_name' : 'province_name';
    const form = select.form;
    if (!form) return;
    const target = form.querySelector(`input[data-field="${field}"]`);
        if (!target) return;
        const handler = () => {
            target.value = input.value.trim();
        };
        input.addEventListener('input', handler);
        handler();
    };

        const fallbackToManual = (select, placeholder) => {
        if (!select) return;
        const fallbackId = `${select.id}-manual`;
        if (document.getElementById(fallbackId)) return;
        select.style.display = 'none';
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control mt-2';
        input.id = fallbackId;
        input.name = select.name || fallbackId;
        input.placeholder = placeholder || 'Nhập thủ công...';
        input.required = !!select.required;
        input.setAttribute('data-fallback-for', select.id);
        if (select.dataset.field) {
            input.setAttribute('data-field', select.dataset.field);
        }
        select.insertAdjacentElement('afterend', input);
        syncFallbackLabel(select, input);
    };

        const getJSON = async (url) => {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    };

        const loadProvinces = async () => {
            if (cache.provinces) return cache.provinces;
            try {
                // Try external API first
                const data = await getJSON(`${PROVINCES_API}/?depth=1`);
                cache.provinces = Array.isArray(data) ? data : [];
            } catch (error) {
                console.warn('External API failed, trying local fallback:', error);
                try {
                    // Fallback to local API
                    const localData = await getJSON(`${LOCAL_API}&action=provinces`);
                    cache.provinces = Array.isArray(localData) ? localData : [];
                } catch (localError) {
                    console.warn('Local API also failed:', localError);
                    cache.provinces = [];
                }
            }
            return cache.provinces;
        };

        const loadWardsByProvince = async (provinceCode) => {
            if (!provinceCode) return [];
            try {
                // Try external API first
                const detail = await getJSON(`${PROVINCES_API}/p/${encodeURIComponent(provinceCode)}?depth=3`);
                const wards = (detail?.districts || []).flatMap((district) => (Array.isArray(district.wards) ? district.wards : []));
                return wards;
            } catch (error) {
                console.warn('External API failed, trying local fallback:', error);
                try {
                    // Fallback to local API
                    const localDetail = await getJSON(`${LOCAL_API}&action=province_detail&code=${encodeURIComponent(provinceCode)}`);
                    const wards = (localDetail?.districts || []).flatMap((district) => (Array.isArray(district.wards) ? district.wards : []));
                    return wards;
                } catch (localError) {
                    console.warn('Local API also failed:', localError);
                    return [];
                }
            }
        };

        const populateSelect = (select, items, valueKey, labelFn) => {
        if (!select) return;
        const current = select.value;
        setPlaceholder(select, 'Chọn...');
        items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item[valueKey]).trim();
            const name = (item.name || '').replace(/\s+/g, ' ').trim();
            const typeText = (item.typeText || '').trim();
            opt.textContent = labelFn ? labelFn(item) : typeText ? `${typeText} ${name}` : name;
            select.appendChild(opt);
        });
        if (current && Array.from(select.options).some((option) => option.value === current)) {
            select.value = current;
        }
        };

        const normalizeVN = (text) => (text || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const selectOptionByText = (select, text) => {
        if (!select || !text) return false;
        const normalized = normalizeVN(text);
        const option = Array.from(select.options).find((opt) => {
            const normOpt = normalizeVN(opt.textContent);
            return normOpt === normalized || normOpt.includes(normalized);
        });
        if (option) {
            select.value = option.value;
            return true;
        }
        return false;
    };

        (async function init() {
        if (provinceSelect) {
            enable(provinceSelect, false);
            setPlaceholder(provinceSelect, 'Đang tải tỉnh/thành...');
            const provinces = await loadProvinces();
            if (!provinces.length) {
                setPlaceholder(provinceSelect, 'Không tải được tỉnh/thành');
                enable(provinceSelect, false);
                showInlineAlert(provinceSelect, 'Không thể tải dữ liệu Tỉnh/Thành và Xã/Phường từ API. Bạn có thể nhập thủ công.');
                fallbackToManual(provinceSelect, 'Nhập Tỉnh/Thành...');
                if (wardSelect) {
                    setPlaceholder(wardSelect, 'Không có phường/xã');
                    enable(wardSelect, false);
                    fallbackToManual(wardSelect, 'Nhập Xã/Phường...');
                }
            } else {
                populateSelect(provinceSelect, provinces, 'code', (it) => (it.name || '').trim());
                enable(provinceSelect, true);
                const prefillCode = provinceSelect.dataset.prefillCode;
                const prefillName = provinceSelect.dataset.prefillName;
                if (prefillCode && Array.from(provinceSelect.options).some((option) => option.value === prefillCode)) {
                    provinceSelect.value = prefillCode;
                } else if (prefillName) {
                    selectOptionByText(provinceSelect, prefillName);
                }
                syncSelectLabel(provinceSelect, provinceNameInput);
            }
            }

        const provinceValue = () => (provinceSelect ? provinceSelect.value : '');

        if (provinceSelect && wardSelect) {
            const onProvinceChange = async (prefillWard = false) => {
                setPlaceholder(wardSelect, 'Đang tải phường/xã...');
                enable(wardSelect, false);
                const wards = await loadWardsByProvince(provinceValue());
                if (!wards.length) {
                    setPlaceholder(wardSelect, 'Không có phường/xã');
                    enable(wardSelect, false);
                    showInlineAlert(wardSelect, 'Không thể tải danh sách phường/xã từ API. Bạn có thể nhập thủ công.');
                    fallbackToManual(wardSelect, 'Nhập Xã/Phường...');
                } else {
                    populateSelect(wardSelect, wards, 'code', (it) => (it.name || '').trim());
                    enable(wardSelect, true);
                    if (prefillWard || wardSelect.dataset.prefillName || wardSelect.dataset.prefillCode) {
                        const prefillCode = wardSelect.dataset.prefillCode;
                        const prefillName = wardSelect.dataset.prefillName;
                        if (prefillCode) {
                            const option = Array.from(wardSelect.options).find((opt) => opt.value === prefillCode);
                            if (option) {
                                wardSelect.value = prefillCode;
                            }
                        }
                        if (!wardSelect.value && prefillName) {
                            selectOptionByText(wardSelect, prefillName);
                        }
                    }
                    syncSelectLabel(wardSelect, wardNameInput);
                }
            };

            provinceSelect.addEventListener('change', () => {
                syncSelectLabel(provinceSelect, provinceNameInput);
                onProvinceChange(false);
            });
            if (provinceSelect.value) onProvinceChange(true);

            const parseTrackAsiaAddress = (components) => {
                let provinceName = '';
                let wardName = '';
                (components || []).forEach((component) => {
                    const types = component.types || [];
                    const longName = component.long_name || component.short_name || '';
                    if (types.includes('administrative_area_level_1')) {
                        provinceName = longName;
                    }
                    if (types.includes('administrative_area_level_2') || types.includes('administrative_area_level_3')) {
                        if (/^(Phường|Xã|Thị trấn)/i.test(longName)) wardName = longName;
                    }
                });
                if (!wardName) {
                    const guess = (components || []).find((component) => /^(Phường|Xã|Thị trấn)/i.test(component.long_name || ''));
                    if (guess) wardName = guess.long_name;
                }
                return { provinceName, wardName };
            };

            const applyTrackAsiaPlace = async (placeId) => {
                if (!placeId) return;
                try {
                    const url = `https://maps.track-asia.com/api/v2/place/details/json?key=public&place_id=${encodeURIComponent(placeId)}&new_admin=true&lang=vi`;
                    const res = await fetch(url);
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const json = await res.json();
                    const components = json?.result?.address_components || [];
                    const { provinceName, wardName } = parseTrackAsiaAddress(components);
                    if (!provinceName) return;
                    if (provinceSelect.options.length <= 1) {
                        const provinces = await loadProvinces();
                        if (provinces.length) populateSelect(provinceSelect, provinces, 'code', (it) => (it.name || '').trim());
                    }
                    const provinceMatched = selectOptionByText(provinceSelect, provinceName);
                    if (!provinceMatched) return;
                    syncSelectLabel(provinceSelect, provinceNameInput);
                    setPlaceholder(wardSelect, 'Đang tải phường/xã...');
                    enable(wardSelect, false);
                    const wards = await loadWardsByProvince(provinceSelect.value);
                    populateSelect(wardSelect, wards, 'code', (it) => (it.name || '').trim());
                    enable(wardSelect, true);
                    if (wardName) selectOptionByText(wardSelect, wardName);
                    syncSelectLabel(wardSelect, wardNameInput);
                } catch (error) {
                    console.warn('Track Asia place apply failed:', error);
                }
            };

            if (trackasiaPlaceInput) {
                if (trackasiaPlaceInput.value) applyTrackAsiaPlace(trackasiaPlaceInput.value);
                trackasiaPlaceInput.addEventListener('change', (event) => applyTrackAsiaPlace(event.target.value));
            }
        }

        if (wardSelect) {
            wardSelect.addEventListener('change', () => {
                syncSelectLabel(wardSelect, wardNameInput);
            });
        }

        // Export functions ra global scope để các trang khác sử dụng
        // (Phải export trong IIFE để có quyền truy cập vào loadProvinces, loadWardsByProvince, populateSelect)
        if (!window.loadProvinces) window.loadProvinces = loadProvinces;
        if (!window.loadWardsByProvince) window.loadWardsByProvince = loadWardsByProvince;
        if (!window.populateAddressSelect) window.populateAddressSelect = populateSelect;
    })();
    });
}
