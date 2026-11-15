/**
 * Arka/Voldemort Cloud Service Details & QR Code Flow
 */
(function() {
    'use strict';
    const App = window.App;

    App.renderServices = async function(container) {
        container.innerHTML = `<div style="text-align: center; padding: 40px; color: var(--text-muted);">در حال دریافت سرویس‌های شما...</div>`;

        const res = await App.api('invoices');
        if (!res.status || !Array.isArray(res.obj) || res.obj.length === 0) {
            container.innerHTML = `
                <div class="card" style="text-align: center; padding: 40px 20px;">
                    <div style="font-size: 42px; margin-bottom: 12px; opacity: 0.6;">📦</div>
                    <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">شما هنوز سرویسی ندارید</div>
                    <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">از منوی فروشگاه می‌توانید اشتراک مورد نظر خود را خریداری کنید.</div>
                    <div style="max-width: 200px; margin: 0 auto;">
                        <button class="btn-lime" onclick="App.navigate('shop')">رفتن به فروشگاه</button>
                    </div>
                </div>
            `;
            return;
        }

        App.myServices = res.obj;
        container.innerHTML = `
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 14px;">لیست سرویس‌های فعال:</div>
            ${res.obj.map(s => App.buildServiceCard(s)).join('')}
        `;
    };

    App.openServiceDetail = async function(username) {
        const container = document.getElementById('content-area');
        if (!container) return;

        App.selectedServiceDetail = username;
        document.getElementById('back-button').style.display = 'flex';
        document.getElementById('topbar-title').innerText = 'جزئیات سرویس';

        container.innerHTML = `<div style="text-align: center; padding: 40px; color: var(--text-muted);">در حال بارگذاری اطلاعات سرویس...</div>`;

        const res = await App.api('service', 'GET', { username: username });
        if (!res.status || !res.obj) {
            container.innerHTML = `<div class="card" style="text-align: center; padding: 30px;">اطلاعات این سرویس یافت نشد.</div>`;
            return;
        }

        const s = res.obj;
        let subUrl = s.sub_url || s.subscription_url || '';
        let configs = Array.isArray(s.configs) ? s.configs.slice() : [];

        // Dynamic fallback from service_output if fields are inside array
        if (Array.isArray(s.service_output)) {
            s.service_output.forEach(item => {
                if ((item.type === 'link' || item.type === 'sub') && !subUrl) {
                    subUrl = item.value;
                } else if (item.type === 'config') {
                    if (Array.isArray(item.value)) {
                        item.value.forEach(v => { if (typeof v === 'string' && v && !configs.includes(v)) configs.push(v); });
                    } else if (typeof item.value === 'string' && item.value && !configs.includes(item.value)) {
                        configs.push(item.value);
                    }
                }
            });
        }
        if (!subUrl && configs.length > 0) {
            subUrl = configs[0];
        }
        let currentConfigIndex = 0;

        container.innerHTML = `
            <!-- Subscription Card -->
            <div class="card">
                <div style="font-size: 13.5px; font-weight: 700; margin-bottom: 10px;">لینک اشتراک</div>
                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <input type="text" readonly value="${subUrl}" 
                           style="flex: 1; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; color: #fff; font-size: 12px; direction: ltr; text-overflow: ellipsis;">
                    <button class="btn-secondary" onclick="App.copyText('${subUrl}')" style="flex-shrink: 0;">📋 کپی</button>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">این لینک را در برنامه خود وارد کنید تا تمام کانفیگ‌ها به صورت خودکار اضافه شوند.</div>
                <button class="btn-secondary" style="width: 100%; border-color: var(--accent-lime); color: var(--accent-lime);" onclick="App.showQrModal('${subUrl}', 'لینک اشتراک')">
                    📱 نمایش کد QR
                </button>
            </div>

            <!-- Single Configs Slider Card -->
            ${configs.length > 0 ? `
                <div class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 13.5px; font-weight: 700;">کانفیگ‌های مجزا</span>
                        <span style="font-size: 12px; color: var(--text-secondary);" id="config-counter">کانفیگ ۱ از ${configs.length}</span>
                    </div>

                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <input type="text" readonly id="config-input" value="${configs[0]}" 
                               style="flex: 1; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; color: #fff; font-size: 12px; direction: ltr; text-overflow: ellipsis;">
                        <button class="btn-secondary" id="config-copy-btn" onclick="App.copyText('${configs[0]}')" style="flex-shrink: 0;">📋 کپی</button>
                    </div>

                    <button class="btn-secondary" style="width: 100%; margin-bottom: 14px;" id="config-qr-btn" onclick="App.showQrModal('${configs[0]}', 'کانفیگ')">
                        📱 نمایش کد QR
                    </button>

                    <div style="display: flex; gap: 10px;">
                        <button class="btn-secondary" id="prev-config-btn" style="flex: 1;" ${configs.length <= 1 ? 'disabled' : ''}>&lsaquo; کانفیگ قبلی</button>
                        <button class="btn-secondary" id="next-config-btn" style="flex: 1;" ${configs.length <= 1 ? 'disabled' : ''}>کانفیگ بعدی &rsaquo;</button>
                    </div>
                </div>
            ` : ''}

            <!-- Service Actions Card -->
            <div class="card">
                <div style="font-size: 13.5px; font-weight: 700; margin-bottom: 14px;">مدیریت سرویس</div>
                <button class="btn-lime" style="margin-bottom: 12px;" onclick="App.navigate('shop')">
                    🔄 تمدید سرویس
                </button>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button class="btn-secondary" style="width: 100%; justify-content: flex-start;" onclick="App.copyText('${configs.join('\\n')}')">
                        📋 کپی تمامی کانفیگ‌ها
                    </button>
                    <button class="btn-secondary" style="width: 100%; justify-content: flex-start;" onclick="App.navigate('tutorials')">
                        📱 راهنمای اتصال و آموزش‌ها
                    </button>
                    <button class="btn-secondary" style="width: 100%; justify-content: flex-start;" onclick="App.navigate('support')">
                        🎧 پشتیبانی این سرویس
                    </button>
                </div>
            </div>
        `;

        // Bind config slider
        if (configs.length > 1) {
            const nextBtn = document.getElementById('next-config-btn');
            const prevBtn = document.getElementById('prev-config-btn');
            const inp = document.getElementById('config-input');
            const counter = document.getElementById('config-counter');
            const copyBtn = document.getElementById('config-copy-btn');
            const qrBtn = document.getElementById('config-qr-btn');

            const updateSlider = (idx) => {
                currentConfigIndex = idx;
                inp.value = configs[idx];
                counter.innerText = `کانفیگ ${idx + 1} از ${configs.length}`;
                copyBtn.onclick = () => App.copyText(configs[idx]);
                qrBtn.onclick = () => App.showQrModal(configs[idx], `کانفیگ ${idx + 1}`);
            };

            nextBtn.onclick = () => {
                const nextIdx = (currentConfigIndex + 1) % configs.length;
                updateSlider(nextIdx);
            };

            prevBtn.onclick = () => {
                const prevIdx = (currentConfigIndex - 1 + configs.length) % configs.length;
                updateSlider(prevIdx);
            };
        }
    };

    App.showQrModal = function(text, title) {
        if (!text) return;
        let modal = document.getElementById('qr-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'qr-modal';
            modal.style.cssText = `
                position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999;
                display: flex; align-items: center; justify-content: center; padding: 20px;
                backdrop-filter: blur(4px);
            `;
            modal.innerHTML = `
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 24px; max-width: 320px; width: 100%; text-align: center;">
                    <div style="font-weight: 700; font-size: 16px; margin-bottom: 16px;" id="qr-modal-title">کد QR</div>
                    <div id="qrcode-box" style="background: #fff; padding: 16px; border-radius: 12px; display: inline-block; margin-bottom: 18px;"></div>
                    <button class="btn-lime" onclick="document.getElementById('qr-modal').style.display='none'">بستن</button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        }

        document.getElementById('qr-modal-title').innerText = title || 'کد QR';
        const box = document.getElementById('qrcode-box');
        box.innerHTML = '';

        if (window.QRCode) {
            new window.QRCode(box, {
                text: text,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: window.QRCode.CorrectLevel.M
            });
        } else {
            box.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}" width="200" height="200">`;
        }

        modal.style.display = 'flex';
    };

})();
