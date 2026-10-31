import sqlite3
import json
import logging
from datetime import datetime
import config

logger = logging.getLogger("douman_db")

class Database:
    def __init__(self):
        self.db_type = config.DB_TYPE
        self.init_db()

    def get_connection(self):
        if self.db_type == "mysql" and config.DB_NAME:
            import pymysql
            return pymysql.connect(
                host=config.DB_HOST,
                port=config.DB_PORT,
                user=config.DB_USER,
                password=config.DB_PASS,
                database=config.DB_NAME,
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor,
                autocommit=True
            )
        else:
            conn = sqlite3.connect(str(config.DB_FILE))
            conn.row_factory = sqlite3.Row
            return conn

    def init_db(self):
        conn = self.get_connection()
        cursor = conn.cursor()

        is_sqlite = (self.db_type != "mysql" or not config.DB_NAME)
        auto_inc = "AUTOINCREMENT" if is_sqlite else "AUTO_INCREMENT"
        pk_def = f"INTEGER PRIMARY KEY {auto_inc}"

        # جدول کاربران
        cursor.execute(f"""
            CREATE TABLE IF NOT EXISTS users (
                id {pk_def},
                user_id BIGINT UNIQUE NOT NULL,
                username TEXT,
                first_name TEXT,
                balance BIGINT DEFAULT 0,
                phone TEXT,
                step TEXT DEFAULT 'none',
                temp_data TEXT,
                is_admin INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        # جدول پنل‌های VPN
        cursor.execute(f"""
            CREATE TABLE IF NOT EXISTS panels (
                id {pk_def},
                name TEXT NOT NULL,
                panel_type TEXT NOT NULL, -- marzban, xui, hiddify
                url TEXT NOT NULL,
                username TEXT,
                password TEXT,
                sub_url TEXT,
                extra_data TEXT,
                status INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        # جدول پلن‌ها / بسته‌ها
        cursor.execute(f"""
            CREATE TABLE IF NOT EXISTS plans (
                id {pk_def},
                panel_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                traffic_gb INTEGER NOT NULL,
                days INTEGER NOT NULL,
                price BIGINT NOT NULL,
                status INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        # جدول سرویس‌های خریداری شده
        cursor.execute(f"""
            CREATE TABLE IF NOT EXISTS services (
                id {pk_def},
                user_id BIGINT NOT NULL,
                panel_id INTEGER NOT NULL,
                plan_id INTEGER,
                service_username TEXT NOT NULL,
                subscription_url TEXT,
                config_data TEXT,
                traffic_limit BIGINT DEFAULT 0,
                days INTEGER DEFAULT 30,
                status TEXT DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        # جدول تراکنش‌ها و کارت به کارت
        cursor.execute(f"""
            CREATE TABLE IF NOT EXISTS transactions (
                id {pk_def},
                user_id BIGINT NOT NULL,
                amount BIGINT NOT NULL,
                payment_method TEXT NOT NULL,
                description TEXT,
                receipt_file_id TEXT,
                status TEXT DEFAULT 'pending', -- pending, approved, rejected
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        # جدول تنظیمات کلیدی
        cursor.execute(f"""
            CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(100) PRIMARY KEY,
                `value` TEXT
            )
        """)

        if is_sqlite:
            conn.commit()
        conn.close()

    def get_or_create_user(self, user_id: int, username: str = "", first_name: str = ""):
        conn = self.get_connection()
        cursor = conn.cursor()
        is_admin = 1 if user_id == config.ADMIN_ID else 0

        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("SELECT * FROM users WHERE user_id = %s", (user_id,))
            user = cursor.fetchone()
            if not user:
                cursor.execute(
                    "INSERT INTO users (user_id, username, first_name, is_admin) VALUES (%s, %s, %s, %s)",
                    (user_id, username or "", first_name or "", is_admin)
                )
                cursor.execute("SELECT * FROM users WHERE user_id = %s", (user_id,))
                user = cursor.fetchone()
        else:
            cursor.execute("SELECT * FROM users WHERE user_id = ?", (user_id,))
            row = cursor.fetchone()
            if not row:
                cursor.execute(
                    "INSERT INTO users (user_id, username, first_name, is_admin) VALUES (?, ?, ?, ?)",
                    (user_id, username or "", first_name or "", is_admin)
                )
                conn.commit()
                cursor.execute("SELECT * FROM users WHERE user_id = ?", (user_id,))
                row = cursor.fetchone()
            user = dict(row) if row else None

        conn.close()
        return user

    def set_user_step(self, user_id: int, step: str, temp_data: dict = None):
        conn = self.get_connection()
        cursor = conn.cursor()
        data_str = json.dumps(temp_data, ensure_ascii=False) if temp_data else None

        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("UPDATE users SET step = %s, temp_data = %s WHERE user_id = %s", (step, data_str, user_id))
        else:
            cursor.execute("UPDATE users SET step = ?, temp_data = ? WHERE user_id = ?", (step, data_str, user_id))
            conn.commit()
        conn.close()

    def add_balance(self, user_id: int, amount: int):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("UPDATE users SET balance = balance + %s WHERE user_id = %s", (amount, user_id))
        else:
            cursor.execute("UPDATE users SET balance = balance + ? WHERE user_id = ?", (amount, user_id))
            conn.commit()
        conn.close()

    def deduct_balance(self, user_id: int, amount: int) -> bool:
        user = self.get_or_create_user(user_id)
        if not user or user.get("balance", 0) < amount:
            return False
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("UPDATE users SET balance = balance - %s WHERE user_id = %s", (amount, user_id))
        else:
            cursor.execute("UPDATE users SET balance = balance - ? WHERE user_id = ?", (amount, user_id))
            conn.commit()
        conn.close()
        return True

    def get_panels(self, active_only: bool = True):
        conn = self.get_connection()
        cursor = conn.cursor()
        query = "SELECT * FROM panels WHERE status = 1" if active_only else "SELECT * FROM panels"
        cursor.execute(query)
        rows = cursor.fetchall()
        panels = [dict(r) for r in rows] if hasattr(rows[0] if rows else None, 'keys') else rows
        conn.close()
        return panels

    def get_panel(self, panel_id: int):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("SELECT * FROM panels WHERE id = %s", (panel_id,))
            row = cursor.fetchone()
        else:
            cursor.execute("SELECT * FROM panels WHERE id = ?", (panel_id,))
            row = cursor.fetchone()
            row = dict(row) if row else None
        conn.close()
        return row

    def add_panel(self, name: str, panel_type: str, url: str, username: str, password: str, sub_url: str = ""):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute(
                "INSERT INTO panels (name, panel_type, url, username, password, sub_url) VALUES (%s, %s, %s, %s, %s, %s)",
                (name, panel_type, url.rstrip('/'), username, password, sub_url.rstrip('/'))
            )
            pid = cursor.lastrowid
        else:
            cursor.execute(
                "INSERT INTO panels (name, panel_type, url, username, password, sub_url) VALUES (?, ?, ?, ?, ?, ?)",
                (name, panel_type, url.rstrip('/'), username, password, sub_url.rstrip('/'))
            )
            conn.commit()
            pid = cursor.lastrowid
        conn.close()
        return pid

    def get_plans(self, panel_id: int = None):
        conn = self.get_connection()
        cursor = conn.cursor()
        if panel_id:
            query = "SELECT * FROM plans WHERE panel_id = %s AND status = 1" if self.db_type == "mysql" and config.DB_NAME else "SELECT * FROM plans WHERE panel_id = ? AND status = 1"
            cursor.execute(query, (panel_id,))
        else:
            cursor.execute("SELECT * FROM plans WHERE status = 1")
        rows = cursor.fetchall()
        plans = [dict(r) for r in rows] if hasattr(rows[0] if rows else None, 'keys') else rows
        conn.close()
        return plans

    def add_plan(self, panel_id: int, name: str, traffic_gb: int, days: int, price: int):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute(
                "INSERT INTO plans (panel_id, name, traffic_gb, days, price) VALUES (%s, %s, %s, %s, %s)",
                (panel_id, name, traffic_gb, days, price)
            )
            pid = cursor.lastrowid
        else:
            cursor.execute(
                "INSERT INTO plans (panel_id, name, traffic_gb, days, price) VALUES (?, ?, ?, ?, ?)",
                (panel_id, name, traffic_gb, days, price)
            )
            conn.commit()
            pid = cursor.lastrowid
        conn.close()
        return pid

    def get_plan(self, plan_id: int):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("SELECT * FROM plans WHERE id = %s", (plan_id,))
            row = cursor.fetchone()
        else:
            cursor.execute("SELECT * FROM plans WHERE id = ?", (plan_id,))
            row = cursor.fetchone()
            row = dict(row) if row else None
        conn.close()
        return row

    def add_service(self, user_id: int, panel_id: int, plan_id: int, service_username: str, subscription_url: str, traffic_limit: int, days: int, config_data: str = ""):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute(
                "INSERT INTO services (user_id, panel_id, plan_id, service_username, subscription_url, traffic_limit, days, config_data) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
                (user_id, panel_id, plan_id, service_username, subscription_url, traffic_limit, days, config_data)
            )
            sid = cursor.lastrowid
        else:
            cursor.execute(
                "INSERT INTO services (user_id, panel_id, plan_id, service_username, subscription_url, traffic_limit, days, config_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                (user_id, panel_id, plan_id, service_username, subscription_url, traffic_limit, days, config_data)
            )
            conn.commit()
            sid = cursor.lastrowid
        conn.close()
        return sid

    def get_user_services(self, user_id: int):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("SELECT s.*, p.name as panel_name FROM services s JOIN panels p ON s.panel_id = p.id WHERE s.user_id = %s ORDER BY s.id DESC", (user_id,))
            rows = cursor.fetchall()
        else:
            cursor.execute("SELECT s.*, p.name as panel_name FROM services s JOIN panels p ON s.panel_id = p.id WHERE s.user_id = ? ORDER BY s.id DESC", (user_id,))
            rows = [dict(r) for r in cursor.fetchall()]
        conn.close()
        return rows

    def add_transaction(self, user_id: int, amount: int, payment_method: str, description: str = "", receipt_file_id: str = ""):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute(
                "INSERT INTO transactions (user_id, amount, payment_method, description, receipt_file_id) VALUES (%s, %s, %s, %s, %s)",
                (user_id, amount, payment_method, description, receipt_file_id)
            )
            tid = cursor.lastrowid
        else:
            cursor.execute(
                "INSERT INTO transactions (user_id, amount, payment_method, description, receipt_file_id) VALUES (?, ?, ?, ?, ?)",
                (user_id, amount, payment_method, description, receipt_file_id)
            )
            conn.commit()
            tid = cursor.lastrowid
        conn.close()
        return tid

    def update_transaction_status(self, tx_id: int, status: str):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("UPDATE transactions SET status = %s WHERE id = %s", (status, tx_id))
        else:
            cursor.execute("UPDATE transactions SET status = ? WHERE id = ?", (status, tx_id))
            conn.commit()
        conn.close()

    def get_transaction(self, tx_id: int):
        conn = self.get_connection()
        cursor = conn.cursor()
        if self.db_type == "mysql" and config.DB_NAME:
            cursor.execute("SELECT * FROM transactions WHERE id = %s", (tx_id,))
            row = cursor.fetchone()
        else:
            cursor.execute("SELECT * FROM transactions WHERE id = ?", (tx_id,))
            row = cursor.fetchone()
            row = dict(row) if row else None
        conn.close()
        return row

    def get_stats(self):
        conn = self.get_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM users")
        total_users = cursor.fetchone()[0] if isinstance(cursor.fetchone, object) else 0

        cursor.execute("SELECT COUNT(*) FROM services WHERE status = 'active'")
        active_services = cursor.fetchone()[0] if isinstance(cursor.fetchone, object) else 0

        cursor.execute("SELECT SUM(amount) FROM transactions WHERE status = 'approved'")
        total_sales = cursor.fetchone()[0] or 0

        conn.close()
        return {
            "users": total_users,
            "services": active_services,
            "sales": total_sales
        }

db = Database()
