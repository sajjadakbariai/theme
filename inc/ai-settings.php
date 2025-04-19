<?php
/**
 * تنظیمات هوش مصنوعی قالب وردپرس
 * آدرس: /wp-content/themes/your-theme/inc/ai-settings.php
 */

if (!defined('ABSPATH')) exit;

// ثبت منوی تنظیمات
add_action('admin_menu', 'ai_theme_settings_page');
function ai_theme_settings_page() {
    add_menu_page(
        'تنظیمات هوش مصنوعی',
        'AI قالب',
        'manage_options',
        'ai-theme-settings',
        'ai_theme_settings_html',
        'dashicons-robot'
    );
}

// صفحه تنظیمات HTML
function ai_theme_settings_html() {
    if (!current_user_can('manage_options')) return;

    // ذخیره تنظیمات
    if (isset($_POST['ai_settings_nonce'])) {
        check_admin_referer('save_ai_settings', 'ai_settings_nonce');

        $settings = array(
            'content_recommendation' => isset($_POST['content_recommendation']) ? 1 : 0,
            'sentiment_analysis' => isset($_POST['sentiment_analysis']) ? 1 : 0,
            'auto_meta_tags' => isset($_POST['auto_meta_tags']) ? 1 : 0,
            'read_time' => isset($_POST['read_time']) ? 1 : 0
        );

        update_option('ai_theme_settings', $settings);
        echo '<div class="notice notice-success"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    // دریافت تنظیمات فعلی
    $settings = get_option('ai_theme_settings', array());
    ?>
    <div class="wrap">
        <h1>تنظیمات هوش مصنوعی قالب</h1>
        <form method="post">
            <?php wp_nonce_field('save_ai_settings', 'ai_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">پیشنهاد محتوای هوشمند</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="content_recommendation" value="1" <?php checked($settings['content_recommendation'] ?? 0, 1); ?>>
                                فعال‌سازی
                            </label>
                            <p class="description">نمایش مطالب مرتبط در انتهای پست‌ها</p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">تحلیل احساسات نظرات</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="sentiment_analysis" value="1" <?php checked($settings['sentiment_analysis'] ?? 0, 1); ?>>
                                فعال‌سازی
                            </label>
                            <p class="description">تشخیص خودکار نظرات مثبت/منفی</p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">تولید متا تگ خودکار</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="auto_meta_tags" value="1" <?php checked($settings['auto_meta_tags'] ?? 0, 1); ?>>
                                فعال‌سازی
                            </label>
                            <p class="description">تولید متا دیسکریپشن از محتوای پست</p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">زمان مطالعه</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="read_time" value="1" <?php checked($settings['read_time'] ?? 0, 1); ?>>
                                فعال‌سازی
                            </label>
                            <p class="description">نمایش زمان تخمینی مطالعه پست</p>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php submit_button('ذخیره تنظیمات'); ?>
        </form>
    </div>
    <?php
}

// بارگذاری ماژول‌های فعال
add_action('wp_loaded', 'load_active_ai_modules');
function load_active_ai_modules() {
    $settings = get_option('ai_theme_settings', array());

    if (is_admin()) return;

    $modules = array(
        'content_recommendation' => 'content-recommendation.php',
        'sentiment_analysis' => 'sentiment-analysis.php',
        'auto_meta_tags' => 'auto-meta-tags.php',
        'read_time' => 'read-time.php'
    );

    foreach ($modules as $option => $file) {
        if (!empty($settings[$option])) {
            require_once get_template_directory() . '/ai-modules/' . $file;
        }
    }
}
