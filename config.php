<?php
/**
 * Telegram VPN Bot & Mini App - Configuration File
 * لطفاً اطلاعات دیتابیس، توکن ربات و دامنه خود را در این بخش وارد نمایید.
 */

$request_exec_timeout = null;

// Database Configuration / تنظیمات پایگاه داده
$dbhost = 'localhost';
$dbname = 'YOUR_DB_NAME';         // نام دیتابیس شما
$usernamedb = 'YOUR_DB_USER';     // نام کاربری دیتابیس
$passworddb = 'YOUR_DB_PASS';     // رمز عبور دیتابیس

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $usernamedb, $passworddb, $options);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (\PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("خطا در اتصال به پایگاه داده! لطفاً فایل config.php را بررسی فرمایید.");
}

// Bot & Domain Configuration / تنظیمات ربات و دامنه
$APIKEY = 'YOUR_BOT_TOKEN_HERE';          // توکن ربات تلگرام دریافتی از BotFather
$adminnumber = 'YOUR_TELEGRAM_NUMERIC_ID'; // شناسه عددی ادمین اصلی در تلگرام
$domainhosts = 'your-domain.com';         // دامنه متصل به هاست (بدون https)
$usernamebot = 'your_bot_username';       // آیدی ربات بدون @

// Security & Bypass Settings / تنظیمات امنیتی
$bypass_ip_check = true;
if (!defined('BYPASS_IP_CHECK')) {
    define('BYPASS_IP_CHECK', true);
}

// Cron Secret Key / کلید امنیتی اجرای کرون‌جاب
$cron_secret_key = 'CHANGE_THIS_SECRET_KEY';
if (!defined('CRON_SECRET_KEY')) {
    define('CRON_SECRET_KEY', $cron_secret_key);
}
?>
