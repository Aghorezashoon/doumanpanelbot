import os
import sys

# افزودن مسیر پوشه فعلی به پایتون پث
CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
if CURRENT_DIR not in sys.path:
    sys.path.insert(0, CURRENT_DIR)

# ایمپورت اپلیکیشن فلاسک جهت هندل وب‌هوک و درخواست‌های مسافر (Passenger)
from douman_app import app as application

if __name__ == '__main__':
    application.run()
