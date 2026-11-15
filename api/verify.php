<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: 0');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

function respond_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) !== 'HTTP_') {
                continue;
            }

            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$headerName] = $value;
        }

        return $headers;
    }
}

function normalize_init_value($value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    if ($value === null) {
        return '';
    }

    return (string) $value;
}

function validate_telegram_init_data($rawData, string $botToken): array
{
    if (is_string($rawData)) {
        $rawData = trim($rawData);
        if ($rawData === '') {
            throw new InvalidArgumentException('Telegram init data is missing or invalid');
        }

        $decodedQuery = html_entity_decode($rawData, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        parse_str($decodedQuery, $initData);
    } elseif (is_array($rawData)) {
        $initData = $rawData;
    } else {
        throw new InvalidArgumentException('Telegram init data is missing or invalid');
    }

    if (!is_array($initData) || $initData === []) {
        throw new InvalidArgumentException('Telegram init data payload is empty');
    }

    if (!isset($initData['hash'])) {
        throw new InvalidArgumentException('Telegram init data is missing required signature');
    }

    $receivedHash = (string) $initData['hash'];
    unset($initData['hash']);

    $dataCheckArray = [];
    foreach ($initData as $key => $value) {
        if ($value === null) {
            continue;
        }

        $stringValue = normalize_init_value($value);
        if ($stringValue === '') {
            continue;
        }

        $dataCheckArray[] = $key . '=' . $stringValue;
    }

    if ($dataCheckArray === []) {
        throw new InvalidArgumentException('Telegram init data payload is empty');
    }

    sort($dataCheckArray, SORT_STRING);
    $dataCheckString = implode("\n", $dataCheckArray);

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    if (!hash_equals($calculatedHash, $receivedHash)) {
        throw new RuntimeException('User verification failed');
    }

    $authDate = isset($initData['auth_date']) ? (int) $initData['auth_date'] : 0;
    if ($authDate <= 0) {
        throw new RuntimeException('Telegram init data is missing auth_date');
    }
    if (time() - $authDate > 86400) {
        throw new RuntimeException('Telegram init data has expired');
    }

    $userRaw = $initData['user'] ?? null;
    if (is_string($userRaw)) {
        $userData = json_decode($userRaw, true);
    } elseif (is_array($userRaw)) {
        $userData = $userRaw;
    } else {
        $userData = null;
    }

    if (!is_array($userData) || !isset($userData['id'])) {
        throw new RuntimeException('User data is missing or malformed in init data');
    }

    return $userData;
}

$rawInput = file_get_contents('php://input');

if ($rawInput === false) {
    respond_json(400, [
        'status' => false,
        'msg' => 'Failed to read request body',
        'token' => null,
    ]);
}

$rawInput = trim($rawInput);
$decodedJson = null;
if ($rawInput !== '') {
    $decodedJson = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $decodedJson = null;
    }
}

$headers = getallheaders();
$candidates = [];

$headerKeys = [
    'X-Telegram-Init-Data',
    'X-Telegram-Web-App-Init-Data',
    'Telegram-Init-Data',
];

foreach ($headerKeys as $headerKey) {
    $value = headerValue($headers, $headerKey);
    if ($value !== null && trim($value) !== '') {
        $candidates[] = trim($value);
    }
}

foreach ([
    $_POST['initData'] ?? null,
    $_POST['init_data'] ?? null,
    $_POST['initDataUnsafe'] ?? null,
    $_POST['init_data_unsafe'] ?? null,
    $_GET['initData'] ?? null,
    $_GET['init_data'] ?? null,
] as $value) {
    if ($value === null) {
        continue;
    }

    if (is_string($value)) {
        $value = trim($value);
        if ($value === '') {
            continue;
        }
    }

    $candidates[] = $value;
}

if ($decodedJson !== null) {
    foreach ([
        $decodedJson,
        $decodedJson['initData'] ?? null,
        $decodedJson['init_data'] ?? null,
        $decodedJson['initDataUnsafe'] ?? null,
        $decodedJson['init_data_unsafe'] ?? null,
    ] as $value) {
        if ($value === null) {
            continue;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
        }

        $candidates[] = $value;
    }
} elseif ($rawInput !== '') {
    $candidates[] = $rawInput;
}

if ($candidates === []) {
    respond_json(400, [
        'status' => false,
        'msg' => 'Telegram init data is missing or invalid',
        'token' => null,
    ]);
}

$lastException = null;
$userData = null;

foreach ($candidates as $candidate) {
    try {
        $userData = validate_telegram_init_data($candidate, $APIKEY);
        break;
    } catch (InvalidArgumentException $exception) {
        $lastException = $exception;
        continue;
    } catch (RuntimeException $exception) {
        $lastException = $exception;
        continue;
    }
}

if ($userData === null) {
    $message = $lastException ? $lastException->getMessage() : 'Telegram init data is missing or invalid';
    $statusCode = $lastException instanceof RuntimeException ? 403 : 400;

    respond_json($statusCode, [
        'status' => false,
        'msg' => $message,
        'token' => null,
    ]);
}

$userId = $userData['id'];
$userRecord = select('user', '*', 'id', $userId, 'select');

if (!$userRecord) {
    try {
        $stmt_reg = $pdo->prepare("INSERT IGNORE INTO user (id, step, limit_usertest, User_Status, number, Balance, pagenumber, username, agent, message_count, last_message_time, affiliates, affiliatescount, cardpayment, number_username, namecustom, register, verify, codeInvitation, pricediscount, maxbuyagent, joinchannel, score, status_cron) VALUES (?, 'none', 1, 'Active', 'none', '0', '1', ?, 'f', '0', '0', '0', '0', 'none', '100', 'none', ?, 1, ?, '0', '0', '0', '0', '1')");
        $invCode = bin2hex(random_bytes(6));
        $uname = $userData['username'] ?? ($userData['first_name'] ?? 'User');
        $stmt_reg->execute([$userId, $uname, time(), $invCode]);
        $userRecord = select('user', '*', 'id', $userId, 'select');
    } catch (Exception $e) {
        error_log('Auto register user error: ' . $e->getMessage());
    }
}

if (!$userRecord) {
    respond_json(404, [
        'status' => false,
        'msg' => 'User not found',
        'token' => null,
    ]);
}

try {
    $randomString = bin2hex(random_bytes(20));
} catch (Exception $exception) {
    error_log('Failed to generate random token: ' . $exception->getMessage());
    respond_json(500, [
        'status' => false,
        'msg' => 'Failed to generate session token',
        'token' => null,
    ]);
}

update('user', 'token', $randomString, 'id', $userId);

respond_json(200, [
    'status' => true,
    'msg' => 'User verified',
    'token' => $randomString,
]);
