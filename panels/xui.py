import time
import uuid
import json
import requests
from .base import BasePanel

class XuiPanel(BasePanel):
    def __init__(self, url: str, username: str, password: str, sub_url: str = ""):
        super().__init__(url, username, password, sub_url)
        self.session = requests.Session()
        self.logged_in = False

    def login(self):
        if self.logged_in:
            return
        res = self.session.post(
            f"{self.url}/login",
            data={"username": self.username, "password": self.password},
            timeout=10
        )
        if res.status_code == 200 and res.json().get("success"):
            self.logged_in = True
            return
        raise Exception(f"خطا در لاگین 3X-UI: {res.text}")

    def test_connection(self) -> dict:
        try:
            self.login()
            res = self.session.get(f"{self.url}/panel/api/inbounds/list", timeout=10)
            if res.status_code == 200 and res.json().get("success"):
                return {"ok": True, "message": "اتصال موفق به 3X-UI"}
            return {"ok": False, "message": "عدم دسترسی به لیست اینباندها"}
        except Exception as e:
            return {"ok": False, "message": str(e)}

    def create_user(self, username: str, traffic_gb: int, days: int) -> dict:
        self.login()
        # دریافت لیست اینباندها جهت انتخاب اولین اینباند فعال
        res = self.session.get(f"{self.url}/panel/api/inbounds/list", timeout=10)
        inbounds = res.json().get("obj", [])
        if not inbounds:
            raise Exception("هیچ اینباندی در پنل 3X-UI یافت نشد.")

        inbound = inbounds[0]
        inbound_id = inbound["id"]

        client_uuid = str(uuid.uuid4())
        total_bytes = int(traffic_gb * 1024 * 1024 * 1024) if traffic_gb > 0 else 0
        expiry_time_ms = int((time.time() + (days * 86400)) * 1000) if days > 0 else 0
        sub_id = uuid.uuid4().hex[:16]

        client_data = {
            "id": client_uuid,
            "email": username,
            "subId": sub_id,
            "totalGB": total_bytes,
            "expiryTime": expiry_time_ms,
            "enable": True,
            "flow": "",
            "limitIp": 2
        }

        payload = {
            "id": inbound_id,
            "settings": json.dumps({"clients": [client_data]})
        }

        add_res = self.session.post(
            f"{self.url}/panel/api/inbounds/addClient",
            json=payload,
            timeout=12
        )

        if add_res.status_code == 200 and add_res.json().get("success"):
            base_sub = self.sub_url if self.sub_url else self.url
            subscription_url = f"{base_sub}/sub/{sub_id}"
            return {
                "ok": True,
                "username": username,
                "subscription_url": subscription_url,
                "uuid": client_uuid,
                "sub_id": sub_id
            }
        raise Exception(f"خطا در ایجاد کلاینت 3X-UI: {add_res.text}")

    def get_user_usage(self, username: str) -> dict:
        self.login()
        res = self.session.get(f"{self.url}/panel/api/inbounds/getClientTraffics/{username}", timeout=10)
        if res.status_code == 200 and res.json().get("success"):
            obj = res.json().get("obj", {})
            up = obj.get("up", 0)
            down = obj.get("down", 0)
            total = obj.get("total", 0)
            expiry = obj.get("expiryTime", 0)
            used = up + down
            return {
                "ok": True,
                "username": username,
                "used_gb": round(used / (1024**3), 2),
                "limit_gb": round(total / (1024**3), 2) if total else "نامحدود",
                "expire_ts": int(expiry / 1000) if expiry else 0,
                "status": "active" if obj.get("enable") else "disabled"
            }
        return {"ok": False, "message": "اطلاعات کاربر یافت نشد"}
