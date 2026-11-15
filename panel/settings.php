<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    csrf_check_post();
    $cur = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $admin = db_fetch($pdo, "SELECT * FROM admin WHERE username = ?", [$_SESSION['admin_user']]);
    $storedPassword = (string) ($admin['password'] ?? '');
    $storedIsHash = str_starts_with($storedPassword, '$2') || str_starts_with($storedPassword, '$argon2');
    $valid = $storedIsHash ? password_verify($cur, $storedPassword) : hash_equals($storedPassword, $cur);

    if (!$valid) {
        flash('error', $textbotlang['panel']['settingsCurrentPasswordWrong']);
    } elseif ($new !== $confirm) {
        flash('error', $textbotlang['panel']['settingsNewPasswordMismatch']);
    } elseif (strlen($new) < 6) {
        flash('error', $textbotlang['panel']['settingsPasswordMinLength']);
    } else {
        db_query(
            $pdo,
            "UPDATE admin SET password = ? WHERE username = ?",
            [password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $_SESSION['admin_user']]
        );
        flash('success', $textbotlang['panel']['settingsPasswordChanged']);
    }
    header('Location: settings.php?tab=security');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_stars_price') {
    csrf_check_post();
    $starPrice = (int)($_POST['price_per_star'] ?? 0);
    $check_exist = db_fetch($pdo, "SELECT * FROM PaySetting WHERE NamePay = 'price_per_star' LIMIT 1");
    if ($check_exist) {
        db_query($pdo, "UPDATE PaySetting SET ValuePay = ? WHERE NamePay = 'price_per_star'", [(string)$starPrice]);
    } else {
        db_query($pdo, "INSERT INTO PaySetting (NamePay, ValuePay) VALUES ('price_per_star', ?)", [(string)$starPrice]);
    }
    flash('success', 'قیمت هر استارز تلگرام با موفقیت ذخیره شد.');
    header('Location: settings.php?tab=stars');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_dollar_rate') {
    csrf_check_post();
    $manualDollar = (int)($_POST['dollar_price_manual'] ?? 0);
    $check_exist = db_fetch($pdo, "SELECT * FROM PaySetting WHERE NamePay = 'dollar_price_manual' LIMIT 1");
    if ($check_exist) {
        db_query($pdo, "UPDATE PaySetting SET ValuePay = ? WHERE NamePay = 'dollar_price_manual'", [(string)$manualDollar]);
    } else {
        db_query($pdo, "INSERT INTO PaySetting (NamePay, ValuePay) VALUES ('dollar_price_manual', ?)", [(string)$manualDollar]);
    }
    @unlink(sys_get_temp_dir() . '/douman_rates_cache.json');
    flash('success', 'تنظیمات نرخ دلار با موفقیت ذخیره شد.');
    header('Location: settings.php?tab=currency');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_agent_test_limit') {
    csrf_check_post();
    $advMode = in_array($_POST['agent_adv_test_mode'] ?? '', ['unlimited', 'daily', 'total']) ? $_POST['agent_adv_test_mode'] : 'unlimited';
    $advCount = max(1, (int)($_POST['agent_adv_test_count'] ?? 100));

    $check_exist = db_fetch($pdo, "SELECT * FROM PaySetting WHERE NamePay = 'agent_adv_test_mode' LIMIT 1");
    if ($check_exist) {
        db_query($pdo, "UPDATE PaySetting SET ValuePay = ? WHERE NamePay = 'agent_adv_test_mode'", [$advMode]);
    } else {
        db_query($pdo, "INSERT INTO PaySetting (NamePay, ValuePay) VALUES ('agent_adv_test_mode', ?)", [$advMode]);
    }

    $check_exist2 = db_fetch($pdo, "SELECT * FROM PaySetting WHERE NamePay = 'agent_adv_test_count' LIMIT 1");
    if ($check_exist2) {
        db_query($pdo, "UPDATE PaySetting SET ValuePay = ? WHERE NamePay = 'agent_adv_test_count'", [(string)$advCount]);
    } else {
        db_query($pdo, "INSERT INTO PaySetting (NamePay, ValuePay) VALUES ('agent_adv_test_count', ?)", [(string)$advCount]);
    }

    flash('success', 'تنظیمات اکانت تست نماینده پیشرفته با موفقیت ذخیره شد.');
    header('Location: settings.php?tab=agents');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['reset_income_today', 'reset_income_week', 'reset_income_month', 'reset_income_all', 'reset_sales_count'])) {
    csrf_check_post();
    $act = $_POST['action'];

    if ($act === 'reset_income_today') {
        $todayStr = date('Y/m/d');
        db_query($pdo, "DELETE FROM Payment_report WHERE time LIKE ?", [$todayStr . '%']);
        flash('success', 'تراکنش‌ها و درآمد امروز با موفقیت ریست شد.');
    } elseif ($act === 'reset_income_week') {
        $weekAgo = date('Y/m/d H:i:s', strtotime('-7 days'));
        db_query($pdo, "DELETE FROM Payment_report WHERE time >= ?", [$weekAgo]);
        flash('success', 'تراکنش‌ها و درآمد ۷ روز اخیر با موفقیت ریست شد.');
    } elseif ($act === 'reset_income_month') {
        $monthAgo = date('Y/m/d H:i:s', strtotime('-30 days'));
        db_query($pdo, "DELETE FROM Payment_report WHERE time >= ?", [$monthAgo]);
        flash('success', 'تراکنش‌ها و درآمد ۳۰ روز اخیر با موفقیت ریست شد.');
    } elseif ($act === 'reset_income_all') {
        db_query($pdo, "DELETE FROM Payment_report");
        flash('success', 'کل سوابق درآمدها و تراکنش‌ها با موفقیت ریست و پاکسازی شدند.');
    } elseif ($act === 'reset_sales_count') {
        $scope = $_POST['scope'] ?? 'all';
        if ($scope === 'all') {
            db_query($pdo, "DELETE FROM invoice");
            flash('success', 'تمام فاکتورها و شمارش فروش با موفقیت ریست شدند.');
        } elseif ($scope === 'unpaid_expired') {
            db_query($pdo, "DELETE FROM invoice WHERE Status IN ('unpaid', 'disabled', 'disabledn', 'end_of_time', 'end_of_volume')");
            flash('success', 'فاکتورهای پرداخت‌نشده و منقضی با موفقیت پاکسازی شدند.');
        }
    }
    header('Location: settings.php?tab=reset_stats');
    exit;
}

$tab = $_GET['tab'] ?? 'appearance';

$themes = [
    'navy' => ['name' => $textbotlang['panel']['settingsThemeBlueSea'], 'desc' => $textbotlang['panel']['settingsThemeBlueSeaDesc'], 'c' => ['#0F172A', '#1E293B', '#06B6D4', '#22C55E'], 'dark' => true],
    'purple' => ['name' => $textbotlang['panel']['settingsThemeDreamPurple'], 'desc' => $textbotlang['panel']['settingsThemeDreamPurpleDesc'], 'c' => ['#180D2E', '#231545', '#A855F7', '#F43F5E'], 'dark' => true],
    'emerald' => ['name' => $textbotlang['panel']['settingsThemeEmeraldGreen'], 'desc' => $textbotlang['panel']['settingsThemeEmeraldGreenDesc'], 'c' => ['#0A1F1C', '#132E2A', '#10B981', '#84CC16'], 'dark' => true],
    'sunset' => ['name' => $textbotlang['panel']['settingsThemeWarmSunset'], 'desc' => $textbotlang['panel']['settingsThemeWarmSunsetDesc'], 'c' => ['#1A0D0D', '#2A1615', '#F97316', '#FBBF24'], 'dark' => true],
    'slate' => ['name' => $textbotlang['panel']['settingsThemeBlack'], 'desc' => $textbotlang['panel']['settingsThemeBlackDesc'], 'c' => ['#080808', '#141414', '#E2E8F0', '#22C55E'], 'dark' => true],
    'light' => ['name' => $textbotlang['panel']['settingsThemeLightWhite'], 'desc' => $textbotlang['panel']['settingsThemeLightWhiteDesc'], 'c' => ['#F1F5F9', '#FFFFFF', '#0891B2', '#16A34A'], 'dark' => false],
    'linen' => ['name' => $textbotlang['panel']['settingsThemeCreamPaper'], 'desc' => $textbotlang['panel']['settingsThemeCreamPaperDesc'], 'c' => ['#FAF7F2', '#FFFFFF', '#B87333', '#5D7C4A'], 'dark' => false],
    'mint' => ['name' => $textbotlang['panel']['settingsThemeMintGreen'], 'desc' => $textbotlang['panel']['settingsThemeMintGreenDesc'], 'c' => ['#F0FDF4', '#FFFFFF', '#166534', '#1D4ED8'], 'dark' => false],
    'lavender' => ['name' => $textbotlang['panel']['settingsThemeLavender'], 'desc' => $textbotlang['panel']['settingsThemeLavenderDesc'], 'c' => ['#FAF5FF', '#FFFFFF', '#6D28D9', '#15803D'], 'dark' => false],
];

$tabs = [
    'appearance' => ['icon' => 'settings', 'label' => $textbotlang['panel']['settingsTabAppearance']],
    'security' => ['icon' => 'block', 'label' => $textbotlang['panel']['settingsTabSecurity']],
    'stars' => ['icon' => 'card', 'label' => 'نرخ استارز تلگرام ⭐'],
    'currency' => ['icon' => 'card', 'label' => 'نرخ ارز و دلار 💵'],
    'agents' => ['icon' => 'users', 'label' => 'تنظیمات نمایندگان 👥'],
    'reset_stats' => ['icon' => 'card', 'label' => 'ریست آمار و مالی 🔄'],
    'system' => ['icon' => 'dashboard', 'label' => $textbotlang['panel']['settingsTabSystem']],
];

$pageTitle = $textbotlang['panel']['settingsTitle'];
$activeNav = 'settings';
$showPageHead = false;
include __DIR__ . '/inc/layout_head.php';
?>

<div style="display:flex;gap:4px;margin-bottom:18px;background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:5px;overflow-x:auto"
    class="fade-up">
    <?php foreach ($tabs as $key => $tab_data): ?>
        <a href="?tab=<?= $key ?>"
            style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:7px;font-size:.82rem;font-weight:600;white-space:nowrap;flex-shrink:0;transition:all .15s;text-decoration:none;
                  <?= $tab === $key ? 'background:var(--ac);color:var(--btn-ac-text, #fff);box-shadow:0 0 14px var(--acg)' : 'color:var(--mute)' ?>">
            <?= icon($tab_data['icon'], 15) ?>     <?= $tab_data['label'] ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'appearance'): ?>

    <div class="card fade-up">
        <div class="card-head">
            <div>
                <div class="card-title"><?= $textbotlang['panel']['settingsHeading'] ?></div>
                <div class="card-subtitle"><?= $textbotlang['panel']['settingsAppearanceSection'] ?></div>
            </div>
        </div>
        <div class="card-body">
            <div
                style="font-size:.75rem;font-weight:700;color:var(--mute);letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px">
                <?= $textbotlang['panel']['settingsThemeLabel'] ?></div>
            <div class="theme-grid" style="margin-bottom:20px">
                <?php foreach ($themes as $key => $theme):
                    if (!$theme['dark'])
                        continue; ?>
                    <div class="theme-card" data-tk="<?= $key ?>" onclick="pickTheme('<?= $key ?>')">
                        <div class="theme-preview">
                            <?php foreach ($theme['c'] as $color): ?>
                                <div style="background:<?= $color ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="theme-name"><?= htmlspecialchars($theme['name']) ?></div>
                        <div class="theme-desc"><?= htmlspecialchars($theme['desc']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div
                style="font-size:.75rem;font-weight:700;color:var(--mute);letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px">
                <?= $textbotlang['panel']['settingsSecuritySection'] ?></div>
            <div class="theme-grid">
                <?php foreach ($themes as $key => $theme):
                    if ($theme['dark'])
                        continue; ?>
                    <div class="theme-card" data-tk="<?= $key ?>" onclick="pickTheme('<?= $key ?>')">
                        <div class="theme-preview" style="border:1px solid var(--bd)">
                            <?php foreach ($theme['c'] as $color): ?>
                                <div style="background:<?= $color ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="theme-name"><?= htmlspecialchars($theme['name']) ?></div>
                        <div class="theme-desc"><?= htmlspecialchars($theme['desc']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card fade-up d1" style="margin-top:14px">
        <div class="card-head">
            <div>
                <div class="card-title"><?= $textbotlang['panel']['settingsCurrentPasswordLabel'] ?></div>
            </div>
        </div>
        <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap">
            <button onclick="setSidebarMode(false)" class="btn btn-ghost" id="modeExpanded"
                style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 20px;flex:1;min-width:120px">
                <svg width="44" height="32" viewBox="0 0 44 32" fill="none">
                    <rect x="0" y="0" width="13" height="32" rx="3" fill="var(--sf3)" />
                    <rect x="2" y="5" width="9" height="2" rx="1" fill="var(--ac)" />
                    <rect x="2" y="10" width="9" height="2" rx="1" fill="var(--bd)" />
                    <rect x="15" y="0" width="29" height="32" rx="3" fill="var(--sf3)" />
                </svg>
                <span style="font-size:.78rem;font-weight:600"><?= $textbotlang['panel']['settingsNewPasswordLabel'] ?></span>
            </button>
            <button onclick="setSidebarMode(true)" class="btn btn-ghost" id="modeCollapsed"
                style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 20px;flex:1;min-width:120px">
                <svg width="44" height="32" viewBox="0 0 44 32" fill="none">
                    <rect x="0" y="0" width="7" height="32" rx="3" fill="var(--sf3)" />
                    <rect x="2" y="5" width="3" height="2" rx="1" fill="var(--ac)" />
                    <rect x="2" y="10" width="3" height="2" rx="1" fill="var(--bd)" />
                    <rect x="9" y="0" width="35" height="32" rx="3" fill="var(--sf3)" />
                </svg>
                <span style="font-size:.78rem;font-weight:600"><?= $textbotlang['panel']['settingsConfirmPasswordLabel'] ?></span>
            </button>
        </div>
    </div>

<?php elseif ($tab === 'security'): ?>

    <div class="two-col">
        <div class="card fade-up">
            <div class="card-head">
                <div>
                    <div class="card-title"><?= $textbotlang['panel']['settingsChangePasswordBtn'] ?></div>
                    <div class="card-subtitle"><?= $textbotlang['panel']['settingsSystemSection'] ?></div>
                </div>
            </div>
            <form method="POST" class="card-body">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="change_password">
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div class="field">
                        <label><?= $textbotlang['panel']['settingsSaveBtn'] ?></label>
                        <div style="position:relative">
                            <input type="password" name="current_password" id="pw1" class="input" required
                                autocomplete="current-password" style="padding-left:40px">
                            <button type="button" onclick="togglePw('pw1', this)"
                                style="position:absolute;left:10px;top:50%;transform:translateY(-50%);border:none;background:none;color:var(--dim);cursor:pointer">
                                <?= icon('eye', 16) ?>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label><?= $textbotlang['panel']['settingsCurrentPasswordPlaceholder'] ?></label>
                        <div style="position:relative">
                            <input type="password" name="new_password" id="pw2" class="input" minlength="6" required
                                autocomplete="new-password" style="padding-left:40px" oninput="checkPwStr(this.value)">
                            <button type="button" onclick="togglePw('pw2', this)"
                                style="position:absolute;left:10px;top:50%;transform:translateY(-50%);border:none;background:none;color:var(--dim);cursor:pointer">
                                <?= icon('eye', 16) ?>
                            </button>
                        </div>
                        <div style="height:4px;background:var(--sf3);border-radius:99px;margin-top:5px">
                            <div id="pwBar"
                                style="height:100%;width:0;border-radius:99px;transition:all .3s;background:var(--no)">
                            </div>
                        </div>
                        <span id="pwHint" class="field-hint"><?= $textbotlang['panel']['settingsNewPasswordPlaceholder'] ?></span>
                    </div>
                    <div class="field">
                        <label><?= $textbotlang['panel']['settingsConfirmPasswordPlaceholder'] ?></label>
                        <input type="password" name="confirm_password" class="input" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 14) ?> <?= $textbotlang['panel']['settingsPasswordStrengthLabel'] ?></button>
                </div>
            </form>
        </div>

        <div class="card fade-up d1" style="height:fit-content">
            <div class="card-head">
                <div>
                    <div class="card-title"><?= $textbotlang['panel']['settingsLogoutBtn'] ?></div>
                </div>
                <a href="logout.php" class="btn btn-no btn-sm"><?= icon('logout', 13) ?> <?= $textbotlang['panel']['settingsSystemInfoTitle'] ?></a>
            </div>
            <div class="kv-list">
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['settingsThemePreviewLabel'] ?></span>
                    <span class="kv-val"><?= htmlspecialchars($_SESSION['admin_user']) ?></span>
                </div>
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['settingsApplyThemeBtn'] ?></span>
                    <span class="kv-val">
                        <?= isset($_SESSION['login_time']) ? date('Y/m/d H:i:s', $_SESSION['login_time']) : '—' ?>
                    </span>
                </div>
                <div class="kv">
                    <span class="kv-key"><?= $textbotlang['panel']['settingsSidebarToggleLabel'] ?></span>
                    <span class="kv-val cm"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'stars'):
    $curStarPrice = (int)(db_fetch($pdo, "SELECT ValuePay FROM PaySetting WHERE NamePay = 'price_per_star' LIMIT 1")['ValuePay'] ?? 0);
?>
    <div class="card fade-up">
        <div class="card-head">
            <h3><?= icon('card', 16) ?> تنظیم نرخ استارز تلگرام (Telegram Stars)</h3>
            <p class="card-head-desc">تعیین نرخ برابری هر ۱ عدد استارز به تومان جهت تبدیل دقیق قیمت سرویس‌ها در پرداخت با استارز</p>
        </div>
        <form method="POST" class="card-body">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save_stars_price">
            <div class="form-grid">
                <div class="field full">
                    <label>قیمت هر استارز (تومان)</label>
                    <input type="number" name="price_per_star" class="input" style="font-size:1.1rem;font-weight:700;" value="<?= $curStarPrice ?>" placeholder="مثلاً 1500" min="0">
                    <small style="color:var(--mute);margin-top:6px;display:block;">
                        💡 <b>راهنما:</b> اگر این فیلد را برابر <b>۰</b> قرار دهید، سیستم قیمت استارز را بر اساس نرخ روز دلار محاسبه خواهد کرد.
                    </small>
                </div>
            </div>
            <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary"><?= icon('check', 14) ?> ذخیره نرخ استارز</button>
            </div>
        </form>
    </div>

<?php elseif ($tab === 'currency'):
    $curManualDollar = (int)(db_fetch($pdo, "SELECT ValuePay FROM PaySetting WHERE NamePay = 'dollar_price_manual' LIMIT 1")['ValuePay'] ?? 0);
    $liveRates = rate_arze();
?>
    <div class="card fade-up">
        <div class="card-head">
            <h3><?= icon('wallet', 16) ?> نرخ لحظه‌ای دلار و ارزها (Live Tracking)</h3>
            <p class="card-head-desc">رهگیری زنده و لحظه‌ای نرخ دلار و رمزارزها از تترلند، والکس و شبکه طلا و ارز (TGJU)</p>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:24px;">
                <div style="background:var(--sf);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center;">
                    <div style="font-size:12px;color:var(--mute);margin-bottom:6px;">💵 نرخ زنده دلار / تتر (USD)</div>
                    <div style="font-size:22px;font-weight:700;color:#10b981;"><?= number_format($liveRates['USD'] ?? 0) ?> تومان</div>
                    <div style="font-size:11px;color:var(--mute);margin-top:4px;">وضعیت: <?= ($curManualDollar > 0) ? 'تنظیم دستی (Override)' : '🟢 رهگیری زنده و لایو' ?></div>
                </div>
                <div style="background:var(--sf);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center;">
                    <div style="font-size:12px;color:var(--mute);margin-bottom:6px;">🔴 نرخ زنده ترون (TRX)</div>
                    <div style="font-size:22px;font-weight:700;color:#ef4444;"><?= number_format($liveRates['TRX'] ?? 0) ?> تومان</div>
                    <div style="font-size:11px;color:var(--mute);margin-top:4px;">محاسبه بر اساس نرخ جهانی</div>
                </div>
                <div style="background:var(--sf);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center;">
                    <div style="font-size:12px;color:var(--mute);margin-bottom:6px;">💎 نرخ زنده تون‌کوین (TON)</div>
                    <div style="font-size:22px;font-weight:700;color:#3b82f6;"><?= number_format($liveRates['Ton'] ?? 0) ?> تومان</div>
                    <div style="font-size:11px;color:var(--mute);margin-top:4px;">محاسبه بر اساس نرخ جهانی</div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_dollar_rate">
                <div class="form-grid">
                    <div class="field full">
                        <label>نرخ دستی دلار (تومان)</label>
                        <input type="number" name="dollar_price_manual" class="input" style="max-width:320px;"
                               value="<?= htmlspecialchars((string)$curManualDollar) ?>"
                               placeholder="0 برای حالت خودکار و لایو">
                        <small style="color:var(--mute);margin-top:6px;display:block;line-height:1.7;">
                            💡 <b>نکته:</b> اگر این فیلد روی <b>0</b> باشد، ربات به صورت لحظه‌ای و آنلاین آخرین قیمت دلار را از صرافی‌های معتبر دریافت کرده و اعمال می‌کند. اگر عدد دلخواهی وارد کنید (مثلاً 270000)، تمام محاسبات درگاه‌ها طبق عدد شما انجام می‌شود.
                        </small>
                    </div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
                    <a href="settings.php?tab=currency" class="btn btn-secondary">
                        <?= icon('refresh', 14) ?> استعلام مجدد نرخ زنده
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= icon('check', 14) ?> ذخیره تنظیمات
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'agents'):
    $advModeRow = db_fetch($pdo, "SELECT ValuePay FROM PaySetting WHERE NamePay = 'agent_adv_test_mode' LIMIT 1");
    $advMode = ($advModeRow && !empty($advModeRow['ValuePay'])) ? $advModeRow['ValuePay'] : 'unlimited';

    $advCountRow = db_fetch($pdo, "SELECT ValuePay FROM PaySetting WHERE NamePay = 'agent_adv_test_count' LIMIT 1");
    $advCount = ($advCountRow && !empty($advCountRow['ValuePay'])) ? (int)$advCountRow['ValuePay'] : 100;
?>
    <div class="card fade-up">
        <div class="card-head">
            <div>
                <div class="card-title">تنظیمات اکانت تست نماینده پیشرفته</div>
                <div class="card-desc">تعیین محدودیت ساخت تست برای نمایندگان دارای دسترسی پیشرفته (سطح ۲)</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="settings.php?tab=agents">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_agent_test_limit">

                <div style="display:flex;flex-direction:column;gap:18px;">
                    <div>
                        <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:8px;">حالت دسترسی ساخت تست:</label>
                        <select name="agent_adv_test_mode" class="select" id="agent_adv_test_mode" onchange="toggleAgentCountVisibility()" style="max-width:400px;">
                            <option value="unlimited" <?= $advMode === 'unlimited' ? 'selected' : '' ?>>بدون محدودیت (نامحدود)</option>
                            <option value="daily" <?= $advMode === 'daily' ? 'selected' : '' ?>>محدودیت روزانه (مثلاً ۱۰۰ عدد در هر روز)</option>
                            <option value="total" <?= $advMode === 'total' ? 'selected' : '' ?>>محدودیت کلی (کلاً ۱۰۰ عدد)</option>
                        </select>
                    </div>

                    <div id="adv_count_wrapper" style="<?= $advMode === 'unlimited' ? 'display:none;' : '' ?>">
                        <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:8px;">سقف مجاز تعداد تست:</label>
                        <input type="number" name="agent_adv_test_count" class="input" style="max-width:400px;" value="<?= $advCount ?>" min="1">
                        <small style="color:var(--mute);margin-top:6px;display:block;line-height:1.7;">
                            💡 در حالت روزانه، هر روز در ساعت ۰۰:۰۰ بامداد سقف نماینده مجدداً ریست می‌شود.
                        </small>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <?= icon('check', 14) ?> ذخیره تنظیمات
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function toggleAgentCountVisibility() {
        var mode = document.getElementById('agent_adv_test_mode').value;
        var wrap = document.getElementById('adv_count_wrapper');
        if (wrap) {
            wrap.style.display = (mode === 'unlimited') ? 'none' : 'block';
        }
    }
    </script>

<?php elseif ($tab === 'reset_stats'):
    $totalSalesAll = db_count($pdo, "SELECT COUNT(*) FROM invoice");
    $totalSalesActive = db_count($pdo, "SELECT COUNT(*) FROM invoice WHERE Status = 'active'");
    $totalSalesDead = db_count($pdo, "SELECT COUNT(*) FROM invoice WHERE Status NOT IN ('active', 'send_on_hold')");

    $todayStr = date('Y/m/d');
    $incomeToday = (int)db_query($pdo, "SELECT COALESCE(SUM(price),0) FROM Payment_report WHERE payment_Status = 'paid' AND time LIKE ?", [$todayStr . '%'])->fetchColumn();
    $weekAgo = date('Y/m/d H:i:s', strtotime('-7 days'));
    $incomeWeek = (int)db_query($pdo, "SELECT COALESCE(SUM(price),0) FROM Payment_report WHERE payment_Status = 'paid' AND time >= ?", [$weekAgo])->fetchColumn();
    $monthAgo = date('Y/m/d H:i:s', strtotime('-30 days'));
    $incomeMonth = (int)db_query($pdo, "SELECT COALESCE(SUM(price),0) FROM Payment_report WHERE payment_Status = 'paid' AND time >= ?", [$monthAgo])->fetchColumn();
    $incomeAll = (int)db_query($pdo, "SELECT COALESCE(SUM(price),0) FROM Payment_report WHERE payment_Status = 'paid'")->fetchColumn();
?>

    <div class="card fade-up" style="margin-bottom:20px;">
        <div class="card-head">
            <div>
                <div class="card-title">🔄 ریست آمار فروش و فاکتورها</div>
                <div class="card-sub">پاکسازی و صفر کردن شمارش فاکتورها و سفارشات ثبت شده در ربات</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;margin:15px 0 20px;">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:0.8rem;color:var(--mute);margin-bottom:4px;">کل سفارشات</div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--tx);"><?= number_format($totalSalesAll) ?> عدد</div>
            </div>
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:0.8rem;color:var(--mute);margin-bottom:4px;">سفارشات فعال</div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--ac);"><?= number_format($totalSalesActive) ?> عدد</div>
            </div>
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:0.8rem;color:var(--mute);margin-bottom:4px;">منقضی یا پرداخت‌نشده</div>
                <div style="font-size:1.3rem;font-weight:700;color:#F43F5E;"><?= number_format($totalSalesDead) ?> عدد</div>
            </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <form method="POST" action="settings.php?tab=reset_stats" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید تمام فاکتورها و سوابق فروش را به طور کامل پاک کنید؟');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_sales_count">
                <input type="hidden" name="scope" value="all">
                <button type="submit" class="btn" style="background:#DC2626;color:#fff;">
                    🗑️ ریست کامل تمام فاکتورها و فروش‌ها
                </button>
            </form>

            <form method="POST" action="settings.php?tab=reset_stats" onsubmit="return confirm('آیا می‌خواهید فقط فاکتورهای منقضی، لغو شده و پرداخت‌نشده را پاک کنید؟');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_sales_count">
                <input type="hidden" name="scope" value="unpaid_expired">
                <button type="submit" class="btn" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);">
                    🧹 پاکسازی فاکتورهای منقضی و معلق
                </button>
            </form>
        </div>
    </div>

    <div class="card fade-up">
        <div class="card-head">
            <div>
                <div class="card-title">💰 ریست آمار درآمد و تراکنش‌ها</div>
                <div class="card-sub">صفر کردن مبالغ درآمد روز، هفته، ماه یا کل تاریخچه تراکنش‌ها</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin:15px 0 20px;">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.85rem;color:var(--mute);">درآمد امروز</span>
                    <span style="font-size:1.1rem;font-weight:700;color:var(--ac);"><?= number_format($incomeToday) ?> <span style="font-size:0.75rem;">تومان</span></span>
                </div>
                <form method="POST" action="settings.php?tab=reset_stats" onsubmit="return confirm('آیا از ریست درآمد امروز مطمئن هستید؟');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_income_today">
                    <button type="submit" class="btn btn-secondary" style="width:100%;font-size:0.8rem;padding:6px 12px;color:#F43F5E;">
                        صفر کردن درآمد امروز
                    </button>
                </form>
            </div>

            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.85rem;color:var(--mute);">درآمد ۷ روز اخیر</span>
                    <span style="font-size:1.1rem;font-weight:700;color:var(--ac);"><?= number_format($incomeWeek) ?> <span style="font-size:0.75rem;">تومان</span></span>
                </div>
                <form method="POST" action="settings.php?tab=reset_stats" onsubmit="return confirm('آیا از ریست درآمد ۷ روز اخیر مطمئن هستید؟');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_income_week">
                    <button type="submit" class="btn btn-secondary" style="width:100%;font-size:0.8rem;padding:6px 12px;color:#F43F5E;">
                        صفر کردن درآمد ۷ روز اخیر
                    </button>
                </form>
            </div>

            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.85rem;color:var(--mute);">درآمد ۳۰ روز اخیر</span>
                    <span style="font-size:1.1rem;font-weight:700;color:var(--ac);"><?= number_format($incomeMonth) ?> <span style="font-size:0.75rem;">تومان</span></span>
                </div>
                <form method="POST" action="settings.php?tab=reset_stats" onsubmit="return confirm('آیا از ریست درآمد ۳۰ روز اخیر مطمئن هستید؟');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_income_month">
                    <button type="submit" class="btn btn-secondary" style="width:100%;font-size:0.8rem;padding:6px 12px;color:#F43F5E;">
                        صفر کردن درآمد ۳۰ روز اخیر
                    </button>
                </form>
            </div>

            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.85rem;color:var(--mute);">کل درآمد ثبت‌شده</span>
                    <span style="font-size:1.1rem;font-weight:700;color:var(--ac);"><?= number_format($incomeAll) ?> <span style="font-size:0.75rem;">تومان</span></span>
                </div>
                <form method="POST" action="settings.php?tab=reset_stats" onsubmit="return confirm('⚠️ اخطار: کل تاریخچه تراکنش‌ها و درآمدهای ربات پاک خواهد شد! آیا کاملاً مطمئن هستید؟');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_income_all">
                    <button type="submit" class="btn" style="width:100%;font-size:0.8rem;padding:6px 12px;background:#DC2626;color:#fff;">
                        صفر کردن کل درآمدها
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'system'): ?>

    <div class="card fade-up">
        <div class="card-head">
            <div>
                <div class="card-title"><?= $textbotlang['panel']['settingsAboutPanelLabel'] ?></div>
            </div>
        </div>
        <div class="kv-list">
            <?php
            $dbVer = '—';
            try {
                $dbVer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            } catch (Exception $e) {
            }
            $sysInfo = [
                [$textbotlang['panel']['settingsPanelVersion'], 1.0],
                ['PHP', phpversion()],
                ['MySQL', $dbVer],
                [$textbotlang['panel']['settingsWebServer'], $_SERVER['SERVER_SOFTWARE'] ?? '—'],
                [$textbotlang['panel']['settingsCurrentAdmin'], $_SESSION['admin_user']],
                [$textbotlang['panel']['settingsServerTime'], date('Y/m/d H:i:s')],
                [$textbotlang['panel']['settingsPhpMemory'], ini_get('memory_limit')],
            ];
            foreach ($sysInfo as [$key, $value]):
                ?>
                <div class="kv">
                    <span class="kv-key"><?= $key ?></span>
                    <span class="kv-val cm" style="font-size:.78rem"><?= htmlspecialchars($value) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="kv">
                <span class="kv-key"><?= $textbotlang['panel']['settingsReportIssue'] ?></span>
                <a class="kv-val btn-link" style="font-size:.78rem" href="https://github.com/Aghorezashoon/doumanpanelbot" target="_blank" rel="noopener"><?= $textbotlang['panel']['settingsReportIssueLink'] ?></a>
            </div>
            <div class="kv">
                <span class="kv-key"><?= $textbotlang['panel']['settingsDonate'] ?></span>
                <a class="btn-glass" href="https://nowpayments.io/donation/mahdi" target="_blank" rel="noopener"><?= icon('heart', 14) ?> <?= $textbotlang['panel']['settingsDonateLink'] ?></a>
            </div>
        </div>
    </div>

<?php endif; ?>

<script src="js/settings.js"></script>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>