import json
import logging
import random
import string
from database.db import db
from panels import get_panel_driver
from bot import telegram_api as tg
from bot import keyboards as kb
from bot import messages as msg
import config

logger = logging.getLogger("douman_handlers")

def random_string(length=8):
    return ''.join(random.choices(string.ascii_lowercase + string.digits, k=length))

def process_update(update: dict):
    try:
        if "message" in update:
            handle_message(update["message"])
        elif "callback_query" in update:
            handle_callback_query(update["callback_query"])
    except Exception as e:
        logger.error(f"Error handling update: {e}", exc_info=True)

def handle_message(message: dict):
    user_data = message.get("from", {})
    user_id = user_data.get("id")
    if not user_id:
        return

    first_name = user_data.get("first_name", "کاربر")
    username = user_data.get("username", "")
    text = (message.get("text") or "").strip()

    # ایجاد یا دریافت اطلاعات کاربر در دیتابیس
    user = db.get_or_create_user(user_id, username, first_name)
    is_admin = (user_id == config.ADMIN_ID or user.get("is_admin") == 1)
    step = user.get("step", "none")

    # بررسی دکمه انصراف
    if text == "❌ انصراف و بازگشت":
        db.set_user_step(user_id, "none")
        tg.send_message(user_id, "عملیات لغو شد. به منوی اصلی بازگشتید.", reply_markup=kb.main_menu(is_admin))
        return

    # پردازش عکس رسید کارت به کارت
    if "photo" in message:
        if step == "awaiting_receipt":
            photo = message["photo"][-1]  # دریافت بالاترین کیفیت
            file_id = photo["file_id"]
            caption = message.get("caption", "")

            # ثبت تراکنش در وضعیت در انتظار تایید
            temp_data = json.loads(user.get("temp_data") or "{}")
            amount = temp_data.get("amount", 0)

            tx_id = db.add_transaction(user_id, amount, "card_to_card", caption, file_id)
            db.set_user_step(user_id, "none")

            tg.send_message(
                user_id,
                "✅ <b>رسید شما با موفقیت ثبت شد!</b>\n\nپس از بررسی و تایید مدیریت، موجودی شما به صورت خودکار شارژ خواهد شد.",
                reply_markup=kb.main_menu(is_admin)
            )

            # ارسال پیام به ادمین همراه با دکمه تایید و رد
            admin_msg = f"🔔 <b>رسید واریزی جدید دریافت شد!</b>\n\n" \
                        f"🆔 کاربر: <code>{user_id}</code> (@{username})\n" \
                        f"👤 نام: {first_name}\n" \
                        f"💰 مبلغ ادعایی: {amount:,} تومان\n" \
                        f"📝 توضیحات: {caption or 'بدون توضیحات'}\n\n" \
                        f"کد تراکنش: #{tx_id}"

            tg.send_photo(
                config.ADMIN_ID,
                photo=file_id,
                caption=admin_msg,
                reply_markup=kb.receipt_admin_keyboard(tx_id)
            )
            return

    # مراحل متنی کاربر (Step handling)
    if step == "awaiting_card_amount":
        try:
            amount = int(text.replace(",", "").replace("،", ""))
            if amount < 10000:
                tg.send_message(user_id, "⚠️ حداقل مبلغ جهت شارژ حساب ۱۰,۰۰۰ تومان می‌باشد. لطفاً مجدداً وارد کنید:")
                return
            db.set_user_step(user_id, "awaiting_receipt", {"amount": amount})
            pay_text = msg.CARD_PAYMENT_INFO.format(
                card_number=config.CARD_NUMBER,
                card_holder=config.CARD_HOLDER
            )
            pay_text += f"\n\nمبلغ انتخابی شما: <b>{amount:,} تومان</b>\nلطفاً پس از واریز، عکس فیش را ارسال نمایید."
            tg.send_message(user_id, pay_text, reply_markup=kb.cancel_keyboard())
            return
        except ValueError:
            tg.send_message(user_id, "⚠️ لطفاً مبلغ را به صورت عدد لاتین (مثلاً 50000) وارد نمایید:")
            return

    # مراحل ادمین
    if is_admin:
        if step == "admin_broadcast":
            db.set_user_step(user_id, "none")
            tg.send_message(user_id, "در حال ارسال پیام همگانی...")
            conn = db.get_connection()
            cursor = conn.cursor()
            cursor.execute("SELECT user_id FROM users")
            all_users = cursor.fetchall()
            conn.close()
            sent_count = 0
            for u in all_users:
                uid = u["user_id"] if isinstance(u, dict) else u[0]
                try:
                    tg.send_message(uid, f"📢 <b>اطلاعیه مدیریت:</b>\n\n{text}")
                    sent_count += 1
                except:
                    pass
            tg.send_message(user_id, f"✅ پیام به {sent_count} کاربر ارسال گردید.", reply_markup=kb.admin_menu())
            return

        if step == "admin_add_panel":
            # فرمت: نام|نوع|آدرس|یوزر|پس|ساب
            parts = text.split("|")
            if len(parts) >= 5:
                name = parts[0].strip()
                ptype = parts[1].strip()
                url = parts[2].strip()
                u_name = parts[3].strip()
                pwd = parts[4].strip()
                sub = parts[5].strip() if len(parts) > 5 else ""
                pid = db.add_panel(name, ptype, url, u_name, pwd, sub)
                db.set_user_step(user_id, "none")
                tg.send_message(user_id, f"✅ پنل <b>{name}</b> (کد #{pid}) با موفقیت ذخیره شد.", reply_markup=kb.admin_menu())
                return
            else:
                tg.send_message(user_id, "⚠️ فرمت اشتباه است. لطفاً مطابق الگو ارسال کنید یا دکمه انصراف را بزنید:")
                return

        if step == "admin_add_plan":
            # فرمت: کدپنل|نام|حجم(GB)|روز|قیمت(تومان)
            parts = text.split("|")
            if len(parts) == 5:
                try:
                    panel_id = int(parts[0].strip())
                    name = parts[1].strip()
                    traffic = int(parts[2].strip())
                    days = int(parts[3].strip())
                    price = int(parts[4].strip())
                    plan_id = db.add_plan(panel_id, name, traffic, days, price)
                    db.set_user_step(user_id, "none")
                    tg.send_message(user_id, f"✅ پلن <b>{name}</b> با موفقیت اضافه شد.", reply_markup=kb.admin_menu())
                    return
                except ValueError:
                    pass
            tg.send_message(user_id, "⚠️ مقادیر نامعتبر است. الگو: کدپنل|نام|حجم|روز|قیمت")
            return

    # دستورات و متن‌های عمومی
    if text == "/start":
        db.set_user_step(user_id, "none")
        welcome = msg.START_MESSAGE.format(first_name=first_name)
        tg.send_message(user_id, welcome, reply_markup=kb.main_menu(is_admin))
        return

    if text == "🛍 خرید اشتراک":
        panels = db.get_panels(active_only=True)
        if not panels:
            tg.send_message(user_id, "⚠️ در حال حاضر سرور یا لوکیشن فعالی تعریف نشده است. لطفاً بعداً مراجعه کنید.")
            return
        tg.send_message(
            user_id,
            "🌐 لطفاً لوکیشن یا پنل سرور مورد نظر خود را انتخاب کنید:",
            reply_markup=kb.panels_inline_keyboard(panels)
        )
        return

    if text == "📱 سرویس‌های من":
        services = db.get_user_services(user_id)
        if not services:
            tg.send_message(user_id, "شما در حال حاضر هیچ سرویس فعالی ندارید. می‌توانید از بخش 🛍 خرید اشتراک اقدام به تهیه نمایید.")
            return
        for s in services:
            stext = f"📦 <b>سرویس #{s['id']} ({s.get('panel_name', 'VPN')})</b>\n" \
                    f"👤 نام اکانت: <code>{s['service_username']}</code>\n" \
                    f"⏳ اعتبار: {s['days']} روز\n" \
                    f"💾 محدودیت حجم: {s['traffic_limit']} GB\n\n" \
                    f"🔗 <b>لینک ساب‌اسکریپشن:</b>\n<code>{s['subscription_url']}</code>"
            tg.send_message(user_id, stext, reply_markup=kb.service_actions_keyboard(s["id"]))
        return

    if text == "⚡ تست رایگان":
        # بررسی اینکه آیا کاربر قبلاً تست گرفته است یا خیر
        services = db.get_user_services(user_id)
        has_test = any("test" in s["service_username"] for s in services)
        if has_test:
            tg.send_message(user_id, "⚠️ شما قبلاً یکبار از اکانت تست رایگان استفاده کرده‌اید.")
            return

        panels = db.get_panels(active_only=True)
        if not panels:
            tg.send_message(user_id, "⚠️ سرور فعالی برای ارائه تست در دسترس نیست.")
            return

        panel = panels[0]
        try:
            driver = get_panel_driver(panel)
            test_username = f"test_{user_id}_{random_string(4)}"
            res = driver.create_user(test_username, traffic_gb=1, days=1)
            sub_url = res.get("subscription_url", "")
            db.add_service(user_id, panel["id"], None, test_username, sub_url, 1, 1)

            success_text = f"🎁 <b>اکانت تست رایگان شما ساخته شد!</b>\n\n" \
                           f"💾 حجم: 1 گیگابایت\n⏳ مدت: 24 ساعت\n\n" \
                           f"🔗 <b>لینک اتصال:</b>\n<code>{sub_url}</code>"
            tg.send_message(user_id, success_text)
        except Exception as e:
            tg.send_message(user_id, f"❌ خطا در ساخت تست رایگان: {e}")
        return

    if text == "💳 شارژ کیف پول":
        tg.send_message(
            user_id,
            "لطفاً مبلغی که می‌خواهید کیف پول خود را شارژ کنید (به تومان) وارد نمایید:\n(حداقل: 10,000 تومان)",
            reply_markup=kb.cancel_keyboard()
        )
        db.set_user_step(user_id, "awaiting_card_amount")
        return

    if text == "🚀 آموزش اتصال":
        tg.send_message(user_id, msg.HELP_MESSAGE)
        return

    if text == "☎️ پشتیبانی":
        s_text = msg.SUPPORT_MESSAGE.format(admin_username=config.BOT_USERNAME or "Admin")
        tg.send_message(user_id, s_text)
        return

    if text == "👤 پروفایل و موجودی":
        services = db.get_user_services(user_id)
        p_text = msg.PROFILE_MESSAGE.format(
            user_id=user_id,
            first_name=first_name,
            balance=user.get("balance", 0),
            active_count=len(services),
            join_date=user.get("created_at", "")[:10]
        )
        tg.send_message(user_id, p_text)
        return

    # منوی اختصاصی ادمین
    if is_admin and (text in ["⚙️ پنل مدیریت ادمین", "/admin"]):
        tg.send_message(user_id, "🛠 <b>پنل مدیریت پیشرفته Douman Panel:</b>", reply_markup=kb.admin_menu())
        return

    if is_admin:
        if text == "📊 آمار و گزارشات":
            stats = db.get_stats()
            s_msg = f"📊 <b>گزارش آمار ربات:</b>\n\n" \
                    f"👥 کل کاربران: {stats['users']}\n" \
                    f"📦 سرویس‌های فعال: {stats['services']}\n" \
                    f"💰 مجموع فروش: {stats['sales']:,} تومان"
            tg.send_message(user_id, s_msg)
            return

        if text == "🖥 افزودن پنل سرور":
            hint = "برای افزودن سرور، اطلاعات را با فرمت زیر با خط عمودی | ارسال کنید:\n\n" \
                   "<code>نام پنل|نوع(marzban/xui/hiddify)|آدرس کامل پنل|نام کاربری|کلمه عبور|آدرس ساب(اختیاری)</code>\n\n" \
                   "مثال مرزبان:\n<code>آلمان 1|marzban|https://panel.site.com:8000|admin|mypassword|https://sub.site.com</code>"
            tg.send_message(user_id, hint, reply_markup=kb.cancel_keyboard())
            db.set_user_step(user_id, "admin_add_panel")
            return

        if text == "📦 افزودن پلن جدید":
            panels = db.get_panels(active_only=False)
            if not panels:
                tg.send_message(user_id, "ابتدا یک پنل سرور تعریف کنید.")
                return
            p_list = "\n".join([f"کد: {p['id']} - {p['name']}" for p in panels])
            hint = f"لیست پنل‌های موجود:\n{p_list}\n\n" \
                   f"الگوی ثبت پلن:\n<code>کدپنل|نام پلن|حجم(GB)|روز|قیمت(تومان)</code>\n" \
                   f"مثال:\n<code>{panels[0]['id']}|یک ماهه 30 گیگ|30|30|75000</code>"
            tg.send_message(user_id, hint, reply_markup=kb.cancel_keyboard())
            db.set_user_step(user_id, "admin_add_plan")
            return

        if text == "📢 ارسال پیام همگانی":
            tg.send_message(user_id, "لطفاً پیام خود را جهت ارسال همگانی به همه کاربران بنویسید:", reply_markup=kb.cancel_keyboard())
            db.set_user_step(user_id, "admin_broadcast")
            return

        if text == "🔙 بازگشت به منوی اصلی":
            tg.send_message(user_id, "به منوی اصلی بازگشتید.", reply_markup=kb.main_menu(is_admin))
            return

def handle_callback_query(callback: dict):
    cb_id = callback.get("id")
    from_user = callback.get("from", {})
    user_id = from_user.get("id")
    data = callback.get("data", "")
    message = callback.get("message", {})
    message_id = message.get("message_id")

    tg.answer_callback(cb_id)

    if data == "cancel":
        tg.edit_message_text(user_id, message_id, "عملیات منقضی یا لغو شد.")
        return

    # انتخاب لوکیشن / پنل
    if data.startswith("select_panel_"):
        panel_id = int(data.split("_")[2])
        panel = db.get_panel(panel_id)
        if not panel:
            tg.edit_message_text(user_id, message_id, "پنل مورد نظر یافت نشد.")
            return

        plans = db.get_plans(panel_id)
        if not plans:
            tg.edit_message_text(user_id, message_id, f"در حال حاضر برای پنل {panel['name']} هیچ پلنی تعریف نشده است.")
            return

        tg.edit_message_text(
            user_id,
            message_id,
            f"📦 پلن‌های موجود برای لوکیشن <b>{panel['name']}</b>:\nیکی از بسته‌های زیر را انتخاب فرمایید:",
            reply_markup=kb.plans_inline_keyboard(plans)
        )
        return

    # بازگشت به پنل‌ها
    if data == "back_to_panels":
        panels = db.get_panels(active_only=True)
        tg.edit_message_text(
            user_id,
            message_id,
            "🌐 لطفاً لوکیشن یا پنل سرور مورد نظر خود را انتخاب کنید:",
            reply_markup=kb.panels_inline_keyboard(panels)
        )
        return

    # انتخاب پلن
    if data.startswith("select_plan_"):
        plan_id = int(data.split("_")[2])
        plan = db.get_plan(plan_id)
        panel = db.get_panel(plan["panel_id"])
        user = db.get_or_create_user(user_id)
        balance = user.get("balance", 0)

        price = plan["price"]
        confirm_text = f"📋 <b>پیش‌فاکتور خرید اشتراک:</b>\n\n" \
                       f"🌐 سرور: <b>{panel['name']}</b>\n" \
                       f"📦 بسته: <b>{plan['name']}</b>\n" \
                       f"💾 ترافیک: {plan['traffic_gb']} گیگابایت\n" \
                       f"⏳ مدت زمان: {plan['days']} روز\n" \
                       f"💵 مبلغ: <b>{price:,} تومان</b>\n\n" \
                       f"💰 موجودی کیف پول شما: <b>{balance:,} تومان</b>\n"

        if balance < price:
            confirm_text += f"\n⚠️ موجودی شما کافی نیست (کسری: {price - balance:,} تومان). لطفاً کیف پول خود را شارژ کنید."

        tg.edit_message_text(user_id, message_id, confirm_text, reply_markup=kb.confirm_buy_keyboard(plan_id))
        return

    # تایید خرید و کسر موجودی
    if data.startswith("confirm_buy_"):
        plan_id = int(data.split("_")[2])
        plan = db.get_plan(plan_id)
        price = plan["price"]
        user = db.get_or_create_user(user_id)

        if not db.deduct_balance(user_id, price):
            tg.edit_message_text(user_id, message_id, "❌ موجودی کیف پول شما کافی نیست. لطفاً ابتدا حساب خود را شارژ کنید.")
            return

        panel = db.get_panel(plan["panel_id"])
        tg.edit_message_text(user_id, message_id, "⏳ در حال ساخت کانفیگ در پنل سرور، لطفاً چند لحظه شکیبا باشید...")

        try:
            driver = get_panel_driver(panel)
            username = f"u{user_id}_{random_string(4)}"
            res = driver.create_user(username, plan["traffic_gb"], plan["days"])
            sub_url = res.get("subscription_url", "")

            db.add_service(user_id, panel["id"], plan["id"], username, sub_url, plan["traffic_gb"], plan["days"])

            success = msg.SERVICE_CREATED_SUCCESS.format(
                service_username=username,
                panel_name=panel["name"],
                traffic_gb=plan["traffic_gb"],
                days=plan["days"],
                sub_url=sub_url
            )
            tg.send_message(user_id, success)
        except Exception as e:
            # عودت وجه به کیف پول کاربر در صورت بروز مشکل در ساخت
            db.add_balance(user_id, price)
            tg.send_message(user_id, f"❌ خطا در ساخت سرویس در پنل: {e}\nمبلغ به موجودی شما بازگردانده شد.")
        return

    # استعلام حجم سرویس
    if data.startswith("check_usage_"):
        service_id = int(data.split("_")[2])
        conn = db.get_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM services WHERE id = ?", (service_id,))
        s = cursor.fetchone()
        conn.close()
        if not s:
            return
        panel = db.get_panel(s["panel_id"])
        try:
            driver = get_panel_driver(panel)
            usage = driver.get_user_usage(s["service_username"])
            if usage.get("ok"):
                u_text = f"📊 <b>وضعیت مصرف سرویس #{service_id}:</b>\n\n" \
                         f"👤 نام اکانت: <code>{usage['username']}</code>\n" \
                         f"📈 مصرف شده: <b>{usage['used_gb']} GB</b>\n" \
                         f"💾 کل حجم: <b>{usage['limit_gb']} GB</b>\n" \
                         f"🟢 وضعیت: {usage.get('status', 'فعال')}"
                tg.send_message(user_id, u_text)
            else:
                tg.send_message(user_id, f"⚠️ {usage.get('message', 'خطا در دریافت اطلاعات')}")
        except Exception as e:
            tg.send_message(user_id, f"خطا در استعلام: {e}")
        return

    # ارسال کد QR
    if data.startswith("get_qr_"):
        service_id = int(data.split("_")[2])
        conn = db.get_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT subscription_url FROM services WHERE id = ?", (service_id,))
        row = cursor.fetchone()
        conn.close()
        if row and row["subscription_url"]:
            qr_api = f"https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={row['subscription_url']}"
            tg.send_photo(user_id, qr_api, caption=f"📲 بارکد QR سرویس #{service_id}\nکافیست در اپلیکیشن خود اسکن نمایید.")
        return

    # عملیات ادمین برای تایید/رد رسید
    if data.startswith("approve_tx_"):
        tx_id = int(data.split("_")[2])
        tx = db.get_transaction(tx_id)
        if tx and tx["status"] == "pending":
            db.update_transaction_status(tx_id, "approved")
            db.add_balance(tx["user_id"], tx["amount"])
            tg.edit_message_text(user_id, message_id, f"✅ تراکنش #{tx_id} تایید شد و مبلغ {tx['amount']:,} تومان به حساب کاربر افزوده شد.")
            tg.send_message(tx["user_id"], f"🎉 <b>واریز شما تایید شد!</b>\n\nمبلغ {tx['amount']:,} تومان به کیف پول شما اضافه گردید.")
        return

    if data.startswith("reject_tx_"):
        tx_id = int(data.split("_")[2])
        tx = db.get_transaction(tx_id)
        if tx and tx["status"] == "pending":
            db.update_transaction_status(tx_id, "rejected")
            tg.edit_message_text(user_id, message_id, f"❌ تراکنش #{tx_id} رد شد.")
            tg.send_message(tx["user_id"], "⚠️ متاسفانه فیش ارسالی شما توسط مدیریت تایید نشد. در صورت بروز اشتباه با پشتیبانی تماس بگیرید.")
        return
