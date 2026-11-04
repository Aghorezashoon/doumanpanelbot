<?php

/**
 * Douman Central Reseller API Client
 * Used by Reseller Telegram Bots to communicate with the Central DOUMAN RESELLER PANEL.
 * Handles provider isolation, independent credentials, and transaction-safe account provisioning.
 */

class DoumanCentralClient
{
    private static $defaultBaseUrl = 'https://tr.doumanshop.cv/api/v1';

    /**
     * Get configured credentials for a provider ('sanayi' or 'solidlayer')
     */
    public static function getProviderConfig(string $providerCode): ?array
    {
        global $pdo;
        $providerCode = strtolower(trim($providerCode));
        try {
            $stmt = $pdo->prepare("SELECT * FROM reseller_provider WHERE provider_code = ? LIMIT 1");
            $stmt->execute([$providerCode]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                return $res;
            }
        } catch (Exception $e) {
            // Table might not exist yet or DB error
        }

        // Fallback: check global constants or config if defined
        if (defined('DOUMAN_' . strtoupper($providerCode) . '_KEY')) {
            return [
                'provider_code' => $providerCode,
                'api_key' => constant('DOUMAN_' . strtoupper($providerCode) . '_KEY'),
                'api_secret' => constant('DOUMAN_' . strtoupper($providerCode) . '_SECRET'),
                'api_base_url' => defined('DOUMAN_CENTRAL_URL') ? constant('DOUMAN_CENTRAL_URL') : self::$defaultBaseUrl,
                'status' => 'active',
            ];
        }

        return null;
    }

    /**
     * Send HTTP request to Central Reseller API
     */
    public static function request(string $providerCode, string $endpoint, string $method = 'GET', array $data = [], ?string $customKey = null, ?string $customSecret = null, ?string $customBaseUrl = null): array
    {
        $providerCode = strtolower(trim($providerCode));
        $apiKey = $customKey;
        $apiSecret = $customSecret;
        $baseUrl = $customBaseUrl ?: self::$defaultBaseUrl;

        if (!$apiKey || !$apiSecret) {
            $cfg = self::getProviderConfig($providerCode);
            if (!$cfg || empty($cfg['api_key']) || empty($cfg['api_secret'])) {
                return [
                    'success' => false,
                    'error_code' => 'MISSING_CREDENTIALS',
                    'message' => "اعتبارنامه ارائه‌دهنده '{$providerCode}' در ربات تنظیم نشده است."
                ];
            }
            if ($cfg['status'] !== 'active') {
                return [
                    'success' => false,
                    'error_code' => 'PROVIDER_DISABLED',
                    'message' => "دسترسی به ارائه‌دهنده '{$providerCode}' غیرفعال است."
                ];
            }
            $apiKey = $cfg['api_key'];
            $apiSecret = $cfg['api_secret'];
            if (!empty($cfg['api_base_url'])) {
                $baseUrl = rtrim($cfg['api_base_url'], '/');
            }
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Douman-Key: ' . $apiKey,
            'X-Douman-Secret: ' . $apiSecret,
            'X-Douman-Provider: ' . $providerCode,
            'User-Agent: DoumanResellerBot/2.0',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error_code' => 'CURL_ERROR',
                'message' => "خطا در برقراری ارتباط با سرور مرکزی: " . $curlError
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error_code' => 'INVALID_JSON_RESPONSE',
                'message' => "پاسخ نامعتبر از سرور مرکزی (کد {$httpCode})",
                'raw' => $response
            ];
        }

        return $decoded;
    }

    /**
     * Verify credentials for a specific provider
     */
    public static function verify(string $providerCode, ?string $key = null, ?string $secret = null, ?string $baseUrl = null): array
    {
        return self::request($providerCode, 'auth/verify', 'GET', [], $key, $secret, $baseUrl);
    }

    /**
     * Retrieve quota balance and permissions for a provider
     */
    public static function getQuotas(string $providerCode): array
    {
        return self::request($providerCode, 'quotas', 'GET');
    }

    /**
     * Create account via Central API
     * Concurrency-safe, requires idempotency_key
     */
    public static function createAccount(string $providerCode, array $params): array
    {
        return self::request($providerCode, 'accounts/create', 'POST', $params);
    }

    /**
     * Retrieve account status
     */
    public static function getAccountStatus(string $providerCode, string $username): array
    {
        return self::request($providerCode, 'accounts/' . urlencode($username), 'GET');
    }
}
