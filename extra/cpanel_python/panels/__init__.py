from .base import BasePanel
from .marzban import MarzbanPanel
from .xui import XuiPanel
from .hiddify import HiddifyPanel

def get_panel_driver(panel_data: dict) -> BasePanel:
    ptype = panel_data.get("panel_type", "").lower()
    url = panel_data.get("url", "")
    username = panel_data.get("username", "")
    password = panel_data.get("password", "")
    sub_url = panel_data.get("sub_url", "")

    if ptype == "marzban":
        return MarzbanPanel(url, username, password, sub_url)
    elif ptype in ["xui", "3x-ui", "sanaei", "alireza"]:
        return XuiPanel(url, username, password, sub_url)
    elif ptype == "hiddify":
        return HiddifyPanel(url, username, password, sub_url)
    else:
        raise ValueError(f"نوع پنل پشتیبانی نمی‌شود: {ptype}")
