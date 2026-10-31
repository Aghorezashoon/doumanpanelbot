<?php
/**
 * Plugin Name: Douman Panel Bot Bridge
 * Plugin URI: https://github.com/Aghorezashoon/doumanpanelbot
 * Description: افزونه اختصاصی اتصال و مدیریت ربات تلگرام فروش کانفیگ VPN (DoumanPanelBot) از داخل پیشخوان وردپرس (wp-admin).
 * Version: 2.0.0
 * Author: Douman
 * Author URI: https://github.com/Aghorezashoon
 * Text Domain: doumanpanelbot
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class DoumanPanelBotBridge {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'دمان پنل بات',
            'دمان پنل بات',
            'manage_options',
            'doumanpanelbot',
            [$this, 'render_admin_page'],
            'dashicons-rest-api',
            26
        );

        add_submenu_page(
            'doumanpanelbot',
            'داشبورد و وضعیت',
            'داشبورد و وضعیت',
            'manage_options',
            'doumanpanelbot',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'doumanpanelbot',
            'تنظیمات وب‌هوک',
            'تنظیمات وب‌هوک',
            'manage_options',
            'doumanpanelbot-webhook',
            [$this, 'render_webhook_page']
        );

        add_submenu_page(
            'doumanpanelbot',
            'پنل مدیریت ربات',
            'پنل مدیریت ربات',
            'manage_options',
            'doumanpanelbot-embed',
            [$this, 'render_embed_page']
        );
    }

    public function register_settings() {
        register_setting('doumanbot_options', 'doumanbot_token');
        register_setting('doumanbot_options', 'doumanbot_url');
        register_setting('doumanbot_options', 'doumanbot_admin_id');
        register_setting('doumanbot_options', 'doumanbot_mode');
    }

    public function register_rest_routes() {
        register_rest_route('doumanbot/v1', '/webhook', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_rest_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_rest_webhook(WP_REST_Request $request) {
        $bot_url = get_option('doumanbot_url', '');
        if (empty($bot_url)) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Bot URL not configured'], 400);
        }

        $body = $request->get_body();
        $response = wp_remote_post($bot_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response(['status' => 'error', 'message' => $response->get_error_message()], 500);
        }

        return new WP_REST_Response(['status' => 'ok'], 200);
    }

    public function render_admin_page() {
        $token = get_option('doumanbot_token', '');
        $bot_url = get_option('doumanbot_url', '');
        $admin_id = get_option('doumanbot_admin_id', '');

        // Fetch Telegram Me info if token exists
        $bot_info = null;
        if (!empty($token)) {
            $response = wp_remote_get("https://api.telegram.org/bot{$token}/getMe", ['timeout' => 5]);
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['ok'])) {
                    $bot_info = $body['result'];
                }
            }
        }
        ?>
        <div class="wrap" style="direction: rtl; text-align: right;">
            <h1 style="display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-rest-api" style="font-size: 30px; width: 30px; height: 30px;"></span>
                مدیریت ربات تلگرام دمان (Douman Panel Bot)
            </h1>
            <p>این افزونه وب‌سایت وردپرسی شما را مستقیماً به ربات فروش VPN (پایتون یا PHP) متصل می‌کند.</p>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
                <div>
                    <div class="postbox" style="padding: 20px;">
                        <h2>⚙️ تنظیمات اصلی ربات</h2>
                        <form method="post" action="options.php">
                            <?php settings_fields('doumanbot_options'); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="doumanbot_token">توکن ربات تلگرام (Bot Token):</label></th>
                                    <td>
                                        <input type="text" id="doumanbot_token" name="doumanbot_token" value="<?php echo esc_attr($token); ?>" class="regular-text" style="direction: ltr;" placeholder="123456789:ABCdefGhIJKlmNoPQRstuVWXyz" />
                                        <p class="description">توکنی که از BotFather@ دریافت کرده‌اید.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="doumanbot_url">آدرس فایل ربات (Webhook URL):</label></th>
                                    <td>
                                        <input type="text" id="doumanbot_url" name="doumanbot_url" value="<?php echo esc_attr($bot_url); ?>" class="regular-text" style="direction: ltr;" placeholder="https://yoursite.com/bot/index.php یا https://yoursite.com/bot/webhook" />
                                        <p class="description">آدرس کامل فایل اجرایی ربات (PHP یا آدرس اپلیکیشن پایتون در سی‌پنل).</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="doumanbot_admin_id">آیدی عددی ادمین:</label></th>
                                    <td>
                                        <input type="text" id="doumanbot_admin_id" name="doumanbot_admin_id" value="<?php echo esc_attr($admin_id); ?>" class="regular-text" style="direction: ltr;" placeholder="12345678" />
                                        <p class="description">شناسه عددی اکانت تلگرام ادمین (از userinfobot@).</p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button('ذخیره تنظیمات'); ?>
                        </form>
                    </div>
                </div>

                <div>
                    <div class="postbox" style="padding: 20px;">
                        <h2>📊 وضعیت اتصال ربات</h2>
                        <?php if ($bot_info): ?>
                            <div style="background: #e6f4ea; border: 1px solid #ceead6; padding: 12px; border-radius: 6px; color: #137333;">
                                <strong>✅ متصل به تلگرام</strong>
                                <br>نام ربات: <strong><?php echo esc_html($bot_info['first_name']); ?></strong>
                                <br>یوزرنیم: <strong>@<?php echo esc_html($bot_info['username']); ?></strong>
                                <br>آیدی ربات: <code><?php echo esc_html($bot_info['id']); ?></code>
                            </div>
                        <?php elseif (!empty($token)): ?>
                            <div style="background: #fce8e6; border: 1px solid #fad2cf; padding: 12px; border-radius: 6px; color: #c5221f;">
                                <strong>❌ خطا در اتصال به تلگرام</strong>
                                <p style="font-size: 12px; margin: 4px 0 0 0;">توکن نامعتبر است یا ارتباط سرور با api.telegram.org مسدود است.</p>
                            </div>
                        <?php else: ?>
                            <p style="color: #666;">لطفاً توکن ربات را در فرم روبرو وارد و ذخیره نمایید.</p>
                        <?php endif; ?>

                        <hr style="margin: 16px 0;">
                        <h3>اطلاعات سرور وردپرس</h3>
                        <ul style="font-size: 13px; line-height: 1.8;">
                            <li>نسخه PHP: <strong><?php echo PHP_VERSION; ?></strong></li>
                            <li>وب‌سرور: <strong><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'N/A'); ?></strong></li>
                            <li>پشتیبانی cURL: <strong><?php echo function_exists('curl_version') ? '✅ فعال' : '❌ غیرفعال'; ?></strong></li>
                            <li>مسیر وردپرس: <code><?php echo esc_html(ABSPATH); ?></code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_webhook_page() {
        $token = get_option('doumanbot_token', '');
        $bot_url = get_option('doumanbot_url', '');
        $msg = '';

        if (isset($_POST['set_webhook']) && check_admin_referer('douman_webhook_action')) {
            if (empty($token) || empty($bot_url)) {
                $msg = '<div class="notice notice-error"><p>ابتدا توکن و آدرس ربات را در صفحه تنظیمات وارد کنید.</p></div>';
            } else {
                $res = wp_remote_post("https://api.telegram.org/bot{$token}/setWebhook", [
                    'body' => ['url' => $bot_url]
                ]);
                if (is_wp_error($res)) {
                    $msg = '<div class="notice notice-error"><p>خطا در اتصال: ' . esc_html($res->get_error_message()) . '</p></div>';
                } else {
                    $body = json_decode(wp_remote_retrieve_body($res), true);
                    $msg = '<div class="notice notice-success"><p>' . esc_html($body['description'] ?? 'وب‌هوک با موفقیت ثبت شد.') . '</p></div>';
                }
            }
        }

        if (isset($_POST['del_webhook']) && check_admin_referer('douman_webhook_action')) {
            $res = wp_remote_post("https://api.telegram.org/bot{$token}/deleteWebhook");
            if (!is_wp_error($res)) {
                $body = json_decode(wp_remote_retrieve_body($res), true);
                $msg = '<div class="notice notice-info"><p>' . esc_html($body['description'] ?? 'وب‌هوک حذف شد.') . '</p></div>';
            }
        }

        // Webhook info
        $webhook_info = null;
        if (!empty($token)) {
            $res = wp_remote_get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            if (!is_wp_error($res)) {
                $body = json_decode(wp_remote_retrieve_body($res), true);
                if (!empty($body['ok'])) {
                    $webhook_info = $body['result'];
                }
            }
        }
        ?>
        <div class="wrap" style="direction: rtl; text-align: right;">
            <h1>🌐 مدیریت و عیب‌یابی وب‌هوک تلگرام</h1>
            <?php echo $msg; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="postbox" style="padding: 20px;">
                    <h2>عملیات وب‌هوک</h2>
                    <form method="post">
                        <?php wp_nonce_field('douman_webhook_action'); ?>
                        <p>آدرس ثبت شده برای وب‌هوک:</p>
                        <code style="display: block; padding: 10px; background: #f0f0f1; margin-bottom: 15px; direction: ltr;"><?php echo esc_html($bot_url ?: 'هنوز تنظیمی انجام نشده است'); ?></code>

                        <input type="submit" name="set_webhook" class="button button-primary" value="🚀 ثبت / به‌روزرسانی وب‌هوک تلگرام" style="margin-left: 8px;" />
                        <input type="submit" name="del_webhook" class="button" value="🗑 حذف وب‌هوک" />
                    </form>

                    <hr style="margin: 20px 0;">
                    <h3>آدرس جایگزین (از طریق API وردپرس):</h3>
                    <p style="font-size: 13px; color: #555;">اگر هاست شما در ارتباط وب‌هوک مستقیم مشکل دارد، می‌توانید این آدرس REST API وردپرس را به عنوان وب‌هوک ست کنید:</p>
                    <code style="display: block; padding: 10px; background: #f0f0f1; direction: ltr;"><?php echo esc_url(get_rest_url(null, 'doumanbot/v1/webhook')); ?></code>
                </div>

                <div class="postbox" style="padding: 20px;">
                    <h2>گزارش وضعیت وب‌هوک از سرور تلگرام</h2>
                    <?php if ($webhook_info): ?>
                        <table class="widefat striped">
                            <tr><td><strong>آدرس وب‌هوک فعلی:</strong></td><td style="direction: ltr;"><?php echo esc_html($webhook_info['url'] ?: 'ثبت نشده'); ?></td></tr>
                            <tr><td><strong>پیام‌های در صف:</strong></td><td><?php echo esc_html($webhook_info['pending_update_count'] ?? 0); ?></td></tr>
                            <tr><td><strong>آخرین تاریخ خطا:</strong></td><td><?php echo !empty($webhook_info['last_error_date']) ? date('Y-m-d H:i:s', $webhook_info['last_error_date']) : 'هیچ خطایی ثبت نشده'; ?></td></tr>
                            <tr><td><strong>متن آخرین خطا:</strong></td><td style="color: red; direction: ltr;"><?php echo esc_html($webhook_info['last_error_message'] ?? 'ندارد'); ?></td></tr>
                            <tr><td><strong>گواهی SSL اختصاصی:</strong></td><td><?php echo !empty($webhook_info['has_custom_certificate']) ? 'بله' : 'خیر'; ?></td></tr>
                        </table>
                    <?php else: ?>
                        <p>اطلاعاتی در دسترس نیست. ابتدا توکن ربات را وارد کنید.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_embed_page() {
        $bot_url = get_option('doumanbot_url', '');
        $panel_url = preg_replace('/(index\.php|webhook)$/', 'admin.php', $bot_url);
        ?>
        <div class="wrap" style="direction: rtl; text-align: right;">
            <h1>💻 پنل مدیریت تحت وب دمان پنل بات</h1>
            <p>
                برای دسترسی مستقیم به پنل مدیریت ربات، می‌توانید روی دکمه زیر کلیک کنید:
                <a href="<?php echo esc_url($panel_url); ?>" target="_blank" class="button button-primary" style="margin-right: 10px;">باز کردن پنل در تب جدید ↗</a>
            </p>
            <div style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px; overflow: hidden; height: 800px; margin-top: 15px;">
                <?php if (!empty($bot_url)): ?>
                    <iframe src="<?php echo esc_url($panel_url); ?>" style="width: 100%; height: 100%; border: none;"></iframe>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <p>هنوز آدرس ربات را در تنظیمات وارد نکرده‌اید.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

add_action('plugins_loaded', ['DoumanPanelBotBridge', 'get_instance']);
