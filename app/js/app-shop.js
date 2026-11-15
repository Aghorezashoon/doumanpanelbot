/**
 * Arka/Voldemort Cloud Shop & Purchase Flow
 */
(function() {
    'use strict';
    const App = window.App;

    App.renderShop = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                در حال دریافت سرورها و تعرفه‌ها...
            </div>
        `;

        // Load countries
        const cRes = await App.api('countries');
        if (!cRes.status || !Array.isArray(cRes.obj) || cRes.obj.length === 0) {
            container.innerHTML = `<div class="card" style="text-align: center; padding: 30px;">سرور فعالی یافت نشد.</div>`;
            return;
        }

        App.countries = cRes.obj;
        if (!App.selectedCountry) {
            App.selectedCountry = App.countries[0];
        }

        const selCode = App.selectedCountry ? (App.selectedCountry.id || App.selectedCountry.code_panel) : '';

        // Render Shop Shell
        container.innerHTML = `
            <!-- Location Picker -->
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">۱. انتخاب لوکیشن سرور:</div>
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 16px;" id="shop-countries">
                ${App.countries.map(c => {
                    const cCode = c.id || c.code_panel;
                    const cName = c.name || c.name_panel || c.country_name || 'سرور';
                    const isCur = (cCode === selCode);
                    return `
                        <button class="btn-secondary country-btn ${isCur ? 'active' : ''}" 
                                data-code="${cCode}"
                                style="${isCur ? 'border-color: var(--accent-lime); color: var(--accent-lime); background: #253316;' : ''}">
                            🌐 ${cName}
                        </button>
                    `;
                }).join('')}
            </div>

            <!-- Duration Tabs -->
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">۲. دسته‌بندی و فیلتر پلن‌ها:</div>
            <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                <button class="btn-secondary period-tab active" data-period="all" style="flex: 1;">همه سرویس‌ها</button>
                <button class="btn-secondary period-tab" data-period="30" style="flex: 1;">۱ ماهه</button>
                <button class="btn-secondary period-tab" data-period="custom" style="flex: 1;">حجم دلخواه ⚡</button>
            </div>

            <!-- Products List Container -->
            <div id="shop-products-container">
                <div style="text-align: center; padding: 20px; color: var(--text-muted);">در حال دریافت پلن‌ها...</div>
            </div>
        `;

        // Bind country switch
        container.querySelectorAll('.country-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const code = btn.dataset.code;
                App.selectedCountry = App.countries.find(c => (c.id === code || c.code_panel === code));
                App.renderShop(container);
            });
        });

        // Bind period tabs
        container.querySelectorAll('.period-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                container.querySelectorAll('.period-tab').forEach(t => {
                    t.classList.remove('active');
                    t.style.borderColor = 'var(--border-color)';
                    t.style.color = 'var(--text-primary)';
                    t.style.background = '#1E293B';
                });
                tab.classList.add('active');
                tab.style.borderColor = 'var(--accent-lime)';
                tab.style.color = 'var(--accent-lime)';
                tab.style.background = '#253316';
                App.loadShopProducts(tab.dataset.period);
            });
        });

        await App.loadShopProducts('all');
    };

    App.loadShopProducts = async function(periodFilter) {
        const pCont = document.getElementById('shop-products-container');
        if (!pCont || !App.selectedCountry) return;

        pCont.innerHTML = `<div style="text-align: center; padding: 20px; color: var(--text-muted);">در حال بارگذاری تعرفه‌ها...</div>`;

        const targetCountryCode = App.selectedCountry.id || App.selectedCountry.code_panel || '';
        const res = await App.api('services', 'GET', { country_id: targetCountryCode, id_panel: targetCountryCode });
        if (!res.status || !Array.isArray(res.obj) || res.obj.length === 0) {
            pCont.innerHTML = `<div class="card" style="text-align: center; padding: 30px;">محصولی برای این سرور یافت نشد.</div>`;
            return;
        }

        let products = res.obj;
        App.currentShopProducts = products;

        if (periodFilter === '30') {
            products = products.filter(p => p.time_days == 30 && !p.custom_volume_enabled);
        } else if (periodFilter === 'custom') {
            products = products.filter(p => p.custom_volume_enabled == 1);
        }

        if (products.length === 0) {
            pCont.innerHTML = `<div class="card" style="text-align: center; padding: 30px;">پلنی در این دسته‌بندی موجود نیست.</div>`;
            return;
        }

        pCont.innerHTML = products.map(prod => App.buildProductCard(prod)).join('');
    };

    App.buildProductCard = function(prod) {
        const isCustom = prod.custom_volume_enabled == 1;
        const minV = prod.min_volume_gb || 10;
        const maxV = prod.max_volume_gb || 200;
        const pPerGb = prod.price_per_gb || 5000;
        const defaultVol = minV;
        const initPrice = isCustom ? (defaultVol * pPerGb) : (prod.price || 0);

        return `
            <div class="card product-card" id="prod-card-${prod.id}" style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div style="font-size: 16px; font-weight: 700; color: #fff;">${prod.name}</div>
                    <span style="font-size: 12px; background: rgba(212, 248, 60, 0.15); color: var(--accent-lime); padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                        ${prod.time_days > 0 ? (prod.time_days + ' روزه') : 'نامحدود زمانی'}
                    </span>
                </div>

                ${isCustom ? `
                    <!-- Custom Volume Slider UI -->
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 13px; color: var(--text-secondary);">حجم دلخواه انتخابی شما:</span>
                            <span style="font-size: 18px; font-weight: 800; color: var(--accent-lime);" id="vol-display-${prod.id}">${defaultVol} GB</span>
                        </div>
                        <input type="range" min="${minV}" max="${maxV}" step="5" value="${defaultVol}" 
                               style="width: 100%; accent-color: var(--accent-lime); cursor: pointer;" 
                               id="range-${prod.id}"
                               oninput="App.updateCustomVolume('${prod.id}', this.value, ${pPerGb})">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-top: 6px;">
                            <span>حداقل: ${minV} GB</span>
                            <span>قیمت هر گیگ: ${Number(pPerGb).toLocaleString('fa-IR')} تومان</span>
                            <span>حداکثر: ${maxV} GB</span>
                        </div>
                    </div>
                ` : `
                    <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                        حجم کل: <b style="color: #fff;">${prod.traffic_gb} گیگابایت</b>
                    </div>
                `}

                <!-- Custom Username Input -->
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12.5px; color: var(--text-secondary); display: block; margin-bottom: 6px;">
                        ✍️ انتخاب نام کاربری دلخواه (اختیاری):
                    </label>
                    <input type="text" id="uname-${prod.id}" placeholder="مثال: vip_reza (حروف انگلیسی و بدون فاصله)" 
                           style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; color: #fff; font-size: 13px; direction: ltr; text-align: left;">
                </div>

                <!-- Price and Buy Action -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid var(--border-light);">
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted);">مبلغ قابل پرداخت</div>
                        <div style="font-size: 17px; font-weight: 800; color: var(--accent-lime);" id="price-${prod.id}">
                            ${Number(initPrice).toLocaleString('fa-IR')} <span style="font-size: 12px; color: var(--text-secondary);">تومان</span>
                        </div>
                    </div>
                    <button class="btn-lime" style="width: auto; padding: 10px 24px;" onclick="App.purchaseProduct('${prod.id}', ${isCustom})">
                        خرید و فعال‌سازی
                    </button>
                </div>
            </div>
        `;
    };

    App.updateCustomVolume = function(prodId, val, pricePerGb) {
        const display = document.getElementById(`vol-display-${prodId}`);
        const priceEl = document.getElementById(`price-${prodId}`);
        if (display) display.innerText = val + ' GB';
        const total = val * pricePerGb;
        if (priceEl) priceEl.innerHTML = `${Number(total).toLocaleString('fa-IR')} <span style="font-size: 12px; color: var(--text-secondary);">تومان</span>`;
    };

    App.purchaseProduct = async function(prodId, isCustom) {
        if (!App.token) {
            App.toast('برای خرید اشتراک، لطفاً ابتدا با حساب تلگرام خود وارد شوید.', true);
            App.navigate('profile');
            return;
        }

        const unameInp = document.getElementById(`uname-${prodId}`);
        const customUname = unameInp ? unameInp.value.trim() : '';

        let customVol = 0;
        if (isCustom) {
            const rangeInp = document.getElementById(`range-${prodId}`);
            customVol = rangeInp ? parseInt(rangeInp.value) : 10;
        }

        if (customUname && !/^[a-zA-Z][a-zA-Z0-9_]{2,31}$/.test(customUname)) {
            App.toast('نام کاربری باید ۳ تا ۳۲ کاراکتر، با حروف انگلیسی و بدون فاصله باشد.', true);
            return;
        }

        App.toast('در حال پردازش سفارش...');

        const panelCode = App.selectedCountry.code_panel || App.selectedCountry.id;
        const payload = {
            country_id: panelCode,
            id_panel: panelCode,
            service_id: prodId,
            id_product: prodId,
            custom_username: customUname || null,
            custom_volume_gb: isCustom ? customVol : null,
            user_limit: 1
        };

        const res = await App.api('purchase', 'POST', payload);
        if (res && (res.status || res.success)) {
            App.toast('خرید شما با موفقیت انجام شد!');
            await App.refreshUserData();
            // Open services to see new config
            setTimeout(() => {
                App.navigate('services');
            }, 1000);
        } else {
            App.toast(res.msg || 'خطا در ثبت سفارش. موجودی کیف پول خود را بررسی کنید.', true);
        }
    };

})();
