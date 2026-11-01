<?php
/**
 * Douman Panel Pro - Ultimate Next-Gen Website & Landing Page
 * وب‌سایت و لندینگ پیج اختصاصی، مدرن و تعاملی دمان پنل پرو
 */

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
    <title>دمان پنل پرو | خرید آنلاین کانفیگ پرسرعت و پایدار V2Ray / Reality</title>
    <meta name="description" content="خرید آنی کانفیگ اختصاصی ضد فیلتر V2Ray، Xray، Reality، Hysteria 2 با تحویل آنی کمتر از ۱۰ ثانیه در تلگرام و پینگ عالی روی تمام اپراتورها">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #070913;
            --bg-card: rgba(15, 23, 42, 0.7);
            --bg-card-hover: rgba(30, 41, 59, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(56, 189, 248, 0.5);
            --primary: #0284c7;
            --primary-light: #38bdf8;
            --accent-purple: #a855f7;
            --accent-emerald: #10b981;
            --text-title: #ffffff;
            --text-body: #94a3b8;
            --radius-xl: 24px;
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
            background-color: var(--bg-dark);
            color: #f1f5f9;
            line-height: 1.7;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Cosmic Background */
        .cosmic-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            z-index: -1;
            pointer-events: none;
            opacity: 0.5;
            animation: floatOrb 12s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #0284c7 0%, transparent 70%);
            top: -100px;
            right: -100px;
        }

        .orb-2 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #7e22ce 0%, transparent 70%);
            bottom: 50px;
            left: -150px;
            animation-duration: 16s;
        }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, 60px) scale(1.15); }
        }

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
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            background: rgba(7, 9, 19, 0.75);
            border-bottom: 1px solid var(--border-glass);
            transition: all 0.3s ease;
        }

        .nav-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 84px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
            font-weight: 900;
            font-size: 22px;
            letter-spacing: -0.5px;
        }

        .logo-icon {
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, var(--primary-light), var(--accent-purple));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.5);
            color: #fff;
        }

        .pro-badge {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            color: #fff;
            font-size: 10px;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            margin-right: 6px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-body);
            text-decoration: none;
            font-size: 14.5px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary-light);
            text-shadow: 0 0 12px rgba(56, 189, 248, 0.6);
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
            transition: all 0.25s ease;
            cursor: pointer;
            border: none;
        }

        .btn-glow {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #04101e;
            font-weight: 800;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
        }

        .btn-glow:hover {
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.7);
            transform: translateY(-2px);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            border: 1px solid var(--border-glass);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--border-glow);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            padding: 80px 0 60px;
            text-align: center;
            position: relative;
        }

        .live-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 18px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 999px;
            color: #34d399;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 26px;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
            animation: livePulse 1.8s infinite;
        }

        @keyframes livePulse {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.15); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .hero h1 {
            font-size: 52px;
            font-weight: 900;
            letter-spacing: -1.5px;
            line-height: 1.3;
            margin-bottom: 22px;
            background: linear-gradient(180deg, #ffffff 40%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero h1 .highlight {
            background: linear-gradient(135deg, #38bdf8 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p.lead {
            font-size: 18px;
            color: var(--text-body);
            max-width: 700px;
            margin: 0 auto 38px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 50px;
        }

        .btn-hero {
            padding: 14px 36px;
            font-size: 16px;
            border-radius: var(--radius-md);
        }

        /* Stats Strip */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-xl);
            padding: 28px;
            backdrop-filter: blur(14px);
            margin-top: 30px;
        }

        .stat-box h3 {
            font-size: 36px;
            font-weight: 900;
            background: linear-gradient(135deg, #38bdf8, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .stat-box p {
            color: var(--text-body);
            font-size: 13.5px;
        }

        /* Server Ping Section */
        .section-header {
            text-align: center;
            margin: 80px 0 45px;
        }

        .section-header h2 {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .section-header p {
            color: var(--text-body);
            font-size: 15px;
        }

        .ping-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .ping-item {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .ping-item:hover {
            border-color: var(--border-glow);
            transform: translateY(-4px);
            background: var(--bg-card-hover);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .location-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .flag-icon {
            font-size: 28px;
        }

        .country-title {
            font-weight: 700;
            font-size: 15px;
        }

        .protocol-tag {
            font-size: 11.5px;
            color: var(--text-body);
        }

        .ping-num {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Interactive Speed Simulator Widget */
        .speed-widget {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.5) 100%);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-xl);
            padding: 36px;
            margin: 50px 0;
            text-align: center;
            backdrop-filter: blur(14px);
            position: relative;
            overflow: hidden;
        }

        .speed-gauge-wrap {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 24px;
            margin-top: 24px;
        }

        .speed-metric {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 18px 30px;
            min-width: 200px;
        }

        .speed-val {
            font-size: 38px;
            font-weight: 900;
            color: var(--primary-light);
            font-family: monospace;
        }

        .speed-label {
            font-size: 13px;
            color: var(--text-body);
        }

        /* Pricing Section */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 22px;
            margin-top: 30px;
        }

        .plan-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-xl);
            padding: 34px 26px;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            backdrop-filter: blur(14px);
        }

        .plan-card:hover {
            border-color: var(--primary-light);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .plan-card.popular {
            border-color: var(--primary-light);
            background: linear-gradient(180deg, rgba(2, 132, 199, 0.15) 0%, var(--bg-card) 100%);
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.15);
        }

        .popular-badge {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #38bdf8, #a855f7);
            color: #070913;
            font-size: 11.5px;
            font-weight: 900;
            padding: 4px 16px;
            border-radius: 999px;
            letter-spacing: 0.5px;
        }

        .plan-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .plan-subtitle {
            font-size: 13px;
            color: var(--text-body);
            min-height: 40px;
            margin-bottom: 24px;
        }

        .plan-cost {
            font-size: 34px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 24px;
        }

        .plan-cost span {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-body);
        }

        .plan-features-list {
            list-style: none;
            margin-bottom: 32px;
            flex-grow: 1;
        }

        .plan-features-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            margin-bottom: 12px;
            color: #cbd5e1;
        }

        .plan-features-list li svg {
            width: 17px;
            height: 17px;
            color: var(--accent-emerald);
            flex-shrink: 0;
        }

        /* Interactive Tutorial Tabs */
        .tutorial-tabs {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .t-tab-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            color: var(--text-body);
            padding: 12px 26px;
            border-radius: var(--radius-sm);
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .t-tab-btn.active, .t-tab-btn:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary-light);
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);
        }

        .t-pane {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-xl);
            padding: 40px;
            display: none;
            backdrop-filter: blur(14px);
        }

        .t-pane.active {
            display: block;
            animation: fadeInTab 0.35s ease;
        }

        @keyframes fadeInTab {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .instruction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .instruction-card {
            background: rgba(7, 9, 19, 0.6);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 24px;
            position: relative;
        }

        .step-index {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #04101e;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 16px;
            margin-bottom: 16px;
        }

        .instruction-card h4 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .instruction-card p {
            font-size: 13.5px;
            color: var(--text-body);
        }

        /* FAQ Accordion */
        .faq-wrap {
            max-width: 820px;
            margin: 0 auto;
        }

        .faq-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            margin-bottom: 14px;
            overflow: hidden;
            transition: all 0.25s;
        }

        .faq-q {
            padding: 22px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 700;
            font-size: 15.5px;
        }

        .faq-a {
            padding: 0 26px 22px;
            font-size: 14px;
            color: var(--text-body);
            display: none;
            line-height: 1.85;
        }

        .faq-card.open .faq-a {
            display: block;
        }

        .faq-card.open {
            border-color: var(--border-glow);
        }

        /* Live Purchase Toast */
        .live-toast {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid var(--border-glow);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            border-radius: 14px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            backdrop-filter: blur(12px);
            animation: toastSlide 0.5s ease;
            transition: opacity 0.5s ease;
        }

        @keyframes toastSlide {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border-glass);
            background: rgba(7, 9, 19, 0.95);
            padding: 60px 0 30px;
            margin-top: 80px;
        }

        .footer-cols {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-cols h4 {
            font-size: 16px;
            margin-bottom: 18px;
            color: #fff;
        }

        .footer-cols ul {
            list-style: none;
        }

        .footer-cols li {
            margin-bottom: 12px;
        }

        .footer-cols a {
            color: var(--text-body);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .footer-cols a:hover {
            color: var(--primary-light);
        }

        .copy-strip {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-glass);
            color: var(--text-body);
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .nav-links { display: none; }
            .hero h1 { font-size: 36px; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .footer-cols { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Ambient Glow Orbs -->
    <div class="cosmic-orb orb-1"></div>
    <div class="cosmic-orb orb-2"></div>

    <!-- Live Toast -->
    <div id="toastNotification" class="live-toast">
        <div style="font-size: 20px;">⚡</div>
        <div>
            <div style="font-size: 13px; font-weight: 700; color: #f8fafc;" id="toastTitle">خرید آنی اشتراک</div>
            <div style="font-size: 12px; color: #94a3b8;" id="toastDesc">کاربر ۹۱۲*** پلن ۳۵ گیگ را فعال کرد (۲ دقیقه پیش)</div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="container nav-wrap">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <span>دمان پنل</span>
                <span class="pro-badge">PRO</span>
            </a>

            <ul class="nav-links">
                <li><a href="#servers">وضعیت سرورها</a></li>
                <li><a href="#speed">تست سرعت زنده</a></li>
                <li><a href="#pricing">تعرفه پلن‌ها</a></li>
                <li><a href="#tutorial">راهنمای اتصال</a></li>
                <li><a href="#faq">سوالات متداول</a></li>
            </ul>

            <div class="nav-actions">
                <a href="app/" class="btn btn-ghost" title="ورود به نسخه وب">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    وب‌اپلیکیشن
                </a>
                <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-glow">
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
            <div class="live-tag">
                <span class="live-dot"></span>
                <span>سرورهای اختصاصی V2Ray / Reality نسخه پرو آنلاین و پرسرعت</span>
            </div>

            <h1>اینترنت <span class="highlight">آزاد، پرسرعت و پایدار</span><br>بدون قطعی روی تمام اپراتورها</h1>

            <p class="lead">
                خرید آنی کانفیگ‌های اختصاصی با پینگ عالی، بدون افت سرعت در ساعات اوج مصرف و تحویل خودکار در کمتر از ۵ ثانیه در ربات تلگرام دمان پنل پرو.
            </p>

            <div class="hero-actions">
                <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-glow btn-hero">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    خرید آنی اشتراک در تلگرام
                </a>
                <a href="<?= htmlspecialchars($botTelegramUrl) ?>?start=test" target="_blank" class="btn btn-ghost btn-hero">
                    ⚡ دریافت اکانت تست رایگان
                </a>
            </div>

            <div class="stats-bar">
                <div class="stat-box">
                    <h3>+۱۵,۲۰۰</h3>
                    <p>کاربر فعال و راضی</p>
                </div>
                <div class="stat-box">
                    <h3>۹۹.۹۸٪</h3>
                    <p>آپتایم سرورها</p>
                </div>
                <div class="stat-box">
                    <h3>۳۴ms</h3>
                    <p>کمترین پینگ ثبت‌شده</p>
                </div>
                <div class="stat-box">
                    <h3>&lt; ۵ ثانیه</h3>
                    <p>تحویل خودکار کانفیگ</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Server Radar & Ping -->
    <section id="servers" class="container">
        <div class="section-header">
            <h2>🌍 مانیتورینگ زنده و پینگ لوکیشن‌ها</h2>
            <p>سرورهای دمان پنل با ترافیک نامحدود شبکه ۱۰ گیگابیت بر ثانیه مستقیماً به دیتاسنترهای معتبر اروپایی متصل هستند.</p>
        </div>

        <div class="ping-grid">
            <div class="ping-item">
                <div class="location-info">
                    <span class="flag-icon">🇩🇪</span>
                    <div>
                        <div class="country-title">آلمان (فرانکفورت)</div>
                        <div class="protocol-tag">پروتکل: VLESS / Reality / gRPC</div>
                    </div>
                </div>
                <span class="ping-num">🟢 ۳۴ms</span>
            </div>

            <div class="ping-item">
                <div class="location-info">
                    <span class="flag-icon">🇫🇮</span>
                    <div>
                        <div class="country-title">فنلاند (هلسینکی)</div>
                        <div class="protocol-tag">پروتکل: Trojan / Reality</div>
                    </div>
                </div>
                <span class="ping-num">🟢 ۳۸ms</span>
            </div>

            <div class="ping-item">
                <div class="location-info">
                    <span class="flag-icon">🇳🇱</span>
                    <div>
                        <div class="country-title">هلند (آمستردام)</div>
                        <div class="protocol-tag">پروتکل: VMess / WS / TLS</div>
                    </div>
                </div>
                <span class="ping-num">🟢 ۴۱ms</span>
            </div>

            <div class="ping-item">
                <div class="location-info">
                    <span class="flag-icon">🇫🇷</span>
                    <div>
                        <div class="country-title">فرانسه (پاریس)</div>
                        <div class="protocol-tag">پروتکل: VLESS / TCP</div>
                    </div>
                </div>
                <span class="ping-num">🟢 ۴۵ms</span>
            </div>

            <div class="ping-item">
                <div class="location-info">
                    <span class="flag-icon">🇹🇷</span>
                    <div>
                        <div class="country-title">ترکیه (استانبول)</div>
                        <div class="protocol-tag">پروتکل: Reality / Vision</div>
                    </div>
                </div>
                <span class="ping-num">🟢 ۲۹ms</span>
            </div>

            <div class="ping-item">
                <div class="location-info">
                    <span class="flag-icon">🇺🇸</span>
                    <div>
                        <div class="country-title">آمریکا (نیویورک)</div>
                        <div class="protocol-tag">پروتکل: Hysteria 2 / UDP</div>
                    </div>
                </div>
                <span class="ping-num">🟢 ۸۸ms</span>
            </div>
        </div>
    </section>

    <!-- Speed Simulator Widget -->
    <section id="speed" class="container">
        <div class="speed-widget">
            <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">🚀 شبیه‌ساز تست سرعت سرورهای دمان پنل</h3>
            <p style="color: var(--text-body); font-size: 14px;">میانگین سرعت دانلود و آپلود کاربران روی شبکه ایرانسل و همراه اول</p>

            <div class="speed-gauge-wrap">
                <div class="speed-metric">
                    <div class="speed-val" id="dlSpeed">284.6</div>
                    <div class="speed-label">دانلود (Mbps)</div>
                </div>
                <div class="speed-metric">
                    <div class="speed-val" id="ulSpeed">195.2</div>
                    <div class="speed-label">آپلود (Mbps)</div>
                </div>
                <div class="speed-metric">
                    <div class="speed-val" style="color: #34d399;">31</div>
                    <div class="speed-label">پینگ به سرور (ms)</div>
                </div>
            </div>

            <button onclick="simulateSpeed()" class="btn btn-ghost" style="margin-top: 24px;">
                🔄 تست مجدد پینگ و سرعت
            </button>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="container">
        <div class="section-header">
            <h2>💎 پلن‌ها و تعرفه‌های اشتراک</h2>
            <p>تمام پلن‌ها شامل آی‌پی ثابت تمیز، لینک هوشمند ساب‌اسکریپشن و پشتیبانی دائمی هستند.</p>
        </div>

        <div class="plans-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $idx => $prod): ?>
                    <div class="plan-card <?= $idx === 1 ? 'popular' : '' ?>">
                        <?php if ($idx === 1): ?>
                            <span class="popular-badge">پیشنهاد ویژه ⭐️</span>
                        <?php endif; ?>
                        <div class="plan-title"><?= htmlspecialchars($prod['name_product']) ?></div>
                        <div class="plan-subtitle">ترافیک: <?= htmlspecialchars($prod['Volume']) ?> گیگابایت | مدت اعتبار: <?= htmlspecialchars($prod['time']) ?> روز</div>
                        <div class="plan-cost"><?= number_format((int)$prod['price']) ?> <span>تومان</span></div>
                        <ul class="plan-features-list">
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پینگ تضمینی عالی برای بازی و تماس</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پشتیبانی کامل از همراه اول و ایرانسل</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>لینک هوشمند ساب با آپدیت خودکار</li>
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>تحویل آنی و خودکار در تلگرام</li>
                        </ul>
                        <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-glow" style="justify-content: center; width: 100%;">
                            خرید این پلن در ربات
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default Pro Tier Plans -->
                <div class="plan-card">
                    <div class="plan-title">پلن پایه (برنزی)</div>
                    <div class="plan-subtitle">مناسب مصارف عادی، وبگردی و ارسال پیام</div>
                    <div class="plan-cost">۶۵,۰۰۰ <span>تومان</span></div>
                    <ul class="plan-features-list">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۱۵ گیگابایت</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۳۰ روزه</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۱ کاربره همزمان</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>لوکیشن‌های آلمان و هلند</li>
                    </ul>
                    <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-ghost" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                </div>

                <div class="plan-card popular">
                    <span class="popular-badge">محبوب‌ترین ⭐️</span>
                    <div class="plan-title">پلن اقتصادی (نقره‌ای)</div>
                    <div class="plan-subtitle">مناسب اینستاگرام، تلگرام و یوتیوب بدون بافر</div>
                    <div class="plan-cost">۱۲۵,۰۰۰ <span>تومان</span></div>
                    <ul class="plan-features-list">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۳۵ گیگابایت</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۳۰ روزه</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۲ کاربره همزمان</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پروتکل‌های پرسرعت Reality</li>
                    </ul>
                    <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-glow" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                </div>

                <div class="plan-card">
                    <div class="plan-title">پلن پیشرفته (طلایی)</div>
                    <div class="plan-subtitle">مناسب تماشای ویدیو، استریم، گیم و ترید</div>
                    <div class="plan-cost">۲۴۰,۰۰۰ <span>تومان</span></div>
                    <ul class="plan-features-list">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۷۵ گیگابایت</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۶۰ روزه</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۳ کاربره همزمان</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>پشتیبانی اختصاصی VIP</li>
                    </ul>
                    <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-ghost" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                </div>

                <div class="plan-card">
                    <div class="plan-title">پلن ویژه (الماس)</div>
                    <div class="plan-subtitle">ترافیک بسیار بالا برای خانواده و دفاتر کاری</div>
                    <div class="plan-cost">۴۴۰,۰۰۰ <span>تومان</span></div>
                    <ul class="plan-features-list">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>حجم: ۱۵۰ گیگابایت</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مدت اعتبار: ۹۰ روزه</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>۵ کاربره همزمان</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>مسیردهی اختصاصی بدون قطعی</li>
                    </ul>
                    <a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank" class="btn btn-ghost" style="justify-content: center; width: 100%;">خرید اشتراک</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Tutorial Tabs Section -->
    <section id="tutorial" class="container">
        <div class="section-header">
            <h2>📱 راهنمای ۳ مرحله‌ای اتصال به کانفیگ‌ها</h2>
            <p>سیستم‌عامل دستگاه خود را انتخاب کنید تا آموزش گام‌به‌گام برای شما نمایش داده شود.</p>
        </div>

        <div class="tutorial-tabs">
            <button class="t-tab-btn active" onclick="activateTab('android')">📱 اندروید (Android)</button>
            <button class="t-tab-btn" onclick="activateTab('ios')">🍏 آیفون و آیپد (iOS)</button>
            <button class="t-tab-btn" onclick="activateTab('windows')">💻 ویندوز (Windows)</button>
            <button class="t-tab-btn" onclick="activateTab('macos')">🍎 مک (macOS)</button>
        </div>

        <!-- Android -->
        <div id="pane-android" class="t-pane active">
            <div class="instruction-grid">
                <div class="instruction-card">
                    <div class="step-index">۱</div>
                    <h4>دانلود نرم‌افزار</h4>
                    <p>برنامه محبوب <b>v2rayNG</b> یا <b>Hiddify</b> را از گوگل‌پلی یا گیت‌هاب دانلود و نصب کنید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۲</div>
                    <h4>وارد کردن اشتراک</h4>
                    <p>لینک ساب‌اسکریپشن دریافتی از ربات را کپی کرده، در برنامه علامت <b>+</b> را زده و <b>Import config from clipboard</b> را لمس کنید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۳</div>
                    <h4>اتصال آنی</h4>
                    <p>دکمه دایره‌ای اتصال پایین را لمس کنید تا بلافاصله به اینترنت پرسرعت و پایدار وصل شوید.</p>
                </div>
            </div>
        </div>

        <!-- iOS -->
        <div id="pane-ios" class="t-pane">
            <div class="instruction-grid">
                <div class="instruction-card">
                    <div class="step-index">۱</div>
                    <h4>نصب برنامه از اپ‌استور</h4>
                    <p>نرم‌افزار رایگان <b>Streisand</b> یا <b>V2Box</b> یا <b>FoXray</b> را از App Store دانلود کنید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۲</div>
                    <h4>افزودن لینک هوشمند</h4>
                    <p>برنامه را باز کرده، روی علامت <b>+</b> بزنید و لینک ساب‌اسکریپشن را در بخش Subscription ذخیره کنید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۳</div>
                    <h4>اتصال</h4>
                    <p>کلید اتصال را بزنید؛ کانفیگ‌ها به صورت کاملاً خودکار و پیوسته آپدیت می‌شوند.</p>
                </div>
            </div>
        </div>

        <!-- Windows -->
        <div id="pane-windows" class="t-pane">
            <div class="instruction-grid">
                <div class="instruction-card">
                    <div class="step-index">۱</div>
                    <h4>دانلود v2rayN</h4>
                    <p>نرم‌افزار <b>v2rayN</b> را دانلود کرده و فایل zip را استخراج (Extract) نمایید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۲</div>
                    <h4>افزودن لینک Sub</h4>
                    <p>به منوی <b>Subscription group</b> رفته، لینک را اضافه کنید و کلید <code>Ctrl + U</code> را بزنید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۳</div>
                    <h4>فعال‌سازی سیستم پروکسی</h4>
                    <p>در تسک‌بار روی آیکون برنامه راست‌کلیک کرده و <b>Set system proxy</b> را فعال کنید.</p>
                </div>
            </div>
        </div>

        <!-- macOS -->
        <div id="pane-macos" class="t-pane">
            <div class="instruction-grid">
                <div class="instruction-card">
                    <div class="step-index">۱</div>
                    <h4>نصب کلاینت</h4>
                    <p>نرم‌افزار <b>FoXray</b> را از Mac App Store یا <b>V2rayU</b> را دانلود کنید.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۲</div>
                    <h4>ایمپورت کانفیگ</h4>
                    <p>لینک ساب دریافتی را کپی و در برنامه Import کنید تا لیست سرورها بارگذاری شود.</p>
                </div>
                <div class="instruction-card">
                    <div class="step-index">۳</div>
                    <h4>اتصال</h4>
                    <p>سوئیچ Connect را روشن کنید تا تمام برنامه‌ها و مرورگرهای مک به اینترنت آزاد وصل شوند.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="container">
        <div class="section-header">
            <h2>❓ سوالات متداول</h2>
            <p>پاسخ سریع به پرسش‌های رایج کاربران قبل از تهیه سرویس</p>
        </div>

        <div class="faq-wrap">
            <div class="faq-card open">
                <div class="faq-q" onclick="toggleAccordion(this)">
                    <span>آیا سرویس‌های دمان پنل روی همراه اول و ایرانسل فعال هستند؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-a">
                    بله! سرورهای دمان پنل پرو از به‌روزترین پروتکل‌های ضد فیلترینگ نظیر VLESS Reality و Hysteria 2 بهره می‌برند که روی تمام اپراتورها (همراه اول، ایرانسل، رایتل و مخابرات) بالاترین سرعت و پایداری را ارائه می‌دهند.
                </div>
            </div>

            <div class="faq-card">
                <div class="faq-q" onclick="toggleAccordion(this)">
                    <span>چگونه اکانت تست رایگان دریافت کنم؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-a">
                    کافیست وارد ربات تلگرام ما شوید و دکمه «⚡ تست رایگان» را لمس کنید تا در چند ثانیه یک کانفیگ تست برای شما صادر شود.
                </div>
            </div>

            <div class="faq-card">
                <div class="faq-q" onclick="toggleAccordion(this)">
                    <span>تحویل کانفیگ بعد از پرداخت چقدر زمان می‌برد؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-a">
                    فرآیند خرید و صدور اکانت به صورت ۱۰۰٪ هوشمند و خودکار است؛ بلافاصله پس از پرداخت در تلگرام، لینک ساب‌اسکریپشن و کد QR در کمتر از ۵ ثانیه تحویل شما می‌گردد.
                </div>
            </div>

            <div class="faq-card">
                <div class="faq-q" onclick="toggleAccordion(this)">
                    <span>روش‌های پرداخت به چه صورت است؟</span>
                    <span>▾</span>
                </div>
                <div class="faq-a">
                    شما می‌توانید به راحتی از طریق کارت به کارت (با ارسال فیش و تایید آنی)، درگاه‌های آنلاین بانکی شتاب و ارز دیجیتال (ترون TRX، تتر USDT و...) حساب خود را شارژ کنید.
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container footer-cols">
            <div>
                <a href="#" class="logo" style="margin-bottom: 14px;">
                    <div class="logo-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <span>دوماین پنل پرو</span>
                </a>
                <p style="color: var(--text-body); font-size: 14px; max-width: 380px;">
                    ارائه‌دهنده سرویس‌های اختصاصی عبور از تحریم و اینترنت آزاد با پایداری ۹۹.۹٪ و پینگ فوق‌العاده روی تمامی دستگاه‌ها.
                </p>
            </div>

            <div>
                <h4>دسترسی سریع</h4>
                <ul>
                    <li><a href="#servers">وضعیت سرورها</a></li>
                    <li><a href="#pricing">تعرفه پلن‌ها</a></li>
                    <li><a href="#tutorial">راهنمای اتصال</a></li>
                    <li><a href="app/">وب‌اپلیکیشن مینی‌اپ</a></li>
                </ul>
            </div>

            <div>
                <h4>بخش مدیریت و ربات</h4>
                <ul>
                    <li><a href="admin.php">ورود به پنل مدیریت وب</a></li>
                    <li><a href="<?= htmlspecialchars($botTelegramUrl) ?>" target="_blank">ربات فروش تلگرام</a></li>
                    <?php if (!empty($channelLink) && $channelLink !== 'https://t.me/'): ?>
                        <li><a href="<?= htmlspecialchars($channelLink) ?>" target="_blank">کانال اطلاعیه‌ها</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="container copy-strip">
            تمامی حقوق محفوظ است © <?= date('Y') ?> دوماین پنل پرو (Douman Panel Bot Pro).
        </div>
    </footer>

    <script>
        function activateTab(name) {
            document.querySelectorAll('.t-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.t-pane').forEach(pane => pane.classList.remove('active'));
            event.target.classList.add('active');
            const target = document.getElementById('pane-' + name);
            if (target) target.classList.add('active');
        }

        function toggleAccordion(el) {
            el.parentElement.classList.toggle('open');
        }

        function simulateSpeed() {
            const dl = document.getElementById('dlSpeed');
            const ul = document.getElementById('ulSpeed');
            dl.innerText = '...';
            ul.innerText = '...';
            setTimeout(() => {
                dl.innerText = (250 + Math.random() * 50).toFixed(1);
                ul.innerText = (170 + Math.random() * 40).toFixed(1);
            }, 600);
        }

        // Live purchase toasts simulation
        const buyers = [
            'کاربر 0912*** پلن نقره‌ای ۳۵ گیگابایت را فعال کرد (چند لحظه پیش)',
            'کاربر 0935*** اکانت تست رایگان ۱ روزه دریافت کرد (۱ دقیقه پیش)',
            'کاربر 0919*** اشتراک طلایی ۷۵ گیگابایت را تمدید کرد (۲ دقیقه پیش)',
            'کاربر 0936*** پلن ویژه الماس ۱۵۰ گیگابایت را فعال کرد (۴ دقیقه پیش)'
        ];
        let toastIdx = 0;
        setInterval(() => {
            const toast = document.getElementById('toastNotification');
            const desc = document.getElementById('toastDesc');
            if (!toast || !desc) return;
            toast.style.opacity = '0';
            setTimeout(() => {
                toastIdx = (toastIdx + 1) % buyers.length;
                desc.innerText = buyers[toastIdx];
                toast.style.opacity = '1';
            }, 500);
        }, 8000);
    </script>
</body>
</html>
