#!/usr/bin/env python3
import sys
import argparse
import config
from bot.telegram_api import set_webhook, get_webhook_info, delete_webhook, get_me

def main():
    parser = argparse.ArgumentParser(description="DoumanPanelBot Webhook Tool")
    parser.add_argument("--set", type=str, help="ثبت وب‌هوک جدید با آدرس مشخص شده")
    parser.add_argument("--info", action="store_true", help="مشاهده وضعیت فعلی وب‌هوک در تلگرام")
    parser.add_argument("--del", dest="delete", action="store_true", help="حذف وب‌هوک فعلی")
    args = parser.parse_args()

    print("🤖 Douman Panel Bot - Webhook Manager")
    print(f"توکن فعال: {config.BOT_TOKEN[:10]}...{config.BOT_TOKEN[-5:] if config.BOT_TOKEN else 'تنظیم نشده'}")

    me = get_me()
    if not me.get("ok"):
        print(f"❌ خطا در اتصال به تلگرام: {me.get('description')}")
        sys.exit(1)

    bot_info = me.get("result", {})
    print(f"✅ متصل به ربات: {bot_info.get('first_name')} (@{bot_info.get('username')})")

    if args.set:
        url = args.set
        print(f"در حال تنظیم وب‌هوک روی: {url} ...")
        res = set_webhook(url, config.WEBHOOK_SECRET)
        print(f"نتیجه: {res.get('description', res)}")
    elif args.delete:
        print("در حال حذف وب‌هوک...")
        res = delete_webhook()
        print(f"نتیجه: {res.get('description', res)}")
    elif args.info or len(sys.argv) == 1:
        if config.WEBHOOK_URL and len(sys.argv) == 1 and not args.info:
            print(f"آدرس وب‌هوک پیش‌فرض در فایل .env: {config.WEBHOOK_URL}")
            res = set_webhook(config.WEBHOOK_URL, config.WEBHOOK_SECRET)
            print(f"نتیجه تنظیم خودکار: {res.get('description', res)}")
        print("\n📊 وضعیت وب‌هوک در تلگرام:")
        info = get_webhook_info().get("result", {})
        for k, v in info.items():
            print(f"  • {k}: {v}")

if __name__ == "__main__":
    main()
