<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

$pageTitle = 'چت آنلاین پشتیبانی';
$pageLede = 'گفتگوی آنلاین با کاربران مینی‌اپ و ربات';
$activeNav = 'support';

$selectedUserId = trim($_GET['user_id'] ?? '');

// Handle POST: Admin sending a reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_post();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_reply') {
        $targetUserId = trim($_POST['user_id'] ?? '');
        $replyText = trim($_POST['message'] ?? '');
        
        if ($targetUserId !== '' && $replyText !== '') {
            $now = time();
            $adminUser = $_SESSION['admin_user'] ?? 'admin';
            
            db_query($pdo, "INSERT INTO support_messages (user_id, sender, admin_id, message, created_at, is_read) VALUES (?, 'admin', ?, ?, ?, 1)", [
                $targetUserId, $adminUser, $replyText, $now
            ]);
            
            // Mark user messages as read
            db_query($pdo, "UPDATE support_messages SET is_read = 1 WHERE user_id = ? AND sender = 'user'", [$targetUserId]);
            
            // Send telegram alert to user
            try {
                require_once __DIR__ . '/../botapi.php';
                $replyAlert = "💬 <b>پاسخ پشتیبانی به پیام شما:</b>\n\n"
                            . htmlspecialchars($replyText) . "\n\n"
                            . "🌐 جهت ادامه گفتگو روی دکمه زیر کلیک فرمایید:";
                $supportWebappUrl = "https://{$domainhosts}/app/?tab=support#support";
                $webappKb = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '💬 باز کردن چت آنلاین پشتیبانی', 'web_app' => ['url' => $supportWebappUrl]]
                        ]
                    ]
                ]);
                telegram('sendmessage', [
                    'chat_id' => $targetUserId,
                    'text' => $replyAlert,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $webappKb
                ]);
            } catch (Exception $e) {
                error_log("Failed to send telegram reply alert: " . $e->getMessage());
            }
            
            flash('success', 'پاسخ شما با موفقیت ارسال شد و به کاربر نیز اطلاع‌رسانی گردید.');
            header("Location: support.php?user_id=" . urlencode($targetUserId));
            exit;
        } else {
            flash('error', 'متن پاسخ نمی‌تواند خالی باشد.');
        }
    }
}

// Fetch list of conversations
$search = trim($_GET['q'] ?? '');
$convsQuery = "
    SELECT sm.user_id, 
           MAX(sm.created_at) AS last_time,
           (SELECT message FROM support_messages WHERE user_id = sm.user_id ORDER BY id DESC LIMIT 1) AS last_message,
           (SELECT sender FROM support_messages WHERE user_id = sm.user_id ORDER BY id DESC LIMIT 1) AS last_sender,
           SUM(CASE WHEN sm.sender = 'user' AND sm.is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
           MAX(u.username) AS username, MAX(u.namecustom) AS namecustom, MAX(u.Balance) AS Balance
    FROM support_messages sm
    LEFT JOIN user u ON u.id = sm.user_id
";

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(sm.user_id LIKE ? OR u.username LIKE ? OR u.namecustom LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
if ($where) {
    $convsQuery .= " WHERE " . implode(' AND ', $where);
}
$convsQuery .= " GROUP BY sm.user_id ORDER BY unread_count DESC, last_time DESC LIMIT 100";

$conversations = db_fetchAll($pdo, $convsQuery, $params);

// If no conversation selected, select the first one with unread or first available
if ($selectedUserId === '' && !empty($conversations)) {
    $selectedUserId = (string) $conversations[0]['user_id'];
}

// Load messages for selected conversation
$activeMessages = [];
$activeUser = null;
if ($selectedUserId !== '') {
    // Mark as read for this conversation
    db_query($pdo, "UPDATE support_messages SET is_read = 1 WHERE user_id = ? AND sender = 'user'", [$selectedUserId]);
    
    $activeMessages = db_fetchAll($pdo, "SELECT * FROM support_messages WHERE user_id = ? ORDER BY id ASC LIMIT 200", [$selectedUserId]);
    $activeUser = db_fetch($pdo, "SELECT * FROM user WHERE id = ?", [$selectedUserId]);
}

include __DIR__ . '/inc/layout_head.php';
?>

<style>
.chat-layout {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 16px;
  min-height: 640px;
  height: calc(100vh - 180px);
}
@media (max-width: 900px) {
  .chat-layout {
    grid-template-columns: 1fr;
    height: auto;
  }
}
.chat-sidebar {
  background: var(--sf);
  border: 1px solid var(--bd);
  border-radius: var(--rd);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.chat-sidebar-search {
  padding: 12px;
  border-bottom: 1px solid var(--bd);
}
.chat-users-list {
  flex: 1;
  overflow-y: auto;
}
.chat-user-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid var(--bd);
  text-decoration: none;
  color: var(--tx);
  transition: background 0.15s;
}
.chat-user-item:hover {
  background: var(--sf2);
}
.chat-user-item.active {
  background: var(--sf3);
  border-right: 3px solid var(--ac);
}
.chat-user-meta {
  flex: 1;
  min-width: 0;
  margin-right: 10px;
}
.chat-user-name {
  font-size: 0.88rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 6px;
}
.chat-user-snippet {
  font-size: 0.78rem;
  color: var(--tx2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 4px;
}
.chat-main {
  background: var(--sf);
  border: 1px solid var(--bd);
  border-radius: var(--rd);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.chat-header {
  padding: 14px 18px;
  border-bottom: 1px solid var(--bd);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--sf2);
}
.chat-history {
  flex: 1;
  overflow-y: auto;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: rgba(0, 0, 0, 0.15);
}
.msg-bubble {
  max-width: 75%;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 0.88rem;
  line-height: 1.6;
  position: relative;
  word-break: break-word;
}
.msg-user {
  align-self: flex-start;
  background: #1e293b;
  border: 1px solid #334155;
  color: #f1f5f9;
  border-bottom-right-radius: 2px;
}
.msg-admin {
  align-self: flex-end;
  background: #064e3b;
  border: 1px solid #059669;
  color: #ecfdf5;
  border-bottom-left-radius: 2px;
}
.msg-time {
  font-size: 0.68rem;
  color: var(--tx3);
  margin-top: 4px;
  text-align: left;
  direction: ltr;
}
.msg-admin .msg-time {
  color: #a7f3d0;
}
.chat-footer {
  padding: 14px 18px;
  border-top: 1px solid var(--bd);
  background: var(--sf2);
}
.canned-btns {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding-bottom: 8px;
  margin-bottom: 8px;
}
.canned-btn {
  background: var(--sf3);
  border: 1px solid var(--bd);
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 0.72rem;
  color: var(--tx2);
  white-space: nowrap;
  cursor: pointer;
}
.canned-btn:hover {
  color: var(--tx);
  border-color: var(--ac);
}
</style>

<div class="chat-layout fade-up">
  <!-- Sidebar: Users list -->
  <div class="chat-sidebar">
    <div class="chat-sidebar-search">
      <form method="GET" style="display:flex;gap:6px;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="جستجوی کاربر یا آیدی..." class="input" style="font-size:0.82rem;padding:7px 10px;">
        <button type="submit" class="btn btn-secondary btn-sm"><?= icon('search', 14) ?></button>
      </form>
    </div>
    
    <div class="chat-users-list">
      <?php if (empty($conversations)): ?>
        <div style="text-align:center;padding:30px;color:var(--tx2);font-size:0.85rem;">
          هنوز پیامی در چت آنلاین دریافت نشده است.
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $c): 
          $isActive = ($selectedUserId === (string)$c['user_id']);
          $uname = $c['username'] ? '@' . $c['username'] : ($c['namecustom'] ?: $c['user_id']);
        ?>
          <a href="support.php?user_id=<?= urlencode($c['user_id']) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" 
             class="chat-user-item <?= $isActive ? 'active' : '' ?>">
            <div class="chat-user-meta">
              <div class="chat-user-name">
                <?= htmlspecialchars(trunc($uname, 22)) ?>
                <?php if ($c['unread_count'] > 0): ?>
                  <span class="tag tag-warn" style="font-size:0.68rem;padding:1px 6px;border-radius:8px;background:#e11d48;color:#fff;"><?= $c['unread_count'] ?></span>
                <?php endif; ?>
              </div>
              <div class="chat-user-snippet">
                <?= ($c['last_sender'] === 'admin') ? '<span style="color:var(--ac);">پشتیبان:</span> ' : '' ?>
                <?= htmlspecialchars(trunc($c['last_message'] ?? '', 28)) ?>
              </div>
            </div>
            <div style="font-size:0.7rem;color:var(--tx3);white-space:nowrap;direction:ltr;">
              <?= safe_date($c['last_time'], 'm/d H:i') ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Main: Active Conversation -->
  <div class="chat-main">
    <?php if ($selectedUserId === ''): ?>
      <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--tx2);">
        لطفاً یک کاربر را از لیست سمت راست انتخاب کنید.
      </div>
    <?php else: ?>
      <!-- Header -->
      <div class="chat-header">
        <div>
          <div style="font-size:0.95rem;font-weight:700;display:flex;align-items:center;gap:8px;">
            <span>کاربر: <?= htmlspecialchars($activeUser['username'] ? '@' . $activeUser['username'] : ($activeUser['namecustom'] ?? $selectedUserId)) ?></span>
            <span class="cm" style="font-size:0.78rem;color:var(--tx2);">(شناسه: <?= htmlspecialchars($selectedUserId) ?>)</span>
          </div>
          <div style="font-size:0.75rem;color:var(--tx2);margin-top:2px;">
            موجودی: <b><?= number_format((int)($activeUser['Balance'] ?? 0)) ?> تومان</b>
            | نقش: <span class="tag <?= user_role_tag($activeUser['agent'] ?? '') ?>"><?= user_role_label($activeUser['agent'] ?? '') ?></span>
          </div>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="user.php?id=<?= urlencode($selectedUserId) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;font-size:0.78rem;">
            <?= icon('user', 14) ?> <span>مشاهده پروفایل</span>
          </a>
          <a href="invoice.php?q=<?= urlencode($selectedUserId) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;font-size:0.78rem;">
            <?= icon('invoice', 14) ?> <span>سرویس‌ها</span>
          </a>
        </div>
      </div>

      <!-- Messages History -->
      <div class="chat-history" id="chatHistoryBox">
        <?php if (empty($activeMessages)): ?>
          <div style="text-align:center;padding:30px;color:var(--tx2);font-size:0.85rem;">
            هنوز پیامی بین شما و این کاربر رد و بدل نشده است.
          </div>
        <?php else: ?>
          <?php foreach ($activeMessages as $m): ?>
            <div class="msg-bubble <?= $m['sender'] === 'admin' ? 'msg-admin' : 'msg-user' ?>">
              <div style="font-size:0.75rem;font-weight:700;margin-bottom:4px;color:<?= $m['sender'] === 'admin' ? '#6ee7b7' : '#93c5fd' ?>;">
                <?= $m['sender'] === 'admin' ? '🛡️ پشتیبانی (' . htmlspecialchars($m['admin_id'] ?? 'مدیریت') . ')' : '👤 ' . htmlspecialchars($activeUser['namecustom'] ?: 'کاربر') ?>
              </div>
              <div style="white-space:pre-wrap;"><?= htmlspecialchars($m['message']) ?></div>
              <div class="msg-time"><?= date('Y/m/d H:i', $m['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Footer / Reply Form -->
      <div class="chat-footer">
        <div class="canned-btns">
          <span style="font-size:0.72rem;color:var(--tx2);align-self:center;">پاسخ‌های آماده:</span>
          <button type="button" class="canned-btn" onclick="insertCanned('درود بر شما، در حال بررسی درخواست شما هستیم. لطفاً چند لحظه شکیبا باشید.')">در حال بررسی</button>
          <button type="button" class="canned-btn" onclick="insertCanned('مشکل اتصال شما بررسی و رفع گردید. لطفاً مجدداً اتصال را تست فرمایید.')">رفع مشکل اتصال</button>
          <button type="button" class="canned-btn" onclick="insertCanned('لطفاً نرم‌افزار خود را بروزرسانی کرده و لینک اشتراک را دوباره Update نمایید.')">بروزرسانی نرم‌افزار</button>
          <button type="button" class="canned-btn" onclick="insertCanned('فیش واریزی شما تایید شد و موجودی به حسابتان اضافه گردید.')">تایید واریزی</button>
        </div>

        <form method="POST" id="replyForm" style="display:flex;gap:10px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_reply">
          <input type="hidden" name="user_id" value="<?= htmlspecialchars($selectedUserId) ?>">
          <textarea name="message" id="replyInput" rows="2" class="input" placeholder="پاسخ خود را بنویسید (کاربر پیام را هم در مینی‌اپ و هم در تلگرام دریافت می‌کند)..." style="flex:1;resize:none;font-size:0.86rem;padding:8px 12px;" required></textarea>
          <button type="submit" class="btn btn-primary" style="padding:0 20px;font-size:0.86rem;display:flex;align-items:center;gap:6px;">
            <span>ارسال پاسخ</span> <?= icon('arrow-left', 14) ?>
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function insertCanned(text) {
  const inp = document.getElementById('replyInput');
  if (inp) {
    inp.value = text;
    inp.focus();
  }
}

// Auto scroll chat to bottom
const chatBox = document.getElementById('chatHistoryBox');
if (chatBox) {
  chatBox.scrollTop = chatBox.scrollHeight;
}

// Support Ctrl+Enter to submit
const replyInp = document.getElementById('replyInput');
if (replyInp) {
  replyInp.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      document.getElementById('replyForm').submit();
    }
  });
}
</script>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>
