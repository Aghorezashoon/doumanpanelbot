<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: users.php');
    exit;
}

$user = db_fetch($pdo, "SELECT * FROM user WHERE id = ?", [$id]);
if (!$user) {
    flash('error', $textbotlang['panel']['userNotFound']);
    header('Location: users.php');
    exit;
}

if (!function_exists('clean_int_val')) {
    function clean_int_val($val): int {
        if (is_numeric($val)) return (int) $val;
        if (!is_string($val)) return 0;
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $val = str_replace($persian, range(0, 9), $val);
        $val = str_replace($arabic, range(0, 9), $val);
        $val = preg_replace('/[^\d-]/', '', $val);
        return (int) $val;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_post();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_invoice') {
        $invId = trim($_POST['id_invoice'] ?? '');
        if ($invId !== '') {
            $inv = db_fetch($pdo, "SELECT * FROM invoice WHERE id_invoice = ? AND id_user = ? LIMIT 1", [$invId, $id]);
            if ($inv) {
                $delPanel = !empty($_POST['delete_panel']);
                if ($delPanel && !empty($inv['username']) && !empty($inv['Service_location'])) {
                    try {
                        require_once __DIR__ . '/../panels.php';
                        $mp = new ManagePanel();
                        $mp->RemoveUser($inv['Service_location'], $inv['username']);
                    } catch (Exception $e) {
                        error_log("Failed to remove user from panel: " . $e->getMessage());
                    }
                }
                db_query($pdo, "DELETE FROM invoice WHERE id_invoice = ? AND id_user = ?", [$invId, $id]);
                flash('success', 'سرویس مورد نظر با موفقیت از پروفایل کاربر و ربات حذف گردید.');
            } else {
                flash('error', 'سرویس یافت نشد.');
            }
        }
        header("Location: user.php?id=" . $id);
        exit;
    }

    if ($action === 'add_balance') {
        $amount = clean_int_val($_POST['amount'] ?? 0);
        if ($amount >= 1) {
            db_query($pdo, "UPDATE user SET Balance = Balance + ? WHERE id = ?", [$amount, $id]);
            
            try {
                $dateacc = date('Y/m/d H:i:s');
                $randomString = bin2hex(random_bytes(5));
                db_query($pdo, "INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)", [
                    $id, $randomString, $dateacc, $amount, 'paid', 'add balance by admin', null
                ]);
            } catch (Exception $e) {}

            try {
                require_once __DIR__ . '/../../botapi.php';
                $heibalanceuser = number_format($amount, 0);
                $newBal = number_format(((int) ($user['Balance'] ?? 0)) + $amount, 0);
                $textadd = "✅ <b>افزایش موجودی توسط مدیریت</b>\n\n"
                         . "مبلغ <b>{$heibalanceuser}</b> تومان به موجودی حساب شما افزوده شد.\n"
                         . "موجودی فعلی شما: <b>{$newBal}</b> تومان";
                telegram('sendmessage', [
                    'chat_id' => $id,
                    'text' => $textadd,
                    'parse_mode' => 'HTML'
                ]);
            } catch (Exception $e) {}

            flash('success', number_format($amount) . ' تومان به موجودی کاربر افزوده شد.');
        } else {
            flash('error', 'لطفاً مبلغ معتبری وارد نمایید.');
        }
    } elseif ($action === 'deduct_balance') {
        $amount = clean_int_val($_POST['amount'] ?? 0);
        $curBal = (int) ($user['Balance'] ?? 0);
        if ($amount < 1) {
            flash('error', 'لطفاً مبلغ معتبری برای کسر وارد نمایید.');
        } else {
            db_query($pdo, "UPDATE user SET Balance = GREATEST(0, Balance - ?) WHERE id = ?", [$amount, $id]);
            
            try {
                $dateacc = date('Y/m/d H:i:s');
                $randomString = bin2hex(random_bytes(5));
                db_query($pdo, "INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)", [
                    $id, $randomString, $dateacc, $amount, 'paid', 'low balance by admin', null
                ]);
            } catch (Exception $e) {}

            try {
                require_once __DIR__ . '/../../botapi.php';
                $lowbalanceuser = number_format($amount, 0);
                $newBal = number_format(max(0, $curBal - $amount), 0);
                $textkam = "⚠️ <b>کسر موجودی توسط مدیریت</b>\n\n"
                         . "مبلغ <b>{$lowbalanceuser}</b> تومان از موجودی حساب شما کسر گردید.\n"
                         . "موجودی فعلی شما: <b>{$newBal}</b> تومان";
                telegram('sendmessage', [
                    'chat_id' => $id,
                    'text' => $textkam,
                    'parse_mode' => 'HTML'
                ]);
            } catch (Exception $e) {}

            flash('success', number_format($amount) . ' تومان از موجودی کاربر کسر گردید.');
        }
    } elseif ($action === 'set_balance') {
        $newBalance = clean_int_val($_POST['new_balance'] ?? 0);
        if ($newBalance < 0) $newBalance = 0;
        db_query($pdo, "UPDATE user SET Balance = ? WHERE id = ?", [$newBalance, $id]);
        
        try {
            $dateacc = date('Y/m/d H:i:s');
            $randomString = bin2hex(random_bytes(5));
            db_query($pdo, "INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)", [
                $id, $randomString, $dateacc, $newBalance, 'paid', 'set balance by admin', null
            ]);
        } catch (Exception $e) {}

        try {
            require_once __DIR__ . '/../../botapi.php';
            $formattedBal = number_format($newBalance, 0);
            $textSet = "ℹ️ <b>تنظیم موجودی توسط مدیریت</b>\n\n"
                     . "موجودی حساب کاربری شما روی <b>{$formattedBal}</b> تومان تنظیم گردید.";
            telegram('sendmessage', [
                'chat_id' => $id,
                'text' => $textSet,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {}

        flash('success', 'موجودی کاربر با موفقیت روی ' . number_format($newBalance) . ' تومان تنظیم شد.');
    } elseif ($action === 'set_role') {
        $newRole = $_POST['new_role'] ?? 'f';
        if (in_array($newRole, ['f', 'n', 'n2', 'seller', 'all'], true)) {
            db_query($pdo, "UPDATE user SET agent = ? WHERE id = ?", [$newRole, $id]);
            flash('success', $textbotlang['panel']['userGroupChangedPrefix'] . user_role_label($newRole) . $textbotlang['panel']['userGroupChangedSuffix']);
        }
    }

    header("Location: user.php?id=$id");
    exit;
}

$invoices = [];
$payments = [];
$referrals = [];

try {
    $invoices = db_fetchAll($pdo, "SELECT * FROM invoice WHERE id_user = ? ORDER BY time_sell DESC LIMIT 30", [$id]);
} catch (Exception $e) {
}

try {
    $payments = db_fetchAll($pdo, "SELECT * FROM Payment_report WHERE id_user = ? ORDER BY time DESC LIMIT 20", [$id]);
} catch (Exception $e) {
}

try {
    $referrals = db_fetchAll($pdo, "SELECT id, username, namecustom, Balance, register, agent FROM user WHERE affiliates = ? ORDER BY register DESC LIMIT 20", [$id]);
} catch (Exception $e) {
}

$balance = (int) ($user['Balance'] ?? 0);
$totalSpent = array_sum(array_column($invoices, 'price_product'));
$activeServices = count(array_filter($invoices, fn($inv) => ($inv['Status'] ?? '') === 'active'));
$expiredServices = count(array_filter($invoices, fn($inv) => in_array($inv['Status'] ?? '', ['end_of_time', 'end_of_volume', 'expired'])));
$paidCount = count(array_filter($payments, fn($p) => in_array($p['payment_Status'] ?? '', ['paid', 'success'])));
$convRate = count($payments) > 0 ? round($paidCount / count($payments) * 100) : 0;

$agent = $user['agent'] ?? 'f';
$isBlocked = ($user['User_Status'] ?? '') === 'block';
$fullName = $user['namecustom'] ?? '';
if ($fullName === 'none')
    $fullName = '';
$username = $user['username'] ?? '';
if ($username === 'none')
    $username = '';
$initials = mb_strtoupper(mb_substr($fullName ?: ($username ?: 'U'), 0, 1, 'UTF-8'), 'UTF-8');

$pageTitle = $fullName ?: ($username ? '@' . $username : $textbotlang['panel']['userNumberPrefix'] . $id);
$activeNav = 'users';
$showPageHead = false;
include __DIR__ . '/inc/layout_head.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px"
    class="fade-up">
    <a href="users.php" class="btn btn-ghost btn-sm"><?= icon('arrow-left', 14) ?> <?= $textbotlang['panel']['userProfileHeading'] ?></a>
    <?php if ($username): ?>
        <a href="https://t.me/<?= htmlspecialchars($username) ?>" target="_blank" rel="noopener"
            class="btn btn-ghost btn-sm">
            <?= icon('eye', 13) ?> <?= $textbotlang['panel']['userBackToUsersBtn'] ?>
        </a>
    <?php endif; ?>
</div>

<div class="stats u-stats fade-up" style="margin-bottom:18px">
    <div class="stat fade-up">
        <div class="stat-label"><?= $textbotlang['panel']['userIdLabel'] ?></div>
        <div class="stat-num"><?= number_format($balance) ?><small><?= $textbotlang['panel']['usernameLabel'] ?></small></div>
        <div class="stat-meta"><?= $textbotlang['panel']['userFirstNameLabel'] ?></div>
    </div>
    <div class="stat ok fade-up d1">
        <div class="stat-label"><?= $textbotlang['panel']['userBalanceLabel'] ?></div>
        <div class="stat-num">
            <?= $totalSpent >= 1_000_000
                ? number_format($totalSpent / 1_000_000, 1) . $textbotlang['panel']['userUnitMillionToman']
                : number_format($totalSpent) . $textbotlang['panel']['userUnitToman'] ?>
        </div>
        <div class="stat-meta"><?= count($invoices) ?> <?= $textbotlang['panel']['userGroupLabel'] ?></div>
    </div>
    <div class="stat warn fade-up d2">
        <div class="stat-label"><?= $textbotlang['panel']['userStatusLabel'] ?></div>
        <div class="stat-num"><?= $activeServices ?></div>
        <div class="stat-meta"><?= $expiredServices ?> <?= $textbotlang['panel']['userJoinDateLabel'] ?></div>
    </div>
    <div class="stat fade-up d3">
        <div class="stat-label"><?= $textbotlang['panel']['userCustomNameLabel'] ?></div>
        <div class="stat-num"><?= $convRate ?>%</div>
        <div class="stat-meta"><?= $paidCount ?> <?= $textbotlang['panel']['userPhoneLabel'] ?> <?= count($payments) ?></div>
    </div>
</div>

<div class="profile-grid u-profile-grid">

    <div class="u-sidebar" style="display:flex;flex-direction:column;gap:12px">

        <div class="card fade-up">
            <div class="profile-head">
                <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="profile-name"><?= htmlspecialchars($fullName ?: $textbotlang['panel']['userNoName']) ?></div>
                <?php if ($username): ?>
                    <div class="profile-handle">@<?= htmlspecialchars($username) ?></div>
                <?php endif; ?>
                <div style="margin-top:10px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                    <span class="tag <?= $isBlocked ? 'tag-no' : 'tag-ok' ?>">
                        <?= $isBlocked ? $textbotlang['panel']['userStatusBlocked'] : $textbotlang['panel']['userStatusActive'] ?>
                    </span>
                    <span class="tag <?= user_role_tag($agent) ?>">
                        <?= user_role_label($agent) ?>
                    </span>
                </div>
            </div>

            <div class="kv-list">
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['userAddBalanceTitle'] ?></span>
                    <span class="kv-val cm"><?= htmlspecialchars($user['id']) ?></span>
                </div>
                <?php if ($fullName): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userAddBalanceBtn'] ?></span>
                        <span class="kv-val"><?= htmlspecialchars($fullName) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($user['number']) && $user['number'] !== 'none'): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userChangeGroupTitle'] ?></span>
                        <span class="kv-val cm"><?= htmlspecialchars($user['number']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['userChangeGroupBtn'] ?></span>
                    <span class="kv-val" style="color:var(--ac)"><?= number_format($balance) ?> <?= $textbotlang['panel']['userBlockUserBtn'] ?></span>
                </div>
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['userUnblockUserBtn'] ?></span>
                    <span class="kv-val">
                        <span class="tag <?= user_role_tag($agent) ?>"><?= user_role_label($agent) ?></span>
                        <span class="cm cf"
                            style="margin-right:6px;font-size:.72rem"><?= htmlspecialchars($agent) ?></span>
                    </span>
                </div>
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['userServicesTabLabel'] ?></span>
                    <span class="kv-val"><?= safe_date($user['register'] ?? null) ?></span>
                </div>
                <?php if (!empty($user['affiliates']) && $user['affiliates'] !== '0'): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userTransactionsTabLabel'] ?></span>
                        <span class="kv-val cm" style="color:var(--ac)"><?= htmlspecialchars($user['affiliates']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((int) ($user['affiliatescount'] ?? 0) > 0): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userOrdersTabLabel'] ?></span>
                        <span class="kv-val"><?= number_format((int) $user['affiliatescount']) ?> <?= $textbotlang['panel']['userColService'] ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((int) ($user['score'] ?? 0) > 0): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userColStatus'] ?></span>
                        <span class="kv-val" style="color:var(--warn)">⭐ <?= number_format((int) $user['score']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($user['expire'])): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userColVolume'] ?></span>
                        <span class="kv-val"
                            style="<?= is_numeric($user['expire']) && (int) $user['expire'] < time() ? 'color:var(--no)' : '' ?>">
                            <?= safe_date($user['expire']) ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($user['codeInvitation'])): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userColTime'] ?></span>
                        <span class="kv-val cm"
                            style="color:var(--ac)"><?= htmlspecialchars($user['codeInvitation']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((int) ($user['message_count'] ?? 0) > 0): ?>
                    <div class="kv">
                        <span class="kv-key"><?= $textbotlang['panel']['userColPanel'] ?></span>
                        <span class="kv-val cn"><?= number_format((int) $user['message_count']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card fade-up d1">
            <div class="card-head">
                <div class="card-title"><?= $textbotlang['panel']['userNoServiceForUser'] ?></div>
            </div>
            <div style="padding:12px;display:flex;flex-direction:column;gap:6px">
                <button class="btn btn-primary btn-sm" style="justify-content:center" onclick="openModal('addModal')">
                    <?= icon('plus', 13) ?> <?= $textbotlang['panel']['userColAmount'] ?>
                </button>
                <button class="btn btn-no btn-sm" style="justify-content:center" onclick="openModal('deductModal')">
                    <?= icon('minus', 13) ?> <?= $textbotlang['panel']['userDeductBalanceBtn'] ?? 'کسر موجودی' ?>
                </button>
                <button class="btn btn-secondary btn-sm" style="justify-content:center" onclick="openModal('setBalanceModal')">
                    <?= icon('edit', 13) ?> تنظیم دقیق موجودی
                </button>
                <button class="btn btn-ghost btn-sm" style="justify-content:center" onclick="openModal('roleModal')">
                    <?= icon('users', 13) ?> <?= $textbotlang['panel']['userColMethod'] ?>
                </button>
                <div style="height:1px;background:var(--bd);margin:2px 0"></div>
                <?php if ($isBlocked): ?>
                    <a href="user_action.php?action=unblock&id=<?= $id ?>&_csrf=<?= csrf_token() ?>&back=user.php"
                        class="btn btn-ok btn-sm" style="justify-content:center" data-confirm="<?= htmlspecialchars($textbotlang['panel']['userConfirmUnblockUser']) ?>">
                        <?= icon('check', 13) ?> <?= $textbotlang['panel']['userColDate'] ?>
                    </a>
                <?php else: ?>
                    <a href="user_action.php?action=block&id=<?= $id ?>&_csrf=<?= csrf_token() ?>&back=user.php"
                        class="btn btn-no btn-sm" style="justify-content:center" data-confirm="<?= htmlspecialchars($textbotlang['panel']['userConfirmBlockUser']) ?>">
                        <?= icon('block', 13) ?> <?= $textbotlang['panel']['userNoTransactionForUser'] ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="u-main-col" style="display:flex;flex-direction:column;gap:16px">

        <div class="card fade-up">
            <div class="card-head">
                <div class="u-tab-bar" style="display:flex;gap:4px;background:var(--sf2);border-radius:7px;padding:3px">
                    <button class="btn btn-sm" id="tabOrders" onclick="switchTab('orders')"
                        style="background:var(--ac);color:var(--btn-ac-text, #fff);border-radius:5px;font-size:.75rem">
                        <?= $textbotlang['panel']['userColProduct'] ?>
                    </button>
                    <button class="btn btn-sm" id="tabPay" onclick="switchTab('pay')"
                        style="background:transparent;color:var(--mute);border-radius:5px;font-size:.75rem;border:none">
                        <?= $textbotlang['panel']['userColPrice'] ?>
                    </button>
                    <?php if (count($referrals) > 0): ?>
                        <button class="btn btn-sm" id="tabRefs" onclick="switchTab('refs')"
                            style="background:transparent;color:var(--mute);border-radius:5px;font-size:.75rem;border:none">
                            <?= $textbotlang['panel']['userNoOrderForUser'] ?>
                            <span
                                style="background:var(--acs);color:var(--ac);padding:1px 6px;border-radius:99px;font-size:.65rem">
                                <?= count($referrals) ?>
                            </span>
                        </button>
                    <?php endif; ?>
                </div>
                <a href="invoice.php?q=<?= urlencode($id) ?>" class="btn-link" style="font-size:.75rem"><?= $textbotlang['panel']['userColTrackingCode'] ?></a>
            </div>

            <div id="paneOrders">
                <div class="tbl-wrap">
                    <table class="tbl-lg">
                        <thead>
                            <tr>
                                <th><?= $textbotlang['panel']['userDetailTitle'] ?></th>
                                <th><?= $textbotlang['panel']['userCloseBtn'] ?></th>
                                <th><?= $textbotlang['panel']['userRoleNormalUser'] ?></th>
                                <th><?= $textbotlang['panel']['userRoleAgent'] ?></th>
                                <th><?= $textbotlang['panel']['userRoleAdvancedAgent'] ?></th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty" style="padding:30px">
                                            <p><?= $textbotlang['panel']['userColId'] ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else:
                                $statusMap = [
                                    'active' => ['tag-ok', $textbotlang['panel']['userStatusActive2']],
                                    'end_of_time' => ['tag-warn', $textbotlang['panel']['userStatusNearTimeEnd']],
                                    'end_of_volume' => ['tag-no', $textbotlang['panel']['userStatusNearVolumeEnd']],
                                    'sendedwarn' => ['tag-warn', $textbotlang['panel']['userNotifAllSent']],
                                    'send_on_hold' => ['tag-plain', $textbotlang['panel']['userStatusWaiting']],
                                    'unpaid' => ['tag-plain', $textbotlang['panel']['userStatusUnpaid']],
                                ];
                                foreach ($invoices as $inv):
                                    [$tagClass, $label] = $statusMap[$inv['Status'] ?? ''] ?? ['tag-plain', $inv['Status'] ?? '—'];
                                    ?>
                                    <tr>
                                        <td class="cs"
                                            style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            <?= htmlspecialchars($inv['name_product'] ?? '—') ?>
                                        </td>
                                        <td class="cn cs" style="white-space:nowrap">
                                            <?= number_format((int) ($inv['price_product'] ?? 0)) ?> <span class="cf"><?= $textbotlang['panel']['userColName'] ?></span>
                                        </td>
                                        <td class="cn cf"><?= htmlspecialchars($inv['Volume'] ?? '—') ?></td>
                                        <td class="cf" style="white-space:nowrap">
                                            <?= safe_date($inv['time_sell'] ?? null, 'Y/m/d') ?>
                                        </td>
                                        <td><span class="tag <?= $tagClass ?>"><?= $label ?></span></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('آیا از حذف این سرویس از پروفایل این کاربر اطمینان دارید؟');" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_invoice">
                                                <input type="hidden" name="id_invoice" value="<?= htmlspecialchars($inv['id_invoice'] ?? '') ?>">
                                                <input type="hidden" name="delete_panel" value="1">
                                                <button type="submit" class="btn btn-sm" style="background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:2px 7px;font-size:.72rem;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:3px;" title="حذف سرویس">
                                                    <?= icon('trash', 12) ?> <span>حذف</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="panePay" style="display:none">
                <div class="tbl-wrap">
                    <table class="tbl-md">
                        <thead>
                            <tr>
                                <th><?= $textbotlang['panel']['userColCreatedAt'] ?></th>
                                <th><?= $textbotlang['panel']['userWalletLabel'] ?></th>
                                <th><?= $textbotlang['panel']['userTotalPurchaseLabel'] ?></th>
                                <th><?= $textbotlang['panel']['userTotalServicesLabel'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty" style="padding:30px">
                                            <p><?= $textbotlang['panel']['userAffiliateCountLabel'] ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else:
                                $methodLabels = [
                                    'cart to cart' => $textbotlang['panel']['userMethodCardToCard'],
                                    'add balance by admin' => $textbotlang['panel']['userMethodAdminAdd'],
                                    'low balance by admin' => $textbotlang['panel']['userMethodAdminDeduct'],
                                    'zarinpal' => $textbotlang['panel']['userMethodZarinpal'],
                                    'aqayepardakht' => $textbotlang['panel']['userMethodAqayePardakht'],
                                    'plisio' => 'Plisio',
                                    'nowpayment' => 'NowPayment',
                                    'Star Telegram' => $textbotlang['panel']['userMethodTelegramStar'],
                                    'Currency Rial 1' => $textbotlang['panel']['userMethodRial1'],
                                    'Currency Rial tow' => $textbotlang['panel']['userMethodRial2'],
                                    'Currency Rial 3' => $textbotlang['panel']['userMethodRial3'],
                                    'arze digital offline' => $textbotlang['panel']['userMethodCrypto'],
                                ];
                                $payStatusMap = [
                                    'paid' => ['tag-ok', $textbotlang['panel']['userStatusSuccess']],
                                    'Unpaid' => ['tag-no', $textbotlang['panel']['userStatusFailed']],
                                    'expire' => ['tag-plain', $textbotlang['panel']['userStatusExpired']],
                                    'reject' => ['tag-no', $textbotlang['panel']['userStatusRejected']],
                                    'waiting' => ['tag-warn', $textbotlang['panel']['userStatusWaiting2']],
                                    'pending' => ['tag-warn', $textbotlang['panel']['userStatusWaiting3']],
                                ];
                                foreach ($payments as $p):
                                    $payStatus = $p['payment_Status'] ?? '';
                                    [$tagClass, $label] = $payStatusMap[$payStatus] ?? ['tag-plain', $payStatus ?: '—'];
                                    $method = $methodLabels[$p['Payment_Method'] ?? ''] ?? ($p['Payment_Method'] ?? '—');
                                    ?>
                                    <tr>
                                        <td class="cn cs" style="white-space:nowrap">
                                            <?= number_format((int) ($p['price'] ?? 0)) ?> <span class="cf"><?= $textbotlang['panel']['userReferrerLabel'] ?></span>
                                        </td>
                                        <td style="font-size:.82rem"><?= htmlspecialchars($method) ?></td>
                                        <td class="cf" style="white-space:nowrap">
                                            <?= safe_date($p['time'] ?? null, 'Y/m/d H:i') ?>
                                        </td>
                                        <td><span class="tag <?= $tagClass ?>"><?= $label ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (count($referrals) > 0): ?>
                <div id="paneRefs" style="display:none">
                    <div class="tbl-wrap">
                        <table class="tbl-md">
                            <thead>
                                <tr>
                                    <th><?= $textbotlang['panel']['userNoteLabel'] ?></th>
                                    <th><?= $textbotlang['panel']['userEditNoteBtn'] ?></th>
                                    <th><?= $textbotlang['panel']['userSendMessageBtn'] ?></th>
                                    <th><?= $textbotlang['panel']['userSendMessageTitle'] ?></th>
                                    <th><?= $textbotlang['panel']['userMessagePlaceholder'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referrals as $ref):
                                    $refName = $ref['namecustom'] ?? '';
                                    if ($refName === 'none')
                                        $refName = '';
                                    $refUname = $ref['username'] ?? '';
                                    if ($refUname === 'none')
                                        $refUname = '';
                                    $refAgent = $ref['agent'] ?? 'f';
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="user.php?id=<?= (int) $ref['id'] ?>" class="cm" style="color:var(--ac)">
                                                <?= htmlspecialchars($ref['id']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($refName): ?>
                                                <span class="cs"><?= htmlspecialchars(trunc($refName, 16)) ?></span>
                                            <?php elseif ($refUname): ?>
                                                <span class="cm"
                                                    style="color:var(--ac)">@<?= htmlspecialchars(trunc($refUname, 14)) ?></span>
                                            <?php else: ?>
                                                <span class="cf">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cn" style="white-space:nowrap">
                                            <?= number_format((int) ($ref['Balance'] ?? 0)) ?> <span class="cf"><?= $textbotlang['panel']['userSendBtn'] ?></span>
                                        </td>
                                        <td>
                                            <span class="tag <?= user_role_tag($refAgent) ?>">
                                                <?= user_role_label($refAgent) ?>
                                            </span>
                                        </td>
                                        <td class="cf"><?= safe_date($ref['register'] ?? null, 'm/d') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<div class="modal-veil" id="addModal">
    <div class="modal">
        <div class="modal-head">
            <h3><?= $textbotlang['panel']['userCancelBtn'] ?></h3>
            <button class="modal-x" onclick="closeModal('addModal')"><?= icon('close', 14) ?></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_balance">
                <div class="field">
                    <label><?= $textbotlang['panel']['userDetailUser'] ?></label>
                    <input type="number" name="amount" class="input" placeholder="<?= htmlspecialchars($textbotlang['panel']['userAmountPlaceholder']) ?>" min="1000" required>
                    <span class="field-hint"><?= $textbotlang['panel']['userDetailAmount'] ?> <strong><?= number_format($balance) ?> <?= $textbotlang['panel']['userDetailMethod'] ?></strong></span>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary"><?= icon('plus', 13) ?> <?= $textbotlang['panel']['userDetailStatus'] ?></button>
                <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')"><?= $textbotlang['panel']['userDetailDate'] ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal-veil" id="deductModal">
    <div class="modal">
        <div class="modal-head">
            <h3><?= $textbotlang['panel']['userDeductBalanceTitle'] ?? 'کسر موجودی کاربر' ?></h3>
            <button class="modal-x" onclick="closeModal('deductModal')"><?= icon('close', 14) ?></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="deduct_balance">
                <div class="field">
                    <label>مبلغ کسر از موجودی (تومان):</label>
                    <input type="number" id="deductAmountInput" name="amount" class="input" placeholder="مثال: ۵۰۰۰۰" min="1" required>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;gap:8px;flex-wrap:wrap">
                        <span class="field-hint">موجودی فعلی: <strong><?= number_format($balance) ?> تومان</strong></span>
                        <?php if ($balance > 0): ?>
                            <button type="button" class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:.7rem" onclick="document.getElementById('deductAmountInput').value = <?= $balance ?>;">
                                <?= $textbotlang['panel']['userDeductAllBtn'] ?? 'کسر کل موجودی' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-no" style="justify-content:center"><?= icon('minus', 13) ?> <?= $textbotlang['panel']['userDeductConfirmBtn'] ?? 'کسر از موجودی' ?></button>
                <button type="button" class="btn btn-ghost" onclick="closeModal('deductModal')"><?= $textbotlang['panel']['userDetailDate'] ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal-veil" id="setBalanceModal">
    <div class="modal">
        <div class="modal-head">
            <h3>تنظیم دستی موجودی کاربر</h3>
            <button class="modal-x" onclick="closeModal('setBalanceModal')"><?= icon('close', 14) ?></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="set_balance">
                <div class="field">
                    <label>موجودی جدید کاربر (تومان):</label>
                    <input type="number" id="setBalanceInput" name="new_balance" class="input" value="<?= $balance ?>" min="0" required>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;gap:8px;flex-wrap:wrap">
                        <span class="field-hint">موجودی فعلی: <strong><?= number_format($balance) ?> تومان</strong></span>
                        <button type="button" class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:.7rem;color:var(--danger)" onclick="document.getElementById('setBalanceInput').value = 0;">
                            صفر کردن موجودی (۰ تومان)
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary" style="justify-content:center"><?= icon('check', 13) ?> ذخیره موجودی جدید</button>
                <button type="button" class="btn btn-ghost" onclick="closeModal('setBalanceModal')"><?= $textbotlang['panel']['userDetailDate'] ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal-veil" id="roleModal">
    <div class="modal">
        <div class="modal-head">
            <h3><?= $textbotlang['panel']['userDetailProduct'] ?></h3>
            <button class="modal-x" onclick="closeModal('roleModal')"><?= icon('close', 14) ?></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="set_role">
                <div class="field">
                    <label><?= $textbotlang['panel']['userDetailService'] ?></label>
                    <select name="new_role" class="select">
                        <option value="f" <?= $agent === 'f' ? 'selected' : '' ?>><?= $textbotlang['panel']['userRoleFreeUser'] ?></option>
                        <option value="n" <?= $agent === 'n' ? 'selected' : '' ?>><?= $textbotlang['panel']['userRoleNormalAgent'] ?></option>
                        <option value="n2" <?= $agent === 'n2' ? 'selected' : '' ?>><?= $textbotlang['panel']['userRoleAdvancedAgent2'] ?></option>
                        <option value="seller" <?= $agent === 'seller' ? 'selected' : '' ?>>فروشنده (seller)</option>
                    </select>
                    <span class="field-hint">
                        <?= $textbotlang['panel']['userDetailPanel'] ?> <strong><?= user_role_label($agent) ?></strong>
                        <span class="cm" style="color:var(--mute)">(<?= htmlspecialchars($agent) ?>)</span>
                    </span>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary"><?= icon('check', 13) ?> <?= $textbotlang['panel']['userDetailTrackingCode'] ?></button>
                <button type="button" class="btn btn-ghost" onclick="closeModal('roleModal')"><?= $textbotlang['panel']['userDetailDescription'] ?></button>
            </div>
        </form>
    </div>
</div>

<script src="js/profile.js"></script>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>