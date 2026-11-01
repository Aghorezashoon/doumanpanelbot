import requests
import json
import logging
import config

logger = logging.getLogger("douman_tg")

BASE_API = f"https://api.telegram.org/bot{config.BOT_TOKEN}"

def call_tg(method: str, params: dict = None, files: dict = None) -> dict:
    url = f"{BASE_API}/{method}"
    try:
        if files:
            res = requests.post(url, data=params, files=files, timeout=15)
        else:
            res = requests.post(url, json=params, timeout=12)
        return res.json()
    except Exception as e:
        logger.error(f"Telegram API Error ({method}): {e}")
        return {"ok": False, "description": str(e)}

def send_message(chat_id: int, text: str, reply_markup=None, parse_mode: str = "HTML") -> dict:
    payload = {
        "chat_id": chat_id,
        "text": text,
        "parse_mode": parse_mode,
        "disable_web_page_preview": True
    }
    if reply_markup:
        if isinstance(reply_markup, str):
            payload["reply_markup"] = json.loads(reply_markup)
        else:
            payload["reply_markup"] = reply_markup
    return call_tg("sendMessage", payload)

def send_photo(chat_id: int, photo: str, caption: str = "", reply_markup=None) -> dict:
    payload = {
        "chat_id": chat_id,
        "photo": photo,
        "caption": caption,
        "parse_mode": "HTML"
    }
    if reply_markup:
        if isinstance(reply_markup, str):
            payload["reply_markup"] = json.loads(reply_markup)
        else:
            payload["reply_markup"] = reply_markup
    return call_tg("sendPhoto", payload)

def edit_message_text(chat_id: int, message_id: int, text: str, reply_markup=None) -> dict:
    payload = {
        "chat_id": chat_id,
        "message_id": message_id,
        "text": text,
        "parse_mode": "HTML",
        "disable_web_page_preview": True
    }
    if reply_markup:
        if isinstance(reply_markup, str):
            payload["reply_markup"] = json.loads(reply_markup)
        else:
            payload["reply_markup"] = reply_markup
    return call_tg("editMessageText", payload)

def answer_callback(callback_query_id: str, text: str = "", show_alert: bool = False) -> dict:
    return call_tg("answerCallbackQuery", {
        "callback_query_id": callback_query_id,
        "text": text,
        "show_alert": show_alert
    })

def set_webhook(url: str, secret_token: str = None) -> dict:
    payload = {"url": url}
    if secret_token:
        payload["secret_token"] = secret_token
    return call_tg("setWebhook", payload)

def get_webhook_info() -> dict:
    return call_tg("getWebhookInfo")

def delete_webhook() -> dict:
    return call_tg("deleteWebhook")

def get_me() -> dict:
    return call_tg("getMe")
