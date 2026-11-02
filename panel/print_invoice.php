<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    die("شناسه فاکتور مشخص نشده است.");
}

$inv = db_fetch($pdo, "SELECT * FROM invoice WHERE id_invoice = ? OR username = ? LIMIT 1", [$id, $id]);
if (!$inv) {
    die("فاکتوری با این مشخصات یافت نشد.");
}

$user = null;
if (!empty($inv['id_user'])) {
    $user = db_fetch($pdo, "SELECT * FROM user WHERE id = ? LIMIT 1", [$inv['id_user']]);
}

// Convert numbers to Persian words
function numberToPersianWords($num) {
    $num = (int)$num;
    if ($num === 0) return 'صفر تومان';
    if ($num < 0) return 'منفی ' . numberToPersianWords(abs($num));

    $ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه',
             'ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
    $tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
    $hundreds = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
    $thousands = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];

    $parts = [];
    $thousandIdx = 0;

    while ($num > 0) {
        $chunk = $num % 1000;
        if ($chunk > 0) {
            $h = intdiv($chunk, 100);
            $r = $chunk % 100;
            $items = [];
            if ($h > 0) $items[] = $hundreds[$h];
            if ($r > 0) {
                if ($r < 20) {
                    $items[] = $ones[$r];
                } else {
                    $t = intdiv($r, 10);
                    $o = $r % 10;
                    if ($t > 0) $items[] = $tens[$t];
                    if ($o > 0) $items[] = $ones[$o];
                }
            }
            $chunkStr = implode(' و ', $items);
            if ($thousandIdx > 0) {
                $chunkStr .= ' ' . $thousands[$thousandIdx];
            }
            array_unshift($parts, $chunkStr);
        }
        $num = intdiv($num, 1000);
        $thousandIdx++;
    }

    return implode(' و ', $parts) . ' تومان';
}

$shamsiDate = '—';
if (!empty($inv['time_sell'])) {
    if (function_exists('jdate')) {
        $shamsiDate = jdate('Y/m/d - H:i', (int)$inv['time_sell']);
    } else {
        $shamsiDate = safe_date($inv['time_sell'], 'Y/m/d H:i');
    }
}

$isPaid = in_array(strtolower($inv['Status'] ?? ''), ['active', 'paid', 'send_on_hold', 'sendedwarn', 'end_of_time', 'end_of_volume']);
$price = (int)($inv['price_product'] ?? 0);
$priceInWords = numberToPersianWords($price);

$userLimitText = '۱ کاربره';
if (isset($inv['limit_ip'])) {
    $lip = (int)$inv['limit_ip'];
    if ($lip === 0) {
        $userLimitText = 'بدون محدودیت کاربر (نامحدود)';
    } else {
        $userLimitText = $lip . ' کاربره';
    }
}

$qrData = "https://aprilonlineshop.ir/panel/print_invoice.php?id=" . urlencode($inv['id_invoice']);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&margin=4&data=" . urlencode($qrData);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>فاکتور رسمی فروش - شماره <?= htmlspecialchars($inv['id_invoice']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, Tahoma, Arial, sans-serif;
    }
    body {
      background: #f1f5f9;
      color: #1e293b;
      padding: 24px 16px;
      direction: rtl;
    }
    .print-actions {
      max-width: 900px;
      margin: 0 auto 20px auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      padding: 12px 20px;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      border: none;
    }
    .btn-primary {
      background: #2563eb;
      color: #fff;
    }
    .btn-primary:hover {
      background: #1d4ed8;
    }
    .btn-ghost {
      background: #f1f5f9;
      color: #475569;
    }
    .btn-ghost:hover {
      background: #e2e8f0;
    }

    /* Official Invoice Sheet */
    .invoice-sheet {
      max-width: 900px;
      margin: 0 auto;
      background: #ffffff;
      padding: 36px 40px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      border: 1px solid #cbd5e1;
      position: relative;
    }

    /* Header */
    .invoice-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 20px;
      margin-bottom: 24px;
    }
    .header-logo {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .header-logo .brand-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #0ea5e9, #2563eb);
      color: #fff;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: 900;
    }
    .header-logo .brand-name {
      font-size: 20px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
    }
    .header-logo .brand-sub {
      font-size: 12px;
      color: #64748b;
      margin-top: 2px;
    }
    .header-title {
      text-align: center;
    }
    .header-title h1 {
      font-size: 20px;
      font-weight: 900;
      color: #0f172a;
    }
    .header-title p {
      font-size: 11px;
      color: #64748b;
      margin-top: 4px;
    }
    .header-meta {
      text-align: left;
      font-size: 12px;
      line-height: 1.8;
      direction: ltr;
    }
    .header-meta span.lbl {
      color: #64748b;
      direction: rtl;
      display: inline-block;
      margin-left: 4px;
    }

    /* Parties Section */
    .parties-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 24px;
    }
    .party-box {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 14px 18px;
      background: #f8fafc;
    }
    .party-box h3 {
      font-size: 13px;
      font-weight: 800;
      color: #0f172a;
      border-bottom: 1.5px solid #cbd5e1;
      padding-bottom: 6px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .party-row {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      margin-bottom: 6px;
      line-height: 1.6;
    }
    .party-row .label {
      color: #64748b;
    }
    .party-row .val {
      font-weight: 600;
      color: #1e293b;
    }

    /* Main Items Table */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
      font-size: 12px;
    }
    .items-table th {
      background: #0f172a;
      color: #fff;
      font-weight: 700;
      padding: 10px 12px;
      text-align: center;
      border: 1px solid #0f172a;
    }
    .items-table td {
      padding: 12px;
      border: 1px solid #cbd5e1;
      text-align: center;
      vertical-align: middle;
    }
    .items-table tr:nth-child(even) {
      background: #f8fafc;
    }
    .text-right {
      text-align: right !important;
    }
    .text-left {
      text-align: left !important;
    }

    /* Summary Section */
    .summary-wrap {
      display: grid;
      grid-template-columns: 1.5fr 1fr;
      gap: 16px;
      margin-bottom: 24px;
    }
    .words-box {
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      padding: 14px;
      background: #fcfcfc;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .words-box .lbl {
      font-size: 11px;
      color: #64748b;
      margin-bottom: 4px;
    }
    .words-box .val {
      font-size: 13px;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.6;
    }
    .totals-box {
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      overflow: hidden;
    }
    .totals-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 14px;
      font-size: 12px;
      border-bottom: 1px solid #e2e8f0;
    }
    .totals-row.grand {
      background: #0f172a;
      color: #fff;
      font-weight: 800;
      font-size: 14px;
      border-bottom: none;
    }
    .totals-row.grand span:last-child {
      color: #38bdf8;
    }

    /* Footer, Signatures & Stamp */
    .invoice-footer {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
      border-top: 1.5px solid #cbd5e1;
      padding-top: 20px;
      margin-top: 10px;
      align-items: center;
    }
    .sign-box {
      text-align: center;
      padding: 10px;
      font-size: 12px;
      color: #475569;
    }
    .sign-title {
      font-weight: 700;
      margin-bottom: 12px;
      color: #0f172a;
    }

    /* Digital Seal / Stamp */
    .official-stamp {
      display: inline-block;
      width: 115px;
      height: 115px;
      border: 3px double #dc2626;
      border-radius: 50%;
      color: #dc2626;
      padding: 8px;
      text-align: center;
      transform: rotate(-8deg);
      box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.15);
      user-select: none;
    }
    .stamp-inner {
      width: 100%;
      height: 100%;
      border: 1px dashed #dc2626;
      border-radius: 50%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2px;
    }
    .stamp-inner .star { font-size: 10px; }
    .stamp-inner .name { font-size: 10px; font-weight: 900; line-height: 1.1; margin: 2px 0; }
    .stamp-inner .approved { font-size: 9px; font-weight: 800; background: #dc2626; color: #fff; padding: 1px 6px; border-radius: 4px; }
    .stamp-inner .date { font-size: 8px; margin-top: 2px; }

    .qr-box {
      text-align: center;
      font-size: 10px;
      color: #64748b;
    }
    .qr-box img {
      width: 90px;
      height: 90px;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      padding: 2px;
      background: #fff;
      display: block;
      margin: 0 auto 6px auto;
    }

    .badge-paid {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #86efac;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 800;
    }
    .badge-unpaid {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #fef3c7;
      color: #b45309;
      border: 1px solid #fde68a;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 800;
    }

    /* Print Styles */
    @media print {
      body {
        background: #fff !important;
        padding: 0 !important;
        color: #000 !important;
      }
      .no-print {
        display: none !important;
      }
      .invoice-sheet {
        max-width: 100% !important;
        box-shadow: none !important;
        border: 1px solid #000 !important;
        padding: 20px 24px !important;
        border-radius: 0 !important;
      }
      .items-table th {
        background: #000 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .totals-row.grand {
        background: #000 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .official-stamp {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .badge-paid, .badge-unpaid {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>

  <!-- Print Actions Bar -->
  <div class="print-actions no-print">
    <div>
      <a href="invoice.php" class="btn btn-ghost">
        <?= icon('arrow-right', 14) ?> بازگشت به لیست سفارشات
      </a>
    </div>
    <div style="display:flex; gap:10px;">
      <button onclick="window.print()" class="btn btn-primary">
        <?= icon('download', 14) ?> چاپ فاکتور رسمی / ذخیره PDF
      </button>
    </div>
  </div>

  <!-- Official Invoice Sheet -->
  <div class="invoice-sheet">
    
    <!-- Header -->
    <div class="invoice-header">
      <div class="header-logo">
        <div class="brand-icon">⚡</div>
        <div>
          <div class="brand-name">وولدمورت کلود</div>
          <div class="brand-sub">Voldemort Cloud Services</div>
        </div>
      </div>
      <div class="header-title">
        <h1>صورت‌حساب فروش کالا و خدمات</h1>
        <p>فاکتور الکترونیکی رسمی سامانه ابری</p>
        <div style="margin-top:6px;">
          <?php if ($isPaid): ?>
            <span class="badge-paid">✓ پرداخت و تسویه شده</span>
          <?php else: ?>
            <span class="badge-unpaid">⏳ در انتظار پرداخت</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="header-meta">
        <div><span class="lbl">شماره فاکتور:</span> <b><?= htmlspecialchars($inv['id_invoice']) ?></b></div>
        <div><span class="lbl">تاریخ صدور:</span> <b><?= htmlspecialchars($shamsiDate) ?></b></div>
        <div><span class="lbl">کد پیگیری:</span> <b><?= htmlspecialchars($inv['id_payment'] ?: $inv['id_invoice']) ?></b></div>
      </div>
    </div>

    <!-- Parties Grid -->
    <div class="parties-grid">
      <!-- Seller -->
      <div class="party-box">
        <h3><?= icon('server', 14) ?> مشخصات فروشنده (ارائه‌دهنده خدمت)</h3>
        <div class="party-row">
          <span class="label">نام شخص حقیقی/حقوقی:</span>
          <span class="val">سامانه ابری وولدمورت (Vold Cloud)</span>
        </div>
        <div class="party-row">
          <span class="label">دامنه و وب‌سایت رسمی:</span>
          <span class="val" style="direction:ltr;">aprilonlineshop.ir</span>
        </div>
        <div class="party-row">
          <span class="label">درگاه ارتباطی / ربات:</span>
          <span class="val" style="direction:ltr;">@doumanvpn_bot</span>
        </div>
        <div class="party-row">
          <span class="label">واحد پشتیبانی و مالی:</span>
          <span class="val">پشتیبانی ۲۴ ساعته تلگرام</span>
        </div>
      </div>

      <!-- Buyer -->
      <div class="party-box">
        <h3><?= icon('user', 14) ?> مشخصات خریدار (مشتری)</h3>
        <div class="party-row">
          <span class="label">شناسه عددی کاربر:</span>
          <span class="val"><?= htmlspecialchars($inv['id_user'] ?: '—') ?></span>
        </div>
        <div class="party-row">
          <span class="label">نام کاربری تلگرام:</span>
          <span class="val" style="direction:ltr;"><?= htmlspecialchars(!empty($user['username']) ? '@' . $user['username'] : ($inv['username'] ? '@' . $inv['username'] : '—')) ?></span>
        </div>
        <div class="party-row">
          <span class="label">نام اکانت کانفیگ:</span>
          <span class="val" style="direction:ltr; font-family:monospace;"><?= htmlspecialchars($inv['username'] ?: '—') ?></span>
        </div>
        <div class="party-row">
          <span class="label">نوع حساب کاربری:</span>
          <span class="val"><?= ($user['agent'] ?? 'f') === 'n2' ? 'نماینده ارشد' : (($user['agent'] ?? 'f') === 'n' ? 'نماینده فروش' : 'کاربر عادی') ?></span>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
      <thead>
        <tr>
          <th style="width:5%">ردیف</th>
          <th style="width:15%">کد سرویس</th>
          <th style="width:30%">شرح کالا یا خدمات</th>
          <th style="width:12%">حجم ترافیک</th>
          <th style="width:12%">مدت اعتبار</th>
          <th style="width:14%">تعداد کاربر (IP)</th>
          <th style="width:12%">مبلغ کل (تومان)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>۱</td>
          <td style="font-family:monospace; font-size:11px;"><?= htmlspecialchars($inv['id_invoice']) ?></td>
          <td class="text-right">
            <b><?= htmlspecialchars($inv['name_product'] ?: 'اشتراک اختصاصی سرور') ?></b>
            <?php if (!empty($inv['Service_location'])): ?>
              <div style="font-size:11px; color:#64748b; margin-top:2px;">لوکیشن: <?= htmlspecialchars($inv['Service_location']) ?></div>
            <?php endif; ?>
          </td>
          <td><b><?= !empty($inv['Volume']) ? htmlspecialchars($inv['Volume']) . ' گیگابایت' : 'نامحدود' ?></b></td>
          <td><b><?= !empty($inv['Service_time']) ? htmlspecialchars($inv['Service_time']) . ' روز' : 'نامحدود' ?></b></td>
          <td><span style="font-weight:700; color:#2563eb;"><?= htmlspecialchars($userLimitText) ?></span></td>
          <td class="text-left"><b><?= number_format($price) ?></b></td>
        </tr>
      </tbody>
    </table>

    <!-- Summary & Totals -->
    <div class="summary-wrap">
      <div class="words-box">
        <span class="lbl">مبلغ کل قابل پرداخت به حروف:</span>
        <span class="val"><?= htmlspecialchars($priceInWords) ?></span>
        <div style="font-size:11px; color:#64748b; margin-top:8px;">
          روش پرداخت: <b><?= !empty($inv['bottype']) ? htmlspecialchars($inv['bottype']) : 'پرداخت الکترونیک امن / کیف پول' ?></b>
        </div>
      </div>

      <div class="totals-box">
        <div class="totals-row">
          <span>جمع کل خدمات:</span>
          <span><?= number_format($price) ?> تومان</span>
        </div>
        <div class="totals-row">
          <span>تخفیف اعمال‌شده:</span>
          <span>۰ تومان</span>
        </div>
        <div class="totals-row">
          <span>مالیات و عوارض ارزش افزوده (VAT):</span>
          <span>۰ تومان</span>
        </div>
        <div class="totals-row grand">
          <span>مبلغ نهایی فاکتور:</span>
          <span><?= number_format($price) ?> تومان</span>
        </div>
      </div>
    </div>

    <!-- Notes & Legal Notice -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; font-size:11px; color:#64748b; margin-bottom:20px; line-height:1.7;">
      <b>شرایط و ضوابط:</b> این صورت‌حساب به صورت مکانیزه توسط سیستم هوشمند فروش وولدمورت کلود صادر گردیده و نسخه چاپی یا دیجیتال آن به همراه کد رهگیری یکتا معتبر می‌باشد. تمامی خدمات شامل پشتیبانی فنی تا پایان دوره اشتراک می‌باشند.
    </div>

    <!-- Footer & Signatures -->
    <div class="invoice-footer">
      <!-- Buyer Sign -->
      <div class="sign-box">
        <div class="sign-title">امضاء و تایید خریدار</div>
        <div style="height:70px; border-bottom:1px dashed #cbd5e1; margin-top:10px;"></div>
      </div>

      <!-- QR Authenticity -->
      <div class="qr-box">
        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Verification">
        <div>اسکن جهت استعلام و تایید اصالت فاکتور</div>
      </div>

      <!-- Seller Stamp -->
      <div class="sign-box">
        <div class="sign-title">مهر و امضای امور مالی فروشنده</div>
        <div class="official-stamp">
          <div class="stamp-inner">
            <div class="star">★ ★ ★</div>
            <div class="name">وولدمورت کلود</div>
            <div class="approved">تایید و تسویه شد</div>
            <div class="date"><?= explode(' ', $shamsiDate)[0] ?></div>
          </div>
        </div>
      </div>
    </div>

  </div>

</body>
</html>
