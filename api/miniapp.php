<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
$textbotlang = languagechange();
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../keyboard.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: 0');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
$ManagePanel = new ManagePanel();
$headers = getallheaders();
$setting = select("setting", "*");
$method = $_SERVER['REQUEST_METHOD'];
$data = null;
if ($method == "GET") {
    $data = array(
        'actions' => isset($_GET['actions']) && is_string($_GET['actions']) ? $_GET['actions'] : '',
        'user_id' => isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int) $_GET['user_id'] : 0,
        'page' => isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1,
        'limit' => isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int) $_GET['limit'] : 10,
        'q' => isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : null,
        'username' => isset($_GET['username']) && is_string($_GET['username']) ? $_GET['username'] : null,
        'id_panel' => isset($_GET['country_id']) && is_string($_GET['country_id']) ? $_GET['country_id'] : "",
        'category_id' => isset($_GET['category_id']) && is_string($_GET['category_id']) ? $_GET['category_id'] : 0,
        'time_range_day' => isset($_GET['time_range_day']) && is_string($_GET['time_range_day']) ? $_GET['time_range_day'] : 0,
        'traffic_gb' => isset($_GET['traffic_gb']) && is_string($_GET['traffic_gb']) ? $_GET['traffic_gb'] : 0,
        'time_days' => isset($_GET['time_days']) && is_string($_GET['time_days']) ? $_GET['time_days'] : 0
    );
} elseif ($method == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        $data = [];
    }
    if (!isset($data['actions']) && isset($_GET['actions'])) {
        $data['actions'] = $_GET['actions'];
    }
}
if (!is_array($data)) {
    echo json_encode([
        'status' => false,
        'msg' => "Data invalid",
        'obj' => []
    ]);
    return;
}

$data = sanitize_recursive($data);

$action = $data['actions'] ?? $_GET['actions'] ?? '';

// Public actions accessible without a session token (allows browsing and web login)
$publicActions = ['web_login_request', 'web_login_verify', 'system_info', 'countries', 'categories', 'services', 'time_ranges'];

$authorization = (string) (headerValue($headers, 'Authorization') ?? '');
$tokencheck = preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $authorization, $bearerMatch) ? $bearerMatch[1] : '';

$usercheck = null;
if ($tokencheck !== '') {
    $usercheck = select('user', "*", "token", $tokencheck, "select");
}

if (!$usercheck || !is_string($usercheck['token'] ?? null) || !hash_equals($usercheck['token'], $tokencheck)) {
    if (!in_array($action, $publicActions, true)) {
        http_response_code(401);
        echo json_encode([
            'status' => false,
            'msg' => "Token invalid",
            'require_login' => true
        ]);
        return;
    }
} else {
    $data['user_id'] = $usercheck['id'];
}

if ($usercheck) {
    if ($method !== "GET") {
        $userLock = $pdo->prepare("SELECT GET_LOCK(?, 0)");
        $userLock->execute(["douman_user_" . $usercheck['id']]);
        if ((int) $userLock->fetchColumn() !== 1) {
            http_response_code(429);
            echo json_encode([
                'status' => false,
                'msg' => "Too many requests",
            ]);
            return;
        }
    }

    if ($usercheck['User_Status'] == "block") {
        http_response_code(402);
        echo json_encode([
            'status' => false,
            'msg' => "user blocked",
        ]);
        return;
    }

    if (!in_array($usercheck['id'], select("admin", "id_admin", null, null, "FETCH_COLUMN"))) {
        if ($setting['Bot_Status'] == "botstatusoff"
            || ($setting['verifystart'] == "onverify" && intval($usercheck['verify']) == 0)
            || ($setting['roll_Status'] == "rolleon" && $usercheck['roll_Status'] == 0)
            || ($setting['get_number'] == "onAuthenticationphone" && $usercheck['number'] == "none")) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'msg' => "access denied",
            ]);
            return;
        }
    }
}

$errorreport = topicId('errorreport');
$porsantreport = topicId('porsantreport');
$buyreport = topicId('buyreport');
$action = $data['actions'] ?? $_GET['actions'] ?? '';

function mini_invoices(array $data, string $method): void
{
    global $pdo, $textbotlang, $ManagePanel, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_id = $data['user_id'] ?? null;
    if (empty($user_id)) {
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $user_id = $user_info['id'];
        }
    }
    if (empty($user_id)) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => "User Not Found", 'obj' => []]);
        return;
    }
    $limit = isset($data['limit']) ? intval($data['limit']) : 10;
    if ($limit <= 0 || $limit > 50) $limit = 10;
    $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
    $username = $data['q'];
    $offset = ($page - 1) * $limit;
    if ($username != null) {
        $querywhere = " AND username LIKE :username";
    } else {
        $querywhere = "";
    }
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') $querywhere");
    $countStmt->bindValue(':user_id', $user_id);
    if ($username != null) {
        $username = "%$username%";
        $countStmt->bindValue(':username', $username, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $limit);
    $stmt = $pdo->prepare("SELECT id_invoice, username, note, Service_location, name_product, Volume, Service_time, time_sell, Status FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') $querywhere  ORDER BY time_sell DESC LIMIT :limit OFFSET :offset ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if ($username != null) {
        $username = "%$username%";
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    }
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $datauser = [];
    if (is_array($invoices)) {
        foreach ($invoices as $invoice) {
            $DataUserOut = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
            $used_Traffic = 0;
            $data_limit = isset($invoice['Volume']) ? (float)$invoice['Volume'] : 0;
            if (!is_array($DataUserOut) || ($DataUserOut['status'] ?? '') == "Unsuccessful") {
                $expire = $textbotlang['common']['labels']['unknown'] ?? 'نامشخص';
                $statusVal = $invoice['Status'] ?? 'active';
            } else {
                $expire = !empty($DataUserOut['expire']) ? jdate('Y/m/d', $DataUserOut['expire']) : ($textbotlang['common']['labels']['unlimited'] ?? 'نامحدود');
                $statusVal = $DataUserOut['status'] ?? ($invoice['Status'] ?? 'active');
                if (isset($DataUserOut['used_traffic']) && is_numeric($DataUserOut['used_traffic'])) {
                    $used_Traffic = (float)$DataUserOut['used_traffic'] / pow(1024, 3);
                }
                if (isset($DataUserOut['data_limit']) && is_numeric($DataUserOut['data_limit']) && (float)$DataUserOut['data_limit'] > 0) {
                    $data_limit = (float)$DataUserOut['data_limit'] / pow(1024, 3);
                }
            }
            $datauser[] = [
                'id_invoice' => $invoice['id_invoice'] ?? '',
                'username' => $invoice['username'],
                'name_product' => !empty($invoice['name_product']) ? $invoice['name_product'] : $invoice['username'],
                'Service_location' => $invoice['Service_location'] ?? '',
                'status' => $statusVal,
                'Status' => $statusVal,
                'traffic_used_gb' => round($used_Traffic, 2),
                'traffic_total_gb' => round((float)$data_limit, 2),
                'remaining_traffic_gb' => round(max(0, (float)$data_limit - $used_Traffic), 2),
                'expire' => $expire,
                'note' => $invoice['note'] ?? ''
            ];
        }
    }
    echo json_encode([
        'status' => true,
        'msg' => "Successful",
        'obj' => $datauser,
        'meta' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]
    ]);
}

function mini_service(array $data, string $method): void
{
    global $pdo, $textbotlang, $ManagePanel, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_id = $data['user_id'] ?? null;
    if (empty($user_id)) {
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $user_id = $user_info['id'];
        }
    }
    if (empty($user_id)) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => "User Not Found", 'obj' => []]);
        return;
    }
    $username = $data['username'];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND username = :username");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($invoice) {
        $panel = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
        if (!$panel) {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "Panel Not Found",
                'obj' => []
            ]);
            return;
        }
        $DataUserOut = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
        if (!is_array($DataUserOut) || !array_key_exists('data_limit', $DataUserOut) || !array_key_exists('used_traffic', $DataUserOut)) {
            http_response_code(502);
            echo json_encode([
                'status' => false,
                'msg' => isset($DataUserOut['msg']) ? $DataUserOut['msg'] : "Service data unavailable",
                'obj' => []
            ]);
            return;
        }
        $data_limit_bytes = is_numeric($DataUserOut['data_limit']) ? (float) $DataUserOut['data_limit'] : 0;
        $used_traffic_bytes = is_numeric($DataUserOut['used_traffic']) ? (float) $DataUserOut['used_traffic'] : 0;
        $remaining_traffic_bytes = max($data_limit_bytes - $used_traffic_bytes, 0);
        $data_limit = $data_limit_bytes / pow(1024, 3);
        $used_Traffic = $used_traffic_bytes / pow(1024, 3);
        $remaining_traffic = $remaining_traffic_bytes / pow(1024, 3);
        $config = [];
        if (!empty($DataUserOut['subscription_url'])) {
            $config[] = [
                'type' => "link",
                'value' => $DataUserOut['subscription_url']
            ];
        }
        if (!empty($DataUserOut['links'])) {
            $config[] = [
                'type' => "config",
                'value' => $DataUserOut['links']
            ];
        }
        if ($panel['type'] == "WGDashboard") {
            $config[] = [
                'type' => "file",
                'value' => $DataUserOut['subscription_url'] ?? '',
                'filename' => $panel['inboundid'] . "_" . $invoice['id_user'] . "_" . $invoice['id_invoice'] . ".config"
            ];
        } elseif (in_array($panel['type'], ['mikrotik', 'ibsng'])) {
            $config[] = [
                'type' => "password",
                'value' => $DataUserOut['password'] ?? ''
            ];
        }
        if (isset($DataUserOut['sub_updated_at']) && $DataUserOut['sub_updated_at'] !== null) {
            $sub_updated = $DataUserOut['sub_updated_at'];
            $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
            $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
        } else {
            $lastupdate = null;
        }
        if (($DataUserOut['online_at'] ?? null) == "online") {
            $lastonline = $textbotlang['common']['connection']['online'];
        } elseif (($DataUserOut['online_at'] ?? null) == "offline") {
            $lastonline = $textbotlang['common']['connection']['offline'];
        } else {
            if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
                $dateString = $DataUserOut['online_at'];
                $date = new DateTime($dateString, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Tehran'));
                $lastonline = jdate('Y/m/d H:i:s', $date->getTimestamp());
            } else {
                $lastonline = $textbotlang['common']['connection']['notConnected'];
            }
        }
        $expireTimestamp = isset($DataUserOut['expire']) && is_numeric($DataUserOut['expire']) ? (int) $DataUserOut['expire'] : 0;
        $expirationDate = $expireTimestamp ? jdate('Y/m/d', $expireTimestamp) : $textbotlang['common']['labels']['unlimited'];
        $usernameOutput = $DataUserOut['username'] ?? $invoice['username'];
$subUrl = '';
        if (!empty($DataUserOut['subscription_url'])) {
            $subUrl = $DataUserOut['subscription_url'];
            if (!empty($panel['sublink'])) {
                $subUrl = "https://" . $panel['sublink'] . "/sub/" . base64_encode($invoice['username']) . "/?token=" . bin2hex(random_bytes(16));
            }
        }
        $rawConfigs = [];
        if (!empty($DataUserOut['links']) && is_array($DataUserOut['links'])) {
            $rawConfigs = $DataUserOut['links'];
        }

        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => array(
                'status' => $DataUserOut['status'],
                'Status' => $DataUserOut['status'],
                'username' => $usernameOutput,
                'product_name' => $invoice['name_product'] ?? $usernameOutput,
                'total_traffic_gb' => round($data_limit, 2),
                'used_traffic_gb' => round($used_Traffic, 2),
                'remaining_traffic_gb' => round($remaining_traffic, 2),
                'expiration_time' => $expirationDate,
                'last_subscription_update' => $lastupdate,
                'online_at' => $lastonline,
                'service_output' => $config,
                'sub_url' => $subUrl,
                'subscription_url' => $subUrl,
                'configs' => $rawConfigs
            ),
        ]);
        return;
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "Service Not  Found",
            'obj' => []
        ]);
    }
}

function mini_user_info(array $data, string $method): void
{
    global $pdo, $textbotlang, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        if ($user_info['codeInvitation'] == null) {
            $randomString = bin2hex(random_bytes(4));
            update("user", "codeInvitation", $randomString, "id", $user_info['id']);
            $user_info['codeInvitation'] = $randomString;
        }
        if ($user_info['number'] == "none") {
            $numberphone = $textbotlang['common']['labels']['receiptNotSent'];
        } else {
            $numberphone = $user_info['number'];
        }
        if ($user_info['number'] == "confrim number by admin") {
            $numberphone = $textbotlang['common']['labels']['confirmedByAdmin'];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE id_user = :id_user AND name_product != :name_product AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
        $stmt->bindValue(':name_product', $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
        $stmt->bindValue(':id_user', $user_info['id'], PDO::PARAM_INT);
        $stmt->execute();
        $countorder = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Payment_report WHERE id_user = :from_id AND payment_Status = 'paid'");
        $stmt->execute([
            ':from_id' => $user_info['id']
        ]);
        $countpayment = (int) $stmt->fetchColumn();
        $groupuser = [
            'f' => $textbotlang['common']['roles']['normal'] ?? 'کاربر عادی',
            'n' => $textbotlang['common']['roles']['agent'] ?? 'نماینده',
            'n2' => $textbotlang['common']['roles']['advancedAgent'] ?? 'نماینده پیشرفته',
            'seller' => 'فروشنده',
        ][$user_info['agent']] ?? 'کاربر عادی';
        $userjoin = jdate('Y/m/d', $user_info['register']);
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => [
                'id' => $user_info['id'],
                'username' => $user_info['username'] ?? '',
                'name' => $user_info['name'] ?? '',
                'codeInvitation' => $user_info['codeInvitation'],
                'balance' => intval($user_info['Balance']),
                'phone' => $numberphone,
                'count_order' => $countorder,
                'count_payment' => $countpayment,
                'agent' => $user_info['agent'],
                'is_agent' => in_array($user_info['agent'], ['n', 'n2', 'seller']),
                'group_type' => $groupuser,
                'time_join' => $userjoin,
                'affiliatescount' => intval($user_info['affiliatescount'] ?? 0),
                'score' => intval($user_info['score'] ?? 0),
                'discount' => intval($user_info['pricediscount'] ?? 0),
                'limit_usertest' => intval($user_info['limit_usertest'] ?? 0)
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_countries(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $uRole = $user_info['agent'] ?? 'f';
        $userTier = match($uRole) {
            'n' => 'agent',
            'n2' => 'agent_adv',
            'seller' => 'seller',
            default => 'user'
        };
        $isAgentGroup = in_array($uRole, ['n', 'n2', 'seller']) ? 'agents' : '___none___';
        $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active' AND (
            (allowed_tiers IS NOT NULL AND allowed_tiers != '' AND FIND_IN_SET(:tier, allowed_tiers))
            OR ((allowed_tiers IS NULL OR allowed_tiers = '') AND (agent = :agent OR agent = 'all' OR agent = :ag_group))
        ) AND type != 'Manualsale' ORDER BY display_order, id");
        $stmt->bindParam(':tier', $userTier);
        $stmt->bindParam(':agent', $uRole);
        $stmt->bindParam(':ag_group', $isAgentGroup);
        $stmt->execute();
        $panel_list = [];
        $setting = select("setting", "*", null, null, "select");
        ;
        $is_note = false;
        if ($setting['statusnamecustom'] == 'onnamecustom')
            $is_note = true;
        if ($setting['statusnoteforf'] == "0" && $user_info['agent'] == "f")
            $is_note = false;
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (in_array(usernameMethodKey($result['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
                $is_username = true;
            } else {
                $is_username = false;
            }
            $statuscustomvolume = panelAgentValue($result['customvolume'], $user_info['agent']);
            if (intval($statuscustomvolume) == 1 && $result['type'] != "Manualsale") {
                $is_custom = true;
            } else {
                $is_custom = false;
            }
            $hidden_users = json_decode($result['hide_user'] ?? '', true);
            if (is_array($hidden_users) && in_array($user_info['id'], $hidden_users))
                continue;
            $panel_list[] = [
                'id' => $result['code_panel'],
                'code_panel' => $result['code_panel'],
                'name' => $result['name_panel'],
                'name_panel' => $result['name_panel'],
                'country_name' => $result['name_panel'],
                'is_custom' => $is_custom,
                'is_username' => $is_username,
                'is_note' => $is_note
            ];
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $panel_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_categories(array $data, string $method): void
{
    global $pdo, $setting, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $setting = select("setting", "*", null, null, "select");
        if ($setting['statuscategorygenral'] == "offcategorys") {
            echo json_encode(array(
                'status' => true,
                'msg' => "Successful",
                'obj' => []
            ));
            return;
        }
        $stmt = $pdo->prepare("SELECT * FROM category ORDER BY display_order, id");
        $stmt->execute();
        $category_list = [];
        $target_panel_id = $data['id_panel'] ?? ($data['country_id'] ?? null);
        if (empty($target_panel_id) || $target_panel_id === 'undefined') {
            $firstPanel = select("marzban_panel", "*", "status", "active", "select");
            if ($firstPanel) {
                $target_panel_id = $firstPanel['code_panel'];
            }
        }
        $panel = select("marzban_panel", "*", "code_panel", $target_panel_id, "select");
        if (!$panel && !empty($target_panel_id)) {
            $panel = select("marzban_panel", "*", "name_panel", $target_panel_id, "select");
        }
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)",
                'obj' => []
            ));
            return;
        }
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stmts = $pdo->prepare("SELECT * FROM product WHERE (Location = :location OR Location = '/all') AND category = :category AND agent = :agent AND status_product != 'disable'");
            $stmts->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
            $stmts->bindParam(':category', $result['remark'], PDO::PARAM_STR);
            $stmts->bindParam(':agent', $user_info['agent']);
            $stmts->execute();
            if ($stmts->rowCount() == 0)
                continue;
            $category_list[] = [
                'id' => $result['id'],
                'name' => $result['remark'],
            ];
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $category_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_time_ranges(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $setting = select("setting", "*", null, null, "select");
        if ($setting['statuscategory'] == "offcategory") {
            echo json_encode(array(
                'status' => true,
                'msg' => "Successful",
                'obj' => []
            ));
            return;
        }
        $category_time_list = [];
        $target_panel_id = $data['id_panel'] ?? ($data['country_id'] ?? null);
        if (empty($target_panel_id) || $target_panel_id === 'undefined') {
            $firstPanel = select("marzban_panel", "*", "status", "active", "select");
            if ($firstPanel) {
                $target_panel_id = $firstPanel['code_panel'];
            }
        }
        $panel = select("marzban_panel", "*", "code_panel", $target_panel_id, "select");
        if (!$panel && !empty($target_panel_id)) {
            $panel = select("marzban_panel", "*", "name_panel", $target_panel_id, "select");
        }
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)",
                'obj' => []
            ));
            return;
        }
        $stmt = $pdo->prepare("SELECT (Service_time) FROM product WHERE (Location = :name_panel OR Location = '/all') AND  agent = :agent");
        $stmt->bindValue(':agent', $user_info['agent'], PDO::PARAM_STR);
        $stmt->bindValue(':name_panel', $panel['name_panel'], PDO::PARAM_STR);
        $stmt->execute();
        $montheproduct = array_flip(array_flip($stmt->fetchAll(PDO::FETCH_COLUMN)));
        if (in_array("1", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['1day'],
                'day' => 1
            );
        }
        if (in_array("7", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['7day'],
                'day' => 7
            );
        }
        if (in_array("31", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['1'],
                'day' => 31
            );
        }
        if (in_array("30", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['1'],
                'day' => 30
            );
        }
        if (in_array("61", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['2'],
                'day' => 61
            );
        }
        if (in_array("60", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['2'],
                'day' => 60
            );
        }
        if (in_array("91", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['3'],
                'day' => 91
            );
        }
        if (in_array("90", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['3'],
                'day' => 90
            );
        }
        if (in_array("121", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['4'],
                'day' => 121
            );
        }
        if (in_array("120", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['4'],
                'day' => 120
            );
        }
        if (in_array("181", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['6'],
                'day' => 181
            );
        }
        if (in_array("180", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['6'],
                'day' => 180
            );
        }
        if (in_array("365", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['365'],
                'day' => 365
            );
        }
        if (in_array("0", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['byVolume'],
                'day' => 0
            );
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $category_time_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_services(array $data, string $method): void
{
    global $pdo, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $product_list = [];
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $target_panel_id = $data['id_panel'] ?? ($data['country_id'] ?? null);
        if (empty($target_panel_id) || $target_panel_id === 'undefined') {
            $firstPanel = select("marzban_panel", "*", "status", "active", "select");
            if ($firstPanel) {
                $target_panel_id = $firstPanel['code_panel'];
            }
        }
        $panel = select("marzban_panel", "*", "code_panel", $target_panel_id, "select");
        if (!$panel && !empty($target_panel_id)) {
            $panel = select("marzban_panel", "*", "name_panel", $target_panel_id, "select");
        }
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)",
                'obj' => []
            ));
            return;
        }
        $category_remark = null;
        $category_remarks = "";
        $user_agent = in_array($user_info['agent'] ?? '', ['n', 'n2']) ? $user_info['agent'] : 'f';
        $queryParams = [':loc' => $panel['name_panel'], ':ag' => $user_agent];
        $selected_category_id = isset($data['category_id']) ? $data['category_id'] : null;
        if (!empty($data['category_id'])) {
            $category_remark = select("category", "*", "id", $data['category_id'], "select");
            if (!is_array($category_remark) || !isset($category_remark['remark'])) {
                echo json_encode([
                    'status' => false,
                    'msg' => "category not found!(invalid category_id)",
                ]);
                return;
            }
            $category_remarks = "AND category = :catrem";
            $queryParams[':catrem'] = $category_remark['remark'];
            $selected_category_id = $category_remark['id'];
        }
        $time_range_day = "";
        if ($data['time_range_day'] != 0) {
            $time_range_day = "AND Service_time = :trd";
            $queryParams[':trd'] = $data['time_range_day'];
        }
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :ag AND status_product != 'disable' $category_remarks $time_range_day ORDER BY display_order, id");
        $stmt->execute($queryParams);
        $product_list = [];
        $countorder = null;
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hide_panel = json_decode($result['hide_panel'], true);
            if (!is_array($hide_panel)) {
                $hide_panel = [];
            }
            if (in_array($panel['name_panel'], $hide_panel))
                continue;
            if ($result['one_buy_status'] == "1") {
                if ($countorder === null) {
                    $stmts2 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE Status != 'Unpaid' AND id_user = :mp4");
                    $stmts2->execute([':mp4' => $user_info['id']]);
                    $countorder = (int) $stmts2->fetchColumn();
                }
                if ($countorder != 0)
                    continue;
            }
            if (intval($user_info['pricediscount']) != 0) {
                $resultper = ($result['price_product'] * $user_info['pricediscount']) / 100;
                $result['price_product'] = $result['price_product'] - $resultper;
            }
            $product_list[] = [
                'id' => $result['code_product'],
                'name' => $result['name_product'],
                'description' => $result['note'],
                'price' => $result['price_product'],
                'traffic_gb' => $result['Volume_constraint'],
                'time_days' => intval($result['Service_time']),
                'category_id' => $selected_category_id,
                'country_id' => $panel['code_panel'],
                'time_range_id' => $result['Service_time'],
                'limit_ip' => isset($result['limit_ip']) ? intval($result['limit_ip']) : 1,
                'extra_user_prices' => json_decode($result['extra_user_prices'] ?? '{}', true),
                'custom_volume_enabled' => intval($result['custom_volume_enabled'] ?? 0),
                'min_volume_gb' => intval($result['min_volume_gb'] ?? 10),
                'max_volume_gb' => intval($result['max_volume_gb'] ?? 200),
                'price_per_gb' => intval($result['price_per_gb'] ?? 5000)
            ];
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $product_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_custom_price(array $data, string $method): void
{
    global $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $target_panel_id = $data['id_panel'] ?? ($data['country_id'] ?? null);
        if (empty($target_panel_id) || $target_panel_id === 'undefined') {
            $firstPanel = select("marzban_panel", "*", "status", "active", "select");
            if ($firstPanel) {
                $target_panel_id = $firstPanel['code_panel'];
            }
        }
        $panel = select("marzban_panel", "*", "code_panel", $target_panel_id, "select");
        if (!$panel && !empty($target_panel_id)) {
            $panel = select("marzban_panel", "*", "name_panel", $target_panel_id, "select");
        }
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)",
                'obj' => []
            ));
            return;
        }
        $agentKey = $user_info['agent'];
        $statuscustomvolume = panelAgentValue($panel['customvolume'], $agentKey);
        $mainvolume = panelAgentValue($panel['mainvolume'], $agentKey);
        $maxvolume = panelAgentValue($panel['maxvolume'], $agentKey);
        $maintime = panelAgentValue($panel['maintime'], $agentKey);
        $maxtime = panelAgentValue($panel['maxtime'], $agentKey);
        $traffic_price = panelAgentValue($panel['pricecustomvolume'], $agentKey);
        $time_price = panelAgentValue($panel['pricecustomtime'], $agentKey);
        if (intval($statuscustomvolume) == 1 && $panel['type'] != "Manualsale") {
            $price = ($traffic_price * intval($data['traffic_gb'])) + ($time_price * intval($data['time_days']));
        } else {
            $price = false;
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => array(
                'price' => $price,
                'traffic_min' => intval($mainvolume),
                'traffic_max' => intval($maxvolume),
                'time_min' => intval($maintime),
                'time_max' => intval($maxtime)
            )
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_purchase(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $ManagePanel, $errorreport, $porsantreport, $buyreport, $usercheck;

    if ($method !== "POST") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be POST",
        ]);
        return;
    }
    if (!empty($data['custom_username']) && !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{1,31}$/', (string) $data['custom_username'])) {
        http_response_code(400);
        echo json_encode(array(
            'status' => false,
            'msg' => strip_tags($textbotlang['users']['invalidusername'])
        ));
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $data['country_id'] ?? '', "select");
    if (empty($panel)) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['panelMissing']
        ));
        return;
    }
    $hidden_users = json_decode($panel['hide_user'] ?? '', true);
    $pAgent = $panel['agent'] ?? 'all';
    $uAgent = $usercheck['agent'] ?? 'f';
    $panelAllowed = ($pAgent === 'all' || empty($pAgent) || $pAgent === $uAgent || ($pAgent === 'agents' && in_array($uAgent, ['n', 'n2'])));
    if ($panel['status'] != "active" || !$panelAllowed || $panel['type'] == "Manualsale" || (is_array($hidden_users) && in_array($usercheck['id'], $hidden_users))) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['panelInactive']
        ));
        return;
    }
    if (is_numeric($panel['limit_panel'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE Status IN ('active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold') AND Service_location = ?");
        $stmt->execute([$panel['name_panel']]);
        if ((int) $stmt->fetchColumn() >= intval($panel['limit_panel'])) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => strip_tags($textbotlang['users']['sell']['panelCapacityFull'])
            ));
            return;
        }
    }
    $user_info = $usercheck;
    if (empty($data['custom_service'])) {
        $product = select("product", "*", "code_product", $data['service_id'] ?? '', "select");
        if (!empty($product)) {
            $productLocation = $product['Location'] ?? '';
            $allowedLocation = ($productLocation === $panel['name_panel'] || $productLocation === '/all');
            $hide_panel = json_decode($product['hide_panel'] ?? '', true);
            if (!is_array($hide_panel)) {
                $hide_panel = [];
            }
            $blocked = !$allowedLocation
                || ($product['agent'] ?? null) !== $user_info['agent']
                || ($product['status_product'] ?? '') == "disable"
                || in_array($panel['name_panel'], $hide_panel);
            if (!$blocked && ($product['one_buy_status'] ?? null) == "1") {
                $stmtOneBuy = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE Status != 'Unpaid' AND id_user = :uid");
                $stmtOneBuy->execute([':uid' => $user_info['id']]);
                if ((int) $stmtOneBuy->fetchColumn() != 0) {
                    $blocked = true;
                }
            }
            if ($blocked) {
                http_response_code(403);
                echo json_encode(array(
                    'status' => false,
                    'msg' => $textbotlang['users']['sell']['productNotFound']
                ));
                return;
            }
            if (!empty($product['custom_volume_enabled'])) {
                $custom_vol = isset($data['custom_volume_gb']) ? intval($data['custom_volume_gb']) : 0;
                $min_v = intval($product['min_volume_gb'] ?: 10);
                $max_v = intval($product['max_volume_gb'] ?: 200);
                $p_per_gb = intval($product['price_per_gb'] ?: 5000);
                if ($custom_vol < $min_v || $custom_vol > $max_v) {
                    http_response_code(400);
                    echo json_encode(array(
                        'status' => false,
                        'msg' => "حجم انتخابی باید بین {$min_v} تا {$max_v} گیگابایت باشد."
                    ));
                    return;
                }
                $product['Volume_constraint'] = $custom_vol;
                $product['price_product'] = $custom_vol * $p_per_gb;
            }
        }
    } else {
        $agentKey = $user_info['agent'];
        if (intval(panelAgentValue($panel['customvolume'], $agentKey)) !== 1 || $panel['type'] == "Manualsale") {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
        $mainvolume = panelAgentValue($panel['mainvolume'], $agentKey);
        $maxvolume = panelAgentValue($panel['maxvolume'], $agentKey);
        $maintime = panelAgentValue($panel['maintime'], $agentKey);
        $maxtime = panelAgentValue($panel['maxtime'], $agentKey);
        $custompricevalue = panelAgentValue($panel['pricecustomvolume'], $agentKey);
        $customtimevalueprice = panelAgentValue($panel['pricecustomtime'], $agentKey);
        $customsrvice = $data['custom_service'];
        if (!is_array($customsrvice) || !isset($customsrvice['traffic_gb'], $customsrvice['time_days'])
            || !is_numeric($customsrvice['traffic_gb']) || !is_numeric($customsrvice['time_days'])) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
        $product = array(
            'code_product' => "customvolume",
            'name_product' => $textbotlang['users']['customSellVolume']['title'],
            'Volume_constraint' => (int) $customsrvice['traffic_gb'],
            'Service_time' => (int) $customsrvice['time_days'],
            'Location' => $panel['name_panel'],
            'category' => null,
            'price_product' => ((int) $customsrvice['traffic_gb'] * $custompricevalue) + ((int) $customsrvice['time_days'] * $customtimevalueprice)
        );
        if (intval($product['Volume_constraint']) > $maxvolume or intval($product['Volume_constraint']) < $mainvolume) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
        if (intval($product['Service_time']) > $maxtime or intval($product['Service_time']) < $maintime) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidTimeRestart']
            ));
            return;
        }
        if ($product['price_product'] <= 0) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
    }
    if (empty($product)) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['productNotFound']
        ));
        return;
    }
    if (intval($user_info['pricediscount']) != 0) {
        $result = ($product['price_product'] * $user_info['pricediscount']) / 100;
        $product['price_product'] = $product['price_product'] - $result;
        sendmessage($user_info['id'], sprintf($textbotlang['users']['Discount']['discountapplied'], $user_info['pricediscount']), null, 'HTML');
    }

    // Multi-user calculation (1 to 5 users or 0 unlimited)
    $prod_limit = isset($product['limit_ip']) ? intval($product['limit_ip']) : 1;
    if ($prod_limit === 0) {
        $user_limit = 0;
    } else {
        $user_limit = isset($data['user_limit']) ? intval($data['user_limit']) : ($prod_limit > 0 ? $prod_limit : 1);
        if ($user_limit < 1 || $user_limit > 5) {
            $user_limit = ($prod_limit > 0 ? $prod_limit : 1);
        }
        if ($user_limit > 1) {
            $product['price_product'] = calculateUserLimitPrice($product['price_product'], $user_limit, $product['extra_user_prices'] ?? null);
        }
    }
    $price_product = $product['price_product'];

    $charged = false;
    if ($price_product > 0) {
        $min_balance = 0;
        $error_msg = $textbotlang['users']['Balance']['lessThanPrice'];
        $stmt = $pdo->prepare("UPDATE user SET Balance = Balance - :price WHERE id = :id AND Balance - :price_check >= :min_balance");
        $stmt->execute([':price' => $price_product, ':id' => $user_info['id'], ':price_check' => $price_product, ':min_balance' => $min_balance]);
        if ($stmt->rowCount() === 0) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $error_msg
            ));
            return;
        }
        $charged = true;
    }

    $randomString = bin2hex(random_bytes(4));

    $rolledBack = false;
    $refundOnFailure = function () use ($pdo, $user_info, $price_product, $randomString, &$charged, &$rolledBack) {
        if ($rolledBack) {
            return;
        }
        $rolledBack = true;

        try {
            $stmt = $pdo->prepare("DELETE FROM invoice WHERE id_invoice = :id_invoice");
            $stmt->execute([':id_invoice' => $randomString]);
        } catch (Exception $e) {
            error_log("Failed to roll back invoice {$randomString}: " . $e->getMessage());
        }

        if (!$charged) {
            return;
        }

        try {
            $stmt = $pdo->prepare("UPDATE user SET Balance = Balance + :price WHERE id = :id");
            $stmt->execute([':price' => $price_product, ':id' => $user_info['id']]);
            $charged = false;
        } catch (Exception $e) {
            error_log("Failed to refund user {$user_info['id']}: " . $e->getMessage());
        }
    };

    try {
        $custom_user_input = trim($data['custom_username'] ?? '');
        if (!empty($custom_user_input)) {
            $clean_uname = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $custom_user_input));
            if (strlen($clean_uname) >= 3) {
                $username_ac = $clean_uname;
            } else {
                $username_ac = generateUsername($user_info['id'], $panel['MethodUsername'], $user_info['username'], $randomString, null, $panel['namecustom'], $user_info['namecustom']);
                $username_ac = strtolower($username_ac);
            }
        } else {
            $username_ac = generateUsername($user_info['id'], $panel['MethodUsername'], $user_info['username'], $randomString, null, $panel['namecustom'], $user_info['namecustom']);
            $username_ac = strtolower($username_ac);
        }
        $DataUserOut = $ManagePanel->DataUser($panel['name_panel'], $username_ac);
        if (isset($DataUserOut['username']) || rowExists("invoice", "username", $username_ac)) {
            $refundOnFailure();
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['usernameExists']
            ));
            return;
        }
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => false,
        ));
        $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,note,refral,notifctions,limit_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?)");
        $Status = "active";
        $date = time();
        $custom_note = isset($data['custom_note']) && strlen(strval($data['custom_note'])) > 1 ? $data['custom_note'] : null;
        $stmt->execute([$user_info['id'], $randomString, $username_ac, $date, $panel['name_panel'], $product['name_product'], $price_product, $product['Volume_constraint'], $product['Service_time'], $Status, $custom_note, $user_info['affiliates'], $notifctions, $user_limit]);
        $datetimestep = strtotime("+" . $product['Service_time'] . "days");
        if ($product['Service_time'] == 0) {
            $datetimestep = 0;
        } else {
            $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
        }
        $datac = array(
            'expire' => $datetimestep,
            'data_limit' => $product['Volume_constraint'] * pow(1024, 3),
            'from_id' => $user_info['id'],
            'username' => $user_info['username'],
            'type' => 'buy',
            'limitip' => $user_limit
        );
        $dataoutput = $ManagePanel->createUser($panel['name_panel'], $product['code_product'], $username_ac, $datac);
        if (!is_array($dataoutput) || ($dataoutput['username'] ?? null) == null) {
            $refundOnFailure();

            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['subscriptionError']
            ));

            $errorDetail = json_encode(is_array($dataoutput) ? ($dataoutput['msg'] ?? null) : null);
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorSubscriptionCreateAdmin'], $errorDetail, $user_info['id'], $user_info['username'], $panel['name_panel']);
            sendReport($texterros, $setting['Channel_Report'], $errorreport);
            return;
        }
    } catch (Throwable $e) {
        error_log("Purchase failed for user {$user_info['id']}: " . $e->getMessage());
        $refundOnFailure();

        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['subscriptionError']
        ));
        return;
    }

    $config = "";
    $output_config_link = $panel['sublink'] == "onsublink" ? ($dataoutput['subscription_url'] ?? "") : "";
    if ($panel['config'] == "onconfig" && is_array($dataoutput['configs'] ?? null)) {
        foreach ($dataoutput['configs'] as $link) {
            $config .= "\n" . $link;
        }
    }
    $textbotlang['textbot']['afterPay'] = $panel['type'] == "Manualsale" ? $textbotlang['textbot']['manual'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $panel['type'] == "WGDashboard" ? $textbotlang['textbot']['wgDashboard'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $panel['type'] == "ibsng" || $panel['type'] == "mikrotik" ? $textbotlang['textbot']['afterPayIbsng'] : $textbotlang['textbot']['afterPay'];
    if (intval($product['Service_time']) == 0)
        $product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if (intval($product['Volume_constraint']) == 0)
        $product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $textbotlang['textbot']['afterPay']);
    $textcreatuser = str_replace('{name_service}', $product['name_product'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $panel['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $product['Service_time'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $product['Volume_constraint'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
    $textcreatuser = str_replace('{links}', $config, $textcreatuser);
    $textcreatuser = str_replace('{links2}', $output_config_link, $textcreatuser);
    sendMessageService($panel, $dataoutput['configs'] ?? null, $output_config_link, $user_info['username'], null, $textcreatuser, $randomString, $user_info['id'], __DIR__ . '/../images.jpg', wireguard: $dataoutput['wireguard'] ?? []);
    if (in_array(usernameMethodKey($panel['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user_info['number_username']) + 1;
        update("user", "number_username", $value, "id", $user_info['id']);
        if (in_array(usernameMethodKey($panel['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($setting['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE name_product != :name_product AND id_user = :id_user");
    $stmt->bindParam(':id_user', $user_info['id']);
    $stmt->bindParam(':name_product', $textbotlang['common']['labels']['testServiceName']);
    $stmt->execute();
    $countinvoice = (int) $stmt->fetchColumn();
    if ($affiliatescommission['status_commission'] == "oncommission" && ($user_info['affiliates'] != null && intval($user_info['affiliates']) != 0)) {
        if ($marzbanporsant_one_buy['porsant_one_buy'] == "on_buy_porsant") {
            if ($countinvoice <= 1) {
                $result = ($product['price_product'] * $setting['affiliatespercentage']) / 100;
                $user_Balance = select("user", "*", "id", $user_info['affiliates'], "select");
                $Balance_prim = $user_Balance['Balance'] + $result;
                if (intval($setting['scorestatus']) == 1) {
                    sendmessage($user_info['affiliates'], $textbotlang['users']['affiliates']['pointsEarned2'], null, 'html');
                    $scorenew = $user_Balance['score'] + 2;
                    update("user", "score", $scorenew, "id", $user_info['affiliates']);
                }
                update("user", "Balance", $Balance_prim, "id", $user_info['affiliates']);
                $result = number_format($result);
                $dateacc = date('Y/m/d H:i:s');
                $textadd = sprintf($textbotlang['users']['affiliates']['commissionPaidMiniapp'], $result);
                $textreportport = sprintf($textbotlang['Admin']['reportgroup']['commissionPaidMiniapp'], $result, $user_info['affiliates'], $user_info['id'], $dateacc);
                if (strlen($setting['Channel_Report']) > 0) {
                    telegram('sendmessage', [
                        'chat_id' => $setting['Channel_Report'],
                        'message_thread_id' => $porsantreport,
                        'text' => $textreportport,
                        'parse_mode' => "HTML"
                    ]);
                }
                sendmessage($user_info['affiliates'], $textadd, null, 'HTML');
            }
        } else {
            $result = ($product['price_product'] * $setting['affiliatespercentage']) / 100;
            $user_Balance = select("user", "*", "id", $user_info['affiliates'], "select");
            $Balance_prim = $user_Balance['Balance'] + $result;
            if (intval($setting['scorestatus']) == 1) {
                sendmessage($user_info['affiliates'], $textbotlang['users']['affiliates']['pointsEarned2b'], null, 'html');
                $scorenew = $user_Balance['score'] + 2;
                update("user", "score", $scorenew, "id", $user_info['affiliates']);
            }
            update("user", "Balance", $Balance_prim, "id", $user_info['affiliates']);
            $result = number_format($result);
            $dateacc = date('Y/m/d H:i:s');
            $textadd = sprintf($textbotlang['users']['affiliates']['commissionPaidMiniapp2'], $result);
            $textreportport = sprintf($textbotlang['Admin']['reportgroup']['commissionPaidMiniapp2'], $result, $user_info['affiliates'], $user_info['id'], $dateacc);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $porsantreport,
                    'text' => $textreportport,
                    'parse_mode' => "HTML"
                ]);
            }
            sendmessage($user_info['affiliates'], $textadd, null, 'HTML');
        }
    }
    if (intval($setting['scorestatus']) == 1) {
        sendmessage($user_info['id'], $textbotlang['users']['affiliates']['pointsEarned1'], null, 'html');
        $scorenew = $user_info['score'] + 1;
        update("user", "score", $scorenew, "id", $user_info['id']);
    }
    $balanceformatsell = number_format(select("user", "Balance", "id", $user_info['id'], "select")['Balance'], 0);
    $textonebuy = "";
    if ($countinvoice == 1) {
        $textonebuy = $textbotlang['common']['labels']['firstPurchase'];
    }
    $balanceformatsellbefore = number_format($user_info['Balance'], 0);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $user_info['id']],
            ],
        ]
    ]);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['accountCreatedMiniapp'], $textonebuy, $user_info['id'], $user_info['username'], $username_ac, $panel['name_panel'], $product['name_product'], $product['Service_time'], $product['Volume_constraint'], $balanceformatsellbefore, $balanceformatsell, $randomString, $user_info['agent'], $user_info['number'], $product['category'], $product['price_product'], $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
    $subscription_url = $output_config_link ?: ($dataoutput['subscription_url'] ?? "");
    $configs = $dataoutput['configs'] ?? [];
    echo json_encode(array(
        'status' => true,
        'success' => true,
        'msg' => 'ok',
        'message' => 'ok',
        'order_id' => $randomString,
        'service' => array(
            'id' => $randomString,
            'username' => $username_ac,
            'status' => 'active',
            'expire' => $datetimestep,
            'subscription_url' => $subscription_url,
            'configs' => $configs
        ),
        'obj' => array(
            'id' => $randomString,
            'username' => $username_ac,
            'status' => 'active',
            'expire' => $datetimestep,
            'subscription_url' => $subscription_url,
            'configs' => $configs
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function mini_wallet_info(array $data, string $method): void
{
    global $pdo, $tokencheck;
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }

    $min_charge = intval(getPaySettingValue('minbalanceiranpay4', '50000'));
    if ($min_charge < 50000) { $min_charge = 50000; }
    $max_charge = intval(getPaySettingValue('maxbalanceiranpay4', '1500000'));
    if ($max_charge <= 0) { $max_charge = 1500000; }

    echo json_encode([
        'status' => true,
        'msg' => 'ok',
        'obj' => [
            'balance' => intval($user_info['Balance']),
            'min_charge' => $min_charge,
            'max_charge' => $max_charge,
            'gateway' => 'hooshpay',
            'gateway_name' => 'هوش‌پی'
        ]
    ]);
}

function mini_payment_methods(array $data, string $method): void
{
    global $pdo, $tokencheck;

    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد', 'obj' => []]);
        return;
    }

    // Get user agent role and limits from PaySetting (matching bot)
    $userRole = $user_info['agent'] ?? 'n';
    $minbalanceData = json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'] ?? '{}', true) ?: [];
    $maxbalanceData = json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'] ?? '{}', true) ?: [];

    $userMin = (int)($minbalanceData[$userRole] ?? ($minbalanceData['n'] ?? 20000));
    $userMax = (int)($maxbalanceData[$userRole] ?? ($maxbalanceData['n'] ?? 100000000));

    $calcLimits = function(string $gwKey, int $defaultMin, int $defaultMax) use ($userMin, $userMax) {
        $gwMin = (int) (getPaySettingValue("minbalance{$gwKey}", $defaultMin) ?: $defaultMin);
        $gwMax = (int) (getPaySettingValue("maxbalance{$gwKey}", $defaultMax) ?: $defaultMax);
        $effMin = max($userMin, $gwMin);
        $effMax = ($gwMax > 0) ? min($userMax, $gwMax) : $userMax;
        if ($effMax < $effMin && $gwMax > 0) $effMax = $gwMax;
        return [$effMin, $effMax];
    };

    $methods = [];

    // 1. HooshPay / AbanGateway (iranpay4)
    $abangateway4 = getPaySettingValue("statusiranpay4", "offiranpay4");
    $apiiranpay4 = trim((string) getPaySettingValue("apiiranpay4", ""));
    if ($abangateway4 === "oniranpay4" && $apiiranpay4 !== "" && $apiiranpay4 !== "0") {
        [$minAmt, $maxAmt] = $calcLimits('iranpay4', 50000, 1500000);
        $methods[] = [
            'id' => 'iranpay4',
            'title' => 'درگاه پرداخت ریالی (هوش‌پی / کارت شتاب)',
            'icon' => 'card',
            'type' => 'online',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'badge' => 'پیشنهادی'
        ];
    }

    // 2. ZarinPal
    $zarinpal = getPaySettingValue("zarinpalstatus");
    if ($zarinpal === "onzarinpal") {
        [$minAmt, $maxAmt] = $calcLimits('zarinpal', 20000, 1000000);
        $methods[] = [
            'id' => 'zarinpal',
            'title' => 'درگاه پرداخت زرین‌پال (ZarinPal)',
            'icon' => 'card',
            'type' => 'online',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'badge' => 'مستقیم'
        ];
    }

    // 3. Aqaye Pardakht
    $aqayepardakht = getPaySettingValue("statusaqayepardakht");
    if ($aqayepardakht === "onaqayepardakht") {
        [$minAmt, $maxAmt] = $calcLimits('aqayepardakht', 20000, 1000000);
        $methods[] = [
            'id' => 'aqayepardakht',
            'title' => 'درگاه آقای پرداخت (کارت شتاب)',
            'icon' => 'card',
            'type' => 'online',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'badge' => 'سریع'
        ];
    }

    // 4. Variza
    $variza = getPaySettingValue("variza_status", "offvariza");
    if ($variza === "onvariza") {
        [$minAmt, $maxAmt] = $calcLimits('variza', 20000, 1000000);
        $methods[] = [
            'id' => 'variza',
            'title' => 'پرداخت امن واریزا (کارت به کارت هوشمند)',
            'icon' => 'card',
            'type' => 'online',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'badge' => 'خودکار'
        ];
    }

    // Rates calculation for Crypto & Stars matching bot
    $rates = function_exists('rate_arze') ? rate_arze() : null;
    $usdRate = (!empty($rates['USD']) && $rates['USD'] > 10000) ? (float)$rates['USD'] : 95000.0;

    $customStarPrice = intval(select("PaySetting", "ValuePay", "NamePay", "price_per_star", "select")['ValuePay'] ?? 0);
    if ($customStarPrice <= 0) {
        $ratesStar = function_exists('rate_arze') ? rate_arze(['USD', 'Ton']) : null;
        $usdForStars = (!empty($ratesStar['USD']) && $ratesStar['USD'] > 10000) ? (float)$ratesStar['USD'] : 95000.0;
        $customStarPrice = max(1, round($usdForStars * 0.016));
    }

    // 5. NOWPayments / Crypto
    $nowstatus = getPaySettingValue("statusnowpayment");
    if ($nowstatus === "1" || getPaySettingValue("nowpaymentstatus") === "onnowpayment") {
        [$minAmt, $maxAmt] = $calcLimits('nowpayment', 270000, 100000000);
        $methods[] = [
            'id' => 'nowpayment',
            'title' => 'ارز دیجیتال (NOWPayments / تتر، ترون و...)',
            'icon' => 'crypto',
            'type' => 'crypto',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'usd_rate' => $usdRate,
            'rate_hint' => 'نرخ هر دلار: ' . number_format($usdRate) . ' تومان',
            'badge' => 'کریپتو'
        ];
    }

    // 5.1 Plisio Crypto
    $plisiostatus = getPaySettingValue("nowpaymentstatus");
    if ($plisiostatus === "onnowpayment") {
        [$minAmt, $maxAmt] = $calcLimits('plisio', 20000, 100000000);
        $methods[] = [
            'id' => 'plisio',
            'title' => 'درگاه کریپتو Plisio (تتر، بیتکوین، ترون)',
            'icon' => 'crypto',
            'type' => 'crypto',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'usd_rate' => $usdRate,
            'rate_hint' => 'نرخ هر دلار: ' . number_format($usdRate) . ' تومان',
            'badge' => 'Plisio'
        ];
    }

    // 5.2 Telegram Stars
    $statusstar = getPaySettingValue("statusstar");
    if ($statusstar === "1" || $statusstar === "on" || intval($statusstar) === 1) {
        [$minAmt, $maxAmt] = $calcLimits('star', 50000, 20000000);
        $methods[] = [
            'id' => 'startelegrams',
            'title' => 'پرداخت با استارز تلگرام (Telegram Stars ⭐)',
            'icon' => 'stars',
            'type' => 'stars',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'star_rate' => $customStarPrice,
            'rate_hint' => 'نرخ هر استارز: ' . number_format($customStarPrice) . ' تومان',
            'badge' => '⭐ Stars'
        ];
    }

    // 6. Card to Card
    $PaySettingcard = getPaySettingValue("Cartstatus", "offcard");
    if ($PaySettingcard === "oncard") {
        $cardRow = select("card_number", "*", null, null, "select");
        if ($cardRow) {
            [$minAmt, $maxAmt] = $calcLimits('cart', 20000, 1000000);
            $methods[] = [
                'id' => 'cart_to_offline',
                'title' => 'کارت به کارت (انتقال شتابی)',
                'icon' => 'card',
                'type' => 'card',
                'min_amount' => $minAmt,
                'max_amount' => $maxAmt,
                'card_number' => $cardRow['card_number'] ?? '',
                'card_name' => $cardRow['card_name'] ?? '',
                'badge' => 'شتاب'
            ];
        }
    }

    // Digital Tron offline
    $affilnecurrency = getPaySettingValue("digistatus");
    if ($affilnecurrency === "ondigi") {
        [$minAmt, $maxAmt] = $calcLimits('digitaltron', 20000, 1000000);
        $methods[] = [
            'id' => 'digitaltron',
            'title' => 'پرداخت با ترون (TRX)',
            'icon' => 'crypto',
            'type' => 'crypto',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'badge' => 'TRX'
        ];
    }

    if (empty($methods)) {
        [$minAmt, $maxAmt] = $calcLimits('iranpay4', 50000, 1500000);
        $methods[] = [
            'id' => 'iranpay4',
            'title' => 'درگاه پرداخت آنلاین شتابی',
            'icon' => 'card',
            'type' => 'online',
            'min_amount' => $minAmt,
            'max_amount' => $maxAmt,
            'badge' => 'آنلاین'
        ];
    }

    echo json_encode([
        'status' => true,
        'msg' => 'ok',
        'user_limits' => [
            'role' => $userRole,
            'min_amount' => $userMin,
            'max_amount' => $userMax
        ],
        'obj' => $methods
    ]);
}


function mini_charge_wallet(array $data, string $method): void
{
    global $pdo, $tokencheck;
    if ($method !== "POST") {
        echo json_encode(['status' => false, 'msg' => 'Method invalid; must be POST']);
        return;
    }

    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }

    $amount = isset($data['amount']) ? intval($data['amount']) : 0;
    $gateway = isset($data['gateway']) ? trim((string)$data['gateway']) : 'iranpay4';

    // Min & Max validation matching bot settings and user role
    $userRole = $user_info['agent'] ?? 'n';
    $minbalanceData = json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'] ?? '{}', true) ?: [];
    $maxbalanceData = json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'] ?? '{}', true) ?: [];
    $userMin = (int)($minbalanceData[$userRole] ?? ($minbalanceData['n'] ?? 20000));
    $userMax = (int)($maxbalanceData[$userRole] ?? ($maxbalanceData['n'] ?? 100000000));

    $gwKey = $gateway;
    if ($gateway === 'startelegrams') $gwKey = 'star';
    elseif ($gateway === 'cart_to_offline') $gwKey = 'cart';

    $gwMin = (int) getPaySettingValue("minbalance{$gwKey}", 0);
    $gwMax = (int) getPaySettingValue("maxbalance{$gwKey}", 0);
    $effMin = max($userMin, $gwMin);
    $effMax = ($gwMax > 0) ? min($userMax, $gwMax) : $userMax;
    if ($effMax < $effMin && $gwMax > 0) $effMax = $gwMax;

    if ($amount < $effMin) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'msg' => 'حداقل مبلغ شارژ برای این درگاه ' . number_format($effMin) . ' تومان می‌باشد.'
        ]);
        return;
    }
    if ($effMax > 0 && $amount > $effMax) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'msg' => 'حداکثر مبلغ شارژ برای این درگاه ' . number_format($effMax) . ' تومان می‌باشد.'
        ]);
        return;
    }

    $randomString = bin2hex(random_bytes(5));
    $dateacc = date('Y/m/d H:i:s');

    if ($gateway === 'zarinpal') {
        $pay = createPayZarinpal($amount, $randomString);
        if (empty($pay['data']['authority']) || ($pay['data']['code'] ?? 0) != 100) {
            http_response_code(500);
            echo json_encode(['status' => false, 'msg' => 'خطا در ایجاد تراکنش زرین‌پال. لطفاً مجدداً تلاش کنید.']);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method, dec_not_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'zarinpal', $pay['data']['authority']]);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'zarinpal',
                'payment_url' => 'https://www.zarinpal.com/pg/StartPay/' . $pay['data']['authority']
            ]
        ]);
        return;
    } elseif ($gateway === 'aqayepardakht') {
        $pay = createPayaqayepardakht($amount, $randomString);
        if (empty($pay['transid']) || empty($pay['status']) || $pay['status'] !== 'success') {
            http_response_code(500);
            echo json_encode(['status' => false, 'msg' => 'خطا در ساخت لینک درگاه آقای پرداخت.']);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method, dec_not_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'aqayepardakht', $pay['transid']]);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'aqayepardakht',
                'payment_url' => 'https://panel.aqayepardakht.ir/startpay/' . $pay['transid']
            ]
        ]);
        return;
    } elseif ($gateway === 'variza') {
        $pay = createPayvariza($amount, $randomString);
        if (empty($pay['status']) || empty($pay['data']['payment_url'])) {
            http_response_code(500);
            echo json_encode(['status' => false, 'msg' => 'خطا در ارتباط با درگاه واریزا.']);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method, dec_not_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'variza', $randomString]);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'variza',
                'payment_url' => $pay['data']['payment_url']
            ]
        ]);
        return;
    } elseif ($gateway === 'cart_to_offline') {
        $cardRow = select("card_number", "*", null, null, "select");
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'cart to cart']);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'cart_to_offline',
                'type' => 'card',
                'card_number' => $cardRow['card_number'] ?? '',
                'card_name' => $cardRow['card_name'] ?? 'مدیریت',
                'payment_url' => null
            ]
        ]);
        return;
    } elseif ($gateway === 'startelegrams') {
        $customStarPrice = intval(select("PaySetting", "ValuePay", "NamePay", "price_per_star", "select")['ValuePay'] ?? 0);
        if ($customStarPrice > 0) {
            $starAmount = max(1, intval(ceil($amount / $customStarPrice)));
        } else {
            $rates = function_exists('rate_arze') ? rate_arze(['USD', 'Ton']) : null;
            $usd = (!empty($rates['USD']) && $rates['USD'] > 10000) ? (float)$rates['USD'] : 95000.0;
            $starRate = $usd * 0.016;
            $starAmount = max(1, intval(ceil($amount / $starRate)));
        }
        $straCreateLink = telegram('createInvoiceLink', [
            'title' => "شارژ کیف پول",
            'description' => "افزایش موجودی به مبلغ " . number_format($amount) . " تومان",
            'payload' => $randomString,
            'currency' => "XTR",
            'prices' => json_encode([
                [
                    'label' => "Stars",
                    'amount' => $starAmount
                ]
            ])
        ]);
        if (empty($straCreateLink['ok']) || empty($straCreateLink['result'])) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'msg' => 'خطا در ایجاد لینک استارز تلگرام: ' . ($straCreateLink['description'] ?? 'خطای تلگرام')
            ]);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'Star Telegram']);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'startelegrams',
                'type' => 'stars',
                'stars' => $starAmount,
                'invoice_link' => $straCreateLink['result'],
                'payment_url' => $straCreateLink['result']
            ]
        ]);
        return;
    } elseif ($gateway === 'plisio') {
        $rates = function_exists('rate_arze') ? rate_arze() : null;
        $usd = (!empty($rates['USD']) && $rates['USD'] > 10000) ? (float)$rates['USD'] : 95000.0;
        $usdprice = round($amount / $usd, 2);
        if ($usdprice < 0.5) $usdprice = 0.5;
        $pay = function_exists('plisio') ? plisio($randomString, $usdprice, $user_info['id']) : null;
        if (empty($pay['invoice_url'])) {
            $err = !empty($pay['message']) ? $pay['message'] : 'خطا در ارتباط با درگاه ارز دیجیتال Plisio';
            http_response_code(500);
            echo json_encode(['status' => false, 'msg' => $err]);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method, dec_not_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'plisio', $pay['txn_id'] ?? $randomString]);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'plisio',
                'payment_url' => $pay['invoice_url']
            ]
        ]);
        return;
    } elseif ($gateway === 'nowpayment') {
        $rates = function_exists('rate_arze') ? rate_arze() : null;
        $usd = (!empty($rates['USD']) && $rates['USD'] > 10000) ? (float) $rates['USD'] : 95000.0;
        $usdprice = round($amount / $usd, 2);
        if ($usdprice < 0.5) {
            $usdprice = 0.5;
        }
        $pay = nowPayments('invoice', $usdprice, $randomString, 'TopUp - ' . $user_info['id']);
        if (empty($pay['id']) || empty($pay['invoice_url'])) {
            $err = !empty($pay['message']) ? $pay['message'] : (!empty($pay['error']) ? $pay['error'] : 'خطا در ارتباط با درگاه ارز دیجیتال NOWPayments');
            http_response_code(500);
            echo json_encode(['status' => false, 'msg' => $err]);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method, dec_not_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'nowpayment', $pay['id']]);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'nowpayment',
                'payment_url' => $pay['invoice_url']
            ]
        ]);
        return;
    } elseif ($gateway === 'digitaltron') {
        $walletaddress = getPaySettingValue("walletaddress", "");
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, 'Unpaid', 'digitaltron']);
        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'digitaltron',
                'type' => 'tron',
                'wallet_address' => $walletaddress,
                'payment_url' => null
            ]
        ]);
        return;
    } else {
        // Default to iranpay4 / HooshPay
        $pay = createPayiranpay4($amount, $randomString);
        if (empty($pay['success']) || empty($pay['payment_link'])) {
            $err = !empty($pay['error']) ? $pay['error'] : (!empty($pay['message']) ? $pay['message'] : 'خطا در ساخت لینک پرداخت هوش‌پی');
            http_response_code(500);
            echo json_encode(['status' => false, 'msg' => $err]);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_info['id'], $randomString, $dateacc, $amount, "Unpaid", "AbanGateway"]);

        echo json_encode([
            'status' => true,
            'msg' => 'ok',
            'obj' => [
                'order_id' => $randomString,
                'amount' => $amount,
                'gateway' => 'iranpay4',
                'payment_url' => $pay['payment_link']
            ]
        ]);
        return;
    }
}


function mini_test_account(array $data, string $method): void
{
    global $pdo, $tokencheck, $textbotlang, $ManagePanel, $setting;
    if ($method !== "POST") {
        echo json_encode(['status' => false, 'msg' => 'Method invalid; must be POST']);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }
    $from_id = $user_info['id'];
    $admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN") ?: [];
    $is_admin = in_array($from_id, $admin_ids);
    $is_adv_agent = (($user_info['agent'] ?? '') === 'n2');

    if (!$is_admin) {
        if ($is_adv_agent) {
            $adv_mode = select("PaySetting", "ValuePay", "NamePay", "agent_adv_test_mode", "select")['ValuePay'] ?? 'unlimited';
            $adv_count = (int) (select("PaySetting", "ValuePay", "NamePay", "agent_adv_test_count", "select")['ValuePay'] ?? 100);
            if ($adv_mode === 'daily') {
                $today_midnight = strtotime('today midnight');
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE id_user = ? AND price_product = '0' AND time_sell >= ? AND Status != 'Unsuccessful'");
                $stmt_chk->execute([$from_id, $today_midnight]);
                $today_used = (int) $stmt_chk->fetchColumn();
                if ($today_used >= $adv_count) {
                    http_response_code(400);
                    echo json_encode(['status' => false, 'msg' => "شما به سقف مجاز ساخت اکانت تست روزانه خود ({$adv_count} عدد) رسیده‌اید."]);
                    return;
                }
            } elseif ($adv_mode === 'total') {
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE id_user = ? AND price_product = '0' AND Status != 'Unsuccessful'");
                $stmt_chk->execute([$from_id]);
                $total_used = (int) $stmt_chk->fetchColumn();
                if ($total_used >= $adv_count) {
                    http_response_code(400);
                    echo json_encode(['status' => false, 'msg' => "شما به سقف مجاز ساخت اکانت تست ({$adv_count} عدد) رسیده‌اید."]);
                    return;
                }
            }
        } else {
            if (intval($user_info['limit_usertest'] ?? 0) <= 0) {
                http_response_code(400);
                echo json_encode(['status' => false, 'msg' => 'سهمیه اکانت تست رایگان شما به اتمام رسیده است.']);
                return;
            }
        }
    }

    $req_panel_code = trim($data['code_panel'] ?? $data['panel_code'] ?? '');
    $user_role = $user_info['agent'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active' AND TestAccount = 'ONTestAccount' AND type != 'Manualsale' ORDER BY id ASC");
    $stmt->execute();
    $all_test_panels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $panel = null;
    foreach ($all_test_panels as $p) {
        if (!empty($p['hide_user'])) {
            $hu = json_decode($p['hide_user'], true);
            if (is_array($hu) && in_array($from_id, $hu)) continue;
        }
        if ($user_role !== 'n2' && strpos($p['name_panel'], 'نماینده پیشرفته') !== false) {
            continue;
        }
        if ($req_panel_code !== '' && ($p['code_panel'] === $req_panel_code || (string)$p['id'] === $req_panel_code)) {
            $panel = $p;
            break;
        }
        if (!$panel) {
            $panel = $p;
        }
    }

    if (!$panel) {
        http_response_code(500);
        echo json_encode(['status' => false, 'msg' => 'در حال حاضر سروری برای اکانت تست در دسترس نیست.']);
        return;
    }

    if (!$is_admin && !$is_adv_agent) {
        update("user", "limit_usertest", intval($user_info['limit_usertest']) - 1, "id", $from_id);
    }

    $randomString = bin2hex(random_bytes(4));
    $custom_name = trim($data['custom_name'] ?? '');
    if (!empty($custom_name) && !preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $custom_name)) {
        $custom_name = '';
    }
    $username_ac = generateUsername($from_id, $panel['MethodUsername'], $user_info['username'], $randomString, $custom_name, $panel['namecustom'], $user_info['namecustom']);
    $username_ac = strtolower($username_ac);
    $check_exist = $ManagePanel->DataUser($panel['name_panel'], $username_ac);
    if (isset($check_exist['username']) || rowExists("invoice", "username", $username_ac)) {
        $username_ac = rand(1000000, 9999999) . "_" . $username_ac;
    }

    $test_hours = intval($panel['time_usertest'] ?: 2);
    $test_mb = intval($panel['val_usertest'] ?: 250);
    $expire_ts = strtotime("+{$test_hours} hours");
    $datac = [
        'expire' => $expire_ts,
        'data_limit' => $test_mb * 1048576,
        'from_id' => $from_id,
        'username' => $user_info['username'],
        'type' => 'usertest'
    ];

    $notif = json_encode(['volume' => false, 'time' => false]);
    $stmt_inv = $pdo->prepare("INSERT INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status, notifctions, limit_ip) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'active', ?, 1)");
    $prod_name = $textbotlang['common']['labels']['testServiceName'] ?? $textbotlang['common']['labels']['testService5'] ?? 'اکانت تست رایگان';
    $stmt_inv->execute([$from_id, $randomString, $username_ac, time(), $panel['name_panel'], $prod_name, $test_mb, $test_hours, $notif]);

    $dataoutput = $ManagePanel->createUser($panel['name_panel'], "usertest", $username_ac, $datac);
    if (!is_array($dataoutput) || empty($dataoutput['username'])) {
        update("invoice", "Status", "Unsuccessful", "id_invoice", $randomString);
        if (!$is_admin && !$is_adv_agent) {
            update("user", "limit_usertest", intval($user_info['limit_usertest']), "id", $from_id);
        }
        http_response_code(500);
        echo json_encode(['status' => false, 'msg' => 'خطا در ایجاد اکانت در پنل سرور. لطفاً مجدداً تلاش کنید.']);
        return;
    }

    $sub_url = $dataoutput['subscription_url'] ?? $dataoutput['sub_url'] ?? $dataoutput['sublink'] ?? '';
    if (empty($sub_url)) {
        $du = $ManagePanel->DataUser($panel['name_panel'], $username_ac);
        if (!empty($du['subscription_url'])) {
            $sub_url = $du['subscription_url'];
        }
    }
    $configs = $dataoutput['configs'] ?? $dataoutput['links'] ?? [];
    if (!is_array($configs) && !empty($configs)) $configs = [$configs];

    // Telegram notification
    try {
        require_once __DIR__ . '/../botapi.php';
        $sub_display = $sub_url ? "\n🔗 <b>لینک اشتراک:</b>\n<code>{$sub_url}</code>\n" : "";
        $notif_text = "🎉 <b>اکانت تست رایگان شما با موفقیت فعال شد!</b>\n\n"
                    . "👤 نام کاربری: <code>{$username_ac}</code>\n"
                    . "⏳ مدت اعتبار: <b>{$test_hours} ساعت</b>\n"
                    . "📊 حجم مصرفی: <b>{$test_mb} مگابایت</b>\n"
                    . "📍 سرور: <b>" . htmlspecialchars($panel['name_panel']) . "</b>\n"
                    . $sub_display;
        telegram('sendmessage', [
            'chat_id' => $from_id,
            'text' => $notif_text,
            'parse_mode' => 'HTML'
        ]);
    } catch (Exception $e) {}

    echo json_encode([
        'status' => true,
        'msg' => 'اکانت تست رایگان با موفقیت فعال شد!',
        'obj' => [
            'username' => $username_ac,
            'sub_url' => $sub_url,
            'configs' => $configs,
            'expire_hours' => $test_hours,
            'volume_mb' => $test_mb,
            'panel_name' => $panel['name_panel']
        ]
    ]);
}

function mini_test_panels(array $data, string $method): void
{
    global $pdo, $tokencheck;
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }
    $user_id = $user_info['id'];
    $user_role = $user_info['agent'] ?? '';

    $stmt = $pdo->prepare("SELECT id, name_panel, code_panel, val_usertest, time_usertest, MethodUsername, hide_user FROM marzban_panel WHERE status = 'active' AND TestAccount = 'ONTestAccount' AND type != 'Manualsale' ORDER BY id ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $list = [];
    foreach ($rows as $r) {
        if (!empty($r['hide_user'])) {
            $hu = json_decode($r['hide_user'], true);
            if (is_array($hu) && in_array($user_id, $hu)) continue;
        }
        if ($user_role !== 'n2' && strpos($r['name_panel'], 'نماینده پیشرفته') !== false) {
            continue;
        }
        $list[] = [
            'id' => (int)$r['id'],
            'code_panel' => $r['code_panel'],
            'name_panel' => $r['name_panel'],
            'volume_mb' => intval($r['val_usertest'] ?: 250),
            'time_hours' => intval($r['time_usertest'] ?: 2),
            'custom_username' => in_array($r['MethodUsername'], ['customUsername', 'customUsernameRandom']),
        ];
    }
    echo json_encode(['status' => true, 'obj' => $list]);
}

function mini_support_messages(array $data, string $method): void
{
    global $pdo, $tokencheck;
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }
    $user_id = $user_info['id'];

    // Mark admin messages as read
    $stmt_up = $pdo->prepare("UPDATE support_messages SET is_read = 1 WHERE user_id = ? AND sender = 'admin' AND is_read = 0");
    $stmt_up->execute([$user_id]);

    $stmt = $pdo->prepare("SELECT id, user_id, sender, message, created_at FROM support_messages WHERE user_id = ? ORDER BY id ASC LIMIT 100");
    $stmt->execute([$user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'messages' => $messages
    ]);
}

function mini_support_send(array $data, string $method): void
{
    global $pdo, $tokencheck, $setting;
    if ($method !== "POST") {
        echo json_encode(['status' => false, 'msg' => 'Method must be POST']);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }
    $user_id = $user_info['id'];
    $message = trim($data['message'] ?? '');
    if ($message === '' || mb_strlen($message) > 3000) {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'متن پیام نمی‌تواند خالی باشد.']);
        return;
    }

    $now = time();
    $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, sender, message, created_at, is_read) VALUES (?, 'user', ?, ?, 0)");
    $stmt->execute([$user_id, $message, $now]);
    $new_id = $pdo->lastInsertId();

    // Alert admins via telegram
    try {
        require_once __DIR__ . '/../botapi.php';
        $uname = !empty($user_info['username']) ? '@' . $user_info['username'] : ($user_info['namecustom'] ?: $user_id);
        $alert_text = "💬 <b>پیام جدید پشتیبانی در چت آنلاین</b>\n\n"
                    . "👤 کاربر: <b>" . htmlspecialchars($uname) . "</b> (<code>{$user_id}</code>)\n"
                    . "📝 متن پیام:\n" . htmlspecialchars($message) . "\n\n"
                    . "🌐 <a href=\"https://{$domainhosts}/panel/support.php?user_id={$user_id}\">مشاهده و پاسخ در پنل وب</a>";

        $admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN") ?: [];
        foreach ($admin_ids as $aid) {
            telegram('sendmessage', [
                'chat_id' => $aid,
                'text' => $alert_text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]);
        }
    } catch (Exception $e) {}

    echo json_encode([
        'status' => true,
        'msg' => 'پیام با موفقیت ارسال شد.',
        'message' => [
            'id' => (int)$new_id,
            'user_id' => $user_id,
            'sender' => 'user',
            'message' => $message,
            'created_at' => $now
        ]
    ]);
}


function mini_redeem_giftcard(array $data, string $method): void
{
    global $pdo, $tokencheck;
    if ($method !== "POST") {
        echo json_encode(['status' => false, 'msg' => 'Method invalid; must be POST']);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }
    $code = trim((string)($data['code'] ?? ''));
    if ($code === '') {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'لطفاً کد کارت هدیه را وارد نمایید.']);
        return;
    }
    $gift = select("Giftcode", "*", "code", $code, "select");
    if (!$gift) {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'کد کارت هدیه وارد شده نامعتبر است یا وجود ندارد.']);
        return;
    }
    $stmt_used = $pdo->prepare("SELECT COUNT(*) FROM Giftcodeconsumed WHERE id_user = ? AND code = ?");
    $stmt_used->execute([$user_info['id'], $code]);
    if ((int)$stmt_used->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'این کارت هدیه قبلاً توسط شما استفاده شده است.']);
        return;
    }

    $gift_price = intval($gift['price']);
    $pdo->beginTransaction();
    try {
        $stmt_ins = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user, code) VALUES (?, ?)");
        $stmt_ins->execute([$user_info['id'], $code]);
        $stmt_up = $pdo->prepare("UPDATE user SET Balance = Balance + ? WHERE id = ?");
        $stmt_up->execute([$gift_price, $user_info['id']]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => false, 'msg' => 'خطا در ثبت کارت هدیه.']);
        return;
    }

    echo json_encode([
        'status' => true,
        'msg' => "مبلغ " . number_format($gift_price) . " تومان با موفقیت به کیف پول شما افزوده شد!",
        'obj' => [
            'added_balance' => $gift_price,
            'new_balance' => intval($user_info['Balance']) + $gift_price
        ]
    ]);
}

function mini_system_info(array $data, string $method): void
{
    global $pdo;
    $setting = select("setting", "*", null, null, "select") ?: [];
    $admin = select("admin", "username", null, null, "select") ?: [];

    echo json_encode([
        'status' => true,
        'msg' => 'ok',
        'obj' => [
            'bot_username' => $setting['bot_username'] ?? '',
            'channel' => $setting['Channel'] ?? '',
            'support_username' => $admin['username'] ?? '',
            'affiliate_percent' => intval($setting['affiliatespercentage'] ?? 10),
            'currency' => 'تومان'
        ]
    ]);
}


function mini_web_login_request(array $data, string $method): void
{
    global $pdo, $domainhosts, $usernamebot;
    if ($method !== 'POST') {
        echo json_encode(['status' => false, 'msg' => 'Method must be POST']);
        return;
    }

    $query = trim((string)($data['query'] ?? ''));
    if ($query === '') {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'لطفاً شناسه کاربری یا آیدی تلگرام خود را وارد نمایید.']);
        return;
    }

    $cleanQuery = ltrim($query, '@');

    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ? OR username = ? LIMIT 1");
    $stmt->execute([$cleanQuery, $cleanQuery]);
    $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRecord) {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => 'کاربری با این شناسه در ربات تلگرام یافت نشد. لطفاً ابتدا در ربات ' . (!empty($usernamebot) ? ('@' . ltrim($usernamebot, '@')) : '') . ' عضو شوید.'
        ]);
        return;
    }

    $userId = $userRecord['id'];
    $code = strval(random_int(10000, 99999));
    $authKey = bin2hex(random_bytes(16));
    $now = time();

    try {
        $pdo->prepare("UPDATE web_login_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$userId]);
        $stmt_ins = $pdo->prepare("INSERT INTO web_login_codes (user_id, code, auth_key, created_at, is_used) VALUES (?, ?, ?, ?, 0)");
        $stmt_ins->execute([$userId, $code, $authKey, $now]);
    } catch (Exception $e) {
        error_log("web_login_request DB error: " . $e->getMessage());
    }

    try {
        require_once __DIR__ . '/../botapi.php';
        $directUrl = "https://{$domainhosts}/app/?auth_key=" . $authKey;
        $msgText = "🔐 <b>درخواست ورود به نسخه وب Vold Cloud</b>\n\n"
                 . "کد تأیید ۵ رقمی شما:\n"
                 . "👉 <code>{$code}</code>\n\n"
                 . "⏱ این کد به مدت ۱۰ دقیقه معتبر است.\n\n"
                 . "🌐 همچنین می‌توانید با زدن دکمه زیر بدون نیاز به وارد کردن کد، مستقیماً وارد پنل شوید:";
        
        telegram('sendmessage', [
            'chat_id' => $userId,
            'text' => $msgText,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🚀 ورود مستقیم به نسخه وب (یک کلیک)', 'url' => $directUrl]]
                ]
            ])
        ]);
    } catch (Exception $e) {
        error_log("Failed to send web login code to telegram: " . $e->getMessage());
    }

    echo json_encode([
        'status' => true,
        'msg' => 'کد تأیید ۵ رقمی به تلگرام شما ارسال گردید.',
        'user_id' => $userId,
        'username' => $userRecord['username'] ?? ''
    ]);
}

function mini_web_login_verify(array $data, string $method): void
{
    global $pdo;
    if ($method !== 'POST') {
        echo json_encode(['status' => false, 'msg' => 'Method must be POST']);
        return;
    }

    $authKey = trim((string)($data['auth_key'] ?? ''));
    $code = trim((string)($data['code'] ?? ''));
    $query = trim((string)($data['query'] ?? ''));

    $tenMinutesAgo = time() - 600;
    $loginRow = null;

    if ($authKey !== '') {
        $stmt = $pdo->prepare("SELECT * FROM web_login_codes WHERE auth_key = ? AND is_used = 0 AND created_at >= ? LIMIT 1");
        $stmt->execute([$authKey, $tenMinutesAgo]);
        $loginRow = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($code !== '') {
        if ($query !== '') {
            $cleanQuery = ltrim($query, '@');
            $stmtUser = $pdo->prepare("SELECT id FROM user WHERE id = ? OR username = ? LIMIT 1");
            $stmtUser->execute([$cleanQuery, $cleanQuery]);
            $u = $stmtUser->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $stmt = $pdo->prepare("SELECT * FROM web_login_codes WHERE user_id = ? AND code = ? AND is_used = 0 AND created_at >= ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$u['id'], $code, $tenMinutesAgo]);
                $loginRow = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM web_login_codes WHERE code = ? AND is_used = 0 AND created_at >= ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$code, $tenMinutesAgo]);
            $loginRow = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!$loginRow) {
        http_response_code(400);
        echo json_encode(['status' => false, 'msg' => 'کد یا لینک ورود نامعتبر است یا منقضی شده است.']);
        return;
    }

    $pdo->prepare("UPDATE web_login_codes SET is_used = 1 WHERE id = ?")->execute([$loginRow['id']]);

    $userId = $loginRow['user_id'];
    $userRecord = select('user', '*', 'id', $userId, 'select');
    if (!$userRecord) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }

    $newToken = bin2hex(random_bytes(20));
    update('user', 'token', $newToken, 'id', $userId);

    echo json_encode([
        'status' => true,
        'msg' => 'ورود با موفقیت انجام شد.',
        'token' => $newToken,
        'user' => [
            'id' => $userRecord['id'],
            'username' => $userRecord['username'] ?? '',
            'name' => $userRecord['namecustom'] ?: 'کاربر',
            'balance' => intval($userRecord['Balance'] ?? 0),
            'group_type' => ($userRecord['agent'] === 'n2') ? 'نماینده پیشرفته' : (($userRecord['agent'] === 'seller') ? 'فروشنده' : (($userRecord['agent'] === 'n') ? 'نماینده' : 'کاربر عادی'))
        ]
    ]);
}

function mini_web_login_create_link(array $data, string $method): void
{
    global $pdo, $tokencheck, $domainhosts;
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if (!$user_info) {
        http_response_code(404);
        echo json_encode(['status' => false, 'msg' => 'کاربر یافت نشد']);
        return;
    }

    $authKey = bin2hex(random_bytes(16));
    $code = strval(random_int(10000, 99999));
    $now = time();

    try {
        $stmt = $pdo->prepare("INSERT INTO web_login_codes (user_id, code, auth_key, created_at, is_used) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$user_info['id'], $code, $authKey, $now]);
    } catch (Exception $e) {
        error_log("mini_web_login_create_link error: " . $e->getMessage());
    }

    $loginUrl = "https://{$domainhosts}/app/?auth_key=" . $authKey;

    echo json_encode([
        'status' => true,
        'msg' => 'ok',
        'url' => $loginUrl,
        'code' => $code
    ]);
}

match ($action) {
    'invoices' => mini_invoices($data, $method),
    'service' => mini_service($data, $method),
    'user_info' => mini_user_info($data, $method),
    'wallet_info' => mini_wallet_info($data, $method),
    'payment_methods' => mini_payment_methods($data, $method),
    'charge_wallet' => mini_charge_wallet($data, $method),
    'countries' => mini_countries($data, $method),
    'categories' => mini_categories($data, $method),
    'time_ranges' => mini_time_ranges($data, $method),
    'services' => mini_services($data, $method),
    'custom_price' => mini_custom_price($data, $method),
    'purchase' => mini_purchase($data, $method),
    'test_account' => mini_test_account($data, $method),
    'test_panels' => mini_test_panels($data, $method),
    'support_messages' => mini_support_messages($data, $method),
    'support_send' => mini_support_send($data, $method),
    'redeem_giftcard' => mini_redeem_giftcard($data, $method),
    'system_info' => mini_system_info($data, $method),
    'web_login_request' => mini_web_login_request($data, $method),
    'web_login_verify' => mini_web_login_verify($data, $method),
    'web_login_create_link' => mini_web_login_create_link($data, $method),
    default => sendJsonResponse(false, "Action Invalid", []),
};

