import os
import sys
import logging
from flask import Flask, request, jsonify, render_template_string
import config
from bot.handlers import process_update
from bot.telegram_api import get_me, get_webhook_info
from database.db import db

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s"
)
logger = logging.getLogger("douman_app")

app = Flask(__name__)
app.secret_key = config.WEBHOOK_SECRET or "douman_panel_secret_key"

DASHBOARD_HTML = """
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد پایتون دمان پنل بات (DoumanPanelBot)</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Vazirmatn", Tahoma, sans-serif; }
        body { background: #0f172a; color: #f8fafc; margin: 0; padding: 24px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { background: #1e293b; border-radius: 16px; padding: 24px; border: 1px solid #334155; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        h1 { margin: 0; color: #38bdf8; font-size: 22px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-num { font-size: 28px; font-weight: 700; color: #38bdf8; margin-top: 8px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .badge-ok { background: #065f46; color: #a7f3d0; padding: 4px 10px; border-radius: 6px; font-size: 13px; }
        .badge-err { background: #7f1d1d; color: #fecaca; padding: 4px 10px; border-radius: 6px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 10px; text-align: right; border-bottom: 1px solid #334155; }
        th { color: #94a3b8; font-size: 13px; }
        code { background: #0f172a; padding: 3px 8px; border-radius: 4px; color: #38bdf8; direction: ltr; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>🤖 دمان پنل بات (DoumanPanelBot - Python)</h1>
            <p style="color: #94a3b8; margin: 4px 0 0 0; font-size: 14px;">در حال اجرا روی موتور پایتون (cPanel Setup Python App / WSGI)</p>
        </div>
        <div>
            <span class="badge-ok">🟢 وضعیت: فعال</span>
        </div>
    </div>

    <div class="grid-3">
        <div class="stat-card">
            <div style="color: #94a3b8; font-size: 13px;">👥 تعداد کاربران</div>
            <div class="stat-num">{{ stats.users }}</div>
        </div>
        <div class="stat-card">
            <div style="color: #94a3b8; font-size: 13px;">📦 سرویس‌های فعال</div>
            <div class="stat-num">{{ stats.services }}</div>
        </div>
        <div class="stat-card">
            <div style="color: #94a3b8; font-size: 13px;">💰 مجموع فروش</div>
            <div class="stat-num">{{ "{:,}".format(stats.sales) }} ت</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8;">🌐 وضعیت اتصال تلگرام</h3>
        <table>
            <tr><td>نام و یوزرنیم ربات:</td><td><strong>{{ bot_me.first_name }} (@{{ bot_me.username }})</strong></td></tr>
            <tr><td>آیدی عددی ربات:</td><td><code>{{ bot_me.id }}</code></td></tr>
            <tr><td>آدرس وب‌هوک در تلگرام:</td><td><code>{{ webhook_info.url or 'هنوز ثبت نشده است' }}</code></td></tr>
            <tr><td>پیام‌های در انتظار صف:</td><td>{{ webhook_info.pending_update_count }}</td></tr>
            <tr><td>آخرین خطای وب‌هوک:</td><td style="color: #f87171;">{{ webhook_info.last_error_message or 'بدون خطا' }}</td></tr>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8;">🖥 اطلاعات سرور و پنل پایتون</h3>
        <table>
            <tr><td>نسخه پایتون:</td><td><code>{{ python_version }}</code></td></tr>
            <tr><td>موتور WSGI:</td><td>Phusion Passenger (cPanel / DirectAdmin)</td></tr>
            <tr><td>نوع پایگاه داده:</td><td><code>{{ db_type }}</code></td></tr>
            <tr><td>مسیر پروژه:</td><td><code>{{ base_dir }}</code></td></tr>
        </table>
    </div>
</div>
</body>
</html>
"""

@app.route("/", methods=["GET"])
def index():
    bot_info = {}
    hook_info = {}
    try:
        bot_res = get_me()
        if bot_res.get("ok"):
            bot_info = bot_res.get("result", {})
        hook_res = get_webhook_info()
        if hook_res.get("ok"):
            hook_info = hook_res.get("result", {})
    except Exception as e:
        logger.error(f"Error fetching bot info: {e}")

    stats = db.get_stats()
    return render_template_string(
        DASHBOARD_HTML,
        stats=stats,
        bot_me=bot_info,
        webhook_info=hook_info,
        python_version=sys.version.split()[0],
        db_type=config.DB_TYPE,
        base_dir=str(config.BASE_DIR)
    )

@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status": "healthy",
        "app": "DoumanPanelBot",
        "version": "2.0.0",
        "python": sys.version.split()[0]
    })

@app.route("/webhook", methods=["POST"])
@app.route("/", methods=["POST"])
def webhook():
    # بررسی کلید مخفی در صورت فعال بودن
    if config.WEBHOOK_SECRET:
        token_header = request.headers.get("X-Telegram-Bot-Api-Secret-Token", "")
        if token_header and token_header != config.WEBHOOK_SECRET:
            return jsonify({"status": "unauthorized"}), 403

    update = request.get_json(force=True, silent=True)
    if not update:
        return jsonify({"status": "invalid json"}), 400

    # پردازش آپدیت به صورت همگام در WSGI
    process_update(update)

    return jsonify({"ok": True}), 200

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
