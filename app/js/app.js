/**

 * Arka/Voldemort Cloud Telegram Mini App Core SPA

 */

const safeStorage = {
    get: function(key) {
        try {
            return localStorage.getItem(key) || sessionStorage.getItem(key);
        } catch(e) {
            return null;
        }
    },
    set: function(key, val) {
        try {
            localStorage.setItem(key, val);
            sessionStorage.setItem(key, val);
        } catch(e) {}
    },
    remove: function(key) {
        try {
            localStorage.removeItem(key);
            sessionStorage.removeItem(key);
        } catch(e) {}
    }
};



const App = {

    token: null,

    user: null,

    currentTab: 'home',

    countries: [],

    categories: [],

    services: [],

    myServices: [],

    selectedCountry: null,

    selectedCategory: null,

    selectedService: null,

    selectedServiceDetail: null,

    customVolume: 30,



    openBotLogin: function() {
        const u = this.systemInfo?.bot_username ? this.systemInfo.bot_username.replace('@', '') : '';
        const url = u ? `https://t.me/${u}?start=weblogin` : '#';
        if (window.Telegram?.WebApp?.openTelegramLink) {
            window.Telegram.WebApp.openTelegramLink(url);
        } else {
            window.open(url, '_blank');
        }
    },

    init: async function() {

        console.log("MiniApp Init...");

        if (window.Telegram && window.Telegram.WebApp) {
            try {
                window.Telegram.WebApp.ready();
                const plat = (window.Telegram.WebApp.platform || '').toLowerCase();
                // Never call expand on tdesktop or web to prevent WebView2 crash!
                if (plat && plat !== 'tdesktop' && plat !== 'web' && plat !== 'weba' && plat !== 'webk') {
                    window.Telegram.WebApp.expand();
                }
            } catch(e) {
                console.warn("Telegram WebApp init warning:", e);
            }
        }

        // Process one-click login via ?auth_key=...
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const authKey = urlParams.get('auth_key');
            if (authKey) {
                const authRes = await this.api('web_login_verify', 'POST', { auth_key: authKey });
                if (authRes && authRes.status && authRes.token) {
                    this.token = authRes.token;
                    safeStorage.set('mini_token', authRes.token);
                    this.user = authRes.user || null;
                    try {
                        const cleanUrl = window.location.origin + window.location.pathname + (window.location.hash || '');
                        window.history.replaceState({}, document.title, cleanUrl);
                    } catch(e) {}
                }
            }
        } catch(e) {
            console.error("Auth key check error:", e);
        }



        try {

            await this.authenticate();

        } catch(e) {

            console.error("Auth error:", e);

        }



        try {

            this.bindEvents();

            this.renderSidebar();

            this.renderMobileNav();

            await this.refreshUserData();

            let initialTab = 'home';
            const validTabs = ['home', 'shop', 'services', 'profile', 'test', 'wallet', 'giftcard', 'affiliate', 'tariffs', 'tutorials', 'agency', 'support'];
            
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const qTab = urlParams.get('tab');
                if (qTab && validTabs.includes(qTab)) initialTab = qTab;
            } catch(e) {}

            if (initialTab === 'home' && window.location.hash) {
                const rawHash = window.location.hash.replace(/^#/, '').trim();
                const cleanHash = rawHash.split('&')[0];
                if (cleanHash && validTabs.includes(cleanHash)) {
                    initialTab = cleanHash;
                }
            }

            // If user is accessing directly via browser and not logged in, go straight to profile/login
            if (!this.token && !window.Telegram?.WebApp?.initData && initialTab === 'home') {
                initialTab = 'profile';
            }

            await this.navigate(initialTab);

        } catch(e) {

            console.error("App setup error:", e);

        }

    },



    authenticate: async function() {
        const initData = window.Telegram?.WebApp?.initData || '';
        if (initData) {
            try {
                const res = await fetch('/api/verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    cache: 'no-store',
                    body: JSON.stringify({ initData: initData })
                });
                const d = await res.json();
                if (d.status && d.token) {
                    this.token = d.token;
                    safeStorage.set('mini_token', d.token);
                    return true;
                } else {
                    console.warn("Verify returned error:", d);
                    safeStorage.remove('mini_token');
                    this.token = null;
                    return false;
                }
            } catch (err) {
                console.error("Auth error:", err);
            }
        }

        this.token = safeStorage.get('mini_token');
        return !!this.token;
    },



    api: async function(action, method = 'GET', body = null) {
        const headers = { 'Content-Type': 'application/json' };
        if (this.token) {
            headers['Authorization'] = 'Bearer ' + this.token;
        }
        let url = '/api/miniapp?actions=' + encodeURIComponent(action);
        if (method === 'GET') {
            if (body) {
                const params = new URLSearchParams(body).toString();
                url += '&' + params;
                body = null;
            }
            url += (url.includes('?') ? '&' : '?') + '_t=' + Date.now();
        } else if (method === 'POST' && body && typeof body === 'object') {
            body = Object.assign({ actions: action }, body);
        }
        try {
            const res = await fetch(url, {
                method: method,
                headers: headers,
                cache: 'no-store',
                body: body ? JSON.stringify(body) : null
            });

            const data = await res.json();



            // Auto re-authenticate if token invalid or expired

            if (res.status === 403 && data.msg === "Token invalid" && window.Telegram?.WebApp?.initData) {

                safeStorage.remove('mini_token');

                this.token = null;

                const ok = await this.authenticate();

                if (ok) {

                    headers['Authorization'] = 'Bearer ' + this.token;

                    const retryRes = await fetch(url, {

                        method: method,

                        headers: headers,

                        body: body ? JSON.stringify(body) : null

                    });

                    return await retryRes.json();

                }

            }

            return data;

        } catch(e) {

            // Fallback with .php

            try {

                let fallbackUrl = '/api/miniapp.php?actions=' + action;

                if (method === 'GET' && body) {

                    fallbackUrl += '&' + new URLSearchParams(body).toString();

                }

                const res2 = await fetch(fallbackUrl, {

                    method: method,

                    headers: headers,

                    body: body ? JSON.stringify(body) : null

                });

                return await res2.json();

            } catch(e2) {

                return { status: false, msg: e.message };

            }

        }

    },



    refreshUserData: async function() {
        const res = await this.api('user_info');
        if (res && res.status && res.obj) {
            this.user = res.obj;
            this.updateHeaderBalance();
            if (this.currentTab === 'home') this.renderHome();
            if (this.currentTab === 'profile') this.renderProfile();
            if (this.currentTab === 'wallet') this.renderWallet();
        }
    },

    updateHeaderBalance: function() {
        const balEl = document.getElementById('header-balance-val');
        if (balEl) {
            if (this.token && this.user) {
                balEl.innerText = Number(this.user.balance || 0).toLocaleString('fa-IR') + ' تومان';
            } else {
                balEl.innerText = 'ورود به حساب 🔐';
            }
        }
    },



    logout: function() {
        safeStorage.remove('mini_token');
        this.token = null;
        this.user = null;
        const balEl = document.getElementById('header-balance-val');
        if (balEl) balEl.innerText = 'ورود به حساب';
        this.toast('با موفقیت از حساب کاربری خارج شدید.');
        this.navigate('profile');
    },

    bindEvents: function() {

        document.getElementById('header-balance-btn')?.addEventListener('click', () => {
            if (!this.token) {
                this.navigate('profile');
            } else {
                this.navigate('wallet');
            }
        });

        document.getElementById('back-button')?.addEventListener('click', () => this.handleBack());

        document.getElementById('bell-button')?.addEventListener('click', () => this.showNoticeModal());

        document.getElementById('tg-channel-btn')?.addEventListener('click', () => {
            const ch = (this.systemInfo?.channel || '').replace('@', '');
            if (ch) {
                const url = ch.startsWith('http') ? ch : 'https://t.me/' + ch;
                if (window.Telegram?.WebApp?.openTelegramLink) {
                    window.Telegram.WebApp.openTelegramLink(url);
                } else {
                    window.open(url, '_blank');
                }
            }
        });



        // Theme buttons

        const darkBtn = document.getElementById('theme-dark-btn');

        const lightBtn = document.getElementById('theme-light-btn');

        darkBtn?.addEventListener('click', () => {

            darkBtn.classList.add('active');

            lightBtn?.classList.remove('active');

            document.documentElement.style.setProperty('--bg-main', '#0B0F19');

            document.documentElement.style.setProperty('--bg-sidebar', '#0F172A');

            document.documentElement.style.setProperty('--bg-card', '#151E2E');

            document.documentElement.style.setProperty('--text-primary', '#F8FAFC');

        });

        lightBtn?.addEventListener('click', () => {

            lightBtn.classList.add('active');

            darkBtn?.classList.remove('active');

            document.documentElement.style.setProperty('--bg-main', '#0f172a');

            document.documentElement.style.setProperty('--bg-sidebar', '#1e293b');

            document.documentElement.style.setProperty('--bg-card', '#1e293b');

            document.documentElement.style.setProperty('--text-primary', '#ffffff');

        });

    },



    handleBack: function() {

        if (this.selectedServiceDetail) {

            this.selectedServiceDetail = null;

            this.navigate('services');

        } else {

            this.navigate('home');

        }

    },



    navigate: async function(tab, param = null) {

        if (tab !== 'support' && this.supportPollInterval) {
            clearInterval(this.supportPollInterval);
            this.supportPollInterval = null;
        }

        this.currentTab = tab;

        this.selectedServiceDetail = null;



        // Telegram Haptic

        try {

            if (window.Telegram?.WebApp?.HapticFeedback && window.Telegram?.WebApp?.platform !== 'tdesktop') { try { window.Telegram.WebApp.HapticFeedback.selectionChanged(); } catch(e){} }

        } catch(e) {}



        // Highlight active nav

        document.querySelectorAll('.nav-item').forEach(el => {

            el.classList.toggle('active', el.dataset.tab === tab);

        });

        document.querySelectorAll('.m-nav-item').forEach(el => {

            el.classList.toggle('active', el.dataset.tab === tab);

        });



        // Set title

        const titles = {

            'home': 'خانه',

            'shop': 'فروشگاه',

            'services': 'سرویس‌های من',

            'profile': 'پروفایل',

            'test': 'تست رایگان',

            'wallet': 'کیف پول',

            'giftcard': 'ثبت کارت هدیه',

            'affiliate': 'زیرمجموعه‌گیری',

            'tariffs': 'تعرفه',

            'tutorials': 'آموزش و دانلود اپ‌ها',

            'agency': 'درخواست نمایندگی',

            'support': 'پشتیبانی'

        };

        const titleEl = document.getElementById('topbar-title');

        if (titleEl) titleEl.innerText = titles[tab] || 'Vold Cloud';



        const backBtn = document.getElementById('back-button');

        if (backBtn) {

            backBtn.style.display = (tab === 'home') ? 'none' : 'flex';

        }



        const container = document.getElementById('content-area');

        if (!container) return;



        switch(tab) {

            case 'home':

                await this.renderHome(container);

                break;

            case 'shop':

                await this.renderShop(container);

                break;

            case 'services':

                await this.renderServices(container);

                break;

            case 'profile':

                await this.renderProfile(container);

                break;

            case 'test':

                await this.renderTest(container);

                break;

            case 'wallet':

                await this.renderWallet(container);

                break;

            case 'giftcard':

                this.renderGiftCard(container);

                break;

            case 'affiliate':

                await this.renderAffiliate(container);

                break;

            case 'tariffs':

                await this.renderTariffs(container);

                break;

            case 'tutorials':

                this.renderTutorials(container);

                break;

            case 'agency':

                await this.renderAgency(container);

                break;

            case 'support':

                await this.renderSupport(container);

                break;

        }

    },



    toast: function(msg, isError = false) {

        let el = document.getElementById('app-toast');

        if (!el) {

            el = document.createElement('div');

            el.id = 'app-toast';

            el.style.cssText = `

                position: fixed; bottom: 85px; left: 50%; transform: translateX(-50%);

                background: rgba(22, 31, 46, 0.95); color: #fff; padding: 12px 24px;

                border-radius: 14px; font-size: 13.5px; z-index: 99999;

                border: 1px solid ${isError ? '#EF4444' : '#D4F83C'};

                box-shadow: 0 10px 30px rgba(0,0,0,0.5); pointer-events: none;

                transition: opacity 0.3s; opacity: 0; text-align: center; max-width: 90%;

            `;

            document.body.appendChild(el);

        }

        el.innerHTML = (isError ? '⚠️ ' : '✅ ') + msg;

        el.style.opacity = '1';

        setTimeout(() => { el.style.opacity = '0'; }, 3000);

    },



    copyText: function(text) {

        if (!text) return;

        try {

            if (window.Telegram?.WebApp?.HapticFeedback && window.Telegram?.WebApp?.platform !== 'tdesktop') { try { window.Telegram.WebApp.HapticFeedback.notificationOccurred('success'); } catch(e){} }

        } catch(e) {}

        navigator.clipboard.writeText(text).then(() => {

            this.toast('با موفقیت کپی شد');

        }).catch(() => {

            const inp = document.createElement('input');

            inp.value = text;

            document.body.appendChild(inp);

            inp.select();

            document.execCommand('copy');

            document.body.removeChild(inp);

            this.toast('با موفقیت کپی شد');

        });

    }

};



window.App = App;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => App.init());
} else {
    App.init();
}

