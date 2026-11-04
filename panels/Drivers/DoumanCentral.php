<?php

require_once __DIR__ . '/../../DoumanCentralClient.php';

class DoumanCentralPanelDriver extends BasePanelDriver
{
    public function create(array $vars)
    {
        $Get_Data_Product = $vars['Get_Data_Product'] ?? [];
        $Get_Data_Panel = $vars['Get_Data_Panel'] ?? [];
        $usernameC = $vars['usernameC'] ?? '';
        $expire = $vars['expire'] ?? 0;
        $note = $vars['note'] ?? '';
        $invoice = $vars['invoice'] ?? false;
        $limitip = intval($vars['limitip'] ?? $vars['limit_ip'] ?? $Get_Data_Product['limit_ip'] ?? 1);
        if ($limitip < 0) { $limitip = 1; }

        // 1. Resolve Provider Code ('sanayi' or 'solidlayer')
        $provider = strtolower(trim((string)($Get_Data_Product['provider'] ?? '')));
        if (empty($provider) || !in_array($provider, ['sanayi', 'solidlayer'])) {
            $panelType = strtolower(trim((string)($Get_Data_Panel['type'] ?? '')));
            if ($panelType === 'solidlayer') {
                $provider = 'solidlayer';
            } elseif ($panelType === 'x-ui_single' || $panelType === 'sanayi') {
                $provider = 'sanayi';
            } else {
                $provider = 'sanayi'; // default
            }
        }

        // 2. Resolve Unlimited vs Volumetric
        $isUnlimited = !empty($Get_Data_Product['is_unlimited']);
        $volumeGb = 0;
        if (!$isUnlimited) {
            $volumeGb = floatval($vars['custom_volume_gb'] ?? $Get_Data_Product['Volume_constraint'] ?? 0);
            if ($volumeGb <= 0) {
                // If data_limit in bytes was passed
                if (!empty($vars['data_limit']) && is_numeric($vars['data_limit'])) {
                    $volumeGb = round($vars['data_limit'] / (1024 * 1024 * 1024), 2);
                }
            }
            if ($volumeGb <= 0) {
                return [
                    'status' => 'Unsuccessful',
                    'msg' => 'حجم بسته برای سرویس حجمی نامعتبر است (باید بیشتر از صفر باشد).'
                ];
            }
        }

        // 3. Resolve Expiry Days
        $expireDays = intval($Get_Data_Product['Service_time'] ?? 30);
        if ($expireDays <= 0 && $expire > 0) {
            $expireDays = max(1, round(($expire - time()) / 86400));
        }

        // 4. Generate Idempotency Key
        $invoiceId = is_array($invoice) ? ($invoice['id_invoice'] ?? '') : (string)$invoice;
        $idempotencyKey = 'bot_' . md5($invoiceId . ':' . $usernameC . ':' . $provider . ':' . ($isUnlimited ? 'unlimited' : $volumeGb));

        // 5. Call Central API
        $payload = [
            'username' => $usernameC,
            'is_unlimited' => $isUnlimited,
            'volume_gb' => $isUnlimited ? 0 : $volumeGb,
            'expire_days' => $expireDays,
            'limit_ip' => $limitip,
            'note' => $note ?: "Order #{$invoiceId}",
            'idempotency_key' => $idempotencyKey
        ];

        $response = DoumanCentralClient::createAccount($provider, $payload);

        if (empty($response['success'])) {
            $msg = $response['message'] ?? $response['error_code'] ?? 'خطای ناشناخته از سرور مرکزی';
            return [
                'status' => 'Unsuccessful',
                'msg' => $msg
            ];
        }

        $data = $response['data'] ?? [];
        $subUrl = $data['subscription_url'] ?? '';
        $configs = $data['configs'] ?? [];

        return [
            'status' => 'successful',
            'username' => $data['username'] ?? $usernameC,
            'subscription_url' => $subUrl,
            'configs' => is_array($configs) ? $configs : [],
            'transaction_id' => $data['transaction_id'] ?? null
        ];
    }

    public function read(array $vars)
    {
        $username = $vars['username'] ?? '';
        $provider = $vars['provider'] ?? 'sanayi';
        return DoumanCentralClient::getAccountStatus($provider, $username);
    }

    public function delete(array $vars)
    {
        return ['status' => 'successful'];
    }

    public function modify(array $vars)
    {
        return ['status' => 'successful'];
    }
}
