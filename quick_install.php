<?php
/**
 * Douman Panel Pro - Web & cPanel Quick Installer
 * نصب‌کننده تحت وب و آسان دوپینل پرو برای هاست‌های اشتراکی cPanel، دایرکت‌ادمین و وردپرس
 */

declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFile = __DIR__ . '/config.php';
$isInstalled = file_exists($configFile) && filesize($configFile) > 100 && !str_contains((string)file_get_contents($configFile), '{database_url}');

$errors = [];
$successMessage = '';
$webhookResult = null;

// Extension checks
$requiredExts = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'openssl', 'json'];
$missingExts = [];
foreach ($requiredExts as $ext) {
    if (!extension_loaded($ext)) {
        $missingExts[] = $ext;
    }
}

// Handle Self-Deletion for security
if (isset($_GET['delete_installer']) && $_GET['delete_installer'] === '1' && $isInstalled) {
    @unlink(__FILE__);
    header("Location: landing.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_submit'])) {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $botToken = trim($_POST['bot_token'] ?? '');
    $adminId = trim($_POST['admin_id'] ?? '');
    $botUsername = trim($_POST['bot_username'] ?? '');
    $domain = trim($_POST['domain'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
    $subDir = trim($_POST['subdir'] ?? '');

    if (empty($dbName) || empty($dbUser) || empty($botToken) || empty($adminId)) {
        $errors[] = 'لطفاً تمامی فیلدهای ستاره‌دار (نام دیتابیس، نام کاربری، رمز عبور، توکن ربات و آیدی عددی ادمین) را پر کنید.';
    } elseif (!preg_match('/^-?[0-9]+$/', $adminId)) {
        $errors[] = 'آیدی عددی ادمین باید صرفاً عدد باشد (مثلاً 12345678). از وارد کردن یوزرنیم یا لینک خودداری کنید.';
    } else {
        // Test Database Connection
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Format Domain Host
            $cleanDomain = rtrim($domain, '/');
            $cleanDomain = preg_replace('#^https?://#', '', $cleanDomain);
            if ($subDir !== '') {
                $subDir = trim($subDir, '/');
                $domainHosts = $cleanDomain . '/' . $subDir;
            } else {
                $domainHosts = $cleanDomain;
            }

            // Clean bot username
            $cleanBotUsername = ltrim($botUsername, '@');

            // Generate config.php
            $configContent = "<?php\n";
            $configContent .= "// Douman Panel Pro Configuration\n";
            $configContent .= "\$request_exec_timeout = null;\n";
            $configContent .= "\$dbhost = " . var_export($dbHost, true) . ";\n";
            $configContent .= "\$dbname = " . var_export($dbName, true) . ";\n";
            $configContent .= "\$usernamedb = " . var_export($dbUser, true) . ";\n";
            $configContent .= "\$passworddb = " . var_export($dbPass, true) . ";\n";
            $configContent .= "\$options = [\n";
            $configContent .= "    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
            $configContent .= "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $configContent .= "    PDO::ATTR_EMULATE_PREPARES => false,\n";
            $configContent .= "];\n";
            $configContent .= "\$dsn = \"mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4\";\n";
            $configContent .= "try {\n";
            $configContent .= "    \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);\n";
            $configContent .= "    \$pdo->exec(\"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\");\n";
            $configContent .= "} catch (\\PDOException \$e) {\n";
            $configContent .= "    error_log(\"Database connection failed: \" . \$e->getMessage());\n";
            $configContent .= "    die(\"error: database connection failed\");\n";
            $configContent .= "}\n";
            $configContent .= "\$APIKEY = " . var_export($botToken, true) . ";\n";
            $configContent .= "\$adminnumber = " . var_export($adminId, true) . ";\n";
            $configContent .= "\$domainhosts = " . var_export($domainHosts, true) . ";\n";
            $configContent .= "\$usernamebot = " . var_export($cleanBotUsername, true) . ";\n";
            $configContent .= "\$bypass_ip_check = true; // Cloudflare / WordPress proxy compatibility\n";
            $configContent .= "define('BYPASS_IP_CHECK', true);\n";
            $configContent .= "\$cron_secret_key = " . var_export(bin2hex(random_bytes(16)), true) . ";\n";
            $configContent .= "define('CRON_SECRET_KEY', \$cron_secret_key);\n";
            $configContent .= "?>\n";

            file_put_contents($configFile, $configContent);

            // Execute table.php to bootstrap DB schema and set Telegram webhook
            require_once __DIR__ . '/table.php';

            $successMessage = 'نصب و راه‌اندازی ربات دومان پنل پرو با موفقیت کامل انجام شد!';
            $isInstalled = true;
        } catch (PDOException $e) {
            $errors[] = 'خطا در اتصال به دیتابیس MySQL: ' . $e->getMessage();
        } catch (Throwable $t) {
            $errors[] = 'خطا در عملیات راه‌اندازی اولیه: ' . $t->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب‌کننده تحت وب دوپینل پرو | Douman Panel Pro (cPanel & Host)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(22, 30, 49, 0.75);
            --card-border: rgba(255, 255, 255, 0.1);
            --primary: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.35);
            --secondary: #818cf8;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(11, 15, 25, 0.85);
            --input-border: rgba(255, 255, 255, 0.15);
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(56, 189, 248, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(129, 140, 248, 0.12) 0%, transparent 40%),
                linear-gradient(to bottom, #07090e, #0b0f19);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .container {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 30px rgba(56, 189, 248, 0.1);
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38bdf8, #818cf8, #c084fc);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 14px;
        }
        h1 { font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 8px; letter-spacing: -0.5px; }
        .subtitle { font-size: 14px; color: var(--text-muted); line-height: 1.6; }
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; }
        .alert-warning { background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fde68a; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #6ee7b7; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 8px; }
        .req { color: var(--danger); margin-right: 4px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.25s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 24px 0 14px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -5px rgba(2, 132, 199, 0.5);
            margin-top: 26px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #0369a1, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(2, 132, 199, 0.6);
        }
        .code-box {
            background: #080c14;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 14px;
            font-family: monospace;
            font-size: 13px;
            color: #38bdf8;
            direction: ltr;
            text-align: left;
            margin: 8px 0;
            word-break: break-all;
        }
        .action-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-primary:hover { background: #7dd3fc; }
        .btn-danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.3); }
        @media (max-width: 600px) {
            .grid-2 { grid-template-columns: 1fr; }
            .container { padding: 24px; border-radius: 18px; }
            .action-links { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="logo-badge">⚡ DOUMAN PANEL PRO</div>
        <h1>نصب‌کننده تحت وب دوپینل پرو</h1>
        <p class="subtitle">ویژه نصب سریع روی هاست‌های اشتراکی cPanel، دایرکت‌ادمین و پوشه ساب‌دامین وردپرس</p>
    </div>

    <?php if (!empty($missingExts)): ?>
        <div class="alert alert-warning">
            <div>
                <strong>⚠️ اکستنشن‌های موردنیاز:</strong>
                بعضی اکستنشن‌های PHP فعال نیستند: <strong><?= implode(', ', $missingExts) ?></strong>.<br>
                می‌توانید در سی‌پنل از بخش <strong>Select PHP Version</strong> این اکستنشن‌ها را فعال نمایید.
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div>
                <?php foreach ($errors as $err): ?>
                    <div>❌ <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <div>
                <strong style="font-size: 16px;">🎉 <?= $successMessage ?></strong>
                <p style="margin-top: 8px; font-size: 13px; opacity: 0.9;">
                    تمام جداول دیتابیس به صورت هوشمند ایجاد شدند، تنظیمات در فایل <code>config.php</code> ذخیره گردید و وب‌هوک تلگرام با موفقیت ست شد.
                </p>
            </div>
        </div>

        <div style="background: rgba(11, 15, 25, 0.9); border-radius: 14px; padding: 18px; margin-bottom: 20px; border: 1px solid var(--card-border);">
            <h3 style="color: #38bdf8; font-size: 15px; margin-bottom: 8px;">⏰ تنظیم کران جاب (Cron Job) در cPanel:</h3>
            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                در سی‌پنل به بخش <strong>Cron Jobs</strong> بروید، زمان‌بندی را روی هر دقیقه (<code>* * * * *</code>) بگذارید و دستور زیر را وارد کنید:
            </p>
            <div class="code-box">/usr/local/bin/php <?= __DIR__ ?>/cronbot/run.php >/dev/null 2>&1</div>
            <p style="font-size: 12px; color: var(--text-muted); margin-top: 10px;">یا دستور فراخوانی اینترنتی وب (Web Cron):</p>
            <div class="code-box">curl -s "https://<?= htmlspecialchars($domainHosts ?? $_SERVER['HTTP_HOST']) ?>/cronbot/run.php?key=<?= defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : '' ?>" >/dev/null 2>&1</div>
        </div>

        <div class="action-links">
            <a href="https://t.me/<?= htmlspecialchars($cleanBotUsername ?? '') ?>" target="_blank" class="action-btn btn-primary">
                🚀 ورود و تست ربات تلگرام
            </a>
            <a href="landing.php" class="action-btn btn-primary" style="background: #818cf8; color: #fff;">
                🌐 مشاهده وب‌سایت و لندینگ
            </a>
        </div>

        <div style="margin-top: 24px; text-align: center;">
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;">
                جهت امنیت هاست، پس از پایان نصب این فایل را حذف کنید:
            </p>
            <a href="?delete_installer=1" class="action-btn btn-danger" onclick="return confirm('آیا فایل quick_install.php حذف شود؟')">
                🗑️ حذف امن فایل quick_install.php
            </a>
        </div>

    <?php else: ?>
        <form method="POST" action="">
            <div class="section-title">📦 مشخصات دیتابیس MySQL (سی‌پنل)</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>آدرس سرور دیتابیس (معمولاً localhost): <span class="req">*</span></label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>نام دیتابیس (Database Name): <span class="req">*</span></label>
                    <input type="text" name="db_name" placeholder="مثلاً: user_doumandb" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>نام کاربری دیتابیس (DB User): <span class="req">*</span></label>
                    <input type="text" name="db_user" placeholder="مثلاً: user_botuser" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور دیتابیس (DB Password): <span class="req">*</span></label>
                    <input type="password" name="db_pass" placeholder="رمز عبور ساخته شده در cPanel" required>
                </div>
            </div>

            <div class="section-title">🤖 مشخصات ربات تلگرام</div>
            <div class="form-group">
                <label>توکن ربات تلگرام (از BotFather@): <span class="req">*</span></label>
                <input type="text" name="bot_token" placeholder="123456789:ABCdefGhIJKlmNoPQRstuVWXyz" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>آیدی عددی ادمین تلگرام (از userinfobot@): <span class="req">*</span></label>
                    <input type="text" name="admin_id" placeholder="مثلاً: 684291823" required>
                </div>
                <div class="form-group">
                    <label>یوزرنیم ربات تلگرام (اختیاری بدون @):</label>
                    <input type="text" name="bot_username" placeholder="DoumanPanelBot">
                </div>
            </div>

            <div class="section-title">🌐 دامنه و آدرس وب‌سایت</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>دامنه وب‌سایت یا هاست: <span class="req">*</span></label>
                    <input type="text" name="domain" value="<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>پوشه فرعی ساب‌فولدر (در صورت نصب در پوشه):</label>
                    <input type="text" name="subdir" placeholder="مثلاً: bot (اگر در روت هاست است خالی بگذارید)">
                </div>
            </div>

            <button type="submit" name="install_submit" class="btn-submit">
                🚀 نصب و راه‌اندازی فوری دوپینل پرو
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
