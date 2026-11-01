import os
from pathlib import Path
from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent

# بارگذاری مقادیر از .env در صورت وجود
load_dotenv(BASE_DIR / ".env")

BOT_TOKEN = os.getenv("BOT_TOKEN", "").strip()
ADMIN_ID = int(os.getenv("ADMIN_ID", "0"))
BOT_USERNAME = os.getenv("BOT_USERNAME", "").lstrip("@").strip()
WEBHOOK_URL = os.getenv("WEBHOOK_URL", "").strip()
WEBHOOK_SECRET = os.getenv("WEBHOOK_SECRET", "").strip()

# نوع پایگاه داده
DB_TYPE = os.getenv("DB_TYPE", "sqlite").lower()
DB_FILE = BASE_DIR / "douman_bot.db"

# اطلاعات اتصال به MySQL در صورت انتخاب
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_NAME", "")
DB_USER = os.getenv("DB_USER", "")
DB_PASS = os.getenv("DB_PASS", "")

# مشخصات کارت جهت واریز کارت به کارت
CARD_NUMBER = os.getenv("CARD_NUMBER", "6037-0000-0000-0000")
CARD_HOLDER = os.getenv("CARD_HOLDER", "صاحب حساب")

# درگاه پرداخت
ZARINPAL_MERCHANT = os.getenv("ZARINPAL_MERCHANT", "")

# مسیر ذخیره فایل‌ها و لاگ‌ها
STORAGE_DIR = BASE_DIR / "storage"
STORAGE_DIR.mkdir(exist_ok=True)
