from abc import ABC, abstractmethod

class BasePanel(ABC):
    def __init__(self, url: str, username: str, password: str, sub_url: str = ""):
        self.url = url.rstrip('/')
        self.username = username
        self.password = password
        self.sub_url = sub_url.rstrip('/') if sub_url else self.url

    @abstractmethod
    def test_connection(self) -> dict:
        """بررسی اتصال به پنل"""
        pass

    @abstractmethod
    def create_user(self, username: str, traffic_gb: int, days: int) -> dict:
        """ساخت کاربر جدید و دریافت لینک ساب و کانفیگ‌ها"""
        pass

    @abstractmethod
    def get_user_usage(self, username: str) -> dict:
        """دریافت اطلاعات مصرف حجم و زمان انقضای کاربر"""
        pass
