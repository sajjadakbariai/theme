<?php
/**
 * پنل تنظیمات حرفه‌ای قالب سئوکار - نسخه نهایی
 *
 * @package    SeoKar
 * @subpackage Admin
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 تیم توسعه سئوکار
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم
}

/**
 * ویژگی‌های امنیتی
 */
trait Seokar_Settings_Security {
    protected function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    protected function check_admin_referer($action) {
        return check_admin_referer($action);
    }
    
    protected function current_user_can($capability) {
        return current_user_can($capability);
    }
}

/**
 * اعتبارسنجی تنظیمات
 */
trait Seokar_Settings_Validation {
    protected function validate_settings($input, $settings_schema) {
        $output = [];
        
        foreach ($settings_schema as $key => $schema) {
            if (!isset($input[$key])) {
                if (isset($schema['default'])) {
                    $output[$key] = $schema['default'];
                }
                continue;
            }
            
            $output[$key] = $this->validate_field($input[$key], $schema);
        }
        
        return $output;
    }
    
    protected function validate_field($value, $schema) {
        switch ($schema['type']) {
            case 'color':
                return sanitize_hex_color($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'html':
                return wp_kses_post($value);
            case 'number':
                return intval($value);
            case 'checkbox':
                return (bool) $value;
            case 'select':
                return in_array($value, $schema['options']) ? $value : $schema['default'];
            default:
                return sanitize_text_field($value);
        }
    }
}

/**
 * مدیریت تب‌ها
 */
final class Seokar_Tab_Manager {
    private $tabs = [];
    
    public function register($tab_id, $config) {
        $this->tabs[$tab_id] = wp_parse_args($config, [
            'capability' => 'manage_options',
            'icon' => 'dashicons-admin-generic',
            'priority' => 10,
            'validator' => null,
            'sanitizer' => null,
            'require' => true
        ]);
        return $this;
    }
    
    public function get_all() {
        uasort($this->tabs, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return array_filter($this->tabs, function($tab) {
            return $tab['require'] && current_user_can($tab['capability']);
        });
    }
    
    public function get($tab_id) {
        return $this->tabs[$tab_id] ?? false;
    }
    
    public function exists($tab_id) {
        return isset($this->tabs[$tab_id]);
    }
}

/**
 * کلاس اصلی تنظیمات
 */
final class Seokar_Theme_Options {
    use Seokar_Settings_Security, Seokar_Settings_Validation;
    
    private static $instance;
    private $tab_manager;
    private $current_tab;
    private $settings_path;
    private $settings_cache = [];
    
    /**
     * دریافت نمونه singleton
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->settings_path = trailingslashit(get_template_directory() . '/inc/admin/settings');
        $this->tab_manager = new Seokar_Tab_Manager();
        
        $this->register_core_tabs();
        $this->init_hooks();
    }
    
    /**
     * ثبت تب‌های اصلی
     */
    private function register_core_tabs() {
        $this->tab_manager
            ->register('general', [
                'title' => __('تنظیمات عمومی', 'seokar'),
                'icon' => 'dashicons-admin-settings',
                'file' => 'general-settings.php',
                'priority' => 10
            ])
            ->register('seo', [
                'title' => __('بهینه‌سازی (SEO)', 'seokar'),
                'icon' => 'dashicons-search',
                'file' => 'seo-settings.php',
                'priority' => 20,
                'validator' => 'validate_seo_settings'
            ])
            ->register('ai', [
                'title' => __('هوش مصنوعی', 'seokar'),
                'icon' => 'dashicons-art',
                'file' => 'ai-settings.php',
                'priority' => 30,
                'require' => defined('SEOKAR_AI_MODULE_ENABLED')
            ])
            ->register('api', [
                'title' => __('اتصال API', 'seokar'),
                'icon' => 'dashicons-rest-api',
                'file' => 'api-settings.php',
                'priority' => 40
            ])
            ->register('advanced', [
                'title' => __('تنظیمات پیشرفته', 'seokar'),
                'icon' => 'dashicons-admin-tools',
                'file' => 'advanced-settings.php',
                'priority' => 50,
                'capability' => 'manage_network_options'
            ])
            ->register('analytics', [
                'title' => __('آمار و ردیابی', 'seokar'),
                'icon' => 'dashicons-chart-bar',
                'file' => 'analytics-settings.php',
                'priority' => 60,
                'validator' => 'validate_analytics_settings'
            ])
            ->register('header-footer', [
                'title' => __('هدر و فوتر', 'seokar'),
                'icon' => 'dashicons-editor-kitchensink',
                'file' => 'header-footer-settings.php',
                'priority' => 70,
                'sanitizer' => 'sanitize_header_footer_settings'
            ])
            ->register('schema', [
                'title' => __('نشانه‌گذاری Schema', 'seokar'),
                'icon' => 'dashicons-editor-code',
                'file' => 'schema-settings.php',
                'priority' => 80,
                'capability' => 'edit_theme_options'
            ])
            ->register('ads', [
                'title' => __('تبلیغات و کدها', 'seokar'),
                'icon' => 'dashicons-money-alt',
                'file' => 'ads-settings.php',
                'priority' => 90,
                'validator' => 'validate_ads_settings'
            ])
            ->register('email', [
                'title' => __('ایمیل و اعلان‌ها', 'seokar'),
                'icon' => 'dashicons-email-alt',
                'file' => 'email-settings.php',
                'priority' => 100,
                'sanitizer' => 'sanitize_email_settings'
            ])
            ->register('license', [
                'title' => __('فعالسازی لایسنس', 'seokar'),
                'icon' => 'dashicons-lock',
                'file' => 'license-settings.php',
                'priority' => 110,
                'require' => is_admin()
            ])
            ->register('support', [
                'title' => __('پشتیبانی', 'seokar'),
                'icon' => 'dashicons-sos',
                'file' => 'support-settings.php',
                'priority' => 120
            ])
            ->register('backup', [
                'title' => __('پشتیبان‌گیری', 'seokar'),
                'icon' => 'dashicons-backup',
                'file' => 'backup-settings.php',
                'priority' => 130,
                'capability' => 'export'
            ]);
    }
    
    /**
     * ثبت هوک‌های وردپرس
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_notices', [$this, 'admin_notices']);
        add_action('wp_ajax_seokar_load_tab', [$this, 'ajax_load_tab']);
        add_action('wp_ajax_seokar_export_settings', [$this, 'ajax_export_settings']);
        add_action('wp_ajax_seokar_import_settings', [$this, 'ajax_import_settings']);
        
        // هوک برای توسعه‌دهندگان
        do_action('seokar_theme_options_init', $this);
    }
    
    /**
     * افزودن منوی مدیریت
     */
    public function add_admin_menu() {
        add_menu_page(
            __('تنظیمات قالب سئوکار', 'seokar'),
            __('سئوکار', 'seokar'),
            'manage_options',
            'seokar-theme-options',
            [$this, 'render_options_page'],
            'dashicons-admin-generic',
            61
        );
    }
    
    /**
     * ثبت تنظیمات
     */
    public function register_settings() {
        $this->current_tab = $this->get_current_tab();
        $tab_config = $this->tab_manager->get($this->current_tab);
        
        if ($tab_config) {
            $file_path = $this->resolve_tab_file_path($tab_config['file']);
            
            if ($this->is_valid_settings_file($file_path)) {
                require_once $file_path;
                
                $class_name = $this->get_tab_class_name($this->current_tab);
                
                if (class_exists($class_name)) {
                    $settings_class = new $class_name();
                    
                    if (method_exists($settings_class, 'register')) {
                        $settings_class->register();
                    }
                    
                    // ثبت هوک برای توسعه‌دهندگان
                    do_action("seokar_register_{$this->current_tab}_settings", $this);
                }
            }
        }
    }
    
    /**
     * بارگذاری فایل‌های CSS و JS
     */
    public function enqueue_admin_assets($hook) {
        if ($hook === 'toplevel_page_seokar-theme-options') {
            // CSS
            wp_enqueue_style(
                'seokar-admin-options',
                get_template_directory_uri() . '/assets/css/admin-options.min.css',
                ['wp-color-picker'],
                filemtime(get_template_directory() . '/assets/css/admin-options.min.css')
            );
            
            // JS
            wp_enqueue_script(
                'seokar-admin-options',
                get_template_directory_uri() . '/assets/js/admin-options.min.js',
                ['jquery', 'wp-color-picker', 'jquery-ui-tabs', 'wp-i18n'],
                filemtime(get_template_directory() . '/assets/js/admin-options.min.js'),
                true
            );
            
            // محلی‌سازی
            wp_localize_script(
                'seokar-admin-options',
                'seokarOptions',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('seokar_admin_nonce'),
                    'load_tab_nonce' => wp_create_nonce('load_tab_nonce'),
                    'export_nonce' => wp_create_nonce('export_settings_nonce'),
                    'import_nonce' => wp_create_nonce('import_settings_nonce'),
                    'currentTab' => $this->get_current_tab(),
                    'i18n' => [
                        'save' => __('ذخیره', 'seokar'),
                        'saving' => __('در حال ذخیره...', 'seokar'),
                        'success' => __('تنظیمات با موفقیت ذخیره شد', 'seokar'),
                        'error' => __('خطا در ذخیره تنظیمات', 'seokar'),
                        'confirm_export' => __('آیا از خروجی گرفتن تنظیمات مطمئن هستید؟', 'seokar'),
                        'confirm_import' => __('این عمل تنظیمات فعلی را بازنویسی می‌کند. آیا ادامه می‌دهید؟', 'seokar')
                    ]
                ]
            );
            
            // اضافه کردن پشتیبانی از ترجمه در JS
            if (function_exists('wp_set_script_translations')) {
                wp_set_script_translations(
                    'seokar-admin-options',
                    'seokar',
                    get_template_directory() . '/languages'
                );
            }
        }
    }
    
    /**
     * نمایش اعلان‌ها
     */
    public function admin_notices() {
        if (isset($_GET['settings-updated'])) {
            $message = __('تنظیمات با موفقیت ذخیره شدند.', 'seokar');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
        
        if (isset($_GET['error'])) {
            $error_messages = [
                'invalid_import' => __('فایل وارد شده معتبر نیست.', 'seokar'),
                'import_failed' => __('خطا در وارد کردن تنظیمات.', 'seokar')
            ];
            
            if (isset($error_messages[$_GET['error']])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_messages[$_GET['error']]) . '</p></div>';
            }
        }
    }
    
    /**
     * نمایش صفحه تنظیمات
     */
    public function render_options_page() {
        $this->current_tab = $this->get_current_tab();
        $available_tabs = $this->tab_manager->get_all();
        ?>
        <div class="wrap seokar-settings-wrap">
            <header class="seokar-settings-header">
                <h1 class="seokar-settings-title">
                    <i class="dashicons dashicons-admin-generic"></i>
                    <?php echo esc_html__('تنظیمات قالب سئوکار', 'seokar'); ?>
                </h1>
                <div class="seokar-settings-version">
                    <?php echo esc_html(sprintf(
                        __('نسخه %s', 'seokar'),
                        defined('SEOKAR_THEME_VERSION') ? SEOKAR_THEME_VERSION : '1.0.0'
                    )); ?>
                </div>
            </header>

            <nav class="seokar-settings-tabs">
                <?php foreach ($available_tabs as $tab => $config): ?>
                    <a href="<?php echo esc_url($this->get_tab_url($tab)); ?>" 
                       class="seokar-settings-tab <?php echo $this->current_tab === $tab ? 'active' : ''; ?>"
                       data-tab="<?php echo esc_attr($tab); ?>">
                        <i class="dashicons <?php echo esc_attr($config['icon']); ?>"></i>
                        <?php echo esc_html($config['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="seokar-settings-content">
                <?php $this->render_tab_content(); ?>
            </div>

            <footer class="seokar-settings-footer">
                <p>
                    <?php echo esc_html(sprintf(
                        __('قالب سئوکار &copy; %s - توسعه داده شده توسط تیم سئوکار', 'seokar'),
                        date('Y')
                    )); ?>
                </p>
            </footer>
        </div>
        <?php
    }
    
    /**
     * نمایش محتوای تب
     */
    public function render_tab_content() {
        $tab_config = $this->tab_manager->get($this->current_tab);
        
        if (!$tab_config) {
            $this->render_error(__('تب مورد نظر یافت نشد.', 'seokar'));
            return;
        }
        
        try {
            $file_path = $this->resolve_tab_file_path($tab_config['file']);
            
            if (!$this->is_valid_settings_file($file_path)) {
                throw new Exception(__('فایل تنظیمات معتبر نیست.', 'seokar'));
            }
            
            include $file_path;
            
            $class_name = $this->get_tab_class_name($this->current_tab);
            
            if (!class_exists($class_name)) {
                throw new Exception(__('کلاس تنظیمات یافت نشد.', 'seokar'));
            }
            
            $settings_class = new $class_name();
            
            if (!method_exists($settings_class, 'render')) {
                throw new Exception(__('متد render در کلاس تنظیمات وجود ندارد.', 'seokar'));
            }
            
            echo '<div class="seokar-tab-content" data-tab="' . esc_attr($this->current_tab) . '">';
            $settings_class->render();
            echo '</div>';
            
        } catch (Exception $e) {
            $this->render_error($e->getMessage());
        }
    }
    
    /**
     * مدیریت درخواست‌های AJAX برای بارگذاری تب‌ها
     */
    public function ajax_load_tab() {
        check_ajax_referer('load_tab_nonce', 'nonce');
        
        if (!$this->current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'seokar'), 403);
        }
        
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'general';
        $this->current_tab = $this->tab_manager->exists($tab) ? $tab : 'general';
        
        ob_start();
        $this->render_tab_content();
        $content = ob_get_clean();
        
        wp_send_json_success([
            'content' => $content,
            'tab' => $this->current_tab
        ]);
    }
    
    /**
     * مدیریت درخواست‌های AJAX برای خروجی گرفتن تنظیمات
     */
    public function ajax_export_settings() {
        check_ajax_referer('export_settings_nonce', 'nonce');
        
        if (!$this->current_user_can('export')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'seokar'), 403);
        }
        
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'all';
        $settings = [];
        
        if ($tab === 'all') {
            foreach ($this->tab_manager->get_all() as $tab_id => $config) {
                $settings[$tab_id] = get_option('seokar_' . $tab_id . '_settings', []);
            }
        } elseif ($this->tab_manager->exists($tab)) {
            $settings = get_option('seokar_' . $tab . '_settings', []);
        }
        
        if (empty($settings)) {
            wp_send_json_error(__('تنظیماتی برای خروجی گرفتن یافت نشد', 'seokar'), 404);
        }
        
        $filename = 'seokar-settings-' . ($tab === 'all' ? 'all' : $tab) . '-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        
        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * مدیریت درخواست‌های AJAX برای وارد کردن تنظیمات
     */
    public function ajax_import_settings() {
        check_ajax_referer('import_settings_nonce', 'nonce');
        
        if (!$this->current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'seokar'), 403);
        }
        
        if (empty($_FILES['import_file'])) {
            wp_send_json_error(__('فایلی آپلود نشده است', 'seokar'), 400);
        }
        
        $file = $_FILES['import_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('خطا در آپلود فایل', 'seokar'), 400);
        }
        
        $content = file_get_contents($file['tmp_name']);
        $settings = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings)) {
            wp_send_json_error(__('فایل وارد شده معتبر نیست', 'seokar'), 400);
        }
        
        $imported = 0;
        $errors = 0;
        
        foreach ($settings as $tab => $tab_settings) {
            if ($this->tab_manager->exists($tab)) {
                if (update_option('seokar_' . $tab . '_settings', $tab_settings)) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }
        
        if ($imported > 0) {
            wp_send_json_success([
                'message' => sprintf(
                    __('%d تنظیمات با موفقیت وارد شدند. %d خطا رخ داد.', 'seokar'),
                    $imported,
                    $errors
                )
            ]);
        } else {
            wp_send_json_error(__('هیچ تنظیماتی وارد نشد', 'seokar'), 400);
        }
    }
    
    /**
     * نمایش خطا
     */
    protected function render_error($message) {
        echo '<div class="seokar-settings-error notice notice-error">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    /**
     * دریافت تب جاری
     */
    protected function get_current_tab() {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        return $this->tab_manager->exists($tab) ? $tab : 'general';
    }
    
    /**
     * ساخت URL برای تب‌ها
     */
    protected function get_tab_url($tab) {
        return add_query_arg('tab', $tab, admin_url('admin.php?page=seokar-theme-options'));
    }
    
    /**
     * بررسی اعتبار فایل تنظیمات
     */
    protected function is_valid_settings_file($file_path) {
        $valid_path = realpath($this->settings_path);
        $file_realpath = realpath($file_path);
        
        return ($file_realpath && strpos($file_realpath, $valid_path) === 0 && file_exists($file_path));
    }
    
    /**
     * ساخت مسیر فایل تب
     */
    protected function resolve_tab_file_path($file) {
        return $this->settings_path . $file;
    }
    
    /**
     * ساخت نام کلاس تب
     */
    protected function get_tab_class_name($tab) {
        $class_name = str_replace('-', ' ', $tab);
        $class_name = ucwords($class_name);
        $class_name = str_replace(' ', '_', $class_name);
        return 'Seokar_' . $class_name . '_Settings';
    }
    
    /**
     * دریافت تنظیمات با کش
     */
    public function get_settings($tab, $force_refresh = false) {
        $cache_key = 'seokar_settings_' . $tab;
        
        if ($force_refresh || !isset($this->settings_cache[$cache_key])) {
            $this->settings_cache[$cache_key] = get_option('seokar_' . $tab . '_settings', []);
        }
        
        return $this->settings_cache[$cache_key];
    }
    
    /**
     * پاک کردن کش تنظیمات
     */
    public function clear_settings_cache($tab) {
        $cache_key = 'seokar_settings_' . $tab;
        unset($this->settings_cache[$cache_key]);
    }
}

// راه‌اندازی سیستم
Seokar_Theme_Options::get_instance();
