<?php
/**
 * مدیریت بخش مدیریت سئو
 * 
 * @package    SeoKar
 * @subpackage Admin
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_Admin {

    /**
     * @var string صفحه تنظیمات فعلی
     */
    private $current_page;

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * افزودن صفحات مدیریت
     */
    public function add_admin_pages() {
        $capability = 'manage_options';
        
        add_menu_page(
            __('سئو پیشرفته', 'seokar'),
            __('سئو پیشرفته', 'seokar'),
            $capability,
            'seokar-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-search',
            80
        );

        $this->current_page = add_submenu_page(
            'seokar-dashboard',
            __('داشبورد سئو', 'seokar'),
            __('داشبورد', 'seokar'),
            $capability,
            'seokar-dashboard',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'seokar-dashboard',
            __('تنظیمات عمومی', 'seokar'),
            __('عمومی', 'seokar'),
            $capability,
            'seokar-general',
            [$this, 'render_general_page']
        );

        add_submenu_page(
            'seokar-dashboard',
            __('شبکه‌های اجتماعی', 'seokar'),
            __('شبکه‌های اجتماعی', 'seokar'),
            $capability,
            'seokar-social',
            [$this, 'render_social_page']
        );

        add_submenu_page(
            'seokar-dashboard',
            __('ریدایرکت‌ها', 'seokar'),
            __('ریدایرکت‌ها', 'seokar'),
            $capability,
            'seokar-redirects',
            [$this, 'render_redirects_page']
        );

        add_submenu_page(
            'seokar-dashboard',
            __('ابزارها', 'seokar'),
            __('ابزارها', 'seokar'),
            $capability,
            'seokar-tools',
            [$this, 'render_tools_page']
        );
    }

    /**
     * ثبت تنظیمات
     */
    public function register_settings() {
        // ثبت تنظیمات عمومی
        register_setting('seokar_general_settings', 'seokar_general');
        
        // ثبت تنظیمات شبکه‌های اجتماعی
        register_setting('seokar_social_settings', 'seokar_social');
        
        // ثبت تنظیمات ریدایرکت‌ها
        register_setting('seokar_redirects_settings', 'seokar_redirects');
        
        // بخش تنظیمات عمومی
        add_settings_section(
            'seokar_general_section',
            __('تنظیمات عمومی سئو', 'seokar'),
            [$this, 'render_general_section'],
            'seokar-general'
        );
        
        // فیلدهای تنظیمات عمومی
        add_settings_field(
            'seo_title_separator',
            __('جداکننده عنوان', 'seokar'),
            [$this, 'render_title_separator_field'],
            'seokar-general',
            'seokar_general_section'
        );
        
        // سایر فیلدها و بخش‌ها...
    }

    /**
     * بارگذاری فایل‌های CSS و JS
     *
     * @param string $hook
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'seokar') === false) {
            return;
        }

        wp_enqueue_style(
            'seokar-admin-css',
            SEOKAR_PLUGIN_URL . 'admin/assets/css/admin.css',
            [],
            SEOKAR_VERSION
        );

        wp_enqueue_script(
            'seokar-admin-js',
            SEOKAR_PLUGIN_URL . 'admin/assets/js/admin.js',
            ['jquery'],
            SEOKAR_VERSION,
            true
        );

        wp_localize_script(
            'seokar-admin-js',
            'seokar_admin',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seokar_admin_nonce')
            ]
        );
    }

    /**
     * نمایش اعلان‌های مدیریت
     */
    public function display_admin_notices() {
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('تنظیمات با موفقیت ذخیره شدند.', 'seokar') . '</p>';
            echo '</div>';
        }
    }

    /**
     * رندر صفحه داشبورد
     */
    public function render_dashboard_page() {
        require_once SEOKAR_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * رندر صفحه تنظیمات عمومی
     */
    public function render_general_page() {
        require_once SEOKAR_PLUGIN_DIR . 'admin/views/general.php';
    }

    /**
     * رندر صفحه شبکه‌های اجتماعی
     */
    public function render_social_page() {
        require_once SEOKAR_PLUGIN_DIR . 'admin/views/social.php';
    }

    /**
     * رندر صفحه ریدایرکت‌ها
     */
    public function render_redirects_page() {
        require_once SEOKAR_PLUGIN_DIR . 'admin/views/redirects.php';
    }

    /**
     * رندر صفحه ابزارها
     */
    public function render_tools_page() {
        require_once SEOKAR_PLUGIN_DIR . 'admin/views/tools.php';
    }

    /**
     * رندر بخش تنظیمات عمومی
     */
    public function render_general_section() {
        echo '<p>' . __('تنظیمات عمومی مربوط به سئو سایت.', 'seokar') . '</p>';
    }

    /**
     * رندر فیلد جداکننده عنوان
     */
    public function render_title_separator_field() {
        $options = get_option('seokar_general');
        $separator = $options['title_separator'] ?? '|';
        
        echo '<input type="text" name="seokar_general[title_separator]" value="' . esc_attr($separator) . '" class="small-text">';
        echo '<p class="description">' . __('نماد جداکننده بین عنوان صفحه و نام سایت', 'seokar') . '</p>';
    }

    // سایر متدهای رندر فیلدها...
}
