<?php
/**
 * فایل functions.php قالب SEOKar
 * 
 * @package SEOKar
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌ها
define('SEOKAR_VERSION', '1.0.0');
define('SEOKAR_DIR', get_template_directory());
define('SEOKAR_URI', get_template_directory_uri());
define('SEOKAR_INC_DIR', SEOKAR_DIR . '/inc/');
define('SEOKAR_DEV_MODE', true); // حالت توسعه

// بارگذاری ترجمه‌ها
function seokar_load_textdomain() {
    load_theme_textdomain('seokar', SEOKAR_DIR . '/languages');
}
add_action('after_setup_theme', 'seokar_load_textdomain');

// غیرفعال‌سازی Gutenberg در قالب (اختیاری)
add_filter('use_block_editor_for_post', '__return_false');

// فعال‌سازی قابلیت آپدیت خودکار قالب (در صورت نیاز)
add_filter('auto_update_theme', '__return_true');

/**
 * بارگذاری فایل‌های مورد نیاز
 */
function seokar_load_required_files() {
    // لیست فایل‌هایی که باید بارگذاری شوند
    $files = array(
        'theme-options',      // تنظیمات تم
        'enqueue-scripts',    // استایل و اسکریپت‌ها
        'ai-settings',        // تنظیمات هوش مصنوعی
        'ai-assistant',       // اتصال به هوش مصنوعی
        'custom-post-types',  // پست تایپ‌های سفارشی
        'custom-taxonomies',  // تاکسونومی‌های سفارشی
        'shortcodes',         // شورتکدها
        'widgets',            // ویجت‌های سفارشی
        'analytics'           // اتصال به سرویس‌های آنالیتیکس
    );

    foreach ($files as $file) {
        $file_path = SEOKAR_INC_DIR . $file . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
        } elseif (WP_DEBUG) {
            error_log(sprintf(
                __('فایل %s در مسیر %s یافت نشد', 'seokar'),
                $file . '.php',
                $file_path
            ));
        }
    }
}
seokar_load_required_files();

/**
 * مدیریت استایل‌های صفحه‌ای
 */
function seokar_enqueue_page_styles() {
    if (is_page()) {
        $file = SEOKAR_DIR . '/assets/css/page-style.css';
        $version = SEOKAR_DEV_MODE ? time() : (file_exists($file) ? filemtime($file) : SEOKAR_VERSION;
        wp_enqueue_style('seokar-page-style', SEOKAR_URI . '/assets/css/page-style.css', array(), $version);
    }
    
    if (is_404()) {
        wp_enqueue_style('seokar-404-style', SEOKAR_URI . '/assets/css/error-404-style.css', array(), SEOKAR_VERSION);
    }
}
add_action('wp_enqueue_scripts', 'seokar_enqueue_page_styles');

/**
 * فونت آویسام
 */
function seokar_enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
}
add_action('wp_enqueue_scripts', 'seokar_enqueue_font_awesome');

/**
 * هوش مصنوعی - نمایش مطالب مرتبط
 */
add_filter('the_content', function($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $content .= ai_display_related_posts(get_the_ID());
    }
    return $content;
});

/**
 * ویجت‌ها - محلی‌سازی اسکریپت‌ها
 */
function seokar_widgets_js_localization() {
    wp_enqueue_script('seokar-widgets');
    
    wp_localize_script('seokar-widgets', 'seokarWidgets', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('seokar_widgets_nonce'),
        'subscribeText' => __('Subscribe', 'seokar'),
        'submittingText' => __('Submitting...', 'seokar'),
        'errorText' => __('An error occurred. Please try again.', 'seokar')
    ));
}
add_action('wp_enqueue_scripts', 'seokar_widgets_js_localization');

/**
 * AJAX handler برای بازدید پست‌ها
 */
function seokar_update_post_views() {
    check_ajax_referer('seokar_widgets_nonce', 'security');
    
    if (isset($_POST['post_id'])) {
        $post_id = absint($_POST['post_id']);
        $count = get_post_meta($post_id, 'post_views_count', true);
        $count = $count ? $count + 1 : 1;
        update_post_meta($post_id, 'post_views_count', $count);
        wp_send_json_success();
    }
    
    wp_send_json_error();
}
add_action('wp_ajax_seokar_update_post_views', 'seokar_update_post_views');
add_action('wp_ajax_nopriv_seokar_update_post_views', 'seokar_update_post_views');

/**
 * AJAX handler برای عضویت در خبرنامه
 */
function seokar_newsletter_subscribe() {
    check_ajax_referer('seokar_widgets_nonce', 'security');
    
    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(__('Please enter a valid email address.', 'seokar'));
    }
    
    $email = sanitize_email($_POST['email']);
    // اینجا می‌توانید منطق ذخیره‌سازی ایمیل را اضافه کنید
    
    wp_send_json_success(array(
        'message' => __('Thank you for subscribing!', 'seokar')
    ));
}
add_action('wp_ajax_seokar_newsletter_subscribe', 'seokar_newsletter_subscribe');
add_action('wp_ajax_nopriv_seokar_newsletter_subscribe', 'seokar_newsletter_subscribe');

/**
 * استایل‌های اضافی برای ماژول‌های هوش مصنوعی
 */
function ai_styles() {
    wp_enqueue_style('ai-styles', SEOKAR_URI . '/ai-modules/ai.css', array(), SEOKAR_VERSION);
}
add_action('wp_enqueue_scripts', 'ai_styles');
