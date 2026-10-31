import json

def reply_keyboard(buttons, resize=True):
    return json.dumps({
        "keyboard": buttons,
        "resize_keyboard": resize,
        "one_time_keyboard": False
    })

def inline_keyboard(buttons):
    return json.dumps({
        "inline_keyboard": buttons
    })

def main_menu(is_admin: bool = False):
    keyboard = [
        [{"text": "🛍 خرید اشتراک"}, {"text": "📱 سرویس‌های من"}],
        [{"text": "⚡ تست رایگان"}, {"text": "💳 شارژ کیف پول"}],
        [{"text": "🚀 آموزش اتصال"}, {"text": "☎️ پشتیبانی"}],
        [{"text": "👤 پروفایل و موجودی"}]
    ]
    if is_admin:
        keyboard.append([{"text": "⚙️ پنل مدیریت ادمین"}])
    return reply_keyboard(keyboard)

def admin_menu():
    keyboard = [
        [{"text": "📊 آمار و گزارشات"}, {"text": "💳 واریزهای در انتظار"}],
        [{"text": "🖥 افزودن پنل سرور"}, {"text": "📦 افزودن پلن جدید"}],
        [{"text": "📢 ارسال پیام همگانی"}, {"text": "➕ افزایش موجودی کاربر"}],
        [{"text": "🔙 بازگشت به منوی اصلی"}]
    ]
    return reply_keyboard(keyboard)

def cancel_keyboard():
    return reply_keyboard([[{"text": "❌ انصراف و بازگشت"}]])

def panels_inline_keyboard(panels):
    buttons = []
    for p in panels:
        buttons.append([{"text": f"🌐 {p['name']} ({p['panel_type']})", "callback_data": f"select_panel_{p['id']}"}])
    buttons.append([{"text": "❌ انصراف", "callback_data": "cancel"}])
    return inline_keyboard(buttons)

def plans_inline_keyboard(plans):
    buttons = []
    for pl in plans:
        price_formatted = f"{pl['price']:,} تومان"
        buttons.append([{
            "text": f"📦 {pl['name']} | {pl['traffic_gb']}GB | {pl['days']} روز | {price_formatted}",
            "callback_data": f"select_plan_{pl['id']}"
        }])
    buttons.append([{"text": "🔙 بازگشت به پنل‌ها", "callback_data": "back_to_panels"}])
    return inline_keyboard(buttons)

def confirm_buy_keyboard(plan_id):
    buttons = [
        [{"text": "✅ پرداخت از کیف پول و فعال‌سازی", "callback_data": f"confirm_buy_{plan_id}"}],
        [{"text": "💳 افزایش موجودی کیف پول", "callback_data": "charge_wallet"}],
        [{"text": "❌ انصراف", "callback_data": "cancel"}]
    ]
    return inline_keyboard(buttons)

def service_actions_keyboard(service_id):
    buttons = [
        [
            {"text": "📊 استعلام حجم و وضعیت", "callback_data": f"check_usage_{service_id}"},
            {"text": "📱 دریافت بارکد QR", "callback_data": f"get_qr_{service_id}"}
        ],
        [{"text": "🔄 تمدید این سرویس", "callback_data": f"renew_service_{service_id}"}]
    ]
    return inline_keyboard(buttons)

def receipt_admin_keyboard(tx_id):
    buttons = [
        [
            {"text": "✅ تایید واریز", "callback_data": f"approve_tx_{tx_id}"},
            {"text": "❌ رد واریز", "callback_data": f"reject_tx_{tx_id}"}
        ]
    ]
    return inline_keyboard(buttons)
