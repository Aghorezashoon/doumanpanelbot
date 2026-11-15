/**
 * Mini App View Renderers
 */
(function() {
    'use strict';
    const App = window.App;

    App.renderSidebar = function() {
        const items = [
            { id: 'home', label: 'خانه', icon: '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>' },
            { id: 'shop', label: 'فروشگاه', icon: '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/>' },
            { id: 'services', label: 'سرویس‌ها', icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>' },
            { id: 'profile', label: 'پروفایل', icon: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>' },
            { id: 'test', label: 'تست رایگان', icon: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>' },
            { id: 'wallet', label: 'کیف پول', icon: '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>' },
            { id: 'giftcard', label: 'ثبت کارت هدیه', icon: '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>' },
            { id: 'affiliate', label: 'زیرمجموعه‌گیری', icon: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
            { id: 'tariffs', label: 'تعرفه', icon: '<path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/>' },
            { id: 'tutorials', label: 'آموزش و اپلیکیشن‌ها', icon: '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>' },
            { id: 'agency', label: 'درخواست نمایندگی', icon: '<path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/>' },
            { id: 'support', label: 'پشتیبانی', icon: '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>' }
        ];

        const nav = document.getElementById('sidebar-nav');
        if (!nav) return;
        nav.innerHTML = items.map(it => `
            <div class="nav-item ${it.id === 'home' ? 'active' : ''}" data-tab="${it.id}">
                <svg viewBox="0 0 24 24">${it.icon}</svg>
                <span>${it.label}</span>
            </div>
        `).join('');

        nav.querySelectorAll('.nav-item').forEach(el => {
            el.addEventListener('click', () => App.navigate(el.dataset.tab));
        });
    };

    App.renderMobileNav = function() {
        const mItems = [
            { id: 'home', label: 'خانه', icon: '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>' },
            { id: 'shop', label: 'فروشگاه', icon: '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>' },
            { id: 'services', label: 'سرویس‌ها', icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>' },
            { id: 'wallet', label: 'کیف پول', icon: '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>' },
            { id: 'profile', label: 'پروفایل', icon: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>' }
        ];
        const mNav = document.getElementById('mobile-bottom-nav');
        if (!mNav) return;
        mNav.innerHTML = mItems.map(it => `
            <div class="m-nav-item ${it.id === 'home' ? 'active' : ''}" data-tab="${it.id}">
                <svg viewBox="0 0 24 24">${it.icon}</svg>
                <span>${it.label}</span>
            </div>
        `).join('');

        mNav.querySelectorAll('.m-nav-item').forEach(el => {
            el.addEventListener('click', () => App.navigate(el.dataset.tab));
        });
    };

    App.renderHome = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;
        const username = App.user?.name || App.user?.username || 'کاربر گرامی';
        const balance = Number(App.user?.balance || 0).toLocaleString('fa-IR');

        const guestBanner = !App.token ? `
            <div class="card" style="background: linear-gradient(135deg, rgba(212, 248, 60, 0.12), rgba(15, 23, 42, 0.9)); border: 1px solid var(--accent-lime-border); padding: 18px 20px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 4px;">🚀 نسخه وب‌اپلیکیشن ${App.systemInfo?.title || ''}</div>
                    <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.6;">جهت خرید سرویس، مشاهده اشتراک‌ها و شارژ کیف پول مستقیم از سایت، با حساب تلگرام خود وارد شوید.</div>
                </div>
                <button class="btn-lime" onclick="App.navigate('profile')" style="width: auto; padding: 10px 22px; font-size: 13px; white-space: nowrap;">
                    🔐 ورود با تلگرام
                </button>
            </div>
        ` : '';

        container.innerHTML = `
            ${guestBanner}
            <div style="margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 20px; font-weight: 800;">سلام ${username} 👾</h2>
            </div>

            <!-- Wallet summary card -->
            <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px;">
                <div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">موجودی کیف پول</div>
                    <div style="font-size: 18px; font-weight: 800; color: #fff;">${balance} <span style="font-size: 12px; font-weight: normal; color: var(--text-secondary);">تومان</span></div>
                </div>
                <button class="btn-secondary" style="padding: 8px 16px; border-radius: 20px;" onclick="App.navigate('wallet')">افزایش موجودی</button>
            </div>

            <!-- Promo Carousel -->
            <div class="promo-banner">
                <div>
                    <div class="promo-title">خرید سرویس جدید!</div>
                    <div class="promo-sub">سرویس مورد نظرتو انتخاب کن و در چند ثانیه فعالش کن!</div>
                    <button class="promo-btn" onclick="App.navigate('shop')">شروع خرید &rsaquo;</button>
                </div>
                <div style="font-size: 48px;">🚀</div>
            </div>

            <!-- Quick Access Grid -->
            <div style="margin-top: 24px;">
                <div style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--text-primary);">دسترسی سریع</div>
                <div class="quick-grid">
                    <div class="quick-card" onclick="App.navigate('shop')">
                        <div class="quick-card-icon">🛍️</div>
                        <div class="quick-card-label">خرید سرویس جدید</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('services')">
                        <div class="quick-card-icon">⚡</div>
                        <div class="quick-card-label">سرویس‌های من</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('test')">
                        <div class="quick-card-icon">⭐</div>
                        <div class="quick-card-label">تست رایگان</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('wallet')">
                        <div class="quick-card-icon">👛</div>
                        <div class="quick-card-label">کیف پول</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('profile')">
                        <div class="quick-card-icon">👤</div>
                        <div class="quick-card-label">حساب کاربری</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('giftcard')">
                        <div class="quick-card-icon">🎁</div>
                        <div class="quick-card-label">ثبت کارت هدیه</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('affiliate')">
                        <div class="quick-card-icon">👥</div>
                        <div class="quick-card-label">زیرمجموعه‌گیری</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('tariffs')">
                        <div class="quick-card-icon">🏷️</div>
                        <div class="quick-card-label">تعرفه</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('tutorials')">
                        <div class="quick-card-icon">📱</div>
                        <div class="quick-card-label">آموزش و دانلود</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('agency')">
                        <div class="quick-card-icon">👑</div>
                        <div class="quick-card-label">درخواست نمایندگی</div>
                    </div>
                    <div class="quick-card" onclick="App.navigate('support')">
                        <div class="quick-card-icon">🎧</div>
                        <div class="quick-card-label">پشتیبانی</div>
                    </div>
                </div>
            </div>

            <!-- Services Section -->
            <div style="margin-top: 30px;">
                <div style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--text-primary);">سرویس‌های من</div>
                <div id="home-services-list">
                    <div style="text-align: center; padding: 24px; color: var(--text-muted);">در حال دریافت سرویس‌ها...</div>
                </div>
            </div>
        `;

        // Fetch user services
        const res = await App.api('invoices');
        const listEl = document.getElementById('home-services-list');
        if (!listEl) return;

        if (res.status && Array.isArray(res.obj) && res.obj.length > 0) {
            App.myServices = res.obj;
            listEl.innerHTML = res.obj.slice(0, 3).map(s => App.buildServiceCard(s)).join('');
        } else {
            listEl.innerHTML = `
                <div class="card" style="text-align: center; padding: 40px 20px;">
                    <div style="font-size: 42px; margin-bottom: 12px; opacity: 0.6;">📦</div>
                    <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">هنوز سرویسی ندارید</div>
                    <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">از فروشگاه یک سرویس بخرید تا اینجا نمایش داده شود.</div>
                    <div style="max-width: 220px; margin: 0 auto;">
                        <button class="btn-lime" onclick="App.navigate('shop')">رفتن به فروشگاه</button>
                    </div>
                </div>
            `;
        }
    };

    App.buildServiceCard = function(s) {
        const usedGb = Number(s.traffic_used_gb || 0);
        const totalGb = Number(s.traffic_total_gb || s.Volume || 0);
        const percent = totalGb > 0 ? Math.min(100, Math.round((usedGb / totalGb) * 100)) : 0;
        const rawStatus = String(s.status || s.Status || 'active').toLowerCase().trim();
        let statusBadge = '';
        if (rawStatus === 'active') {
            statusBadge = '<span style="background: rgba(16, 185, 129, 0.15); color: #10B981; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">فعال</span>';
        } else if (rawStatus === 'limited' || rawStatus === 'end_of_volume') {
            statusBadge = '<span style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">پایان حجم</span>';
        } else if (rawStatus === 'expired' || rawStatus === 'end_of_time') {
            statusBadge = '<span style="background: rgba(239, 68, 68, 0.15); color: #EF4444; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">منقضی شده</span>';
        } else if (rawStatus === 'on_hold' || rawStatus === 'send_on_hold') {
            statusBadge = '<span style="background: rgba(59, 130, 246, 0.15); color: #3B82F6; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">در انتظار اتصال</span>';
        } else {
            statusBadge = `<span style="background: rgba(148, 163, 184, 0.15); color: #94A3B8; padding: 3px 10px; border-radius: 12px; font-size: 11px;">${rawStatus}</span>`;
        }

        return `
            <div class="card" style="cursor: pointer; transition: border-color 0.2s;" onclick="App.openServiceDetail('${s.username}')">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="font-weight: 700; font-size: 15px;">${s.name_product || s.username}</div>
                    ${statusBadge}
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; display: flex; justify-content: space-between;">
                    <span>مصرف: ${usedGb} GB از ${totalGb > 0 ? totalGb + ' GB' : 'نامحدود'}</span>
                    <span>${percent}%</span>
                </div>
                <div style="width: 100%; height: 6px; background: #223049; border-radius: 4px; overflow: hidden; margin-bottom: 12px;">
                    <div style="width: ${percent}%; height: 100%; background: var(--accent-lime); border-radius: 4px;"></div>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--text-muted);">
                    <span>لوکیشن: ${s.Service_location || 'سرور اختصاصی'}</span>
                    <span style="color: var(--accent-lime); font-weight: 600;">مدیریت سرویس &rsaquo;</span>
                </div>
            </div>
        `;
    };

    App.renderProfile = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        // If user is NOT logged in, render the Telegram Web Login interface!
        if (!App.token || !App.user) {
            container.innerHTML = `
                <div class="card" style="text-align: center; padding: 30px 20px;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #1E293B, #0F172A); border: 2px solid var(--accent-lime); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 0 25px rgba(212, 248, 60, 0.25);">
                        🔐
                    </div>
                    <div style="font-size: 19px; font-weight: 800; color: #fff; margin-bottom: 6px;">ورود به حساب کاربری (نسخه وب)</div>
                    <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.7; max-width: 340px; margin: 0 auto 22px;">
                        جهت مشاهده سرویس‌ها، شارژ کیف پول و خرید مستقیم اشتراک از سایت، لطفاً با حساب تلگرام خود وارد شوید.
                    </div>

                    <!-- Step 1: Request Code -->
                    <div id="login-step-request" style="max-width: 360px; margin: 0 auto; text-align: right;">
                        <label style="font-size: 12.5px; color: var(--text-secondary); display: block; margin-bottom: 6px;">
                            شناسه عددی یا آیدی تلگرام شما:
                        </label>
                        <input type="text" id="login-query-inp" placeholder="مثال: 123456789 یا username@" 
                               style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; color: #fff; font-size: 14px; direction: ltr; text-align: left; margin-bottom: 14px; outline: none;">
                        
                        <button class="btn-lime" id="login-request-btn" onclick="App.requestWebLoginCode()" style="margin-bottom: 16px;">
                            📩 ارسال کد ورود به تلگرام
                        </button>

                        <div style="font-size: 12px; color: var(--text-muted); text-align: center; line-height: 1.6; margin-bottom: 16px;">
                            یا می‌توانید بدون نیاز به کد، مستقیماً از ربات تلگرام وارد شوید:
                        </div>

                        <button class="btn-secondary" onclick="App.openBotLogin()" style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; font-size: 13px; padding: 11px; cursor: pointer;">
                            <span>🚀 ورود یک‌کلیکه از ربات تلگرام</span>
                        </button>
                    </div>

                    <!-- Step 2: Verify 5-digit Code -->
                    <div id="login-step-verify" style="display: none; max-width: 360px; margin: 0 auto; text-align: right;">
                        <div style="background: rgba(212, 248, 60, 0.1); border: 1px solid var(--accent-lime-border); border-radius: 10px; padding: 10px 14px; font-size: 12px; color: var(--accent-lime); margin-bottom: 14px; text-align: center;">
                            ✓ کد تأیید ۵ رقمی به چت ربات تلگرام شما ارسال شد!
                        </div>
                        <label style="font-size: 12.5px; color: var(--text-secondary); display: block; margin-bottom: 6px;">
                            کد تأیید ۵ رقمی:
                        </label>
                        <input type="number" id="login-code-inp" placeholder="• • • • •" 
                               style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; color: #fff; font-size: 20px; font-weight: 800; letter-spacing: 8px; text-align: center; margin-bottom: 14px; outline: none;">
                        
                        <button class="btn-lime" id="login-verify-btn" onclick="App.verifyWebLoginCode()" style="margin-bottom: 10px;">
                            ✓ تأیید کد و ورود به حساب
                        </button>

                        <button class="btn-secondary" onclick="document.getElementById('login-step-verify').style.display='none'; document.getElementById('login-step-request').style.display='block';" style="width: 100%; font-size: 12px; padding: 8px;">
                            بازگشت و تغییر شناسه
                        </button>
                    </div>
                </div>
            `;
            return;
        }

        const user = App.user || {};
        const username = user.username ? '@' + user.username : (user.name || 'کاربر گرامی');
        const balance = Number(user.balance || 0).toLocaleString('fa-IR');
        const groupType = user.group_type || 'کاربر عادی';
        const phone = user.phone && user.phone !== 'none' ? user.phone : 'ثبت نشده';
        const timeJoin = user.time_join || '—';
        const invCode = user.codeInvitation || '—';

        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 28px 20px; position: relative;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #1E293B, #0F172A); border: 2px solid var(--accent-lime); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: 0 0 20px rgba(212, 248, 60, 0.2);">
                    👤
                </div>
                <div style="font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 4px;">${username}</div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(212, 248, 60, 0.12); color: var(--accent-lime); border: 1px solid var(--accent-lime-border); border-radius: 20px; padding: 4px 14px; font-size: 12px; font-weight: 600; margin-bottom: 16px;">
                    ⭐ سطح: ${groupType}
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-align: center;">
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 4px;">موجودی کیف پول</div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--accent-lime);">${balance} <span style="font-size: 11px;">تومان</span></div>
                    </div>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-align: center;">
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 4px;">سفارش‌های فعال</div>
                        <div style="font-size: 15px; font-weight: 800; color: #fff;">${user.count_order || 0}</div>
                    </div>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-align: center;">
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 4px;">تراکنش‌های موفق</div>
                        <div style="font-size: 15px; font-weight: 800; color: #fff;">${user.count_payment || 0}</div>
                    </div>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-align: center;">
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 4px;">زیرمجموعه‌ها</div>
                        <div style="font-size: 15px; font-weight: 800; color: #fff;">${user.affiliatescount || 0} نفر</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 14px; color: var(--text-primary);">مشخصات کاربری</div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                        <span style="color: var(--text-secondary);">شناسه عددی (User ID)</span>
                        <span style="font-weight: 600; font-family: monospace; color: #fff; display: flex; align-items: center; gap: 6px;">
                            ${user.id || '—'}
                            <button class="btn-secondary" style="padding: 2px 8px; font-size: 11px;" onclick="App.copyText('${user.id}')">کپی</button>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                        <span style="color: var(--text-secondary);">شماره همراه</span>
                        <span style="font-weight: 600; color: #fff; direction: ltr;">${phone}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                        <span style="color: var(--text-secondary);">تاریخ عضویت</span>
                        <span style="font-weight: 600; color: #fff;">${timeJoin}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: var(--text-secondary);">کد دعوت اختصاصی</span>
                        <span style="font-weight: 700; color: var(--accent-lime); font-family: monospace;">${invCode}</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn-lime" onclick="App.navigate('wallet')">
                    💳 شارژ و افزایش موجودی کیف پول
                </button>
                <button class="btn-secondary" style="width: 100%;" onclick="App.createWebLoginLink()">
                    🔗 دریافت لینک ورود سریع برای مرورگر دسکتاپ
                </button>
                <button class="btn-secondary" style="width: 100%;" onclick="App.navigate('giftcard')">
                    🎁 ثبت کارت هدیه
                </button>
                <button class="btn-secondary" style="width: 100%; color: #f43f5e; border-color: rgba(244, 63, 94, 0.3);" onclick="App.logout()">
                    🚪 خروج از حساب کاربری
                </button>
            </div>
        `;
    };

    App.requestWebLoginCode = async function() {
        const inp = document.getElementById('login-query-inp');
        const q = (inp ? inp.value : '').trim();
        if (!q) {
            App.toast('لطفاً شناسه کاربری یا آیدی تلگرام خود را وارد کنید.', true);
            return;
        }

        const btn = document.getElementById('login-request-btn');
        if (btn) { btn.disabled = true; btn.innerText = 'در حال ارسال کد به تلگرام...'; }

        const res = await App.api('web_login_request', 'POST', { query: q });
        if (btn) { btn.disabled = false; btn.innerText = '📩 ارسال کد ورود به تلگرام'; }

        if (res && res.status) {
            App.toast(res.msg || 'کد ورود به تلگرام شما ارسال شد!');
            const s1 = document.getElementById('login-step-request');
            const s2 = document.getElementById('login-step-verify');
            if (s1 && s2) {
                s1.style.display = 'none';
                s2.style.display = 'block';
                const codeInp = document.getElementById('login-code-inp');
                if (codeInp) codeInp.focus();
            }
        } else {
            App.toast(res.msg || 'کاربر یافت نشد یا خطا در ارسال پیام', true);
        }
    };

    App.verifyWebLoginCode = async function() {
        const qInp = document.getElementById('login-query-inp');
        const cInp = document.getElementById('login-code-inp');
        const q = (qInp ? qInp.value : '').trim();
        const code = (cInp ? cInp.value : '').trim();

        if (!code) {
            App.toast('لطفاً کد ۵ رقمی را وارد نمایید.', true);
            return;
        }

        const btn = document.getElementById('login-verify-btn');
        if (btn) { btn.disabled = true; btn.innerText = 'در حال بررسی کد...'; }

        const res = await App.api('web_login_verify', 'POST', { query: q, code: code });
        if (btn) { btn.disabled = false; btn.innerText = '✓ تأیید کد و ورود به حساب'; }

        if (res && res.status && res.token) {
            App.token = res.token;
            safeStorage.set('mini_token', res.token);
            App.user = res.user || null;
            App.toast('با موفقیت وارد شدید! خوش آمدید.');
            await App.refreshUserData();
            App.navigate('home');
        } else {
            App.toast(res.msg || 'کد وارد شده اشتباه یا منقضی است.', true);
        }
    };

    App.createWebLoginLink = async function() {
        const res = await App.api('web_login_create_link');
        if (res && res.status && res.url) {
            App.showNoticeModal(
                'لینک ورود سریع به نسخه وب',
                `
                <div style="text-align: center; font-size: 13px; line-height: 1.8;">
                    <div style="color: var(--text-secondary); margin-bottom: 10px;">
                        از این لینک می‌توانید برای ورود مستقیم به پنل وب در مرورگر کامپیوتر یا دستگاه دیگر (بدون نیاز به وارد کردن مجدد اطلاعات) استفاده فرمایید:
                    </div>
                    <div style="background: rgba(0,0,0,0.3); border: 1px dashed var(--accent-lime); border-radius: 10px; padding: 12px; margin-bottom: 14px; word-break: break-all; direction: ltr; font-family: monospace; font-size: 11px; color: var(--accent-lime);">
                        ${res.url}
                    </div>
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        <button class="btn-lime" style="width: auto; padding: 8px 18px;" onclick="navigator.clipboard.writeText('${res.url}'); App.toast('لینک کپی شد!');">
                            📋 کپی لینک ورود
                        </button>
                    </div>
                </div>
                `
            );
        } else {
            App.toast('خطا در ایجاد لینک ورود', true);
        }
    };

    App.renderTest = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 48px; margin-bottom: 12px;">⭐</div>
                <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px; color: #fff;">دریافت اکانت تست رایگان</h3>
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 20px; max-width: 440px; margin-left: auto; margin-right: auto;">
                    جهت بررسی کیفیت، سرعت بالا و پایداری اتصال در موقعیت‌های مختلف، می‌توانید یک اکانت تست رایگان دریافت و تست فرمایید.
                </p>

                <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; margin-bottom: 20px; text-align: right;">
                    <div style="font-size: 13px; font-weight: 700; margin-bottom: 8px; color: var(--accent-lime);">ویژگی‌های اکانت تست:</div>
                    <ul style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.8; padding-right: 18px; margin: 0;">
                        <li>اتصال با پروتکل‌های ضد فیلتر و پرسرعت</li>
                        <li>قابل استفاده روی انواع اپلیکیشن‌ها (v2rayNG, Streisand, Sing-box و ...)</li>
                        <li>ترافیک هدیه تست با زمان مشخص</li>
                    </ul>
                </div>

                <div id="test-action-wrap">
                    <button class="btn-lime" id="claim-test-btn" onclick="App.claimTestAccount()">
                        🚀 دریافت و فعال‌سازی اکانت تست
                    </button>
                </div>
                <div id="test-result-box" style="margin-top: 18px; display: none;"></div>
            </div>
        `;
    };

    App.claimTestAccount = async function() {
        const btn = document.getElementById('claim-test-btn');
        const resBox = document.getElementById('test-result-box');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'در حال ساخت اکانت تست...';
        }

        const res = await App.api('test_account', 'POST');
        if (btn) {
            btn.disabled = false;
            btn.innerText = '🚀 دریافت و فعال‌سازی اکانت تست';
        }

        if (res.status && res.obj) {
            App.toast('اکانت تست با موفقیت فعال شد!');
            const obj = res.obj;
            const sub = obj.sub_url || '';
            const configs = Array.isArray(obj.configs) ? obj.configs : [];
            const cfg = configs[0] || sub;

            resBox.style.display = 'block';
            resBox.innerHTML = `
                <div class="card" style="text-align: right; background: #162438; border-color: var(--accent-lime);">
                    <div style="font-size: 14px; font-weight: 800; color: var(--accent-lime); margin-bottom: 10px;">✅ اطلاعات اکانت تست شما:</div>
                    <div style="font-size: 12.5px; margin-bottom: 12px; color: var(--text-secondary);">
                        نام کاربری: <b style="color: #fff;">${obj.username}</b>
                    </div>
                    ${sub ? `
                        <div style="margin-bottom: 12px;">
                            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">لینک اشتراک:</div>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" readonly value="${sub}" style="flex: 1; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: #fff; font-size: 12px; direction: ltr;">
                                <button class="btn-secondary" onclick="App.copyText('${sub}')">کپی</button>
                            </div>
                        </div>
                    ` : ''}
                    <div style="display: flex; gap: 8px; margin-top: 14px;">
                        <button class="btn-lime" style="flex: 1;" onclick="App.showQrModal('${sub || cfg}', 'اکانت تست')">📱 نمایش کد QR</button>
                        <button class="btn-secondary" style="flex: 1;" onclick="App.navigate('services')">سرویس‌های من &rsaquo;</button>
                    </div>
                </div>
            `;
        } else {
            App.toast(res.msg || 'امکان دریافت اکانت تست وجود ندارد.', true);
            if (resBox) {
                resBox.style.display = 'block';
                resBox.innerHTML = `
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #EF4444; border-radius: 12px; padding: 14px; color: #fff; font-size: 13px;">
                        ⚠️ ${res.msg || 'سهمیه تست شما به پایان رسیده است.'}
                        <div style="margin-top: 12px;">
                            <button class="btn-lime" onclick="App.navigate('shop')">مشاهده و خرید اشتراک از فروشگاه &rsaquo;</button>
                        </div>
                    </div>
                `;
            }
        }
    };

    App.selectedWalletGateway = 'iranpay4';

    App.renderWallet = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        if (!App.token) {
            container.innerHTML = `
                <div class="card" style="text-align: center; padding: 36px 20px;">
                    <div style="font-size: 44px; margin-bottom: 14px;">💳</div>
                    <div style="font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 8px;">کیف پول و افزایش موجودی</div>
                    <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.8; max-width: 340px; margin: 0 auto 20px;">
                        جهت مشاهده موجودی، شارژ آنلاین یا پرداخت کریپتو و استارز، لطفاً ابتدا وارد حساب تلگرام خود شوید.
                    </div>
                    <button class="btn-lime" onclick="App.navigate('profile')" style="max-width: 240px; margin: 0 auto;">
                        🔐 ورود با تلگرام
                    </button>
                </div>
            `;
            return;
        }

        const balance = Number(App.user?.balance || 0).toLocaleString('fa-IR');

        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 24px 20px; background: linear-gradient(135deg, #182338 0%, #0F172A 100%); border-color: var(--border-color);">
                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">موجودی فعلی کیف پول:</div>
                <div style="font-size: 28px; font-weight: 800; color: var(--accent-lime); margin-bottom: 6px;">
                    ${balance} <span style="font-size: 14px; font-weight: normal; color: var(--text-secondary);">تومان</span>
                </div>
                <div style="font-size: 11.5px; color: var(--text-muted);">موجودی جهت خرید و تمدید اشتراک‌ها و سرویس‌ها مصرف می‌گردد.</div>
            </div>

            <div class="card">
                <!-- 1. Gateway Selection -->
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">۱. انتخاب درگاه یا روش پرداخت:</div>
                <div id="wallet-gateways-list" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px;">
                    <div style="text-align: center; color: var(--text-muted); padding: 12px; font-size: 13px;">در حال دریافت درگاه‌های فعال...</div>
                </div>

                <!-- 2. Amount Selection -->
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">۲. انتخاب مبلغ شارژ (تومان):</div>
                <div id="wallet-presets-container" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px;">
                    <!-- Dynamically populated based on gateway min/max limits -->
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12.5px; color: var(--text-secondary); display: block; margin-bottom: 6px;">
                        یا مبلغ دلخواه (تومان) را وارد نمایید:
                    </label>
                    <input type="number" id="wallet-amount-inp" value="300000" min="20000" step="5000"
                           style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 14px; color: #fff; font-size: 16px; font-weight: 700;">
                    <div id="wallet-range-hint" style="font-size: 11.5px; color: var(--accent-lime); margin-top: 6px; font-weight: 600;">
                        در حال بارگذاری محدوده‌های شارژ...
                    </div>
                    <div id="wallet-converted-hint" style="font-size: 12px; color: var(--accent-lime); margin-top: 6px; font-weight: 600;"></div>
                </div>

                <button class="btn-lime" id="pay-wallet-btn" onclick="App.startWalletPayment()">
                    🔗 اتصال به درگاه و افزایش موجودی
                </button>
            </div>
        `;

        // Load active payment methods from API
        const mRes = await App.api('payment_methods');
        const gList = document.getElementById('wallet-gateways-list');
        if (gList) {
            let methods = (mRes && mRes.status && Array.isArray(mRes.obj) && mRes.obj.length > 0) ? mRes.obj : [
                { id: 'iranpay4', title: 'درگاه پرداخت ریالی (هوش‌پی / کارت شتاب)', icon: 'card', min_amount: 50000, max_amount: 1500000, badge: 'پیشنهادی' }
            ];

            App.paymentMethods = methods;
            if (!App.selectedWalletGateway || !methods.find(m => m.id === App.selectedWalletGateway)) {
                App.selectedWalletGateway = methods[0].id;
            }

            gList.innerHTML = methods.map(m => {
                const isSelected = (m.id === App.selectedWalletGateway);
                const minTxt = m.min_amount ? Number(m.min_amount).toLocaleString('fa-IR') + ' تومان' : '';
                const maxTxt = m.max_amount ? Number(m.max_amount).toLocaleString('fa-IR') + ' تومان' : '';
                return `
                    <div class="gateway-card ${isSelected ? 'active' : ''}" data-id="${m.id}"
                         style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 10px; border: 1px solid ${isSelected ? 'var(--accent-lime)' : 'var(--border-color)'}; background: ${isSelected ? '#1E293B' : 'rgba(0,0,0,0.2)'}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">${m.type === 'crypto' ? '🪙' : (m.type === 'stars' ? '⭐' : (m.type === 'card' ? '💳' : '⚡'))}</span>
                            <div>
                                <div style="font-size: 13.5px; font-weight: 700; color: #fff;">${m.title}</div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    ${minTxt ? 'حداقل: ' + minTxt : 'پرداخت امن آنلاین'}${maxTxt ? ' | حداکثر: ' + maxTxt : ''}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            ${m.badge ? `<span style="font-size: 10px; background: rgba(212, 248, 60, 0.15); color: var(--accent-lime); padding: 2px 7px; border-radius: 5px;">${m.badge}</span>` : ''}
                            <input type="radio" name="gateway_rad" value="${m.id}" ${isSelected ? 'checked' : ''} style="accent-color: var(--accent-lime);">
                        </div>
                    </div>
                `;
            }).join('');

            App.renderWalletPresets = function(minAmt, maxAmt) {
                const pBox = document.getElementById('wallet-presets-container');
                if (!pBox) return;
                let rawList = [];
                if (minAmt >= 300000) {
                    rawList = [minAmt, minAmt + 200000, minAmt * 2, minAmt * 3, 2000000, 5000000];
                } else if (minAmt >= 100000) {
                    rawList = [minAmt, 200000, 300000, 500000, 1000000, 2000000];
                } else {
                    rawList = [minAmt || 50000, 100000, 200000, 300000, 500000, 1000000];
                }
                const filtered = Array.from(new Set(rawList.filter(v => (!maxAmt || v <= maxAmt) && v >= minAmt))).sort((a,b)=>a-b).slice(0, 6);
                const finalPresets = filtered.length > 0 ? filtered : [minAmt];

                pBox.innerHTML = finalPresets.map((val, idx) => `
                    <button class="btn-secondary charge-preset ${idx === 0 ? 'active' : ''}" data-val="${val}"
                            style="${idx === 0 ? 'border-color: var(--accent-lime); color: var(--accent-lime); background: #253316;' : ''}">
                        ${Number(val).toLocaleString('fa-IR')}
                    </button>
                `).join('');

                pBox.querySelectorAll('.charge-preset').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const inp = document.getElementById('wallet-amount-inp');
                        if (inp) inp.value = btn.dataset.val;
                        pBox.querySelectorAll('.charge-preset').forEach(b => {
                            b.classList.remove('active');
                            b.style.borderColor = 'var(--border-color)';
                            b.style.color = 'var(--text-primary)';
                            b.style.background = '#1E293B';
                        });
                        btn.classList.add('active');
                        btn.style.borderColor = 'var(--accent-lime)';
                        btn.style.color = 'var(--accent-lime)';
                        btn.style.background = '#253316';
                        App.updateWalletConversion();
                    });
                });
            };

            App.syncGatewayLimits = function() {
                const cur = (App.paymentMethods || []).find(m => m.id === App.selectedWalletGateway);
                const inp = document.getElementById('wallet-amount-inp');
                const hint = document.getElementById('wallet-range-hint');
                if (!cur || !inp) return;

                const minAmt = Number(cur.min_amount) || 20000;
                const maxAmt = Number(cur.max_amount) || 100000000;

                inp.min = minAmt;
                inp.max = maxAmt;

                if (hint) {
                    hint.innerHTML = `📌 <b>حداقل مبلغ شارژ:</b> ${Number(minAmt).toLocaleString('fa-IR')} تومان | <b>حداکثر:</b> ${Number(maxAmt).toLocaleString('fa-IR')} تومان`;
                }

                let curVal = parseInt(inp.value) || 0;
                if (curVal < minAmt || curVal > maxAmt) {
                    inp.value = minAmt;
                }

                App.renderWalletPresets(minAmt, maxAmt);
                App.updateWalletConversion();
            };

            App.updateWalletConversion = function() {
                const hint = document.getElementById('wallet-converted-hint');
                if (!hint) return;
                const inp = document.getElementById('wallet-amount-inp');
                const amount = inp ? parseInt(inp.value) || 0 : 0;
                const gwId = App.selectedWalletGateway;
                const method = (App.paymentMethods || []).find(m => m.id === gwId);
                if (!method || !amount) { hint.innerText = ''; return; }

                if (method.type === 'crypto' || gwId === 'nowpayment' || gwId === 'plisio') {
                    const usdRate = Number(method.usd_rate) || 95000;
                    const usdVal = (amount / usdRate).toFixed(2);
                    hint.innerText = `معادل دلاری: $${usdVal} USD (نرخ هر دلار: ${usdRate.toLocaleString('fa-IR')} تومان)`;
                } else if (method.type === 'stars' || gwId === 'startelegrams') {
                    const starRate = Number(method.star_rate) || 5000;
                    const starCount = Math.max(1, Math.ceil(amount / starRate));
                    hint.innerText = `معادل استارز: ⭐ ${starCount.toLocaleString('fa-IR')} استارز (نرخ هر استارز: ${starRate.toLocaleString('fa-IR')} تومان)`;
                } else {
                    hint.innerText = '';
                }
            };

            gList.querySelectorAll('.gateway-card').forEach(card => {
                card.addEventListener('click', () => {
                    App.selectedWalletGateway = card.dataset.id;
                    gList.querySelectorAll('.gateway-card').forEach(c => {
                        c.style.borderColor = 'var(--border-color)';
                        c.style.background = 'rgba(0,0,0,0.2)';
                        const rad = c.querySelector('input[type="radio"]');
                        if (rad) rad.checked = false;
                    });
                    card.style.borderColor = 'var(--accent-lime)';
                    card.style.background = '#1E293B';
                    const curRad = card.querySelector('input[type="radio"]');
                    if (curRad) curRad.checked = true;
                    App.syncGatewayLimits();
                });
            });

            App.syncGatewayLimits();
        }

        document.getElementById('wallet-amount-inp')?.addEventListener('input', () => {
            if (typeof App.updateWalletConversion === 'function') App.updateWalletConversion();
        });
    };

    App.startWalletPayment = async function() {
        const inp = document.getElementById('wallet-amount-inp');
        const amount = inp ? parseInt(inp.value) : 0;
        const gateway = App.selectedWalletGateway || 'iranpay4';
        const curGw = (App.paymentMethods || []).find(m => m.id === gateway);

        if (!amount || isNaN(amount)) {
            App.toast('لطفاً مبلغ معتبری وارد نمایید.', true);
            return;
        }

        if (curGw) {
            if (curGw.min_amount && amount < curGw.min_amount) {
                App.toast('حداقل مبلغ شارژ برای این درگاه ' + Number(curGw.min_amount).toLocaleString('fa-IR') + ' تومان است.', true);
                return;
            }
            if (curGw.max_amount && amount > curGw.max_amount) {
                App.toast('حداکثر مبلغ شارژ برای این درگاه ' + Number(curGw.max_amount).toLocaleString('fa-IR') + ' تومان است.', true);
                return;
            }
        }

        const btn = document.getElementById('pay-wallet-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'در حال ایجاد لینک پرداخت...';
        }

        const res = await App.api('charge_wallet', 'POST', { amount: amount, gateway: gateway });
        if (btn) {
            btn.disabled = false;
            btn.innerText = '🔗 اتصال به درگاه و افزایش موجودی';
        }

        if (res.status && res.obj) {
            if (res.obj.type === 'card') {
                App.showNoticeModal(
                    'شماره کارت جهت واریز وجه',
                    `
                    <div style="font-size: 14px; line-height: 1.8; text-align: center;">
                        <div style="color: var(--text-secondary); margin-bottom: 8px;">مبلغ قابل پرداخت:</div>
                        <div style="font-size: 22px; font-weight: 800; color: var(--accent-lime); margin-bottom: 14px;">${Number(amount).toLocaleString('fa-IR')} تومان</div>
                        <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 12px;">
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">شماره کارت (جهت کپی کلیک کنید):</div>
                            <div style="font-size: 18px; font-weight: 700; letter-spacing: 2px; color: #fff; cursor: pointer;" onclick="navigator.clipboard.writeText('${res.obj.card_number}'); App.toast('شماره کارت کپی شد');">${res.obj.card_number || 'تنظیم نشده'}</div>
                            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">به نام: ${res.obj.card_name || 'مدیریت'}</div>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            پس از واریز، تصویر فیش واریزی را از بخش پشتیبانی آنلاین ارسال فرمایید.
                        </div>
                    </div>
                    `
                );
            } else if (res.obj.type === 'tron') {
                App.showNoticeModal(
                    'آدرس ولت ترون (TRX)',
                    `
                    <div style="font-size: 14px; line-height: 1.8; text-align: center;">
                        <div style="color: var(--text-secondary); margin-bottom: 8px;">مبلغ افزایش موجودی:</div>
                        <div style="font-size: 22px; font-weight: 800; color: var(--accent-lime); margin-bottom: 14px;">${Number(amount).toLocaleString('fa-IR')} تومان</div>
                        <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 12px;">
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">آدرس ولت ترون (جهت کپی کلیک کنید):</div>
                            <div style="font-size: 14px; font-weight: 700; word-break: break-all; color: #fff; cursor: pointer;" onclick="navigator.clipboard.writeText('${res.obj.wallet_address}'); App.toast('آدرس ولت کپی شد');">${res.obj.wallet_address || 'تنظیم نشده'}</div>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            پس از انتقال ترون، هش تراکنش یا فیش واریز را در بخش پشتیبانی آنلاین ارسال فرمایید.
                        </div>
                    </div>
                    `
                );
            } else if (res.obj.type === 'stars' || res.obj.gateway === 'startelegrams') {
                const invUrl = res.obj.invoice_link || res.obj.payment_url;
                if (window.Telegram?.WebApp?.openInvoice) {
                    window.Telegram.WebApp.openInvoice(invUrl, (status) => {
                        if (status === 'paid') {
                            App.toast('پرداخت با استارز با موفقیت انجام شد! موجودی شارژ گردید.');
                            App.refreshUserData();
                        } else if (status === 'cancelled') {
                            App.toast('پرداخت استارز لغو شد.', true);
                        } else if (status === 'failed') {
                            App.toast('پرداخت استارز ناموفق بود.', true);
                        }
                    });
                } else if (window.Telegram?.WebApp?.openTelegramLink) {
                    window.Telegram.WebApp.openTelegramLink(invUrl);
                } else {
                    window.open(invUrl, '_blank');
                }
            } else if (res.obj.payment_url) {
                try {
                    if (window.Telegram?.WebApp?.openLink) {
                        window.Telegram.WebApp.openLink(res.obj.payment_url);
                    } else {
                        window.location.href = res.obj.payment_url;
                    }
                } catch(e) {
                    window.location.href = res.obj.payment_url;
                }
            }
        } else {
            App.toast(res.msg || 'خطا در ارتباط با درگاه پرداخت', true);
        }
    };

    App.renderGiftCard = function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 48px; margin-bottom: 12px;">🎁</div>
                <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px; color: #fff;">ثبت کارت هدیه و کوپن</h3>
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 24px; max-width: 440px; margin-left: auto; margin-right: auto;">
                    اگر کد کارت هدیه دریافت کرده‌اید، آن را در کادر زیر وارد کنید تا اعتبار آن مستقیماً به کیف پول شما افزوده شود.
                </p>

                <div style="max-width: 360px; margin: 0 auto;">
                    <input type="text" id="giftcode-inp" placeholder="مثال: GIFT-ABCD123"
                           style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; color: #fff; font-size: 15px; text-align: center; font-weight: 700; text-transform: uppercase; margin-bottom: 14px;">
                    <button class="btn-lime" id="apply-gift-btn" onclick="App.applyGiftCard()">
                        ثبت و دریافت اعتبار هدیه
                    </button>
                </div>
            </div>
        `;
    };

    App.applyGiftCard = async function() {
        const inp = document.getElementById('giftcode-inp');
        const code = inp ? inp.value.trim() : '';
        if (!code) {
            App.toast('لطفاً کد کارت هدیه را وارد نمایید.', true);
            return;
        }

        const btn = document.getElementById('apply-gift-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'در حال بررسی...';
        }

        const res = await App.api('redeem_giftcard', 'POST', { code: code });
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'ثبت و دریافت اعتبار هدیه';
        }

        if (res.status) {
            App.toast(res.msg || 'کارت هدیه با موفقیت اعمال شد!');
            inp.value = '';
            await App.refreshUserData();
            setTimeout(() => App.navigate('wallet'), 1200);
        } else {
            App.toast(res.msg || 'کد هدیه نامعتبر است یا قبلاً استفاده شده.', true);
        }
    };

    App.renderAffiliate = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        const user = App.user || {};
        const invCode = user.codeInvitation || '';
        const sysInfo = await App.api('system_info');
        const botName = sysInfo.obj?.bot_username || 'Bot';
        const percent = sysInfo.obj?.affiliate_percent || 10;
        const refLink = `https://t.me/${botName}?start=${invCode}`;

        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 24px 20px; background: linear-gradient(135deg, #182838 0%, #0F172A 100%);">
                <div style="font-size: 42px; margin-bottom: 8px;">👥</div>
                <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 6px;">سیستم همکاری در فروش و زیرمجموعه‌گیری</h3>
                <p style="font-size: 13px; color: var(--text-secondary); max-width: 440px; margin: 0 auto 16px;">
                    دوستان خود را به استفاده از سرویس دعوت کنید و با هر خرید آن‌ها، 
                    <b style="color: var(--accent-lime);">${percent}٪ پورسانت نقدی</b> دریافت نمایید!
                </p>

                <div style="display: flex; justify-content: center; gap: 16px;">
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 24px; text-align: center;">
                        <div style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px;">تعداد زیرمجموعه‌ها</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--accent-lime);">${user.affiliatescount || 0} نفر</div>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 24px; text-align: center;">
                        <div style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px;">درصد پورسانت شما</div>
                        <div style="font-size: 18px; font-weight: 800; color: #fff;">${percent}٪</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">لینک اختصاصی دعوت شما:</div>
                <div style="display: flex; gap: 8px; margin-bottom: 14px;">
                    <input type="text" readonly value="${refLink}"
                           style="flex: 1; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; color: #fff; font-size: 12px; direction: ltr;">
                    <button class="btn-secondary" onclick="App.copyText('${refLink}')" style="flex-shrink: 0;">📋 کپی لینک</button>
                </div>

                <button class="btn-lime" style="width: 100%;" onclick="App.shareReferral('${encodeURIComponent(refLink)}')">
                    ✈️ اشتراک‌گذاری مستقیم در تلگرام
                </button>
            </div>

            <div class="card">
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 12px;">مراحل کسب درآمد:</div>
                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12.5px; color: var(--text-secondary);">
                    <div>۱. لینک دعوت خود را در تلگرام یا شبکه‌های اجتماعی با دوستانتان به اشتراک بگذارید.</div>
                    <div>۲. دوستان شما با کلیک روی لینک وارد ربات شده و ثبت‌نام می‌کنند.</div>
                    <div>۳. با هر خرید یا تمدید اشتراک توسط آنها، پورسانت نقدی آنی به کیف پول شما اضافه می‌شود!</div>
                </div>
            </div>
        `;
    };

    App.shareReferral = function(encLink) {
        const text = encodeURIComponent('سریع‌ترین و پایدارترین فیلترشکن بدون قطعی! از اینجا وصل شو:');
        const shareUrl = `https://t.me/share/url?url=${encLink}&text=${text}`;
        if (window.Telegram?.WebApp?.openTelegramLink) {
            window.Telegram.WebApp.openTelegramLink(shareUrl);
        } else {
            window.open(shareUrl, '_blank');
        }
    };

    App.renderTariffs = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        container.innerHTML = `<div style="text-align: center; padding: 40px; color: var(--text-muted);">در حال دریافت تعرفه‌ها...</div>`;

        const cRes = await App.api('countries');
        if (!cRes.status || !Array.isArray(cRes.obj) || cRes.obj.length === 0) {
            container.innerHTML = `<div class="card" style="text-align: center; padding: 30px;">سرور فعالی یافت نشد.</div>`;
            return;
        }

        const countries = cRes.obj;
        const country = App.selectedCountry || countries[0];
        const pRes = await App.api('services', 'GET', { country_id: country.code_panel });
        const prods = (pRes.status && Array.isArray(pRes.obj)) ? pRes.obj : [];

        container.innerHTML = `
            <div style="margin-bottom: 14px; font-size: 14px; font-weight: 700;">جدول کامل تعرفه‌ها و پلن‌ها:</div>
            
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 16px;">
                ${countries.map(c => `
                    <button class="btn-secondary country-tariff-btn ${c.code_panel === country.code_panel ? 'active' : ''}" 
                            data-code="${c.code_panel}"
                            style="${c.code_panel === country.code_panel ? 'border-color: var(--accent-lime); color: var(--accent-lime);' : ''}">
                        ${c.country_name || c.name_panel}
                    </button>
                `).join('')}
            </div>

            ${prods.length === 0 ? `<div class="card" style="text-align: center; padding: 30px;">پلنی برای این لوکیشن یافت نشد.</div>` : `
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    ${prods.map(p => `
                        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; margin-bottom: 0;">
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #fff;">${p.name}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                                    ${p.custom_volume_enabled ? 'تعیین حجم دلخواه مشتری' : (p.traffic_gb + ' گیگابایت')} 
                                    • ${p.time_days > 0 ? (p.time_days + ' روزه') : 'نامحدود'}
                                </div>
                            </div>
                            <div style="text-align: left;">
                                <div style="font-size: 15px; font-weight: 800; color: var(--accent-lime);">
                                    ${p.custom_volume_enabled ? ('هر گیگ ' + Number(p.price_per_gb).toLocaleString('fa-IR')) : Number(p.price).toLocaleString('fa-IR')} تومان
                                </div>
                                <button class="btn-lime" style="padding: 6px 14px; font-size: 12px; margin-top: 6px; width: auto;" onclick="App.navigate('shop')">
                                    خرید 🛍️
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `}
        `;

        container.querySelectorAll('.country-tariff-btn').forEach(b => {
            b.addEventListener('click', () => {
                App.selectedCountry = App.countries.find(x => x.code_panel === b.dataset.code);
                App.renderTariffs(container);
            });
        });
    };

    App.renderTutorials = function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        container.innerHTML = `
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 12px;">راهنما و دانلود اپلیکیشن‌ها:</div>

            <div class="card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">🤖</span>
                    <span style="font-size: 15px; font-weight: 700;">سیستم عامل اندروید (Android)</span>
                </div>
                <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.6;">
                    نرم‌افزار‌های پیشنهادی: <b>v2rayNG</b> یا <b>Happ</b> یا <b>Sing-box</b>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="https://play.google.com/store/apps/details?id=com.v2ray.ang" target="_blank" class="btn-secondary" style="text-decoration: none; font-size: 12px;">
                        Google Play: v2rayNG
                    </a>
                    <a href="https://github.com/2dust/v2rayNG/releases" target="_blank" class="btn-secondary" style="text-decoration: none; font-size: 12px;">
                        دانلود مستقیم APK
                    </a>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">🍎</span>
                    <span style="font-size: 15px; font-weight: 700;">سیستم عامل آیفون / آیپد (iOS)</span>
                </div>
                <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.6;">
                    نرم‌افزارهای پیشنهادی: <b>V2Box</b> یا <b>Streisand</b> یا <b>FoXray</b>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="https://apps.apple.com/app/v2box-v2ray-client/id6446814690" target="_blank" class="btn-secondary" style="text-decoration: none; font-size: 12px;">
                        App Store: V2Box
                    </a>
                    <a href="https://apps.apple.com/app/streisand/id6450534064" target="_blank" class="btn-secondary" style="text-decoration: none; font-size: 12px;">
                        App Store: Streisand
                    </a>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">💻</span>
                    <span style="font-size: 15px; font-weight: 700;">سیستم عامل ویندوز (Windows)</span>
                </div>
                <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.6;">
                    نرم‌افزارهای پیشنهادی: <b>v2rayN</b> یا <b>Nekoray</b>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="https://github.com/2dust/v2rayN/releases" target="_blank" class="btn-secondary" style="text-decoration: none; font-size: 12px;">
                        دانلود v2rayN از گیت‌هاب
                    </a>
                </div>
            </div>

            <div class="card" style="background: rgba(0,0,0,0.25);">
                <div style="font-size: 13.5px; font-weight: 700; margin-bottom: 10px; color: var(--accent-lime);">نحوه اتصال سریع:</div>
                <ol style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.8; padding-right: 18px; margin: 0;">
                    <li>نرم‌افزار مناسب دستگاه خود را نصب کنید.</li>
                    <li>وارد بخش <b>سرویس‌های من</b> شوید و روی دکمه <b>کپی لینک اشتراک</b> بزنید.</li>
                    <li>در نرم‌افزار گزینه Add from clipboard یا + را بزنید و دکمه اتصال را روشن کنید.</li>
                </ol>
            </div>
        `;
    };

    App.renderAgency = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        const sysInfo = await App.api('system_info');
        const adminUname = sysInfo.obj?.support_username || 'Support';

        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 48px; margin-bottom: 12px;">👑</div>
                <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px; color: #fff;">درخواست پنل نمایندگی و همکاری</h3>
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 24px; max-width: 440px; margin-left: auto; margin-right: auto;">
                    اگر فروشنده هستید یا کانال و مشتریان زیادی دارید، می‌توانید با دریافت پنل اختصاصی نمایندگی از تخفیف‌های ویژه، سقف خرید نامحدود و مدیریت کامل کانفیگ‌ها بهره‌مند شوید.
                </p>

                <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 24px; text-align: right;">
                    <div style="font-size: 13px; font-weight: 700; color: var(--accent-lime); margin-bottom: 10px;">مزایای نمایندگی:</div>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12.5px; color: var(--text-secondary);">
                        <div>✔ تخفیف‌های تصاعدی بر روی تمامی سرویس‌ها</div>
                        <div>✔ پنل اختصاصی تحت وب برای ساخت و مدیریت اکانت‌ها</div>
                        <div>✔ امکان تعیین ترافیک و مدت زمان دلخواه برای مشتریان</div>
                        <div>✔ سرورهای اختصاصی VIP با پشتیبانی ۲۴ ساعته</div>
                    </div>
                </div>

                <button class="btn-lime" style="max-width: 320px; margin: 0 auto;" onclick="App.openTelegramUser('${adminUname}')">
                    👑 گفتگو با مدیریت و دریافت نمایندگی
                </button>
            </div>
        `;
    };

    App.openTelegramUser = function(uname) {
        const clean = (uname || '').replace('@', '');
        const url = `https://t.me/${clean}`;
        if (window.Telegram?.WebApp?.openTelegramLink) {
            window.Telegram.WebApp.openTelegramLink(url);
        } else {
            window.open(url, '_blank');
        }
    };

    App.renderSupport = async function(container) {
        if (!container) container = document.getElementById('content-area');
        if (!container) return;

        if (!App.token) {
            container.innerHTML = `
                <div class="card" style="text-align: center; padding: 36px 20px;">
                    <div style="font-size: 44px; margin-bottom: 14px;">💬</div>
                    <div style="font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 8px;">پشتیبانی آنلاین</div>
                    <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.8; max-width: 340px; margin: 0 auto 20px;">
                        جهت ارتباط مستقیم با پشتیبانی و ارسال تیکت، لطفاً ابتدا وارد حساب تلگرام خود شوید.
                    </div>
                    <button class="btn-lime" onclick="App.navigate('profile')" style="max-width: 240px; margin: 0 auto;">
                        🔐 ورود با تلگرام
                    </button>
                </div>
            `;
            return;
        }

        // Clear previous poll interval if active
        if (App.supportPollInterval) {
            clearInterval(App.supportPollInterval);
            App.supportPollInterval = null;
        }

        const sysInfo = await App.api('system_info');
        const adminUname = sysInfo.obj?.support_username || 'Support';
        const channel = sysInfo.obj?.channel || '';

        container.innerHTML = `
            <div class="card" style="padding: 14px 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; border-color: var(--border-color); background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.9) 100%);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="position: relative; width: 38px; height: 38px; border-radius: 50%; background: #064e3b; display: flex; align-items: center; justify-content: center; font-size: 19px;">
                        🛡️
                        <span style="position: absolute; bottom: 0; right: 0; width: 9px; height: 9px; background: #10b981; border: 2px solid #0F172A; border-radius: 50%;"></span>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 800; color: #fff;">چت آنلاین با پشتیبانی</div>
                        <div style="font-size: 11px; color: var(--accent-lime); margin-top: 2px;">🟢 آنلاین | پاسخگویی سریع</div>
                    </div>
                </div>
                <button class="btn-secondary" style="width: auto; padding: 6px 12px; font-size: 11px; border-radius: 8px;" onclick="App.openTelegramUser('${adminUname}')">
                    ارتباط در PV &rsaquo;
                </button>
            </div>

            <!-- Chat Container -->
            <div class="card" style="padding: 0; display: flex; flex-direction: column; height: calc(100vh - 210px); min-height: 480px; overflow: hidden; background: #0B1120; border-color: var(--border-color);">
                <!-- Messages Box -->
                <div id="support-chat-messages" style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; scroll-behavior: smooth;">
                    <div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 30px;">
                        در حال بارگذاری پیام‌ها...
                    </div>
                </div>

                <!-- Chat Input Bar -->
                <div style="padding: 12px; border-top: 1px solid var(--border-color); background: var(--bg-card); display: flex; gap: 8px; align-items: center;">
                    <input type="text" id="support-chat-inp" placeholder="پیام خود را بنویسید..." 
                           style="flex: 1; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 12px; padding: 11px 14px; color: #fff; font-size: 13.5px; outline: none;">
                    <button class="btn-lime" id="support-chat-send-btn" style="width: auto; min-width: 75px; padding: 11px 18px; border-radius: 12px; font-size: 13.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span>ارسال</span>
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    </button>
                </div>
            </div>
        `;

        const msgBox = document.getElementById('support-chat-messages');
        const inp = document.getElementById('support-chat-inp');
        const sendBtn = document.getElementById('support-chat-send-btn');

        let lastMessagesHash = '';

        const escapeHtml = (str) => {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        };

        App.loadSupportChat = async function(forceScroll = false) {
            const res = await App.api('support_messages');
            if (!msgBox) return;
            const msgs = (res && res.status && Array.isArray(res.messages)) ? res.messages : [];
            
            const currentHash = msgs.map(m => m.id + ':' + m.created_at).join('|');
            if (currentHash === lastMessagesHash && !forceScroll) {
                return;
            }
            lastMessagesHash = currentHash;

            if (msgs.length === 0) {
                msgBox.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary); margin: auto;">
                        <div style="font-size: 42px; margin-bottom: 12px;">👋</div>
                        <div style="font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 6px;">سلام! به پشتیبانی آنلاین خوش آمدید</div>
                        <div style="font-size: 12.5px; line-height: 1.8; max-width: 300px; margin: 0 auto; color: var(--text-muted);">
                            هرگونه سوال، مشکل اتصال، شارژ حساب یا درخواست خرید دارید را بنویسید. کارشناسان ما پاسخگوی شما هستند.
                        </div>
                    </div>
                `;
            } else {
                msgBox.innerHTML = msgs.map(m => {
                    const isAdmin = (m.sender === 'admin');
                    const d = new Date(m.created_at * 1000);
                    const timeStr = d.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
                    return `
                        <div style="display: flex; flex-direction: column; align-items: ${isAdmin ? 'flex-start' : 'flex-end'}; max-width: 82%; align-self: ${isAdmin ? 'flex-start' : 'flex-end'};">
                            <div style="font-size: 10.5px; color: ${isAdmin ? 'var(--accent-lime)' : 'var(--text-secondary)'}; margin-bottom: 3px; font-weight: 700; padding: 0 4px;">
                                ${isAdmin ? '🛡️ پشتیبانی ' + (App.systemInfo?.title || '') : '👤 شما'}
                            </div>
                            <div style="padding: 10px 14px; border-radius: 14px; ${isAdmin ? 'border-bottom-right-radius: 3px;' : 'border-bottom-left-radius: 3px;'} background: ${isAdmin ? '#064e3b' : '#1e293b'}; border: 1px solid ${isAdmin ? '#059669' : '#334155'}; color: #fff; font-size: 13px; line-height: 1.6; word-break: break-word; white-space: pre-wrap;">
                                ${escapeHtml(m.message)}
                            </div>
                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 3px; padding: 0 4px; direction: ltr;">
                                ${timeStr}
                            </div>
                        </div>
                    `;
                }).join('');
            }

            if (forceScroll || !msgBox.dataset.scrolled) {
                msgBox.scrollTop = msgBox.scrollHeight;
                msgBox.dataset.scrolled = '1';
            }
        };

        App.sendSupportMsg = async function() {
            if (!inp) return;
            const text = (inp.value || '').trim();
            if (!text) return;

            inp.value = '';
            if (sendBtn) sendBtn.disabled = true;

            // Optimistic bubble append
            const tempDiv = document.createElement('div');
            tempDiv.style.cssText = "display: flex; flex-direction: column; align-items: flex-end; max-width: 82%; align-self: flex-end;";
            tempDiv.innerHTML = `
                <div style="font-size: 10.5px; color: var(--text-secondary); margin-bottom: 3px; font-weight: 700; padding: 0 4px;">👤 شما</div>
                <div style="padding: 10px 14px; border-radius: 14px; border-bottom-left-radius: 3px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 13px; line-height: 1.6; word-break: break-word; white-space: pre-wrap;">${escapeHtml(text)}</div>
                <div style="font-size: 9.5px; color: var(--text-muted); margin-top: 3px; padding: 0 4px;">در حال ارسال...</div>
            `;
            if (msgBox) {
                msgBox.appendChild(tempDiv);
                msgBox.scrollTop = msgBox.scrollHeight;
            }

            try {
                const res = await App.api('support_send', 'POST', { message: text });
                if (sendBtn) sendBtn.disabled = false;
                if (res && res.status) {
                    await App.loadSupportChat(true);
                } else {
                    App.toast(res.msg || 'خطا در ارسال پیام', true);
                }
            } catch(e) {
                if (sendBtn) sendBtn.disabled = false;
                App.toast('خطا در برقراری ارتباط با سرور', true);
            }
            inp.focus();
        };

        sendBtn?.addEventListener('click', App.sendSupportMsg);
        inp?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                App.sendSupportMsg();
            }
        });

        // Initial load
        await App.loadSupportChat(true);

        // Auto polling every 4 seconds
        App.supportPollInterval = setInterval(() => {
            if (App.currentTab !== 'support') {
                clearInterval(App.supportPollInterval);
                App.supportPollInterval = null;
                return;
            }
            App.loadSupportChat(false);
        }, 4000);
    };

    App.showNoticeModal = function() {
        let modal = document.getElementById('notice-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'notice-modal';
            modal.style.cssText = `
                position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999;
                display: flex; align-items: center; justify-content: center; padding: 20px;
                backdrop-filter: blur(4px);
            `;
            modal.innerHTML = `
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 24px; max-width: 360px; width: 100%; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🔔</div>
                    <div style="font-weight: 800; font-size: 16px; margin-bottom: 12px; color: #fff;">اطلاعیه‌ها و اخبار</div>
                    <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 20px; text-align: right; background: rgba(0,0,0,0.25); padding: 12px; border-radius: 12px;">
                        • کلیه سرورها در وضعیت <b>عالی و پایدار</b> می‌باشند.<br>
                        • قابلیت انتخاب نام کاربری دلخواه و حجم سفارشی در ربات و مینی‌اپ فعال گردید.<br>
                        • برای سرعت بهتر پیشنهاد می‌شود لینک سابسکریپشن خود را هر ۲۴ ساعت بروزرسانی کنید.
                    </div>
                    <button class="btn-lime" onclick="document.getElementById('notice-modal').style.display='none'">متوجه شدم</button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        }
        modal.style.display = 'flex';
    };

})();
