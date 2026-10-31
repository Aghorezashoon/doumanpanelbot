<?php
/**
 * Douman Panel Bot - Quick Shared Hosting & WordPress Installer
 * نصب آسان ربات روی هاست اشتراکی، سی‌پنل، دایرکت‌ادمین و وردپرس
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
        $errors[] = 'لطفاً تمام فیلدهای الزامی (نام دیتابیس، نام کاربری دیتابیس، توکن ربات و آیدی عددی ادمین) را پر کنید.';
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
            $configContent .= "// Douman Panel Bot Configuration\n";
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

            // Execute table.php to bootstrap DB and set webhook
            require_once __DIR__ . '/table.php';

            $successMessage = 'نصب و پیکربندی ربات دمان با موفقیت انجام شد!';
            $isInstalled = true;
        } catch (PDOException $e) {
            $errors[] = 'خطا در اتصال به دیتابیس MySQL: ' . $e->getMessage();
        } catch (Throwable $t) {
            $errors[] = 'خطا در عملیات راه‌اندازی: ' . $t->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب‌کننده سریع دمان پنل بات (DoumanPanelBot)</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Vazirmatn", Tahoma, sans-serif; }
        body { background: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: #1e293b; border-radius: 16px; padding: 32px; width: 100%; max-width: 640px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5); border: 1px solid #334155; }
        h1 { color: #38bdf8; font-size: 24px; margin-top: 0; margin-bottom: 8px; text-align: center; }
        p.subtitle { color: #94a3b8; font-size: 14px; text-align: center; margin-bottom: 24px; }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
        .alert-error { background: #7f1d1d; color: #fecaca; border: 1px solid #991b1b; }
        .alert-success { background: #064e3b; color: #a7f3d0; border: 1px solid #065f46; }
        .alert-warning { background: #78350f; color: #fde68a; border: 1px solid #92400e; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #475569; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: border-color 0.2s; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #38bdf8; }
        .btn { display: block; width: 100%; padding: 12px; background: #0284c7; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 24px; }
        .btn:hover { background: #0369a1; }
        .code-block { background: #0f172a; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 13px; color: #38bdf8; overflow-x: auto; direction: ltr; margin: 8px 0; border: 1px solid #334155; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-ok { background: #059669; color: #ecfdf5; }
        .badge-err { background: #dc2626; color: #fef2f2; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="card">
    <h1>🤖 Douman Panel Bot</h1>
    <p class="subtitle">نصب‌کننده خودکار و بهینه‌شده برای هاست اشتراکی سی‌پنل، دایرکت‌ادمین و وردپرس</p>

    <?php if (!empty($missingExts)): ?>
        <div class="alert alert-warning">
            ⚠️ برخی اکستنشن‌های PHP فعال نیستند: <strong><?= implode(', ', $missingExts) ?></strong>
            <br>می‌توانید از بخش Select PHP Version در هاست این اکستنشن‌ها را فعال نمایید.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div>• <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <strong>✅ <?= $successMessage ?></strong>
            <p style="margin: 8px 0 0 0; font-size: 13px;">
                جداول دیتابیس ساخته شدند و وب‌هوک تلگرام با موفقیت ثبت شد.
            </p>
        </div>

        <div style="background: #0f172a; border-radius: 8px; padding: 16px; margin-bottom: 20px; border: 1px solid #334155;">
            <h3 style="color: #38bdf8; margin-top: 0; font-size: 15px;">⏰ تنظیم کران‌جاب در هاست (Cron Job):</h3>
            <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 8px 0;">
                در سی‌پنل به بخش <strong>Cron Jobs</strong> بروید و زمان را روی هر ۱ دقیقه (<code>* * * * *</code>) قرار دهید و دستور زیر را وارد کنید:
            </p>
            <div class="code-block">/usr/local/bin/php <?= __DIR__ ?>/cronbot/run.php >/dev/null 2>&1</div>
            <p style="font-size: 12px; color: #94a3b8; margin: 8px 0 4px 0;">یا دستور curl از طریق وب:</p>
            <div class="code-block">curl -s https://<?= htmlspecialchars($domainHosts ?? $_SERVER['HTTP_HOST']) ?>/cronbot/run.php?key=<?= defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : '' ?> >/dev/null 2>&1</div>
        </div>

        <div style="text-align: center;">
            <p style="font-size: 14px; color: #cbd5e1;">برای امنیت سرور، فایل <code>quick_install.php</code> را حذف کنید.</p>
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #38bdf8; color: #0f172a; font-weight: 600; text-decoration: none; border-radius: 8px; margin-top: 10px;">ورود به ربات</a>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <div class="grid-2">
                <div class="form-group">
                    <label>آدرس هاست دیتابیس (معمولاً localhost):</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>نام دیتابیس (Database Name):</label>
                    <input type="text" name="db_name" placeholder="user_douman" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>نام کاربری دیتابیس (DB User):</label>
                    <input type="text" name="db_user" placeholder="user_dbuser" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور دیتابیس (DB Password):</label>
                    <input type="password" name="db_pass" placeholder="••••••••••••" required>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #334155; margin: 20px 0;">

            <div class="form-group">
                <label>توکن ربات تلگرام (از BotFather@):</label>
                <input type="text" name="bot_token" placeholder="123456789:ABCdefGhIJKlmNoPQRstuVWXyz" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>آیدی عددی ادمین تلگرام (از userinfobot@):</label>
                    <input type="text" name="admin_id" placeholder="12345678" required>
                </div>
                <div class="form-group">
                    <label>یوزرنیم ربات (بدون @):</label>
                    <input type="text" name="bot_username" placeholder="doumanpanelbot">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>دامنه وب‌سایت:</label>
                    <input type="text" name="domain" value="<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>پوشه فرعی (در صورت نصب در پوشه وردپرس):</label>
                    <input type="text" name="subdir" placeholder="مثلاً: bot (خالی اگر در ریشه است)">
                </div>
            </div>

            <button type="submit" name="install_submit" class="btn">🚀 نصب و راه‌اندازی فوری</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
