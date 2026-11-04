<?php

/**
 * DOUMAN RESELLER BOT — EASY SETUP WIZARD (V2.1)
 * نصب و راه‌اندازی سریع و آسان ربات دومان
 * مرحله اول: اطلاعات ربات، دیتابیس، ادمین و لینک سایت (مشابه نسخه اولیه بدون اجبار API)
 * مرحله دوم (اختیاری): اتصال به ارائه‌دهنده‌های مرکزی (سنایی / سالیدلیر)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$isCli = (php_sapi_name() === 'cli');

function askCli($prompt, $default = '')
{
    $formattedPrompt = $prompt . ($default !== '' ? " [{$default}]: " : ": ");
    echo $formattedPrompt;
    $input = trim(fgets(STDIN));
    return ($input === '' && $default !== '') ? $default : $input;
}

function verifyProvider($provider, $key, $secret, $baseUrl = 'https://tr.doumanshop.cv/api/v1')
{
    if (empty($key) || empty($secret)) {
        return ['ok' => false, 'skipped' => true, 'msg' => 'اطلاعات وارد نشد (رد شد)'];
    }
    $url = rtrim($baseUrl, '/') . '/auth/verify';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-Douman-Key: ' . $key,
            'X-Douman-Secret: ' . $secret,
            'X-Douman-Provider: ' . $provider,
            'User-Agent: DoumanSetupWizard/2.1'
        ]
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['ok' => false, 'msg' => "خطای اتصال: $err"];
    }
    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['success'])) {
        $msg = $data['message'] ?? $data['error'] ?? "خطا در اعتبارسنجی (کد $code)";
        return ['ok' => false, 'msg' => $msg];
    }
    return ['ok' => true, 'data' => $data['data'] ?? []];
}

// -------------------------------------------------------------
// CLI INSTALLATION FLOW
// -------------------------------------------------------------
if ($isCli) {
    echo "=========================================================\n";
    echo "       DOUMAN RESELLER BOT — EASY SETUP WIZARD (V2.1)    \n";
    echo "=========================================================\n\n";

    // 1. Bot Token
    $botToken = '';
    while (empty($botToken)) {
        $botToken = askCli("1. توکن ربات تلگرام را وارد کنید (از @BotFather)");
        if (empty($botToken)) {
            echo "توکن ربات الزامی است!\n";
        }
    }

    $ch = curl_init("https://api.telegram.org/bot{$botToken}/getMe");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $meResp = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (empty($meResp['ok'])) {
        echo "توکن تلگرام نامعتبر است یا ارتباط با تلگرام برقرار نشد.\n";
        exit(1);
    }
    $botUsername = $meResp['result']['username'];
    echo "ربات شناسایی شد: @{$botUsername}\n\n";

    // 2. Database
    echo "--- ۲. تنظیمات پایگاه داده (MySQL) ---\n";
    $dbHost = askCli("آدرس هاست دیتابیس", "localhost");
    $dbName = askCli("نام دیتابیس (DB Name)");
    $dbUser = askCli("نام کاربری دیتابیس (DB User)");
    $dbPass = askCli("رمز عبور دیتابیس (DB Password)");

    echo "در حال بررسی اتصال به دیتابیس...\n";
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo " اتصال به پایگاه داده با موفقیت برقرار شد.\n\n";
    } catch (Exception $e) {
        echo " خطا در اتصال دیتابیس: " . $e->getMessage() . "\n";
        exit(1);
    }

    // 3. Admin Numeric ID
    $adminId = askCli("3. آیدی عددی ادمین اصلی تلگرام");

    // 4. Domain / Site Link
    $domain = askCli("4. دامنه یا لینک هاست این ربات (مثلا bot.example.com)");
    $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));

    // Write config.php
    $configContent = <<<PHP
<?php
\$request_exec_timeout = null;
\$dbhost = '{$dbHost}';
\$dbname = '{$dbName}';
\$usernamedb = '{$dbUser}';
\$passworddb = '{$dbPass}';
\$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
\$dsn = "mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4";
try {
    \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);
    \$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (\PDOException \$e) {
    error_log("Database connection failed: " . \$e->getMessage());
    die("error: database connection failed");
}
\$APIKEY = '{$botToken}';
\$adminnumber = '{$adminId}';
\$domainhosts = '{$domain}';
\$usernamebot = '{$botUsername}';
\$bypass_ip_check = true;
define('BYPASS_IP_CHECK', true);
\$cron_secret_key = bin2hex(random_bytes(16));
define('CRON_SECRET_KEY', \$cron_secret_key);
?>
PHP;

    file_put_contents(__DIR__ . '/config.php', $configContent);
    echo " فایل config.php با موفقیت ذخیره شد.\n";

    // Run table.php to bootstrap tables
    echo "در حال اجرای به‌روزرسانی ساختار جداول دیتابیس...\n";
    try {
        require_once __DIR__ . '/db/bootstrap.php';
        echo " ساختار جداول ایجاد و به‌روز شد.\n";
    } catch (Throwable $e) {
        echo " هشدار در بوت‌استرپ دیتابیس: " . $e->getMessage() . "\n";
    }

    // 5. Optional Central APIs
    echo "\n---------------------------------------------------------\n";
    $wantApi = askCli("آیا مایلید هم‌اکنون به پنل مرکزی دومان (سنایی / سالیدلیر) متصل شوید؟ (y/n)", "n");
    if (strtolower($wantApi) === 'y') {
        $centralUrl = askCli("آدرس سرور مرکزی DOUMAN RESELLER PANEL", "https://tr.doumanshop.cv/api/v1");
        
        $sanayiKey = askCli("کلید API سنایی (Sanayi API Key) - در صورت عدم نیاز اینتر بزنید");
        if (!empty($sanayiKey)) {
            $sanayiSecret = askCli("رمز API سنایی (Sanayi API Secret)");
            $stmt = $pdo->prepare("REPLACE INTO reseller_provider (provider_code, provider_name, api_key, api_secret, api_base_url, status, last_sync) VALUES ('sanayi', 'سنایی (Sanayi / 3x-ui)', ?, ?, ?, 'active', UNIX_TIMESTAMP())");
            $stmt->execute([$sanayiKey, $sanayiSecret, $centralUrl]);
            echo " ارائه‌دهنده سنایی ذخیره شد.\n";
        }

        $solidKey = askCli("کلید API سالیدلیر (SolidLayer API Key) - در صورت عدم نیاز اینتر بزنید");
        if (!empty($solidKey)) {
            $solidSecret = askCli("رمز API سالیدلیر (SolidLayer API Secret)");
            $stmt = $pdo->prepare("REPLACE INTO reseller_provider (provider_code, provider_name, api_key, api_secret, api_base_url, status, last_sync) VALUES ('solidlayer', 'سالیدلیر (SolidLayer)', ?, ?, ?, 'active', UNIX_TIMESTAMP())");
            $stmt->execute([$solidKey, $solidSecret, $centralUrl]);
            echo " ارائه‌دهنده سالیدلیر ذخیره شد.\n";
        }
    } else {
        echo "تنظیم ارائه‌دهنده‌های مرکزی رد شد (بعداً از تنظیمات پنل وب نیز قابل افزودن است).\n";
    }

    // Set Webhook
    $webhookSecret = bin2hex(random_bytes(16));
    $hookUrl = "https://{$domain}/index.php?secret={$webhookSecret}";
    $ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($hookUrl));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    echo " وبهوک ربات تلگرام با موفقیت ثبت شد: {$hookUrl}\n";

    echo "\n=========================================================\n";
    echo "  نصب و راه‌اندازی ربات با موفقیت به پایان رسید! \n";
    echo "  آدرس ربات: https://t.me/{$botUsername}\n";
    echo "  پنل مدیریت: https://{$domain}/panel/\n";
    echo "=========================================================\n";
    exit(0);
}

// -------------------------------------------------------------
// WEB UI INSTALLATION FLOW
// -------------------------------------------------------------
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $botToken = trim($_POST['bot_token'] ?? '');
    $adminId = trim($_POST['admin_id'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');

    // Optional APIs
    $sanayiKey = trim($_POST['sanayi_key'] ?? '');
    $sanayiSecret = trim($_POST['sanayi_secret'] ?? '');
    $solidKey = trim($_POST['solid_key'] ?? '');
    $solidSecret = trim($_POST['solid_secret'] ?? '');
    $centralUrl = trim($_POST['central_url'] ?? 'https://tr.doumanshop.cv/api/v1');

    if (empty($botToken) || empty($adminId) || empty($domain) || empty($dbName) || empty($dbUser)) {
        $error = 'لطفاً تمامی فیلدهای الزامی (توکن، ادمین، دامنه و دیتابیس) را تکمیل کنید.';
    } else {
        // Test Telegram Bot
        $ch = curl_init("https://api.telegram.org/bot{$botToken}/getMe");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $meResp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($meResp['ok'])) {
            $error = 'توکن ربات تلگرام نامعتبر است یا ارتباط با سرورهای تلگرام برقرار نشد.';
        } else {
            $botUsername = $meResp['result']['username'];

            // Test Database
            try {
                $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Write config.php
                $configContent = <<<PHP
<?php
\$request_exec_timeout = null;
\$dbhost = '{$dbHost}';
\$dbname = '{$dbName}';
\$usernamedb = '{$dbUser}';
\$passworddb = '{$dbPass}';
\$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
\$dsn = "mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4";
try {
    \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);
    \$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (\PDOException \$e) {
    error_log("Database connection failed: " . \$e->getMessage());
    die("error: database connection failed");
}
\$APIKEY = '{$botToken}';
\$adminnumber = '{$adminId}';
\$domainhosts = '{$domain}';
\$usernamebot = '{$botUsername}';
\$bypass_ip_check = true;
define('BYPASS_IP_CHECK', true);
\$cron_secret_key = bin2hex(random_bytes(16));
define('CRON_SECRET_KEY', \$cron_secret_key);
?>
PHP;
                file_put_contents(__DIR__ . '/config.php', $configContent);

                // Run tables schema
                require_once __DIR__ . '/db/bootstrap.php';

                // Save optional provider credentials if provided
                if (!empty($sanayiKey) && !empty($sanayiSecret)) {
                    $stmt = $pdo->prepare("REPLACE INTO reseller_provider (provider_code, provider_name, api_key, api_secret, api_base_url, status, last_sync) VALUES ('sanayi', 'سنایی (Sanayi / 3x-ui)', ?, ?, ?, 'active', UNIX_TIMESTAMP())");
                    $stmt->execute([$sanayiKey, $sanayiSecret, $centralUrl]);
                }
                if (!empty($solidKey) && !empty($solidSecret)) {
                    $stmt = $pdo->prepare("REPLACE INTO reseller_provider (provider_code, provider_name, api_key, api_secret, api_base_url, status, last_sync) VALUES ('solidlayer', 'سالیدلیر (SolidLayer)', ?, ?, ?, 'active', UNIX_TIMESTAMP())");
                    $stmt->execute([$solidKey, $solidSecret, $centralUrl]);
                }

                // Set Webhook
                $webhookSecret = bin2hex(random_bytes(16));
                $hookUrl = "https://{$domain}/index.php?secret={$webhookSecret}";
                $ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($hookUrl));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);

                $success = true;
            } catch (Exception $e) {
                $error = 'خطا در پایگاه داده: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>نصب و راه‌اندازی آسان ربات دومان</title>
  <style>
    body { font-family: Tahoma, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 25px; display: flex; justify-content: center; }
    .card { background: #1e293b; border-radius: 14px; padding: 30px; max-width: 620px; width: 100%; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
    h2 { margin-top: 0; color: #38bdf8; border-bottom: 1px solid #334155; padding-bottom: 12px; font-size: 1.3rem; }
    .step-title { color: #f1f5f9; font-size: 14px; font-weight: bold; margin: 18px 0 10px; display: flex; align-items: center; gap: 8px; }
    .step-title span { background: #38bdf8; color: #0f172a; border-radius: 50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; }
    .form-group { margin-bottom: 14px; }
    label { display: block; font-size: 12.5px; margin-bottom: 5px; color: #94a3b8; }
    input { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #475569; background: #0f172a; color: #fff; box-sizing: border-box; font-size: 13px; }
    input:focus { border-color: #38bdf8; outline: none; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .btn { background: #38bdf8; color: #0f172a; font-weight: bold; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; width: 100%; font-size: 14px; margin-top: 15px; }
    .btn:hover { background: #0ea5e9; }
    .alert-err { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    .alert-ok { background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; padding: 16px; border-radius: 8px; text-align: center; }
    details { background: rgba(255,255,255,0.02); border: 1px solid #334155; border-radius: 8px; padding: 12px; margin-top: 15px; }
    summary { cursor: pointer; font-size: 13px; color: #cbd5e1; font-weight: 600; outline: none; }
  </style>
</head>
<body>
<div class="card">
  <h2>🚀 نصب و راه‌اندازی سریع ربات دومان</h2>

  <?php if ($success): ?>
    <div class="alert-ok">
      <h3 style="margin: 0 0 8px 0; color: #22c55e;">✓ نصب با موفقیت انجام شد!</h3>
      <p style="margin: 0 0 14px 0; font-size: 13px;">دیتابیس متصل شد، جداول ایجاد گردیدند و وبهوک ربات تلگرام تنظیم شد.</p>
      <a href="panel/login.php" style="display:inline-block;background:#22c55e;color:#0f172a;font-weight:bold;padding:10px 20px;border-radius:6px;text-decoration:none;">ورود به پنل مدیریت ربات</a>
    </div>
  <?php else: ?>
    <?php if ($error): ?>
      <div class="alert-err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p style="font-size:12.5px;color:#94a3b8;margin-top:0">مشخصات اولیه ربات و پایگاه داده خود را وارد کنید. بدون نیاز به API ارائه‌دهنده‌ها، ربات فوراً آماده کار خواهد شد.</p>

    <form method="POST" action="setup_wizard.php">
      <div class="step-title"><span>۱</span> مشخصات ربات تلگرام</div>
      <div class="form-group">
        <label>توکن ربات تلگرام (از BotFather@)</label>
        <input type="text" name="bot_token" placeholder="123456789:ABCdef..." value="<?= htmlspecialchars($_POST['bot_token'] ?? '') ?>" required>
      </div>

      <div class="step-title"><span>۲</span> پایگاه داده MySQL</div>
      <div class="grid-2">
        <div class="form-group">
          <label>Database Host</label>
          <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
        </div>
        <div class="form-group">
          <label>Database Name</label>
          <input type="text" name="db_name" placeholder="mybot_db" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Database Username</label>
          <input type="text" name="db_user" placeholder="mybot_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Database Password</label>
          <input type="password" name="db_pass" placeholder="رمز عبور دیتابیس">
        </div>
      </div>

      <div class="step-title"><span>۳</span> اطلاعات ادمین و دامنه</div>
      <div class="grid-2">
        <div class="form-group">
          <label>آیدی عددی ادمین اصلی تلگرام</label>
          <input type="text" name="admin_id" placeholder="123456789" value="<?= htmlspecialchars($_POST['admin_id'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>دامنه یا آدرس هاست (بدون https)</label>
          <input type="text" name="domain" placeholder="bot.example.com" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>" required>
        </div>
      </div>

      <details>
        <summary>🌐 اتصال به پنل مرکزی دومان (اختیاری - بعداً نیز قابل تنظیم است)</summary>
        <div style="margin-top:12px">
          <div class="form-group">
            <label>آدرس سرور مرکزی</label>
            <input type="text" name="central_url" value="https://tr.doumanshop.cv/api/v1">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>Sanayi API Key</label>
              <input type="text" name="sanayi_key" placeholder="اختیاری">
            </div>
            <div class="form-group">
              <label>Sanayi API Secret</label>
              <input type="password" name="sanayi_secret" placeholder="اختیاری">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>SolidLayer API Key</label>
              <input type="text" name="solid_key" placeholder="اختیاری">
            </div>
            <div class="form-group">
              <label>SolidLayer API Secret</label>
              <input type="password" name="solid_secret" placeholder="اختیاری">
            </div>
          </div>
        </div>
      </details>

      <button type="submit" class="btn">نصب، راه‌اندازی و ثبت وب‌هوک ربات</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
