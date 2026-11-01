<?php
/**
 * Douman Panel - Modern Website & Landing Page
 * وب‌سایت و لندینگ پیج اختصاصی برای دمان پنل / میرزا بات
 */

// دریافت اطلاعات پویا از دیتابیس در صورت امکان
$botUsername = 'doumanpanelbot';
$adminName = 'پشتیبانی دمان پنل';
$channelLink = 'https://t.me/';
$products = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $settingStmt = $pdo->query("SELECT * FROM setting LIMIT 1");
        $settingData = $settingStmt ? $settingStmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!empty($settingData['bot_username'])) {
            $botUsername = ltrim($settingData['bot_username'], '@');
        } elseif (!empty($usernamebot)) {
            $botUsername = ltrim($usernamebot, '@');
        }
        if (!empty($settingData['channel'])) {
            $channelLink = 'https://t.me/' . ltrim($settingData['channel'], '@');
        }

        // دریافت لیست پلن‌ها از دیتابیس
        $prodStmt = $pdo->query("SELECT * FROM product WHERE status = 1 ORDER BY price ASC LIMIT 6");
        if ($prodStmt) {
            $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        // Fallback gracefully
    }
} elseif (isset($usernamebot) && !empty($usernamebot)) {
    $botUsername = ltrim($usernamebot, '@');
}

$botTelegramUrl = "https://t.me/" . $botUsername;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دوماین پنل | خرید آنلاین کانفیگ پرسرعت و پایدار V2Ray / Xray</title>
    <meta name="description" content="خرید آنی اشتراک اختصاصی V2Ray، Xray، Reality، Hysteria با تحویل فوری در تلگرام، پینگ عالی و بدون قطعی در تمام اپراتورها">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0b0f19;
            --bg-card: rgba(17, 24, 39, 0.75);
            --bg-card-hover: rgba(30, 41, 59, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(56, 189, 248, 0.4);
            --primary: #0284c7;
            --primary-glow: #38bdf8;
            --accent: #8b5cf6;
            --success: #10b981;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --radius-lg: 20px;
            --radius-md: 14px;
            --radius-sm: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            line-height: 1.7;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(56, 189, 248, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
            background-attachment: fixed;
        }

        /* Ambient Glows */
        .ambient-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
            filter: blur(80px);
            z-index: -1;
            pointer-events: none;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header / Navbar */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background: rgba(11, 15, 25, 0.8);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .nav-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 20px;
        }

        .logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary-glow), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--primary-glow);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0369a1, #075985);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.5);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--border-hover);
        }

        .btn-telegram {
            background: #229ed9;
            color: #fff;
        }

        .btn-telegram:hover {
            background: #1b8ec5;
            box-shadow: 0 4px 15px rgba(34, 158, 217, 0.4);
        }

        /* Hero Section */
        .hero {
            padding: 90px 0 60px;
            text-align: center;
            position: relative;
        }

        .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 999px;
            color: #34d399;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .hero h1 {
            font-size: 46px;
            font-weight: 900;
            letter-spacing: -1px;
            line-height: 1.35;
            margin-bottom: 20px;
            background: linear-gradient(180deg, #ffffff 30%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--primary-glow), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p.lead {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 680px;
            margin: 0 auto 36px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 50px;
        }

        .btn-large {
            padding: 14px 34px;
            font-size: 16px;
            border-radius: var(--radius-md);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 28px;
            border-radius: var(--radius-lg);
            backdrop-filter: blur(10px);
            margin-top: 20px;
        }

        .stat-item h3 {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-glow);
            margin-bottom: 4px;
        }

        .stat-item p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Server Ping Section */
        .section-title {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-title h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-title p {
            color: var(--text-muted);
            font-size: 15px;
        }

        .ping-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 70px;
        }

        .ping-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.25s ease;
        }

        .ping-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-3px);
            background: var(--bg-card-hover);
        }

        .server-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .server-flag {
            font-size: 26px;
        }

        .server-name {
            font-size: 15px;
            font-weight: 700;
        }

        .server-proto {
            font-size: 12px;
            color: var(--text-muted);
        }

        .ping-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .ping-good {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Pricing Section */
        .pricing-section {
            padding: 40px 0 80px;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .plan-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px 24px;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .plan-card:hover {
            border-color: var(--primary-glow);
            transform: translateY(-6px);
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.4);
        }

        .plan-card.featured {
            border-color: var(--primary-glow);
            background: linear-gradient(180deg, rgba(2, 132, 199, 0.12) 0%, var(--bg-card) 100%);
        }

        .plan-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary-glow), var(--accent));
            color: #0b0f19;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 14px;
            border-radius: 999px;
            letter-spacing: 0.5px;
        }

        .plan-name {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .plan-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
            min-height: 38px;
        }

        .plan-price {
            font-size: 32px;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 24px;
        }

        .plan-price span {
            font-size: 14px;
            font-weight: 400;
            color: var(--text-muted);
        }

        .plan-features {
            list-style: none;
            margin-bottom: 30px;
            flex-grow: 1;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            margin-bottom: 12px;
            color: #cbd5e1;
        }

        .plan-features li svg {
            width: 16px;
            height: 16px;
            color: var(--success);
            flex-shrink: 0;
        }

        /* Instructions / Tabs */
        .tutorial-section {
            padding: 50px 0 80px;
        }

        .tabs-header {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 10px 24px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active, .tab-btn:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary-glow);
        }

        .tab-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 36px;
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .step-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
        }

        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .step-box h4 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .step-box p {
            font-size: 13.5px;
            color: var(--text-muted);
        }

        /* FAQ Section */
        .faq-section {
            padding: 40px 0 80px;
        }

        .faq-accordion {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .faq-question {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
        }

        .faq-answer {
            padding: 0 24px 20px;
            font-size: 14px;
            color: var(--text-muted);
            display: none;
            line-height: 1.8;
        }

        .faq-item.open .faq-answer {
            display: block;
        }

        .faq-item.open {
            border-color: var(--border-hover);
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border-color);
            background: rgba(11, 15, 25, 0.95);
            padding: 50px 0 30px;
            margin-top: 40px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-about p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 12px;
            max-width: 380px;
        }

        .footer-links h4 {
            font-size: 15px;
            margin-bottom: 16px;
            color: var(--text-main);
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--primary-glow);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .hero h1 { font-size: 34px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="container nav-wrap">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <span>دمان پنل</span>
            </a>

            <ul class="nav-links">
                <li><a href="#features">ویژگی‌ها</a></li>
                <li><a href="#servers">وضعیت سرورها</a></li>
                <li><a href="#pricing">تعرفه‌ها</a></li>
                <li><a href="#tutorial">آموزش اتصال</a></li>
                <li><a href="#faq">سوالات متداول</a></li>
            </ul>

            <div class="nav-actions">
                <a href="app/" class="btn btn-outline" title="ورود به نسخه وب">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    وب‌اپلیکیشن
                </a>
                <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-telegram">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.832.942z"/>
                    </svg>
                    ربات تلگرام
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="badge-live">
                <span class="pulse-dot"></span>
                <span>سرورهای اختصاصی V2Ray / Reality فعال و آماده اتصال هستند</span>
            </div>

            <h1>اینترنت <span>آزاد، پرسرعت و پایدار</span><br>برای تمام دستگاه‌ها</h1>

            <p class="lead">
                خرید آنی کانفیگ‌های اختصاصی با پینگ عالی، بدون افت سرعت در ساعات اوج مصرف و تحویل خودکار در کمتر از ۱۰ ثانیه از طریق ربات تلگرام.
            </p>

            <div class="hero-actions">
                <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-primary btn-large">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    خرید آنی اشتراک در تلگرام
                </a>
                <a href="<?= htmlspecialchars($botTelegramUrl) ?>?start=test" target="_blank" class="btn btn-outline btn-large">
                    ⚡ دریافت تست رایگان
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <h3>+۱۲,۵۰۰</h3>
                    <p>کاربر فعال و راضی</p>
                </div>
                <div class="stat-item">
                    <h3>۹۹.۹٪</h3>
                    <p>آپتایم سرورها</p>
                </div>
                <div class="stat-item">
                    <h3>۳۸ms</h3>
                    <p>میانگین پینگ سرورها</p>
                </div>
                <div class="stat-item">
                    <h3>&lt; ۵ ثانیه</h3>
                    <p>تحویل آنی بعد از پرداخت</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Server Ping / Status Section -->
    <section id="servers" class="container" style="padding-top: 40px;">
        <div class="section-title">
            <h2>🌍 وضعیت زنده و پینگ سرورها</h2>
            <p>تمام لوکیشن‌ها به صورت ۲۴ ساعته مانیتور شده و دارای بهترین مسیر به دیتاسنترهای ایران هستند.</p>
        </div>

        <div class="ping-grid">
            <div class="ping-card">
                <div class="server-info">
                    <span class="server-flag">🇩🇪</span>
                    <div>
                        <div class="server-name">آلمان (فرانکفورت)</div>
                        <div class="server-proto">پروتکل: VLESS / Reality / gRPC</div>
                    </div>
                </div>
                <span class="ping-badge ping-good">🟢 ۳۸ms</span>
            </div>

            <div class="ping-card">
                <div class="server-info">
                    <span class="server-flag">🇫🇮</span>
                    <div>
                        <div class="server-name">فنلاند (هلسینکی)</div>
                        <div class="server-proto">پروتکل: Trojan / Reality</div>
                    </div>
                </div>
                <span class="ping-badge ping-good">🟢 ۴۲ms</span>
            </div>

            <div class="ping-card">
                <div class="server-info">
                    <span class="server-flag">🇳🇱</span>
                    <div>
                        <div class="server-name">هلند (آمستردام)</div>
                        <div class="server-proto">پروتکل: VMess / WS / TLS</div>
                    </div>
                </div>
                <span class="ping-badge ping-good">🟢 ۴۵ms</span>
            </div>

            <div class="ping-card">
                <div class="server-info">
                    <span class="server-flag">🇫🇷</span>
                    <div>
                        <div class="server-name">فرانسه (پاریس)</div>
                        <div class="server-proto">پروتکل: VLESS / TCP</div>
                    </div>
                </div>
                <span class="ping-badge ping-good">🟢 ۴۹ms</span>
            </div>

            <div class="ping-card">
                <div class="server-info">
                    <span class="server-flag">🇹🇷</span>
                    <div>
                        <div class="server-name">ترکیه (استانبول)</div>
                        <div class="server-proto">پروتکل: Reality / Vision</div>
                    </div>
                </div>
                <span class="ping-badge ping-good">🟢 ۳۵ms</span>
            </div>

            <div class="ping-card">
                <div class="server-info">
                    <span class="server-flag">🇺🇸</span>
                    <div>
                        <div class="server-name">آمریکا (نیویورک)</div>
                        <div class="server-proto">پروتکل: Hysteria 2 / UDP</div>
                    </div>
                </div>
                <span class="ping-badge ping-good">🟢 ۹۵ms</span>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing-section">
        <div class="container">
            <div class="section-title">
                <h2>💎 پلن‌ها و تعرفه‌های اشتراک</h2>
                <p>تمامی پلن‌ها شامل آی‌پی ثابت، لینک ساب‌اسکریپشن هوشمند و پشتیبانی اختصاصی هستند.</p>
            </div>

            <div class="plans-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $idx => $prod): ?>
                        <div class="plan-card <?= $idx === 1 ? 'featured' : '' ?>">
                            <?php if ($idx === 1): ?>
                                <span class="plan-badge">محبوب‌ترین ⭐️</span>
                            <?php endif; ?>
                            <div class="plan-name"><?= htmlspecialchars($prod['name_product']) ?></div>
                            <div class="plan-desc">ترافیک: <?= htmlspecialchars($prod['Volume']) ?> گیگابایت | مدت: <?= htmlspecialchars($prod['time']) ?> روز</div>
                            <div class="plan-price"><?= number_format((int)$prod['price']) ?> <span>تومان</span></div>
                            <ul class="plan-features">
                                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پینگ پایین و عالی برای تماس و بازی</li>
                                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پشتیبانی از همراه اول، ایرانسل و رایتل</li>
                                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>لینک هوشمند ساب‌اسکریپشن (Sub)</li>
                                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پشتیبانی ۲۴ ساعته و رفع مشکل فوری</li>
                            </ul>
                            <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-primary" style="justify-content: center; width: 100%;">
                                خرید این پلن در ربات
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default Plans -->
                    <div class="plan-card">
                        <div class="plan-name">پلن پایه (برنزی)</div>
                        <div class="plan-desc">مناسب مصارف عادی، وبگردی و پیام‌رسان‌ها</div>
                        <div class="plan-price">۶۰,۰۰۰ <span>تومان</span></div>
                        <ul class="plan-features">
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۱۵ گیگابایت</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۳۰ روز</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>تک کاربره همزمان</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>تحویل آنی در تلگرام</li>
                        </ul>
                        <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-primary" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                    </div>

                    <div class="plan-card featured">
                        <span class="plan-badge">محبوب‌ترین ⭐️</span>
                        <div class="plan-name">پلن اقتصادی (نقره‌ای)</div>
                        <div class="plan-desc">مناسب اینستاگرام، تلگرام و تماشای ویدیو در یوتیوب</div>
                        <div class="plan-price">۱۲۰,۰۰۰ <span>تومان</span></div>
                        <ul class="plan-features">
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۳۵ گیگابایت</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۳۰ روز</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۲ کاربره همزمان</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>لوکیشن‌های آلمان و فنلاند</li>
                        </ul>
                        <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-primary" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                    </div>

                    <div class="plan-card">
                        <div class="plan-name">پلن پیشرفته (طلایی)</div>
                        <div class="plan-desc">مناسب مصارف سنگین، ترید، دانلود و خانواده</div>
                        <div class="plan-price">۲۳۰,۰۰۰ <span>تومان</span></div>
                        <ul class="plan-features">
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۷۵ گیگابایت</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۶۰ روز</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۳ کاربره همزمان</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پشتیبانی اولویت‌دار VIP</li>
                        </ul>
                        <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-primary" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                    </div>

                    <div class="plan-card">
                        <div class="plan-name">پلن ویژه (الماس)</div>
                        <div class="plan-desc">ترافیک فوق‌العاده بالا برای دفاتر و کاربران حرفه‌ای</div>
                        <div class="plan-price">۴۲۰,۰۰۰ <span>تومان</span></div>
                        <ul class="plan-features">
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۱۵۰ گیگابایت</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۹۰ روز</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۵ کاربره بدون افت سرعت</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>کانال ترافیک اختصاصی</li>
                        </ul>
                        <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-primary" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Connection Guide Section -->
    <section id="tutorial" class="tutorial-section container">
        <div class="section-title">
            <h2>📱 راهنمای تصویری اتصال به سرویس‌ها</h2>
            <p>روی سیستم‌عامل دستگاه خود کلیک کنید تا آموزش ۳ مرحله‌ای اتصال را ببینید.</p>
        </div>

        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab('android')">📱 اندروید (Android)</button>
            <button class="tab-btn" onclick="switchTab('ios')">🍏 آیفون و آیپد (iOS)</button>
            <button class="tab-btn" onclick="switchTab('windows')">💻 ویندوز (Windows)</button>
            <button class="tab-btn" onclick="switchTab('macos')">🍎 مک (macOS)</button>
        </div>

        <!-- Android Tab -->
        <div id="tab-android" class="tab-content active">
            <div class="step-list">
                <div class="step-box">
                    <div class="step-num">۱</div>
                    <h4>دانلود نرم‌افزار</h4>
                    <p>برنامه <b>v2rayNG</b> یا <b>Hiddify</b> را از گوگل‌پلی یا گیت‌هاب دانلود و نصب کنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۲</div>
                    <h4>وارد کردن اشتراک</h4>
                    <p>لینک ساب‌اسکریپشن دریافتی از ربات را کپی کرده، در برنامه علامت <b>+</b> را زده و <b>Import config from clipboard</b> را بزنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۳</div>
                    <h4>اتصال و لذت از سرعت</h4>
                    <p>روی دکمه دایره‌ای پایین بزنید تا متصل شوید. کانفیگ‌ها به صورت خودکار بروزرسانی می‌شوند.</p>
                </div>
            </div>
        </div>

        <!-- iOS Tab -->
        <div id="tab-ios" class="tab-content">
            <div class="step-list">
                <div class="step-box">
                    <div class="step-num">۱</div>
                    <h4>دانلود از اپ استور</h4>
                    <p>نرم‌افزار رایگان <b>Streisand</b> یا <b>V2Box</b> یا <b>FoXray</b> را از App Store دانلود نمایید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۲</div>
                    <h4>افزودن لینک هوشمند</h4>
                    <p>برنامه را باز کرده، روی علامت <b>+</b> در گوشه بالا بزنید و لینک ساب را در بخش Subscription وارد کنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۳</div>
                    <h4>اتصال آنی</h4>
                    <p>دکمه Connect را لمس کرده و با اینترنت پرسرعت و بدون قطعی وصل شوید.</p>
                </div>
            </div>
        </div>

        <!-- Windows Tab -->
        <div id="tab-windows" class="tab-content">
            <div class="step-list">
                <div class="step-box">
                    <div class="step-num">۱</div>
                    <h4>دانلود کلاینت ویندوز</h4>
                    <p>نرم‌افزار <b>v2rayN</b> یا <b>Nekoray</b> را دانلود و از حالت فشرده خارج کنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۲</div>
                    <h4>تنظیم گروه ساب</h4>
                    <p>به تب <b>Subscription group</b> رفته، Add را بزنید و آدرس ساب‌اسکریپشن را ذخیره کرده و <code>Ctrl+U</code> را بزنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۳</div>
                    <h4>فعال‌سازی سیستم پروکسی</h4>
                    <p>روی آیکون برنامه در تسک‌بار راست کلیک کرده و گزینه <b>Set system proxy</b> را فعال نمایید.</p>
                </div>
            </div>
        </div>

        <!-- macOS Tab -->
        <div id="tab-macos" class="tab-content">
            <div class="step-list">
                <div class="step-box">
                    <div class="step-num">۱</div>
                    <h4>دانلود نرم‌افزار مک</h4>
                    <p>برنامه <b>FoXray</b> را از Mac App Store یا <b>V2rayU</b> را دانلود کنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۲</div>
                    <h4>Import لینک ساب</h4>
                    <p>لینک اختصاصی را کپی کرده و از منوی Subscribe گزینه Update را بزنید.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">۳</div>
                    <h4>اتصال</h4>
                    <p>سوییچ اتصال را روشن کنید تا تمام برنامه‌ها و مرورگرها از اینترنت آزاد استفاده کنند.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq-section container">
        <div class="section-title">
            <h2>❓ سوالات متداول کاربران</h2>
            <p>پاسخ سریع به پرتکرارترین پرسش‌ها قبل از خرید اشتراک</p>
        </div>

        <div class="faq-accordion">
            <div class="faq-item open">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>آیا این سرویس‌ها روی تمام اپراتورها (همراه اول، ایرانسل، مخابرات) کار می‌کنند؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-answer">
                    بله! سرورهای ما از جدیدترین پروتکل‌های ضد فیلتر نظیر Reality، VLESS، Trojan و Hysteria 2 استفاده می‌کنند که روی تمام اپراتورهای سیار و ثابت با پینگ عالی و بدون افت سرعت متصل می‌شوند.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>نحوه تحویل سرویس چگونه است؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-answer">
                    تحویل سرویس‌ها به صورت ۱۰۰٪ خودکار و آنی است. بلافاصله پس از پرداخت در ربات تلگرام، لینک اختصاصی ساب‌اسکریپشن به همراه کد QR برای شما ارسال می‌شود.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>چگونه می‌توانم اکانت تست رایگان دریافت کنم؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-answer">
                    کافیست وارد ربات تلگرام ما شده و دکمه «⚡ تست رایگان» را لمس کنید تا یک اکانت تست برای شما فوراً ساخته شود و از کیفیت و سرعت آن مطمئن شوید.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>روش‌های پرداخت چیست؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-answer">
                    شما می‌توانید از طریق کارت به کارت (با ارسال فیش و تایید سریع)، درگاه‌های آنلاین بانکی شتاب و همچنین ارزهای دیجیتال (ترون TRX، تتر USDT و...) حساب خود را شارژ نمایید.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>در صورت بروز مشکل چگونه پشتیبانی بگیرم؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-answer">
                    تیم پشتیبانی ما به صورت ۲۴ ساعته در تلگرام آماده پاسخگویی و راهنمایی شما در مراحل اتصال و رفع مشکل است.
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container footer-grid">
            <div class="footer-about">
                <a href="#" class="logo">
                    <div class="logo-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <span>دمان پنل (Douman Panel)</span>
                </a>
                <p>
                    ارائه‌دهنده سرویس‌های اختصاصی و پرسرعت عبور از تحریم با پروتکل‌های مدرن، امنیت فوق‌العاده و آپتایم ۹۹.۹ درصد.
                </p>
            </div>

            <div class="footer-links">
                <h4>دسترسی سریع</h4>
                <ul>
                    <li><a href="#servers">وضعیت سرورها</a></li>
                    <li><a href="#pricing">تعرفه پلن‌ها</a></li>
                    <li><a href="#tutorial">راهنمای اتصال</a></li>
                    <li><a href="app/">وب‌اپلیکیشن (Mini App)</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>بخش مدیریت و ارتباط</h4>
                <ul>
                    <li><a href="admin.php">ورود به پنل مدیریت وب</a></li>
                    <li><a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank">ربات فروش تلگرام</a></li>
                    <?php if (!empty($channelLink) && $channelLink !== 'https://t.me/'): ?>
                        <li><a href="<?= htmlspecialchars($channelLink) ?>" target="_blank">کانال تلگرام اطلاعیه‌ها</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="container footer-bottom">
            تمامی حقوق محفوظ است © <?= date('Y') ?> دوماین پنل (Douman Panel Bot).
        </div>
    </footer>

    <script>
        function switchTab(platform) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            
            event.target.classList.add('active');
            const target = document.getElementById('tab-' + platform);
            if (target) target.classList.add('active');
        }

        function toggleFaq(el) {
            const item = el.parentElement;
            item.classList.toggle('open');
        }
    </script>
</body>
</html>
