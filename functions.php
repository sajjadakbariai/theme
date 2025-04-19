<?php
// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌ها
define('SEOKAR_VERSION', '1.0.0');
define('SEOKAR_DIR', get_template_directory());
define('SEOKAR_URI', get_template_directory_uri());

// بارگذاری فایل‌های اصلی
require_once SEOKAR_DIR . '/inc/setup.php';           // تنظیمات اولیه و پشتیبانی‌ها
require_once SEOKAR_DIR . '/inc/enqueue.php';         // بارگذاری استایل و اسکریپت
require_once SEOKAR_DIR . '/inc/theme-options.php';   // پنل تنظیمات قالب
require_once SEOKAR_DIR . '/inc/seo-functions.php';   // توابع سئو
require_once SEOKAR_DIR . '/inc/ai-assistant.php';    // اتصال به هوش مصنوعی
require_once SEOKAR_DIR . '/inc/custom-post-types.php';   // پست تایپ‌ها
require_once SEOKAR_DIR . '/inc/custom-taxonomies.php';   // دسته‌بندی‌ها
require_once SEOKAR_DIR . '/inc/shortcodes.php';      // شورت‌کدها
require_once SEOKAR_DIR . '/inc/widgets.php';         // ابزارک‌ها
require_once SEOKAR_DIR . '/inc/analytics.php';       // اتصال به آنالیتیکس

// بارگذاری ترجمه‌ها
function seokar_load_textdomain() {
    load_theme_textdomain('seokar', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'seokar_load_textdomain');

// غیرفعال‌سازی Gutenberg در قالب (اختیاری)
add_filter('use_block_editor_for_post', '__return_false');

// فعال‌سازی قابلیت آپدیت خودکار قالب (در صورت نیاز)
add_filter('auto_update_theme', '__return_true');

// در فایل functions.php
require_once get_template_directory() . '/inc/theme-options.php';

// حالت توسعه (Dev Mode) برای جلوگیری از کش استایل و اسکریپت
define('SEOKAR_DEV_MODE', true);

function seokar_enqueue_page_styles() {
    if (is_page()) {
        $file = get_template_directory() . '/assets/css/page-style.css';
        
        if (SEOKAR_DEV_MODE) {
            $version = time(); // همیشه نسخه جدید (کش نشه)
        } else {
            $version = file_exists($file) ? filemtime($file) : '1.0.0';
        }
        
        wp_enqueue_style('seokar-page-style', get_template_directory_uri() . '/assets/css/page-style.css', array(), $version);
    }
}
add_action('wp_enqueue_scripts', 'seokar_enqueue_page_styles');
