import time
import requests
from .base import BasePanel

class MarzbanPanel(BasePanel):
    def __init__(self, url: str, username: str, password: str, sub_url: str = ""):
        super().__init__(url, username, password, sub_url)
        self.token = None

    def get_token(self) -> str:
        if self.token:
            return self.token
        res = requests.post(
            f"{self.url}/api/admin/token",
            data={"username": self.username, "password": self.password},
            timeout=10
        )
        if res.status_code == 200:
            self.token = res.json().get("access_token")
            return self.token
        raise Exception(f"خطا در احراز هویت مرزبان: {res.status_code} - {res.text}")

    def test_connection(self) -> dict:
        try:
            token = self.get_token()
            headers = {"Authorization": f"Bearer {token}"}
            res = requests.get(f"{self.url}/api/system", headers=headers, timeout=10)
            if res.status_code == 200:
                return {"ok": True, "message": "اتصال موفق به پنل مرزبان", "data": res.json()}
            return {"ok": False, "message": f"پاسخ پنل: {res.status_code}"}
        except Exception as e:
            return {"ok": False, "message": str(e)}

    def create_user(self, username: str, traffic_gb: int, days: int) -> dict:
        token = self.get_token()
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }

        # محاسبه حجم به بایت
        data_limit = int(traffic_gb * 1024 * 1024 * 1024) if traffic_gb > 0 else 0
        # محاسبه تاریخ انقضا (timestamp)
        expire = int(time.time() + (days * 86400)) if days > 0 else 0

        payload = {
            "username": username,
            "proxies": {"vless": {}, "vmess": {}, "trojan": {}, "shadowsocks": {}},
            "inbounds": {},
            "data_limit": data_limit,
            "expire": expire,
            "data_limit_reset_strategy": "no_reset"
        }

        res = requests.post(f"{self.url}/api/user", json=payload, headers=headers, timeout=12)
        if res.status_code in [200, 201]:
            data = res.json()
            sub_url = data.get("subscription_url", "")
            if sub_url and not sub_url.startswith("http"):
                base_sub = self.sub_url if self.sub_url else self.url
                sub_url = f"{base_sub}{sub_url}"
            return {
                "ok": True,
                "username": username,
                "subscription_url": sub_url,
                "links": data.get("links", []),
                "expire": expire,
                "data_limit": data_limit
            }
        raise Exception(f"خطا در ساخت کاربر مرزبان: {res.status_code} - {res.text}")

    def get_user_usage(self, username: str) -> dict:
        token = self.get_token()
        headers = {"Authorization": f"Bearer {token}"}
        res = requests.get(f"{self.url}/api/user/{username}", headers=headers, timeout=10)
        if res.status_code == 200:
            data = res.json()
            used_traffic = data.get("used_traffic", 0)
            data_limit = data.get("data_limit", 0)
            expire = data.get("expire", 0)
            status = data.get("status", "active")
            return {
                "ok": True,
                "username": username,
                "used_gb": round(used_traffic / (1024**3), 2),
                "limit_gb": round(data_limit / (1024**3), 2) if data_limit else "نامحدود",
                "expire_ts": expire,
                "status": status,
                "subscription_url": data.get("subscription_url", "")
            }
        return {"ok": False, "message": f"کاربر یافت نشد: {res.status_code}"}
