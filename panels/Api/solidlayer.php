<?php

/**
 * SolidLayer Panel API Integration
 * Base URL: https://app.solidlayerco.com
 * Supports both API Token (X-API-KEY header) and Username/Password (JWT Bearer Token)
 */

if (!function_exists('get_solidlayer_auth')) {
    function get_solidlayer_auth($panel)
    {
        $username = trim((string) ($panel['username_panel'] ?? ''));
        $password = trim((string) ($panel['password_panel'] ?? ''));

        // If username is "token", "null", or empty, or password is a 64-char API key -> Use API Key
        if ($username === '' || $username === 'token' || $username === 'null' || strlen($password) === 64) {
            return [
                'type' => 'apikey',
                'header' => 'X-API-KEY: ' . $password
            ];
        }

        // Otherwise use Username & Password to obtain/reuse JWT Bearer Token
        if (!empty($panel['datelogin'])) {
            $date = json_decode($panel['datelogin'], true);
            if (is_array($date) && !empty($date['time']) && !empty($date['access_token'])) {
                $elapsed = time() - strtotime($date['time']);
                if ($elapsed < 3600) {
                    return [
                        'type' => 'bearer',
                        'header' => 'Authorization: Bearer ' . $date['access_token']
                    ];
                }
            }
        }

        // Request new JWT token from /api/admins/token
        $url = rtrim($panel['url_panel'], '/') . '/api/admins/token';
        $payload = json_encode([
            'username' => $username,
            'password' => $password
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT_MS => ($GLOBALS['request_exec_timeout'] ?? null) ?: 8000,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($response, true);
        $token = $body['token'] ?? $body['access_token'] ?? null;
        if ($token) {
            $saveData = json_encode([
                'time' => date('Y/m/d H:i:s'),
                'access_token' => $token
            ]);
            update("marzban_panel", "datelogin", $saveData, 'name_panel', $panel['name_panel']);
            return [
                'type' => 'bearer',
                'header' => 'Authorization: Bearer ' . $token
            ];
        }

        // Fallback to API Key using password_panel if JWT login failed
        return [
            'type' => 'apikey',
            'header' => 'X-API-KEY: ' . $password
        ];
    }
}

if (!function_exists('solidlayer_request')) {
    function solidlayer_request($panel, $endpoint, $method = 'GET', $body = null)
    {
        $auth = get_solidlayer_auth($panel);
        $url = rtrim($panel['url_panel'], '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            $auth['header'],
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];

        $ch = curl_init($url);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => ($GLOBALS['request_exec_timeout'] ?? null) ?: 10000,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        if (strtoupper($method) === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            if ($body !== null) {
                $payload = is_string($body) ? $body : json_encode($body);
                $curlOptions[CURLOPT_POSTFIELDS] = $payload;
                $headers[] = 'Content-Type: application/json';
            }
        } elseif (strtoupper($method) === 'PUT') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
            if ($body !== null) {
                $payload = is_string($body) ? $body : json_encode($body);
                $curlOptions[CURLOPT_POSTFIELDS] = $payload;
                $headers[] = 'Content-Type: application/json';
            }
        } elseif (strtoupper($method) === 'DELETE') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            if ($body !== null) {
                $payload = is_string($body) ? $body : json_encode($body);
                $curlOptions[CURLOPT_POSTFIELDS] = $payload;
                $headers[] = 'Content-Type: application/json';
            }
        } else {
            $curlOptions[CURLOPT_HTTPGET] = true;
        }

        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'status' => 0,
                'error' => $curlError,
                'body' => null
            ];
        }

        return [
            'status' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true)
        ];
    }
}

if (!function_exists('get_solidlayer_services')) {
    function get_solidlayer_services($panel)
    {
        $res = solidlayer_request($panel, 'api/services', 'GET');
        if (!empty($res['data']['items']) && is_array($res['data']['items'])) {
            return $res['data']['items'];
        }
        return [];
    }
}

if (!function_exists('add_user_solidlayer')) {
    function add_user_solidlayer($panel_name, $data_limit, $username, $expire, $note = '', $name_product = null)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => 404, 'error' => 'Panel not found'];
        }

        // Determine service id
        $service_id = 4; // default
        if (!empty($panel['inboundid']) && intval($panel['inboundid']) > 0) {
            $service_id = intval($panel['inboundid']);
        } else {
            $services = get_solidlayer_services($panel);
            if (!empty($services[0]['id'])) {
                $service_id = intval($services[0]['id']);
            }
        }

        $limitUsage = ($data_limit > 0) ? intval($data_limit) : 0;
        $limitExpire = ($expire > 0) ? intval($expire) : 0;

        // SolidLayer does not allow unlimited usage (limit_usage = 0 returns permission error)
        if ($limitUsage <= 0) {
            $limitUsage = 20 * 1024 * 1024 * 1024; // Default to 20 GB if volume is not set
        }
        if ($limitExpire <= 0) {
            $limitExpire = time() + (30 * 86400); // Default to 30 days if expire is not set
        }

        $body = [
            [
                'username' => $username,
                'limit_usage' => $limitUsage,
                'limit_expire' => $limitExpire,
                'services' => [$service_id],
                'note' => $note
            ]
        ];

        return solidlayer_request($panel, 'api/subscriptions', 'POST', $body);
    }
}

if (!function_exists('get_user_solidlayer')) {
    function get_user_solidlayer($panel_name, $username)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => 404, 'error' => 'Panel not found'];
        }

        $endpoint = 'api/subscriptions?usernames=' . urlencode($username);
        return solidlayer_request($panel, $endpoint, 'GET');
    }
}

if (!function_exists('get_user_links_solidlayer')) {
    function get_user_links_solidlayer($panel_name, $username)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return [];
        }

        $endpoint = 'api/subscriptions/' . urlencode($username) . '/links';
        $res = solidlayer_request($panel, $endpoint, 'GET');
        if (!empty($res['data']) && is_array($res['data'])) {
            return $res['data'];
        }
        return [];
    }
}

if (!function_exists('modify_user_solidlayer')) {
    function modify_user_solidlayer($panel_name, $username, $config = [])
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => false, 'msg' => 'Panel not found'];
        }

        $payload = [
            'usernames' => [$username]
        ];

        if (isset($config['limit_expire'])) {
            $payload['limit_expire'] = intval($config['limit_expire']);
        } elseif (isset($config['expire'])) {
            $payload['limit_expire'] = intval($config['expire']);
        }

        if (isset($config['limit_usage'])) {
            $payload['limit_usage'] = intval($config['limit_usage']);
        } elseif (isset($config['data_limit'])) {
            $payload['limit_usage'] = intval($config['data_limit']);
        }

        if (isset($config['enabled'])) {
            $payload['enabled'] = (bool) $config['enabled'];
        }

        if (isset($config['note'])) {
            $payload['note'] = (string) $config['note'];
        }

        $res = solidlayer_request($panel, 'api/subscriptions', 'PUT', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return ['status' => true, 'msg' => 'Updated successfully'];
        }

        $msg = $res['data']['message'] ?? $res['error'] ?? 'Update failed (HTTP ' . $res['status'] . ')';
        return ['status' => false, 'msg' => $msg];
    }
}

if (!function_exists('delete_user_solidlayer')) {
    function delete_user_solidlayer($panel_name, $username)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => false, 'msg' => 'Panel not found'];
        }

        $payload = ['usernames' => [$username]];
        $res = solidlayer_request($panel, 'api/subscriptions', 'DELETE', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return ['status' => true, 'msg' => 'Deleted successfully'];
        }
        $msg = $res['data']['message'] ?? $res['error'] ?? 'Delete failed';
        return ['status' => false, 'msg' => $msg];
    }
}

if (!function_exists('reset_user_solidlayer')) {
    function reset_user_solidlayer($panel_name, $username)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => false, 'msg' => 'Panel not found'];
        }

        $payload = ['usernames' => [$username]];
        $res = solidlayer_request($panel, 'api/subscriptions/reset', 'POST', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return ['status' => true, 'msg' => 'Reset successful'];
        }
        $msg = $res['data']['message'] ?? $res['error'] ?? 'Reset failed';
        return ['status' => false, 'msg' => $msg];
    }
}

if (!function_exists('revoke_sub_solidlayer')) {
    function revoke_sub_solidlayer($panel_name, $username)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => false, 'msg' => 'Panel not found'];
        }

        $payload = ['usernames' => [$username]];
        $res = solidlayer_request($panel, 'api/subscriptions/revoke', 'POST', $payload);
        return $res;
    }
}

if (!function_exists('change_status_solidlayer')) {
    function change_status_solidlayer($panel_name, $username, $status)
    {
        $panel = select("marzban_panel", "*", "name_panel", $panel_name, "select");
        if (!$panel) {
            return ['status' => false, 'msg' => 'Panel not found'];
        }

        $endpoint = ($status === 'disabled') ? 'api/subscriptions/disable' : 'api/subscriptions/enable';
        $payload = ['usernames' => [$username]];
        $res = solidlayer_request($panel, $endpoint, 'POST', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return ['status' => true, 'msg' => 'Status changed'];
        }
        $msg = $res['data']['message'] ?? $res['error'] ?? 'Status change failed';
        return ['status' => false, 'msg' => $msg];
    }
}

if (!function_exists('status_server_solidlayer')) {
    function status_server_solidlayer($panel)
    {
        $res = solidlayer_request($panel, 'api/admins/current', 'GET');
        return $res;
    }
}
