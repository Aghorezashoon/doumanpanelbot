<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

try {
    require_once __DIR__ . '/db/bootstrap.php';

    foreach (['Marzban.php', 'marzneshin.php', 'hiddify.php', 'ibsng.php', 'mikrotik.php', 'mirza_agent.php', 'nexora.php', 'Rebecca.php', 'WGDashboard.php', 'wg_mate.php', 'x-ui_single.php', 'alireza_single.php', 's_ui.php'] as $oldPanelFile) {
        if (is_file(__DIR__ . '/' . $oldPanelFile)) {
            @unlink(__DIR__ . '/' . $oldPanelFile);
        }
    }

    global $domainhosts;

    $setting = select("setting", "*");
    if (intval($setting['Channel_Report'] ?? 0) != 0) {
        syncReportTopics($setting['Channel_Report'], languagechange());
    }

    $webhookSecret = ensureWebhookSecret();

    $webhookResult = telegram('setWebhook', [
        'url' => "https://$domainhosts/index.php?secret={$webhookSecret['secret']}",
    ]);

    $webhookOk = !empty($webhookResult['ok']);
    $webhookDesc = $webhookResult['description'] ?? 'Webhook set';

    echo "<!doctype html><html lang='fa' dir='rtl'><head><meta charset='utf-8'><title>به‌روزرسانی موفق</title>"
        . "<meta name='viewport' content='width=device-width, initial-scale=1'>"
        . "<style>"
        . "body { font-family: Tahoma, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }"
        . ".card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 36px 28px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }"
        . ".icon { width: 64px; height: 64px; background: rgba(34, 197, 94, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #22c55e; font-size: 32px; font-weight: bold; }"
        . "h1 { font-size: 1.35rem; margin: 0 0 12px; color: #f1f5f9; }"
        . "p { color: #94a3b8; font-size: 0.95rem; line-height: 1.6; margin: 0 0 16px; }"
        . ".badge { display: inline-block; background: #0f172a; border: 1px solid #334155; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; color: #38bdf8; font-family: monospace; }"
        . "</style></head><body>"
        . "<div class='card'>"
        . "<div class='icon'>✓</div>"
        . "<h1>عملیات به‌روزرسانی موفقیت‌آمیز بود</h1>"
        . "<p>جداول دیتابیس با موفقیت تطبیق یافتند و ساختار جداول بدون آسیب رسیدن به اطلاعات قبلی به‌روز شد.</p>"
        . "<p><span class='badge'>وضعیت وب‌هوک: " . htmlspecialchars($webhookDesc) . "</span></p>"
        . "<p style='font-size: 0.85rem; color: #64748b; margin-top: 24px;'>اکنون می‌توانید این صفحه را ببندید و از ربات تلگرام خود استفاده نمایید.</p>"
        . "</div></body></html>";

} catch (Throwable $e) {
    echo "<!doctype html><html lang='fa' dir='rtl'><head><meta charset='utf-8'><title>خطا در به‌روزرسانی</title>"
        . "<style>body{font-family:Tahoma,sans-serif;background:#0f172a;color:#f8fafc;padding:40px;text-align:center;}</style></head><body>"
        . "<h2 style='color:#ef4444'>❌ خطا در اجرای عملیات:</h2>"
        . "<pre style='background:#1e293b;padding:20px;border-radius:8px;text-align:left;direction:ltr;color:#fca5a5;max-width:700px;margin:20px auto;overflow:auto;'>"
        . htmlspecialchars($e->getMessage() . "\n\nدر فایل: " . $e->getFile() . ":" . $e->getLine() . "\n\n" . $e->getTraceAsString())
        . "</pre></body></html>";
}
