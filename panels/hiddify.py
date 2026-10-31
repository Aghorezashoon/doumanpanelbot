import requests
from .base import BasePanel

class HiddifyPanel(BasePanel):
    def __init__(self, url: str, username: str, password: str, sub_url: str = ""):
        # در هیدیفای password معمولاً همان Admin API Key است
        super().__init__(url, username, password, sub_url)
        self.api_key = password

    def get_headers(self):
        return {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Hiddify-API-Key": self.api_key
        }

    def test_connection(self) -> dict:
        try:
            res = requests.get(f"{self.url}/api/v2/admin/status/", headers=self.get_headers(), timeout=10)
            if res.status_code == 200:
                return {"ok": True, "message": "اتصال موفق به هیدیفای"}
            return {"ok": False, "message": f"پاسخ هیدیفای: {res.status_code}"}
        except Exception as e:
            return {"ok": False, "message": str(e)}

    def create_user(self, username: str, traffic_gb: int, days: int) -> dict:
        payload = {
            "name": username,
            "package_days": days if days > 0 else 30,
            "usage_limit_GB": traffic_gb if traffic_gb > 0 else 1000,
            "enable": True
        }
        res = requests.post(f"{self.url}/api/v2/admin/user/", json=payload, headers=self.get_headers(), timeout=12)
        if res.status_code in [200, 201]:
            data = res.json()
            sub_url = data.get("sub_link") or data.get("link", "")
            if not sub_url.startswith("http"):
                base_sub = self.sub_url if self.sub_url else self.url
                sub_url = f"{base_sub}/{sub_url.lstrip('/')}"
            return {
                "ok": True,
                "username": username,
                "subscription_url": sub_url,
                "uuid": data.get("uuid", "")
            }
        raise Exception(f"خطا در ایجاد کاربر هیدیفای: {res.status_code} - {res.text}")

    def get_user_usage(self, username: str) -> dict:
        res = requests.get(f"{self.url}/api/v2/admin/user/?name={username}", headers=self.get_headers(), timeout=10)
        if res.status_code == 200:
            data = res.json()
            users = data if isinstance(data, list) else [data]
            if users:
                u = users[0]
                return {
                    "ok": True,
                    "username": username,
                    "used_gb": round(u.get("current_usage_GB", 0), 2),
                    "limit_gb": u.get("usage_limit_GB", "نامحدود"),
                    "status": "active" if u.get("enable") else "disabled",
                    "subscription_url": u.get("sub_link", "")
                }
        return {"ok": False, "message": "کاربر یافت نشد"}
