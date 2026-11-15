<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$textbotlang = languagechange();
class ServiceMonitor
{
    private $Panel;
    private $pdo;
    private $setting;
    private $reportCron;
    private $text_Purchased_services;
    private $status_cron;
    const SECONDS_PER_DAY = 86400;
    private $textBotLang;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->Panel = new ManagePanel();
        $this->reportCron = select("topicid", "idreport", "report", "reportcron", "select")['idreport'];
        $this->setting = select("setting", "*");
        $this->status_cron = json_decode($this->setting['cron_status'], true);
        $this->textBotLang = languagechange(dirname(__DIR__));
        $this->text_Purchased_services = $this->textBotLang['textbot']['purchasedServices'] ?? '';
    }

    public function RunNotifactions()
    {
        $invoices = $this->getActiveInvoices();
        if ($invoices == false)
            return;
        $stmtCron = $this->pdo->prepare("UPDATE invoice SET time_cron = ? WHERE id_invoice = ?");
        foreach ($invoices as $invoice) {
            if ($invoice['time_cron'] != null && (time() - $invoice['time_cron']) < 1600)
                continue;
            $stmtCron->execute([time(), $invoice['id_invoice']]);
            clearSelectCache('invoice');
            $check_send = json_decode($invoice['notifctions'], true);
            $data = $this->processInvoice($invoice);
            if (!is_array($data))
                continue;
            if (!is_array($check_send)) {
                $check_send = ['volume' => false, 'time' => false, 'volume_ended' => false, 'time_ended' => false];
            }
            if ($this->status_cron['volume']) {
                $this->checkVolumeStatus($data['invoice'], $data['user'], $data['userData'], $invoice['username'], $check_send);
            }
            $data['invoice'] = select("invoice", "*", "id_invoice", $invoice['id_invoice']);
            $check_send = json_decode($data['invoice']['notifctions'], true) ?: $check_send;

            if ($this->status_cron['day']) {
                $this->checkTimeStatus($data['invoice'], $data['user'], $data['userData'], $invoice['username'], $check_send);
            }
            if ($this->status_cron['remove'])
                $this->shouldRemoveService($data['invoice'], $data['user'], $data['userData'], $invoice['username']);
            if ($this->status_cron['remove_volume'])
                $this->shouldRemoveServiceـvolume($data['invoice'], $data['user'], $data['userData'], $invoice['username']);
            if ($data['panel']['inboundstatus'] == "oninbounddisable" && $data['panel']['type'] == "marzban" && $data['panel']['version_panel'] != "1")
                $this->active_inbound_expire($data['invoice'], $data['userData'], $data['panel']);
        }
    }


    private function getActiveInvoices()
    {
        $time_hours = time() - 3600;
        $QUERY = "SELECT * FROM invoice WHERE (Status = 'active' OR Status = 'end_of_time' OR Status = 'end_of_volume' OR Status = 'sendedwarn' OR Status = 'send_on_hold') AND name_product != :testName AND (time_cron <= :timeHours OR time_cron IS NULL) ORDER BY time_cron  LIMIT 30";
        $stmt = $this->pdo->prepare($QUERY);
        $stmt->execute([':testName' => $this->textBotLang['common']['labels']['testServiceName'], ':timeHours' => $time_hours]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function processInvoice($invoice)
    {
        $username = $invoice['username'];

        // Get panel information
        $panelInfo = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
        if (!$panelInfo)
            return false;

        if ($panelInfo['status'] == "disabled")
            return false;
        // Get user information
        $user = select("user", "*", "id", $invoice['id_user'], "select");
        if ($user == false)
            return false;

        // Get username data from panel
        $userData = $this->Panel->DataUser($invoice['Service_location'], $username);
        if (!$userData || $userData['status'] == "Unsuccessful")
            return;
        return [
            'invoice' => $invoice,
            'panel' => $panelInfo,
            'user' => $user,
            'userData' => $userData
        ];
    }

    private function checkVolumeStatus($invoice, $user, $userData, $username, &$check_send)
    {
        $dataLimit = intval($userData['data_limit'] ?? 0);
        $usedTraffic = intval($userData['used_traffic'] ?? 0);
        $remainingVolume = $dataLimit - $usedTraffic;
        $status = $userData['status'] ?? 'active';

        // Check A: Has volume ended? (remaining <= 0 or status is limited)
        $isVolumeEnded = ($dataLimit > 0 && $remainingVolume <= 0) || $status === 'limited' || $invoice['Status'] === 'end_of_volume';

        if ($isVolumeEnded) {
            if (empty($check_send['volume_ended'])) {
                $message = "⚠️ <b>اتمام حجم سرویس</b>\n\n" .
                    "کاربر گرامی، حجم سرویس <code>{$username}</code> شما به اتمام رسید!\n" .
                    "جهت برقراری مجدد اتصال، لطفاً نسبت به تمدید سرویس خود اقدام فرمایید.";

                $reportMessage = "⚠️ <b>گزارش اتمام حجم</b>\n" .
                    "نام کاربری: <code>{$username}</code>\n" .
                    "شناسه کاربر: <code>{$invoice['id_user']}</code>\n" .
                    "لوکیشن: {$invoice['Service_location']}\n" .
                    "وضعیت: اتمام حجم";

                $this->send_notifactions($invoice, $user, $message, true, $invoice['bottype']);
                $this->sendReportNotification($reportMessage);

                $check_send['volume_ended'] = true;
                $check_send['volume'] = true;
                update("invoice", "notifctions", json_encode($check_send), "id_invoice", $invoice['id_invoice']);
                update("invoice", "Status", "end_of_volume", "id_invoice", $invoice['id_invoice']);
                return true;
            }
            return false;
        }

        // Check B: Volume threshold warning (only if not already warned and remaining > 0)
        if (empty($check_send['volume'])) {
            $volumeWarningThreshold = intval($this->setting['volumewarn']) * pow(1024, 3);
            $isVolumeWarning = $dataLimit > 0 && $remainingVolume <= $volumeWarningThreshold && $remainingVolume > 0 && in_array($status, ['active', 'Unknown', 'disabled']);

            if ($isVolumeWarning) {
                $formattedVolume = formatBytes($remainingVolume);
                $message = ($this->textBotLang['users']['notify']['greeting'] ?? "با سلام 👋\n") .
                    sprintf($this->textBotLang['users']['notify']['volumeRemaining'] ?? "🚨 از حجم سرویس %s تنها %s باقی مانده است.", $username, $formattedVolume) . "\n" .
                    sprintf($this->textBotLang['users']['notify']['volumeActionHint'] ?? "لطفاً در صورت تمایل برای خرید حجم اضافه یا تمدید اقدام فرمایید.", $this->text_Purchased_services);
                $reportMessage = ($this->textBotLang['users']['notify']['volumeTitle'] ?? "📌 هشدار حجم:\n") .
                    sprintf($this->textBotLang['users']['notify']['serviceUsername'] ?? "نام کاربری: %s\n", $username) .
                    sprintf($this->textBotLang['users']['notify']['serviceStatus'] ?? "وضعیت: %s\n", $status) .
                    sprintf($this->textBotLang['users']['notify']['remainingVolume'] ?? "حجم باقی‌مانده: %s\n", $formattedVolume);

                $this->send_notifactions($invoice, $user, $message, true, $invoice['bottype']);
                $this->sendReportNotification($reportMessage);

                $check_send['volume'] = true;
                update("invoice", "notifctions", json_encode($check_send), "id_invoice", $invoice['id_invoice']);
                return true;
            }
        }

        return false;
    }
    private function shouldRemoveService($invoice, $user, $userData, $username)
    {
        if (!in_array($userData['status'], ['limited', 'expired']))
            return false;
        if (empty($userData['expire']))
            return false;
        $timeService = $userData['expire'] - time();
        $daysRemaining = intval($timeService / 86400);
        $removalThreshold = intval("-" . $this->setting['removedayc']);
        $result = $daysRemaining <= $removalThreshold;
        $statusText = [
            'active' => $this->textBotLang['users']['status']['active'],
            'limited' => $this->textBotLang['users']['status']['limited'],
            'disabled' => $this->textBotLang['users']['status']['disabled'],
            'expired' => $this->textBotLang['users']['status']['expired'],
            'on_hold' => $this->textBotLang['users']['status']['on_hold'],
            'Unknown' => $this->textBotLang['users']['status']['unknown']
        ][$userData['status']];
        $remainingVolume = formatBytes($userData['data_limit'] - $userData['used_traffic']);
        if ($result) {
            update("invoice", "status", "removeTime", "username", $username);
            $this->Panel->RemoveUser($invoice['Service_location'], $username);
            $message = sprintf($this->textBotLang['users']['notify']['serviceDeleted'], $invoice['username']);
            $reportMessage = sprintf($this->textBotLang['users']['notify']['deleteInfo'], $invoice['username'], $statusText, $daysRemaining, $remainingVolume);
            $this->send_notifactions($invoice, $user, $message, false, $invoice['bottype']);
            $this->sendReportNotification($reportMessage);
        }
    }
    private function shouldRemoveServiceـvolume($invoice, $user, $userData, $username)
    {
        if (!in_array($userData['status'], ['limited', 'expired']))
            return false;
        $panel = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
        if ($panel['type'] != "marzban")
            return;
        if ($userData['data_limit_reset'] != "no_reset")
            return;
        if ($userData['status'] == "Unsuccessful")
            return;
        if (in_array($userData['status'], ['Unknown', 'active', 'on_hold', 'disabled', 'expired']))
            return;
        if (empty($userData['online_at']) or $userData['online_at'] == null) {
            $timelastconect = 0;
        } else {
            $time = strtotime($userData['online_at']);
            $timelastconect = (time() - $time) / 86400;
        }
        if ($timelastconect == 0)
            return;
        $timeService = $userData['expire'] - time();
        $daysRemaining = intval($timeService / 86400);
        $removalThreshold = intval($this->setting['cronvolumere']);
        $result = $timelastconect >= $removalThreshold;
        $statusText = [
            'active' => $this->textBotLang['users']['status']['active'],
            'limited' => $this->textBotLang['users']['status']['limited'],
            'disabled' => $this->textBotLang['users']['status']['disabled'],
            'expired' => $this->textBotLang['users']['status']['expired'],
            'on_hold' => $this->textBotLang['users']['status']['on_hold'],
            'Unknown' => $this->textBotLang['users']['status']['unknown']
        ][$userData['status']];
        $remainingVolume = formatBytes($userData['data_limit'] - $userData['used_traffic']);
        if ($result) {
            update("invoice", "status", "removevolume", "username", $username);
            $this->Panel->RemoveUser($invoice['Service_location'], $username);
            $message = sprintf($this->textBotLang['users']['notify']['serviceDeleted2'], $username);
            $reportMessage = sprintf($this->textBotLang['users']['notify']['volumeDeleteInfo'], $username, $statusText, $daysRemaining, $remainingVolume, $userData['online_at']);
            $this->send_notifactions($invoice, $user, $message, false, $invoice['bottype']);
            $this->sendReportNotification($reportMessage);
        }
    }
    private function active_inbound_expire($invoice, $userData, $panel_info)
    {
        if ($invoice['uuid'] != null || $userData['data_limit_reset'] != "no_reset" || !in_array($userData['status'], ['expired', 'limited']))
            return;
        $inbound = explode("*", $panel_info['inbound_deactive']);
        $proxies = [];
        $proxies[$inbound[0]] = new stdClass();
        ;
        $inbounds[$inbound[0]][] = $inbound[1];
        $configs = array(
            "proxies" => $proxies,
            "inbounds" => $inbounds
        );
        $modify = $this->Panel->Modifyuser($invoice['username'], $panel_info['name_panel'], $configs);
        if (!empty($modify['status']))
            update("invoice", "uuid", json_encode($userData['uuid']), "id_invoice", $invoice['id_invoice']);
    }
    private function checkTimeStatus($invoice, $user, $userData, $username, &$check_send)
    {
        $expire = intval($userData['expire'] ?? 0);
        $timeRemaining = $expire > 0 ? ($expire - time()) : 999999999;
        $status = $userData['status'] ?? 'active';

        // Check A: Has time expired? (timeRemaining <= 0 or status is expired)
        $isTimeExpired = ($expire > 0 && $timeRemaining <= 0) || $status === 'expired' || $invoice['Status'] === 'end_of_time';

        if ($isTimeExpired) {
            if (empty($check_send['time_ended'])) {
                $message = "⚠️ <b>اتمام زمان سرویس (منقضی شده)</b>\n\n" .
                    "کاربر گرامی، مدت زمان اعتبار سرویس <code>{$username}</code> شما به پایان رسید!\n" .
                    "جهت برقراری مجدد اتصال، لطفاً نسبت به تمدید سرویس خود اقدام فرمایید.";

                $reportMessage = "⚠️ <b>گزارش انقضای سرویس</b>\n" .
                    "نام کاربری: <code>{$username}</code>\n" .
                    "شناسه کاربر: <code>{$invoice['id_user']}</code>\n" .
                    "لوکیشن: {$invoice['Service_location']}\n" .
                    "وضعیت: منقضی شده";

                $this->send_notifactions($invoice, $user, $message, true, $invoice['bottype']);
                $this->sendReportNotification($reportMessage);

                $check_send['time_ended'] = true;
                $check_send['time'] = true;
                update("invoice", "notifctions", json_encode($check_send), "id_invoice", $invoice['id_invoice']);
                update("invoice", "Status", "end_of_time", "id_invoice", $invoice['id_invoice']);
                return true;
            }
            return false;
        }

        // Check B: Time expiration warning (days remaining <= threshold)
        if (empty($check_send['time'])) {
            $daysRemaining = intval($timeRemaining / self::SECONDS_PER_DAY);
            $warningThreshold = intval($this->setting['daywarn']) * self::SECONDS_PER_DAY;
            $isTimeWarning = $expire > 0 && $timeRemaining <= $warningThreshold && $timeRemaining > 0 && !in_array($status, ['expired', 'on_hold']);

            if ($isTimeWarning) {
                $message = ($this->textBotLang['users']['notify']['greeting2'] ?? "با سلام 👋\n") .
                    sprintf($this->textBotLang['users']['notify']['timeRemaining'] ?? "📌 از مهلت زمانی استفاده از سرویس %s فقط %s روز باقی مانده است.", $username, $daysRemaining) . "\n" .
                    sprintf($this->textBotLang['users']['notify']['timeActionHint'] ?? "لطفاً در صورت تمایل برای تمدید اقدام فرمایید.", $this->text_Purchased_services) . "\n" .
                    ($this->textBotLang['users']['notify']['thanks'] ?? "با تشکر");
                $reportMessage = ($this->textBotLang['users']['notify']['timeTitle'] ?? "📌 هشدار زمان:\n") .
                    sprintf($this->textBotLang['users']['notify']['serviceUsername2'] ?? "نام کاربری: %s\n", $username) .
                    sprintf($this->textBotLang['users']['notify']['serviceStatus2'] ?? "وضعیت: %s\n", $status) .
                    sprintf($this->textBotLang['users']['notify']['remainingDays'] ?? "روزهای باقی‌مانده: %s\n", $daysRemaining);

                $this->send_notifactions($invoice, $user, $message, true, $invoice['bottype']);
                $this->sendReportNotification($reportMessage);

                $check_send['time'] = true;
                update("invoice", "notifctions", json_encode($check_send), "id_invoice", $invoice['id_invoice']);
                return true;
            }
        }

        return false;
    }

    private function send_notifactions($invoice, $status_cron_user, $message, $keyboard_active, $bot_token)
    {
        if (is_array($status_cron_user)) {
            $status_cron_user = $status_cron_user['status_cron'] ?? 1;
        }
        if (intval($status_cron_user) == 0)
            return;
        $keyboard = $this->createExtendServiceKeyboard($invoice['id_invoice']);
        $keyboard = $keyboard_active ? $keyboard : null;
        sendmessage($invoice['id_user'], $message, $keyboard, 'HTML', $bot_token);
    }

    public function createExtendServiceKeyboard($invoiceId)
    {
        return json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $this->textBotLang['keyboard']['renewService'], 'callback_data' => 'extend_' . $invoiceId],
                ],
            ]
        ]);
    }

    private function sendReportNotification($reportMessage)
    {
        if (empty($this->setting['Channel_Report']))
            return;


        telegram('sendmessage', [
            'chat_id' => $this->setting['Channel_Report'],
            'message_thread_id' => $this->reportCron,
            'text' => $reportMessage,
            'parse_mode' => "HTML"
        ]);
    }

    private function updateInvoiceStatus($type, $invoice)
    {
        $data = json_decode($invoice['notifctions'], true);
        $data[$type] = true;
        $data = json_encode($data);
        update("invoice", "notifctions", $data, "id_invoice", $invoice['id_invoice']);
    }
}

// Execute the volume monitoring
$volumeMonitor = new ServiceMonitor();
$volumeMonitor->RunNotifactions();