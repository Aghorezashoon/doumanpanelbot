<?php

/**
 * DOUMAN RESELLER BOT — INTERACTIVE SETUP WIZARD
 * CLI & Web-based installation and configuration wizard for reseller bots.
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

function verifyProvider($provider, $key, $secret, $baseUrl)
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
            'User-Agent: DoumanSetupWizard/2.0'
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

if ($isCli) {
    echo "=========================================================\n";
    echo "       DOUMAN RESELLER BOT — SETUP & CONFIG WIZARD       \n";
    echo "=========================================================\n\n";

    // 1. Telegram Bot Token
    $botToken = '';
    while (empty($botToken)) {
        $botToken = askCli("1. لطفا توکن ربات تلگرام خود را وارد کنید (از @BotFather)");
        if (empty($botToken)) {
            echo "توکن الزامی است!\n";
        }
    }

    // Verify Bot Token
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

    // 2. Admin ID
    $adminId = askCli("2. آیدی عددی ادمین تلگرام را وارد کنید");
    echo "ادمین اصلی: {$adminId}\n\n";

    // 3. Central Panel URL
    $centralUrl = askCli("3. آدرس سرور مرکزی DOUMAN RESELLER PANEL", "https://tr.doumanshop.cv/api/v1");

    // 4. Sanayi Credentials
    echo "\n--- تنظیم ارائه‌دهنده سنایی (Sanayi) ---\n";
    $sanayiKey = askCli("کلید API سنایی (Sanayi API Key) - در صورت عدم نیاز خالی بگذارید");
    $sanayiSecret = '';
    $sanayiVerified = null;
    if (!empty($sanayiKey)) {
        $sanayiSecret = askCli("رمز API سنایی (Sanayi API Secret)");
        echo "در حال بررسی اتصال به ارائه‌دهنده سنایی...\n";
        $vSanayi = verifyProvider('sanayi', $sanayiKey, $sanayiSecret, $centralUrl);
        if ($vSanayi['ok']) {
            $sanayiVerified = $vSanayi['data'];
            echo " اتصال به سنایی با موفقیت تایید شد!\n";
            echo "  - نام نماینده: " . ($sanayiVerified['reseller_name'] ?? 'نامشخص') . "\n";
            echo "  - سهمیه نامحدود باقی‌مانده: " . ($sanayiVerified['quotas']['unlimited_remaining'] ?? 0) . "\n";
            echo "  - اعتبار حجمی باقی‌مانده: " . ($sanayiVerified['quotas']['volume_gb_remaining'] ?? 0) . " GB\n";
        } else {
            echo " اعتبارسنجی سنایی ناموفق: " . $vSanayi['msg'] . "\n";
            $cont = askCli("آیا مایلید با وجود خطا ادامه دهید؟ (y/n)", "n");
            if (strtolower($cont) !== 'y') {
                exit(1);
            }
        }
    } else {
        echo "ارائه‌دهنده سنایی برای این نماینده تنظیم نشد.\n";
    }

    // 5. SolidLayer Credentials
    echo "\n--- تنظیم ارائه‌دهنده سالیدلیر (SolidLayer) ---\n";
    $solidKey = askCli("کلید API سالیدلیر (SolidLayer API Key) - در صورت عدم نیاز خالی بگذارید");
    $solidSecret = '';
    $solidVerified = null;
    if (!empty($solidKey)) {
        $solidSecret = askCli("رمز API سالیدلیر (SolidLayer API Secret)");
        echo "در حال بررسی اتصال به ارائه‌دهنده سالیدلیر...\n";
        $vSolid = verifyProvider('solidlayer', $solidKey, $solidSecret, $centralUrl);
        if ($vSolid['ok']) {
            $solidVerified = $vSolid['data'];
            echo " اتصال به سالیدلیر با موفقیت تایید شد!\n";
            echo "  - نام نماینده: " . ($solidVerified['reseller_name'] ?? 'نامشخص') . "\n";
            echo "  - سهمیه نامحدود باقی‌مانده: " . ($solidVerified['quotas']['unlimited_remaining'] ?? 0) . "\n";
            echo "  - اعتبار حجمی باقی‌مانده: " . ($solidVerified['quotas']['volume_gb_remaining'] ?? 0) . " GB\n";
        } else {
            echo " اعتبارسنجی سالیدلیر ناموفق: " . $vSolid['msg'] . "\n";
            $cont = askCli("آیا مایلید با وجود خطا ادامه دهید؟ (y/n)", "n");
            if (strtolower($cont) !== 'y') {
                exit(1);
            }
        }
    } else {
        echo "ارائه‌دهنده سالیدلیر برای این نماینده تنظیم نشد.\n";
    }

    // 6. Domain Name for Webhook
    $domain = askCli("\n4. دامنه یا آدرس هاست این ربات (مثلا bot.example.com)");
    $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));

    // 7. Database settings
    echo "\n--- تنظیمات پایگاه داده (MySQL) ---\n";
    $dbHost = askCli("DB Host", "localhost");
    $dbName = askCli("DB Name");
    $dbUser = askCli("DB User");
    $dbPass = askCli("DB Password");

    echo "\nدر حال اعتبارسنجی اتصال دیتابیس...\n";
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo " اتصال پایگاه داده موفقیت‌آمیز بود.\n";
    } catch (Exception $e) {
        echo " خطا در اتصال به پایگاه داده: " . $e->getMessage() . "\n";
        exit(1);
    }

    // 8. Write config.php
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
} catch (\\PDOException \$e) {
    error_log("Database connection failed: " . \$e->getMessage());
    die("error: database connection failed");
}
\$APIKEY = '{$botToken}';
\$adminnumber = '{$adminId}';
\$domainhosts = '{$domain}';
\$usernamebot = '{$botUsername}';
PHP;

    file_put_contents(__DIR__ . '/config.php', $configContent);
    echo " فایل config.php با موفقیت ذخیره شد.\n";

    // 9. Run DB Migrations
    echo "در حال ایجاد و به‌روزرسانی جداول دیتابیس...\n";
    require_once __DIR__ . '/db/bootstrap.php';

    // 10. Store Reseller Providers in DB
    if (!empty($sanayiKey) && !empty($sanayiSecret)) {
        $stmt = $pdo->prepare("REPLACE INTO reseller_provider (provider_code, provider_name, api_key, api_secret, api_base_url, status, last_sync) VALUES (?, ?, ?, ?, ?, 'active', ?)");
        $stmt->execute(['sanayi', 'Sanayi', $sanayiKey, $sanayiSecret, $centralUrl, time()]);
        echo " ارائه‌دهنده سنایی در دیتابیس محلی ذخیره شد.\n";
    }

    if (!empty($solidKey) && !empty($solidSecret)) {
        $stmt = $pdo->prepare("REPLACE INTO reseller_provider (provider_code, provider_name, api_key, api_secret, api_base_url, status, last_sync) VALUES (?, ?, ?, ?, ?, 'active', ?)");
        $stmt->execute(['solidlayer', 'SolidLayer', $solidKey, $solidSecret, $centralUrl, time()]);
        echo " ارائه‌دهنده سالیدلیر در دیتابیس محلی ذخیره شد.\n";
    }

    // Set Webhook
    if (!empty($domain)) {
        echo "در حال تنظیم وبهوک تلگرام...\n";
        $secret = bin2hex(random_bytes(16));
        $res = telegram('setWebhook', [
            'url' => "https://{$domain}/index.php?secret={$secret}"
        ]);
        echo "وبهوک تنظیم شد: " . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n=========================================================\n";
    echo " نصب و راه‌اندازی ربات نماینده با موفقیت به پایان رسید! \n";
    echo " آدرس ربات: https://t.me/{$botUsername}\n";
    echo "=========================================================\n";
    exit(0);
}

// Web UI mode
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>نصب و راه‌اندازی ربات نماینده دومان</title>
  <style>
    body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 30px; display: flex; justify-content: center; }
    .card { background: #1e293b; border-radius: 12px; padding: 30px; max-width: 650px; width: 100%; border: 1px solid #334155; }
    h2 { margin-top: 0; color: #38bdf8; border-bottom: 1px solid #334155; padding-bottom: 12px; }
    .form-group { margin-bottom: 18px; }
    label { display: block; font-size: 13px; margin-bottom: 6px; color: #94a3b8; }
    input, select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #475569; background: #0f172a; color: #fff; box-sizing: border-box; }
    .btn { background: #38bdf8; color: #0f172a; font-weight: bold; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; width: 100%; }
    .btn:hover { background: #0ea5e9; }
    .box { background: rgba(255,255,255,0.03); border: 1px solid #334155; border-radius: 8px; padding: 15px; margin-bottom: 18px; }
    .box h4 { margin: 0 0 10px 0; color: #a7f3d0; }
  </style>
</head>
<body>
<div class="card">
  <h2>نصب و راه‌اندازی ربات نماینده DOUMAN</h2>
  <p style="font-size:13px;color:#94a3b8">لطفاً مشخصات زیر را تکمیل کنید. نیازی به ورود به پنل سرور مرکزی ندارید، تنها اعتبارنامه‌های ارائه‌دهنده اختصاصی شما کفایت می‌کند.</p>

  <form method="POST" action="setup_wizard.php">
    <div class="form-group">
      <label>توکن ربات تلگرام (از BotFather@)</label>
      <input type="text" name="bot_token" placeholder="123456789:ABCdef..." required>
    </div>
    <div class="form-group">
      <label>آیدی عددی ادمین اصلی تلگرام</label>
      <input type="text" name="admin_id" placeholder="123456789" required>
    </div>
    <div class="form-group">
      <label>دامنه یا ساب‌دامنه هاست این ربات</label>
      <input type="text" name="domain" placeholder="bot.example.com" required>
    </div>

    <div class="box">
      <h4>🌐 ارائه‌دهنده سنایی (Sanayi)</h4>
      <div class="form-group">
        <label>Sanayi API Key</label>
        <input type="text" name="sanayi_key" placeholder="کلید اختصاصی سنایی (در صورت عدم تخصیص خالی بگذارید)">
      </div>
      <div class="form-group">
        <label>Sanayi API Secret</label>
        <input type="password" name="sanayi_secret" placeholder="رمز اختصاصی سنایی">
      </div>
    </div>

    <div class="box">
      <h4>⚡ ارائه‌دهنده سالیدلیر (SolidLayer)</h4>
      <div class="form-group">
        <label>SolidLayer API Key</label>
        <input type="text" name="solid_key" placeholder="کلید اختصاصی سالیدلیر (در صورت عدم تخصیص خالی بگذارید)">
      </div>
      <div class="form-group">
        <label>SolidLayer API Secret</label>
        <input type="password" name="solid_secret" placeholder="رمز اختصاصی سالیدلیر">
      </div>
    </div>

    <div class="box">
      <h4>🗄️ مشخصات پایگاه داده MySQL</h4>
      <div class="form-group"><label>Database Host</label><input type="text" name="db_host" value="localhost" required></div>
      <div class="form-group"><label>Database Name</label><input type="text" name="db_name" required></div>
      <div class="form-group"><label>Database Username</label><input type="text" name="db_user" required></div>
      <div class="form-group"><label>Database Password</label><input type="password" name="db_pass"></div>
    </div>

    <button type="submit" class="btn">اعتبارسنجی، اتصال و راه‌اندازی ربات</button>
  </form>
</div>
</body>
</html>
