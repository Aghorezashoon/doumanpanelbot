<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

$msg = '';
$msgType = 'success';

// Handle Save Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedTiers = $_POST['tiers'] ?? [];
    
    // Fetch all panel IDs
    $allPanels = $pdo->query("SELECT id FROM marzban_panel")->fetchAll(PDO::FETCH_COLUMN);
    
    $updateStmt = $pdo->prepare("UPDATE marzban_panel SET allowed_tiers = ?, agent = ? WHERE id = ?");
    
    foreach ($allPanels as $panelId) {
        $pTiers = $submittedTiers[$panelId] ?? [];
        $selected = [];
        
        if (!empty($pTiers['user'])) {
            $selected[] = 'user';
        }
        if (!empty($pTiers['agent'])) {
            $selected[] = 'agent';
        }
        if (!empty($pTiers['agent_adv'])) {
            $selected[] = 'agent_adv';
        }
        if (!empty($pTiers['seller'])) {
            $selected[] = 'seller';
        }
        
        $tiersStr = implode(',', $selected);
        
        // Compute legacy agent column for maximum compatibility
        $hasUser = in_array('user', $selected, true);
        $hasAgent = in_array('agent', $selected, true);
        $hasAdv = in_array('agent_adv', $selected, true);
        $hasSeller = in_array('seller', $selected, true);
        
        $legacyAgent = 'all';
        if ($hasAdv && !$hasUser && !$hasAgent && !$hasSeller) {
            $legacyAgent = 'n2';
        } elseif ($hasAgent && !$hasUser && !$hasAdv && !$hasSeller) {
            $legacyAgent = 'n';
        } elseif ($hasSeller && !$hasUser && !$hasAgent && !$hasAdv) {
            $legacyAgent = 'seller';
        } elseif (!$hasUser && !$hasAgent && !$hasAdv && !$hasSeller) {
            $legacyAgent = 'none';
        } else {
            $legacyAgent = 'all';
        }
        
        $updateStmt->execute([$tiersStr, $legacyAgent, $panelId]);
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'تنظیمات دسترسی با موفقیت ذخیره شد.']);
        exit;
    }
    
    $msg = 'تنظیمات سطح دسترسی پنل‌ها با موفقیت به‌روزرسانی شد.';
}

// Fetch all panels
$stmt = $pdo->query("SELECT id, name_panel, type, status, agent, allowed_tiers FROM marzban_panel ORDER BY display_order, id");
$panels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'مدیریت سطح دسترسی پنل‌ها';
$pageLede = 'تعیین دسترسی هر سرور به تفکیک کاربر عادی، نماینده عادی و نماینده پیشرفته';
$activeNav = 'panels_access';

include __DIR__ . '/inc/layout_head.php';
?>

<style>
.access-table-wrap {
  background: var(--sf, #1e293b);
  border: 1px solid var(--bd, #334155);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.access-tbl {
  width: 100%;
  border-collapse: collapse;
  text-align: right;
  font-size: .88rem;
}
.access-tbl th {
  background: rgba(0,0,0,0.2);
  color: var(--mute, #94a3b8);
  padding: 14px 16px;
  font-weight: 700;
  border-bottom: 1px solid var(--bd, #334155);
  font-size: .82rem;
  letter-spacing: .02em;
}
.access-tbl td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  vertical-align: middle;
}
.access-tbl tr:last-child td {
  border-bottom: none;
}
.access-tbl tr:hover td {
  background: rgba(255,255,255,0.02);
}
.badge-type {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: .75rem;
  font-weight: 600;
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}
.badge-status-on {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: .75rem;
  font-weight: 600;
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
}
.badge-status-off {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: .75rem;
  font-weight: 600;
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

/* iOS Toggle Switch */
.switch-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}
.switch-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}
.switch-slider {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  background-color: #475569;
  border-radius: 24px;
  transition: all .25s ease;
}
.switch-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  border-radius: 50%;
  transition: all .25s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}
.switch-input:checked + .switch-slider {
  background-color: #22c55e;
}
.switch-input:checked + .switch-slider:before {
  transform: translateX(20px);
}
.switch-text {
  font-size: .82rem;
  font-weight: 600;
  color: var(--text, #f8fafc);
}
.btn-save-access {
  background: var(--ac, #0ea5e9);
  color: #fff;
  border: none;
  padding: 12px 28px;
  border-radius: 8px;
  font-weight: 700;
  font-size: .9rem;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(14, 165, 233, 0.4);
  transition: all .15s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.btn-save-access:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
}
.alert-box {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: .86rem;
  font-weight: 600;
}
.alert-box.success {
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.4);
  color: #86efac;
}
#toast {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: #10b981;
  color: #fff;
  padding: 10px 24px;
  border-radius: 30px;
  font-weight: bold;
  font-size: .85rem;
  box-shadow: 0 8px 24px rgba(0,0,0,0.3);
  opacity: 0;
  transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 9999;
}
#toast.show {
  transform: translateX(-50%) translateY(0);
  opacity: 1;
}
</style>

<div id="toast">✅ تغییرات با موفقیت ذخیره شد</div>

<?php if (!empty($msg)): ?>
  <div class="alert-box success fade-up">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<form method="POST" id="accessForm">
  <div class="access-table-wrap fade-up">
    <div style="padding:16px 20px;border-bottom:1px solid var(--bd,#334155);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
      <div>
        <h3 style="margin:0;font-size:1.05rem;color:var(--text,#fff);display:flex;align-items:center;gap:8px">
          <?= icon('server', 20) ?> تنظیم دسترسی پنل‌ها و سرورها
        </h3>
        <p style="margin:4px 0 0;font-size:.8rem;color:var(--mute,#94a3b8)">
          برای هر پنل می‌توانید مشخص کنید کدام گروه از مشتریان امکان مشاهده و خرید آن را داشته باشند.
        </p>
      </div>
      <div>
        <button type="submit" class="btn-save-access">
          <?= icon('check', 16) ?> ذخیره تغییرات دسترسی
        </button>
      </div>
    </div>

    <div style="overflow-x:auto">
      <table class="access-tbl">
        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th>نام پنل / سرور</th>
            <th style="width:120px">نوع ارائه‌دهنده</th>
            <th style="width:100px">وضعیت سرور</th>
            <th style="width:170px;text-align:center">۱. کاربر عادی</th>
            <th style="width:170px;text-align:center">۲. نماینده عادی</th>
            <th style="width:170px;text-align:center">۳. نماینده پیشرفته</th>
            <th style="width:170px;text-align:center">۴. فروشنده</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($panels)): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:30px;color:var(--mute)">هیچ پنلی یافت نشد.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($panels as $idx => $p): 
              $pId = $p['id'];
              $allowed = !empty($p['allowed_tiers']) ? array_map('trim', explode(',', strtolower($p['allowed_tiers']))) : [];
              
              // If not set yet, parse legacy agent column
              if (empty($p['allowed_tiers'])) {
                  $legacy = $p['agent'] ?? 'all';
                  if ($legacy === 'all') {
                      $allowed = ['user', 'agent', 'agent_adv', 'seller'];
                  } elseif ($legacy === 'n2') {
                      $allowed = ['agent_adv'];
                  } elseif ($legacy === 'n') {
                      $allowed = ['agent'];
                  } elseif ($legacy === 'seller') {
                      $allowed = ['seller'];
                  }
              }
              
              $isUser = in_array('user', $allowed, true);
              $isAgent = in_array('agent', $allowed, true);
              $isAdv = in_array('agent_adv', $allowed, true);
              $isSeller = in_array('seller', $allowed, true);
              
              $typeLabel = $p['type'];
              if ($p['type'] === 'solidlayer') $typeLabel = 'SolidLayer';
              elseif ($p['type'] === 'x-ui_single') $typeLabel = 'سنایی (Sanayi)';
              elseif ($p['type'] === 'marzban') $typeLabel = 'مرزبان';
            ?>
              <tr>
                <td style="color:var(--mute);font-weight:700"><?= $idx + 1 ?></td>
                <td>
                  <strong style="color:var(--text,#fff);font-size:.92rem"><?= htmlspecialchars($p['name_panel']) ?></strong>
                  <div style="font-size:.76rem;color:var(--mute);margin-top:3px">کد پنل: <?= htmlspecialchars($p['code_panel'] ?? $p['id']) ?></div>
                </td>
                <td>
                  <span class="badge-type"><?= htmlspecialchars($typeLabel) ?></span>
                </td>
                <td>
                  <?php if ($p['status'] === 'active'): ?>
                    <span class="badge-status-on">● فعال</span>
                  <?php else: ?>
                    <span class="badge-status-off">○ غیرفعال</span>
                  <?php endif; ?>
                </td>

                <!-- ۱. کاربر عادی -->
                <td style="text-align:center">
                  <label class="switch-label">
                    <input type="checkbox" class="switch-input auto-sync" name="tiers[<?= $pId ?>][user]" value="1" <?= $isUser ? 'checked' : '' ?>>
                    <span class="switch-slider"></span>
                    <span class="switch-text"><?= $isUser ? 'روشن' : 'خاموش' ?></span>
                  </label>
                </td>

                <!-- ۲. نماینده عادی -->
                <td style="text-align:center">
                  <label class="switch-label">
                    <input type="checkbox" class="switch-input auto-sync" name="tiers[<?= $pId ?>][agent]" value="1" <?= $isAgent ? 'checked' : '' ?>>
                    <span class="switch-slider"></span>
                    <span class="switch-text"><?= $isAgent ? 'روشن' : 'خاموش' ?></span>
                  </label>
                </td>

                <!-- ۳. نماینده پیشرفته -->
                <td style="text-align:center">
                  <label class="switch-label">
                    <input type="checkbox" class="switch-input auto-sync" name="tiers[<?= $pId ?>][agent_adv]" value="1" <?= $isAdv ? 'checked' : '' ?>>
                    <span class="switch-slider"></span>
                    <span class="switch-text"><?= $isAdv ? 'روشن' : 'خاموش' ?></span>
                  </label>
                </td>

                <!-- ۴. فروشنده -->
                <td style="text-align:center">
                  <label class="switch-label">
                    <input type="checkbox" class="switch-input auto-sync" name="tiers[<?= $pId ?>][seller]" value="1" <?= $isSeller ? 'checked' : '' ?>>
                    <span class="switch-slider"></span>
                    <span class="switch-text"><?= $isSeller ? 'روشن' : 'خاموش' ?></span>
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div style="padding:16px 20px;border-top:1px solid var(--bd,#334155);background:rgba(0,0,0,0.1);display:flex;justify-content:flex-end">
      <button type="submit" class="btn-save-access">
        <?= icon('check', 16) ?> ذخیره تغییرات دسترسی
      </button>
    </div>
  </div>
</form>

<script>
// Toggle text update and instant autosave
document.querySelectorAll('.switch-input').forEach(function(input) {
  input.addEventListener('change', function() {
    const textSpan = this.parentElement.querySelector('.switch-text');
    if (textSpan) {
      textSpan.textContent = this.checked ? 'روشن' : 'خاموش';
    }
    
    // Auto-save via AJAX
    const form = document.getElementById('accessForm');
    const formData = new FormData(form);
    
    fetch('panels_access.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast();
      }
    })
    .catch(() => {});
  });
});

function showToast() {
  const toast = document.getElementById('toast');
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 2200);
}
</script>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>
