<?php
$appTitle = "VPN Panel - Mini App";
$brandTitle = "VPN Panel";
$brandLogo = "V";
try {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->query("SELECT value FROM shopSetting WHERE Namevalue = 'title' LIMIT 1");
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['value'])) {
                    $brandTitle = htmlspecialchars($row['value']);
                    $appTitle = $brandTitle . " - Mini App";
                    $brandLogo = mb_substr($brandTitle, 0, 1, 'UTF-8');
                }
            }
        }
    }
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $appTitle ?></title>
    <link rel="stylesheet" href="./css/theme.css?v=<?= time() ?>">
    <script>
        window.addEventListener('error', function(e) {
            console.error('Captured Global Error:', e.message, e.filename, e.lineno);
        });
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Captured Rejection:', e.reason);
        });
    </script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        if (!window.Telegram || !window.Telegram.WebApp) {
            const fallbackScript = document.createElement('script');
            fallbackScript.src = './js/telegram-web-app.js';
            document.head.appendChild(fallbackScript);
        }
    </script>
    <script src="./js/qrcode.min.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Desktop Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand-wrap">
                    <div class="brand-logo"><?= $brandLogo ?></div>
                    <div class="brand-title"><?= $brandTitle ?></div>
                </div>
            </div>
            <nav class="sidebar-nav" id="sidebar-nav"></nav>
            <div class="sidebar-footer">
                <div class="theme-row">
                    <div class="theme-pill-group">
                        <button class="theme-pill active" id="theme-dark-btn">تاریک</button>
                        <button class="theme-pill" id="theme-light-btn">روشن</button>
                    </div>
                    <button class="tg-circle-btn" id="tg-channel-btn" title="کانال تلگرام">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                    </button>
                </div>
                <div class="credit-tag">نسخه مینی‌اپ ۲.۰ | <?= $brandTitle ?></div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-right">
                    <button class="back-btn" id="back-button" title="بازگشت">
                        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.2" fill="none"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <span class="page-title" id="topbar-title">خانه</span>
                </div>
                <div class="topbar-left">
                    <div class="balance-pill" id="header-balance-btn">
                        <span>موجودی:</span>
                        <span class="num" id="header-balance-val">۰ تومان</span>
                        <span style="font-weight: bold; color: var(--accent-lime); font-size: 15px;">+</span>
                    </div>
                    <div class="bell-btn" id="bell-button" title="اطلاعیه‌ها">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <span class="bell-badge"></span>
                    </div>
                </div>
            </header>

            <main class="content-view" id="content-area">
                <!-- Views rendered dynamically by App.navigate -->
            </main>

            <!-- Mobile Bottom Nav -->
            <nav class="mobile-bottom-nav" id="mobile-bottom-nav"></nav>
        </div>
    </div>

    <!-- Core App Scripts -->
    <script src="./js/app.js?v=<?= time() ?>"></script>
    <script src="./js/app-views.js?v=<?= time() ?>"></script>
    <script src="./js/app-shop.js?v=<?= time() ?>"></script>
    <script src="./js/app-services.js?v=<?= time() ?>"></script>
</body>
</html>
