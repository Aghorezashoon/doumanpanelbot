/**
 * Douman / Voldemort Cloud Mini App Enhancements
 * - Direct Wallet & Balance Display
 * - Instant Online Recharge with HooshPay Gateway
 * - Instant QR Code & Subscription Link display upon Purchase
 */
(function () {
    'use strict';

    let currentBalance = 0;
    let isModalOpen = false;

    // Helper: format numbers with Persian commas
    function formatMoney(num) {
        if (num === null || num === undefined || isNaN(num)) return '۰';
        return Number(num).toLocaleString('fa-IR');
    }

    // Helper: convert Persian digits to English
    function toEnglishDigits(str) {
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        let res = String(str);
        for (let i = 0; i < 10; i++) {
            res = res.replaceAll(persian[i], String(i)).replaceAll(arabic[i], String(i));
        }
        return res;
    }

    // Toast notification
    function showToast(msg, isError = false) {
        let toast = document.getElementById('douman-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'douman-toast';
            toast.style.cssText = `
                position: fixed;
                bottom: 85px;
                left: 50%;
                transform: translateX(-50%) translateY(20px);
                background: rgba(24, 24, 27, 0.95);
                color: #fff;
                padding: 12px 20px;
                border-radius: 12px;
                font-family: inherit;
                font-size: 14px;
                z-index: 99999;
                opacity: 0;
                transition: all 0.3s ease;
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
                border: 1px solid rgba(255,255,255,0.1);
                text-align: center;
                pointer-events: none;
                max-width: 90%;
            `;
            document.body.appendChild(toast);
        }
        toast.style.borderColor = isError ? '#ef4444' : '#10b981';
        toast.innerHTML = (isError ? '⚠️ ' : '✅ ') + msg;
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(20px)';
        }, 3500);
    }

    // Copy to clipboard helper
    window.doumanCopy = function (text, label) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showToast(label ? `${label} کپی شد!` : 'با موفقیت کپی شد!');
            }).catch(() => fallbackCopy(text, label));
        } else {
            fallbackCopy(text, label);
        }
    };

    function fallbackCopy(text, label) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showToast(label ? `${label} کپی شد!` : 'با موفقیت کپی شد!');
        } catch (err) {
            showToast('خطا در کپی متن', true);
        }
        document.body.removeChild(textArea);
    }

    // Fetch user balance
    async function refreshBalance() {
        if (!window.__douman_api) return;
        try {
            const res = await window.__douman_api.get('/miniapp', { params: { actions: 'wallet_info' } });
            if (res.data && res.data.obj) {
                currentBalance = Number(res.data.obj.balance || 0);
                updateWalletBadges();
            }
        } catch (e) {
            // Ignore if unauthorized
        }
    }

    // Update wallet elements in UI
    function updateWalletBadges() {
        const badge = document.getElementById('douman-header-wallet-badge');
        if (badge) {
            const valSpan = badge.querySelector('.douman-balance-val');
            if (valSpan) {
                valSpan.textContent = formatMoney(currentBalance) + ' تومان';
            }
        }
        const modalBal = document.getElementById('douman-modal-current-bal');
        if (modalBal) {
            modalBal.textContent = formatMoney(currentBalance) + ' تومان';
        }
    }

    // Inject Top Bar Wallet Badge
    function injectHeaderWallet() {
        if (document.getElementById('douman-header-wallet-badge')) return;
        const root = document.getElementById('root');
        if (!root) return;

        // Find header bar where theme toggle is
        const headerContainer = root.querySelector('div.flex.items-center.justify-center.mt-3') ||
                                root.querySelector('header') ||
                                root.querySelector('main')?.previousElementSibling;

        if (!headerContainer) return;

        const badge = document.createElement('div');
        badge.id = 'douman-header-wallet-badge';
        badge.style.cssText = `
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 12px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.25));
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #10b981;
            transition: all 0.2s ease;
            user-select: none;
        `;
        badge.innerHTML = `
            <span>💳</span>
            <span class="douman-balance-val">کیف پول: ${formatMoney(currentBalance)} تومان</span>
            <span style="background:#10b981; color:#fff; border-radius:50%; width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; font-size:12px; margin-right:4px;">+</span>
        `;
        badge.addEventListener('click', openRechargeModal);
        badge.addEventListener('mouseenter', () => { badge.style.transform = 'scale(1.03)'; });
        badge.addEventListener('mouseleave', () => { badge.style.transform = 'scale(1)'; });

        headerContainer.style.display = 'flex';
        headerContainer.style.alignItems = 'center';
        headerContainer.style.justifyContent = 'center';
        headerContainer.style.gap = '8px';
        headerContainer.appendChild(badge);
    }

    // Create & Show Recharge Modal
    function openRechargeModal() {
        if (isModalOpen) return;
        isModalOpen = true;

        let modal = document.getElementById('douman-recharge-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'douman-recharge-modal';
            modal.style.cssText = `
                position: fixed;
                inset: 0;
                z-index: 99998;
                background: rgba(0, 0, 0, 0.65);
                backdrop-filter: blur(4px);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                opacity: 0;
                transition: opacity 0.25s ease;
            `;
            document.body.appendChild(modal);
        }

        modal.innerHTML = `
            <div style="
                background: #18181b;
                color: #f4f4f5;
                width: 100%;
                max-width: 420px;
                border-radius: 20px;
                border: 1px solid rgba(255,255,255,0.1);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                overflow: hidden;
                direction: rtl;
                font-family: inherit;
                transform: scale(0.95);
                transition: transform 0.25s ease;
            " id="douman-modal-card">
                <!-- Modal Header -->
                <div style="padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:20px;">💳</span>
                        <h3 style="margin:0; font-size:17px; font-weight:700;">افزایش موجودی کیف پول</h3>
                    </div>
                    <button id="douman-modal-close" style="background:transparent; border:none; color:#a1a1aa; font-size:22px; cursor:pointer; line-height:1;">&times;</button>
                </div>

                <!-- Modal Body -->
                <div style="padding: 20px;">
                    <!-- Current balance banner -->
                    <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 12px 16px; display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px;">
                        <span style="color:#a1a1aa; font-size:13px;">موجودی فعلی شما:</span>
                        <span id="douman-modal-current-bal" style="font-weight:700; color:#10b981; font-size:15px;">${formatMoney(currentBalance)} تومان</span>
                    </div>

                    <!-- Quick amount buttons -->
                    <label style="display:block; font-size:13px; color:#d4d4d8; margin-bottom:8px; font-weight:600;">انتخاب سریع مبلغ:</label>
                    <div style="display:grid; grid-cols: 3; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px;">
                        <button type="button" class="douman-quick-btn" data-val="50000" style="padding:9px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:10px; color:#fff; font-size:12px; cursor:pointer; font-weight:600;">۵۰,۰۰۰</button>
                        <button type="button" class="douman-quick-btn" data-val="100000" style="padding:9px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:10px; color:#fff; font-size:12px; cursor:pointer; font-weight:600;">۱۰۰,۰۰۰</button>
                        <button type="button" class="douman-quick-btn" data-val="200000" style="padding:9px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:10px; color:#fff; font-size:12px; cursor:pointer; font-weight:600;">۲۰۰,۰۰۰</button>
                        <button type="button" class="douman-quick-btn" data-val="300000" style="padding:9px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:10px; color:#fff; font-size:12px; cursor:pointer; font-weight:600;">۳۰۰,۰۰۰</button>
                        <button type="button" class="douman-quick-btn" data-val="500000" style="padding:9px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:10px; color:#fff; font-size:12px; cursor:pointer; font-weight:600;">۵۰۰,۰۰۰</button>
                        <button type="button" class="douman-quick-btn" data-val="1000000" style="padding:9px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:10px; color:#fff; font-size:12px; cursor:pointer; font-weight:600;">۱,۰۰۰,۰۰۰</button>
                    </div>

                    <!-- Amount input -->
                    <label style="display:block; font-size:13px; color:#d4d4d8; margin-bottom:8px; font-weight:600;">مبلغ دلخواه (تومان):</label>
                    <div style="position:relative; margin-bottom: 10px;">
                        <input id="douman-recharge-input" type="text" inputmode="numeric" placeholder="مثال: ۲۵۰,۰۰۰" style="
                            width: 100%;
                            padding: 12px 14px 12px 60px;
                            background: rgba(0,0,0,0.3);
                            border: 1px solid rgba(255,255,255,0.15);
                            border-radius: 12px;
                            color: #fff;
                            font-size: 16px;
                            font-weight: 700;
                            box-sizing: border-box;
                            outline: none;
                        " />
                        <span style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#71717a; font-size:13px;">تومان</span>
                    </div>
                    <div style="font-size:11px; color:#a1a1aa; margin-bottom: 20px;">
                        ⚡ حداقل شارژ: <b>۵۰,۰۰۰</b> | حداکثر: <b>۱,۵۰۰,۰۰۰</b> تومان (درگاه آنلاین هوش‌پی)
                    </div>

                    <!-- Submit Button -->
                    <button id="douman-recharge-submit" style="
                        width: 100%;
                        padding: 14px;
                        background: linear-gradient(135deg, #10b981, #059669);
                        color: #fff;
                        border: none;
                        border-radius: 12px;
                        font-size: 15px;
                        font-weight: 700;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
                        transition: opacity 0.2s;
                    ">
                        <span>پرداخت آنلاین و افزایش موجودی</span>
                        <span>🚀</span>
                    </button>
                </div>
            </div>
        `;

        requestAnimationFrame(() => {
            modal.style.opacity = '1';
            const card = document.getElementById('douman-modal-card');
            if (card) card.style.transform = 'scale(1)';
        });

        // Close handlers
        const closeModal = () => {
            modal.style.opacity = '0';
            const card = document.getElementById('douman-modal-card');
            if (card) card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                isModalOpen = false;
                modal.remove();
            }, 250);
        };

        document.getElementById('douman-modal-close').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Quick buttons handler
        const input = document.getElementById('douman-recharge-input');
        modal.querySelectorAll('.douman-quick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modal.querySelectorAll('.douman-quick-btn').forEach(b => {
                    b.style.background = 'rgba(255,255,255,0.06)';
                    b.style.borderColor = 'rgba(255,255,255,0.12)';
                });
                btn.style.background = 'rgba(16, 185, 129, 0.2)';
                btn.style.borderColor = '#10b981';
                const val = btn.getAttribute('data-val');
                input.value = Number(val).toLocaleString('en-US');
            });
        });

        // Input formatting handler
        input.addEventListener('input', () => {
            let raw = toEnglishDigits(input.value).replace(/[^0-9]/g, '');
            if (raw) {
                input.value = Number(raw).toLocaleString('en-US');
            } else {
                input.value = '';
            }
        });

        // Submit recharge handler
        const submitBtn = document.getElementById('douman-recharge-submit');
        submitBtn.addEventListener('click', async () => {
            const rawAmount = toEnglishDigits(input.value).replace(/[^0-9]/g, '');
            const amount = parseInt(rawAmount, 10);

            if (!amount || isNaN(amount)) {
                showToast('لطفاً مبلغ شارژ را وارد کنید', true);
                return;
            }
            if (amount < 50000 || amount > 1500000) {
                showToast('مبلغ باید بین ۵۰,۰۰۰ الی ۱,۵۰۰,۰۰۰ تومان باشد', true);
                return;
            }

            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            submitBtn.innerHTML = '<span>در حال ایجاد درگاه هوش‌پی...</span> <span style="animation:spin 1s linear infinite;">⏳</span>';

            try {
                const api = window.__douman_api;
                if (!api) {
                    showToast('خطا در دسترسی به ارتباط سرور', true);
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    return;
                }

                const res = await api.post('/miniapp', {
                    actions: 'charge_wallet',
                    amount: amount
                });

                if (res.data && res.data.status && res.data.obj && res.data.obj.payment_url) {
                    const payUrl = res.data.obj.payment_url;
                    showToast('در حال هدایت به درگاه پرداخت هوش‌پی...');
                    closeModal();

                    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.openLink) {
                        window.Telegram.WebApp.openLink(payUrl);
                    } else {
                        window.open(payUrl, '_blank');
                    }
                } else {
                    const errMsg = (res.data && res.data.msg) ? res.data.msg : 'خطا در ایجاد لینک پرداخت';
                    showToast(errMsg, true);
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = '<span>پرداخت آنلاین و افزایش موجودی</span> <span>🚀</span>';
                }
            } catch (err) {
                const errMsg = err?.response?.data?.msg || err.message || 'خطا در برقراری ارتباط';
                showToast(errMsg, true);
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.innerHTML = '<span>پرداخت آنلاین و افزایش موجودی</span> <span>🚀</span>';
            }
        });
    }

    // Enhance Account Page with Recharge Button
    function enhanceAccountPage() {
        if (location.pathname.indexOf('/account') === -1) return;
        if (document.getElementById('douman-account-charge-btn')) return;

        // Search for the Balance card element
        const headings = Array.from(document.querySelectorAll('div, span, p'));
        const balLabel = headings.find(el => el.textContent && el.textContent.trim() === 'موجودی:');
        if (!balLabel) return;

        const container = balLabel.closest('div.bg-neutral-100, div.dark\\:bg-neutral-800') || balLabel.parentElement;
        if (!container) return;

        const chargeBtn = document.createElement('button');
        chargeBtn.id = 'douman-account-charge-btn';
        chargeBtn.style.cssText = `
            margin-top: 10px;
            width: 100%;
            padding: 10px 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: transform 0.15s;
        `;
        chargeBtn.innerHTML = '<span>➕ شارژ آنلاین کیف پول (هوش‌پی)</span>';
        chargeBtn.addEventListener('click', openRechargeModal);

        container.parentElement.insertBefore(chargeBtn, container.nextSibling);
    }

    // Enhance Purchase Success Screen (/buy Step 6) with QR Code & Link
    async function enhancePurchaseSuccessScreen() {
        if (location.pathname.indexOf('/buy') === -1) return;
        if (document.getElementById('douman-purchase-qr-container')) return;

        // Look for the success heading
        const headings = Array.from(document.querySelectorAll('h2'));
        const successHeading = headings.find(h => h.textContent && h.textContent.includes('خرید با موفقیت انجام شد'));
        if (!successHeading) return;

        // Extract username from the rendered service card
        const userH4 = document.querySelector('h4.font-bold');
        const username = userH4 ? userH4.textContent.trim() : '';

        if (!username) return;

        const parent = successHeading.closest('.space-y-6') || successHeading.parentElement;
        if (!parent) return;

        const qrContainer = document.createElement('div');
        qrContainer.id = 'douman-purchase-qr-container';
        qrContainer.style.cssText = `
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin: 16px 0;
            direction: rtl;
            text-align: center;
            font-family: inherit;
        `;
        qrContainer.innerHTML = `
            <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:12px;">
                <span style="font-size:20px;">📱</span>
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#10b981;">لینک سابسکریپشن و بارکد اتصال</h3>
            </div>
            <div id="douman-qr-loading" style="color:#a1a1aa; font-size:13px; padding:15px;">در حال دریافت اطلاعات اتصال سرویس... ⏳</div>
            <div id="douman-qr-content" style="display:none;"></div>
        `;

        // Insert before the action buttons
        const actionButtons = parent.querySelector('div.flex.flex-col.sm\\:flex-row') || parent.lastElementChild;
        parent.insertBefore(qrContainer, actionButtons);

        // Fetch service details from API
        try {
            const api = window.__douman_api;
            if (!api) return;

            const res = await api.get('/miniapp', { params: { actions: 'service', username: username } });
            const serviceData = res.data?.obj;

            const loading = document.getElementById('douman-qr-loading');
            const content = document.getElementById('douman-qr-content');
            if (loading) loading.style.display = 'none';
            if (!content) return;

            let subLink = '';
            const configs = [];

            if (serviceData && Array.isArray(serviceData.service_output)) {
                for (const item of serviceData.service_output) {
                    if (item.type === 'link' && item.value) {
                        subLink = item.value;
                    } else if (item.type === 'config' && Array.isArray(item.value)) {
                        configs.push(...item.value);
                    }
                }
            }

            if (!subLink && configs.length > 0) {
                subLink = configs[0];
            }

            if (!subLink) {
                content.style.display = 'block';
                content.innerHTML = `<p style="color:#f59e0b; font-size:13px;">لینک سابسکریپشن ثبت شد و در ربات برای شما ارسال گردید.</p>`;
                return;
            }

            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=10&data=${encodeURIComponent(subLink)}`;

            content.style.display = 'block';
            content.innerHTML = `
                <!-- QR Code Box -->
                <div style="display:inline-block; background:#fff; padding:10px; border-radius:12px; margin-bottom:14px; box-shadow:0 4px 15px rgba(0,0,0,0.3);">
                    <img src="${qrUrl}" alt="QR Code" style="width:190px; height:190px; display:block; border-radius:6px;" />
                </div>
                <div style="font-size:12px; color:#a1a1aa; margin-bottom:12px;">اسکن مستقیم با نرم‌افزارهای v2rayNG / Streisand / V2Box / Shadowrocket</div>

                <!-- Subscription Link Copy Input -->
                <div style="position:relative; margin-bottom:10px;">
                    <input type="text" readonly value="${subLink}" style="
                        width: 100%;
                        padding: 10px 12px;
                        background: rgba(0,0,0,0.3);
                        border: 1px solid rgba(255,255,255,0.15);
                        border-radius: 10px;
                        color: #10b981;
                        font-family: monospace;
                        font-size: 11px;
                        direction: ltr;
                        text-align: left;
                        box-sizing: border-box;
                    " />
                </div>
                <button onclick="window.doumanCopy('${subLink}', 'لینک سابسکریپشن')" style="
                    width: 100%;
                    padding: 11px;
                    background: rgba(16, 185, 129, 0.2);
                    border: 1px solid #10b981;
                    border-radius: 10px;
                    color: #10b981;
                    font-size: 13px;
                    font-weight: 700;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                ">
                    <span>📋 کپی لینک سابسکریپشن</span>
                </button>
            `;
        } catch (e) {
            console.error('Failed to load service detail:', e);
            const loading = document.getElementById('douman-qr-loading');
            if (loading) loading.innerHTML = 'اطلاعات اکانت در ربات تلگرام برای شما ارسال شد.';
        }
    }

    // Multi-user pricing calculation (+150k for user 2, +150k for user 3, +100k for user 4, +100k for user 5)
    function calcUserPrice(basePrice, userCount) {
        basePrice = Number(basePrice) || 0;
        userCount = parseInt(userCount, 10) || 1;
        if (userCount <= 1) return basePrice;
        let extra = 0;
        const extraUsers = userCount - 1;
        for (let i = 1; i <= extraUsers; i++) {
            if (i <= 2) {
                extra += 150000;
            } else {
                extra += 100000;
            }
        }
        return basePrice + extra;
    }

    // Setup Axios request and response interceptors
    function setupApiInterceptors() {
        if (!window.__douman_api || window.__douman_interceptors_done) return;
        window.__douman_interceptors_done = true;

        // Response interceptor: cache returned products
        window.__douman_api.interceptors.response.use((response) => {
            try {
                if (response.config && response.config.params && response.config.params.actions === 'services') {
                    if (response.data && Array.isArray(response.data.obj)) {
                        window.__douman_services_cache = response.data.obj;
                    }
                }
            } catch (e) {}
            return response;
        }, (err) => Promise.reject(err));

        // Request interceptor: attach selected user_limit to purchase
        window.__douman_api.interceptors.request.use((config) => {
            try {
                if (config.data && (config.data.actions === 'purchase' || config.data.actions === 'buy')) {
                    if (window.__selected_user_limit !== undefined && window.__selected_user_limit !== null) {
                        config.data.user_limit = window.__selected_user_limit;
                    }
                }
            } catch (e) {}
            return config;
        }, (err) => Promise.reject(err));
    }

    // Enhance Step 5 (Order Confirmation) with Multi-User selector
    function enhanceStep5MultiUser() {
        setupApiInterceptors();

        // Check if we are on confirmation screen (Look for "خلاصه سفارش")
        const allHeadings = Array.from(document.querySelectorAll('h2, h3, div, span'));
        const summaryHeading = allHeadings.find(el => (el.textContent || '').trim() === 'خلاصه سفارش');
        if (!summaryHeading) {
            return;
        }

        // Find the card container containing summaryHeading
        const card = summaryHeading.closest('div.border, div.rounded-lg, div.rounded-xl, div.bg-card') || summaryHeading.parentElement?.parentElement;
        if (!card) return;

        // Find the total price container (contains "قیمت کل:")
        const allSpans = Array.from(card.querySelectorAll('span, div'));
        const totalLabelSpan = allSpans.find(el => (el.textContent || '').trim().startsWith('قیمت کل'));
        if (!totalLabelSpan) return;

        const totalRow = totalLabelSpan.closest('div.flex.justify-between') || totalLabelSpan.parentElement;
        if (!totalRow) return;

        // Find price element inside totalRow
        const priceEl = totalRow.querySelector('.text-primary') || totalRow.lastElementChild;
        if (!priceEl) return;

        // Find service name from the summary card
        let serviceName = '';
        const serviceLabel = allSpans.find(el => (el.textContent || '').trim() === 'سرویس:');
        if (serviceLabel && serviceLabel.nextElementSibling) {
            serviceName = (serviceLabel.nextElementSibling.textContent || '').trim();
        }

        // If card already inserted for this exact service, just keep it
        const existingCard = document.getElementById('douman-multiuser-card');
        if (existingCard) {
            if (existingCard.getAttribute('data-service') === serviceName) {
                return;
            } else {
                existingCard.remove();
            }
        }

        // Extract base price
        const rawText = toEnglishDigits(priceEl.textContent.replace(/[^0-9۰-۹]/g, ''));
        const currentPriceVal = parseInt(rawText, 10);
        if (!currentPriceVal || isNaN(currentPriceVal)) return;

        // Look up product in cache to check limit_ip
        const cachedProduct = (window.__douman_services_cache || []).find(p => p.name === serviceName);
        const prodLimit = cachedProduct ? parseInt(cachedProduct.limit_ip, 10) : 1;

        window.__current_base_price = currentPriceVal;
        window.__current_service_name = serviceName;

        const container = document.createElement('div');
        container.id = 'douman-multiuser-card';
        container.setAttribute('data-service', serviceName);
        container.style.cssText = `
            margin: 14px 0 10px 0;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-sizing: border-box;
        `;

        if (prodLimit === 0) {
            // Unlimited users
            window.__selected_user_limit = 0;
            container.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="font-size: 22px;">👥</div>
                    <div>
                        <div style="font-weight: 700; color: #10b981; font-size: 13px;">بدون محدودیت کاربر (اتصال همزمان نامحدود)</div>
                        <div style="font-size: 11px; color: #a1a1aa; margin-top: 2px;">این سرویس به صورت نامحدود و بدون محدودیت IP تنظیم می‌شود.</div>
                    </div>
                </div>
            `;
        } else {
            // Multi-user choice (1 to 5)
            window.__selected_user_limit = 1;
            container.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="font-weight: 700; font-size: 13px; color: #f4f4f5; display:flex; align-items:center; gap:6px;">
                        <span>👥</span>
                        <span>تعداد کاربر مجاز (اتصال همزمان)</span>
                    </div>
                    <span id="douman-user-count-badge" style="
                        font-size: 11px;
                        font-weight: 700;
                        background: rgba(59, 130, 246, 0.2);
                        color: #60a5fa;
                        border: 1px solid #3b82f6;
                        padding: 2px 8px;
                        border-radius: 20px;
                    ">۱ کاربره</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px;" id="douman-pills-row">
                    <button type="button" class="douman-user-pill active" data-u="1" style="
                        background: rgba(59, 130, 246, 0.25);
                        border: 1.5px solid #3b82f6;
                        border-radius: 10px;
                        padding: 8px 2px;
                        color: #fff;
                        cursor: pointer;
                        text-align: center;
                    ">
                        <div style="font-weight: 800; font-size: 13px;">۱</div>
                        <div style="font-size: 9px; color: #93c5fd; margin-top: 2px;">تک کاربر</div>
                    </button>
                    <button type="button" class="douman-user-pill" data-u="2" style="
                        background: rgba(255, 255, 255, 0.04);
                        border: 1px solid rgba(255, 255, 255, 0.12);
                        border-radius: 10px;
                        padding: 8px 2px;
                        color: #d4d4d8;
                        cursor: pointer;
                        text-align: center;
                    ">
                        <div style="font-weight: 800; font-size: 13px;">۲</div>
                        <div style="font-size: 9px; color: #10b981; margin-top: 2px;">+۱۵۰ ت</div>
                    </button>
                    <button type="button" class="douman-user-pill" data-u="3" style="
                        background: rgba(255, 255, 255, 0.04);
                        border: 1px solid rgba(255, 255, 255, 0.12);
                        border-radius: 10px;
                        padding: 8px 2px;
                        color: #d4d4d8;
                        cursor: pointer;
                        text-align: center;
                    ">
                        <div style="font-weight: 800; font-size: 13px;">۳</div>
                        <div style="font-size: 9px; color: #10b981; margin-top: 2px;">+۳۰۰ ت</div>
                    </button>
                    <button type="button" class="douman-user-pill" data-u="4" style="
                        background: rgba(255, 255, 255, 0.04);
                        border: 1px solid rgba(255, 255, 255, 0.12);
                        border-radius: 10px;
                        padding: 8px 2px;
                        color: #d4d4d8;
                        cursor: pointer;
                        text-align: center;
                    ">
                        <div style="font-weight: 800; font-size: 13px;">۴</div>
                        <div style="font-size: 9px; color: #10b981; margin-top: 2px;">+۴۰۰ ت</div>
                    </button>
                    <button type="button" class="douman-user-pill" data-u="5" style="
                        background: rgba(255, 255, 255, 0.04);
                        border: 1px solid rgba(255, 255, 255, 0.12);
                        border-radius: 10px;
                        padding: 8px 2px;
                        color: #d4d4d8;
                        cursor: pointer;
                        text-align: center;
                    ">
                        <div style="font-weight: 800; font-size: 13px;">۵</div>
                        <div style="font-size: 9px; color: #10b981; margin-top: 2px;">+۵۰۰ ت</div>
                    </button>
                </div>
                <div style="font-size: 11px; color: #71717a; margin-top: 8px; text-align: center;">
                    کاربر اضافه ۱ و ۲: نفری ۱۵۰,۰۰۰ ت • کاربر ۳ تا ۵: نفری ۱۰۰,۰۰۰ ت
                </div>
            `;

            // Setup click events on pills
            const pills = container.querySelectorAll('.douman-user-pill');
            pills.forEach(pill => {
                pill.addEventListener('click', () => {
                    const chosen = parseInt(pill.getAttribute('data-u'), 10) || 1;
                    window.__selected_user_limit = chosen;

                    // Update pills appearance
                    pills.forEach(p => {
                        const isThis = p === pill;
                        p.style.background = isThis ? 'rgba(59, 130, 246, 0.25)' : 'rgba(255, 255, 255, 0.04)';
                        p.style.border = isThis ? '1.5px solid #3b82f6' : '1px solid rgba(255, 255, 255, 0.12)';
                        p.style.color = isThis ? '#fff' : '#d4d4d8';
                    });

                    // Update badge
                    const badge = container.querySelector('#douman-user-count-badge');
                    if (badge) {
                        badge.textContent = chosen + ' کاربره';
                    }

                    // Update total price text in order summary card
                    const newTotal = calcUserPrice(window.__current_base_price, chosen);
                    priceEl.textContent = formatMoney(newTotal) + ' تومان';
                });
            });
        }

        // Insert container right before totalRow
        totalRow.parentNode.insertBefore(container, totalRow);
    }

    // Periodic observer for page navigation and layout updates
    let pollInterval = null;
    function startObserver() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            injectHeaderWallet();
            enhanceAccountPage();
            enhancePurchaseSuccessScreen();
            enhanceStep5MultiUser();
        }, 800);

        // Also refresh balance initially and every 30 seconds
        refreshBalance();
        setInterval(refreshBalance, 30000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startObserver);
    } else {
        startObserver();
    }
})();
