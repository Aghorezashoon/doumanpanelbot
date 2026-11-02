<?php

ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels/Api/Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../jdf.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$ManagePanel = new ManagePanel();
$textbotlang = languagechange();

function hooshpay_webhook_respond(int $code, string $msg): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = [];
if ($raw !== false && $raw !== '') {
    $data = json_decode($raw, true) ?: [];
}
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

if (empty($data)) {
    hooshpay_webhook_respond(400, 'empty body');
}

// Extract order identifier
$order_id = (string) (
    $data['order_id']
    ?? $data['id_order']
    ?? $data['order']
    ?? $data['slug']
    ?? $data['invoice_id']
    ?? $data['track_id']
    ?? ''
);

if ($order_id === '' && isset($data['data']) && is_array($data['data'])) {
    $order_id = (string) (
        $data['data']['order_id']
        ?? $data['data']['id_order']
        ?? $data['data']['order']
        ?? ''
    );
}

if ($order_id === '') {
    hooshpay_webhook_respond(400, 'missing order_id');
}

// Find order
$payment = select("Payment_report", "*", "id_order", $order_id, "select");
if (!$payment) {
    $payment = select("Payment_report", "*", "dec_not_confirmed", $order_id, "select");
}
if (!$payment) {
    error_log("hooshpay webhook: order not found: {$order_id}");
    hooshpay_webhook_respond(404, 'order not found');
}

if ($payment['payment_Status'] === 'paid') {
    hooshpay_webhook_respond(200, 'already paid');
}

// Check amount if provided
$amount = (int) ($data['amount'] ?? $data['data']['amount'] ?? 0);
$billed = (int) $payment['price'];
if ($amount > 0 && $amount < $billed) {
    error_log("hooshpay webhook: amount mismatch for {$order_id}: billed {$billed}, got {$amount}");
    hooshpay_webhook_respond(400, 'amount mismatch');
}

// Claim payment atomically
if (!claimPaymentPaid($order_id)) {
    hooshpay_webhook_respond(200, 'already claimed');
}

try {
    DirectPayment($order_id, __DIR__ . "/../images.jpg");
} catch (Throwable $e) {
    error_log("hooshpay webhook: DirectPayment failed for {$order_id}: " . $e->getMessage());
    hooshpay_webhook_respond(500, 'delivery failed');
}

// Record gateway confirmation data
$statement = $pdo->prepare(
    "UPDATE Payment_report SET dec_not_confirmed = :answer WHERE id_order = :id_order"
);
$statement->bindValue(':answer', json_encode($data, JSON_UNESCAPED_UNICODE));
$statement->bindValue(':id_order', $order_id);
$statement->execute();

// Handle cashback
$cashback = intval(getPaySettingValue('chashbackiranpay4', '0'));
if ($cashback > 0) {
    $buyer = select("user", "*", "id", $payment['id_user'], "select");
    if ($buyer) {
        $reward = intval($billed * $cashback / 100);
        if ($reward > 0) {
            update("user", "Balance", intval($buyer['Balance']) + $reward, "id", $payment['id_user']);
            $text_gift = sprintf($textbotlang['paymentGateway']['giftReport'], $reward);
            sendmessage($payment['id_user'], $text_gift, null, 'HTML');
        }
    }
}

// Channel report
$setting = select("setting", "*");
$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'] ?? null;
if (!empty($setting['Channel_Report']) && $paymentreports) {
    $buyer = select("user", "*", "id", $payment['id_user'], "select");
    $text_report = "💳 <b>گزارش پرداخت هوش‌پی (HooshPay)</b>\n\n"
        . "👤 کاربر: @" . ($buyer['username'] ?? '-') . " (<code>{$payment['id_user']}</code>)\n"
        . "💰 مبلغ: " . number_format($billed) . " تومان\n"
        . "🔖 شناسه سفارش: <code>{$order_id}</code>";
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => $text_report,
        'parse_mode' => "HTML"
    ]);
}

hooshpay_webhook_respond(200, 'ok');
