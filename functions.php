<?php
// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌ها
define('SEOKAR_VERSION', '1.0.0');
define('SEOKAR_DIR', get_template_directory());
define('SEOKAR_URI', get_template_directory_uri());

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
// Load 404 page specific styles
function seokar_404_styles() {
    if (is_404()) {
        wp_enqueue_style('seokar-404-style', get_template_directory_uri() . '/assets/css/error-404-style.css', array(), '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'seokar_404_styles');


add_filter('the_content', function($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        // این تابع به صورت خودکار CSS را هم اضافه می‌کند
        $content .= ai_display_related_posts(get_the_ID());
    }
    return $content;
});
<?php
/**
 * فایل هوش 
 */

// بارگذاری تنظیمات هوش مصنوعی
require_once get_template_directory() . '/inc/ai-settings.php';

// استایل‌های اضافی برای ماژول‌ها
add_action('wp_enqueue_scripts', 'ai_styles');
function ai_styles() {
    wp_enqueue_style('ai-styles', get_template_directory_uri() . '/ai-modules/ai.css');
}
// Register and localize widget JS
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

// AJAX handler for post views
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

// AJAX handler for newsletter subscription
function seokar_newsletter_subscribe() {
    check_ajax_referer('seokar_widgets_nonce', 'security');
    
    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(__('Please enter a valid email address.', 'seokar'));
    }
    
    $email = sanitize_email($_POST['email']);
    
    // Here you can add your subscription logic (save to database, send to email service, etc.)
    // For example:
    // $subscribed = save_newsletter_subscriber($email);
    
    // For demo purposes, we'll just return a success message
    wp_send_json_success(array(
        'message' => __('Thank you');

function seokar_enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
}
add_action('wp_enqueue_scripts', 'seokar_enqueue_font_awesome');
require_once get_template_directory() . '/inc/enqueue-scripts.php';
    
