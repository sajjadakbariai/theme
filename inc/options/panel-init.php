<?php
/**
 * پنل تنظیمات پیشرفته قالب سئوکـار
 *
 * این فایل بخشی از سیستم مدیریت قالب سئوکـار است و وظیفه بارگذاری و مدیریت تنظیمات حرفه‌ای مربوط به سئو، عملکرد، ظاهر و اتصال به API را بر عهده دارد.
 *
 * @package    Seokar
 * @subpackage Admin/Settings
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 تیم توسعه سئوکـار
 * @version    2.1.0
 * @since      1.0.1 اولین نسخه‌ای که پنل تنظیمات سئوکـار معرفی شد.
 */
 
if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم به فایل
}
if (!class_exists('Seokar_Theme_Options')) {

    class Seokar_Theme_Options {

        private $tabs = array();
        private $current_tab;
        private $settings_path;

        public function __construct() {
            $this->settings_path = trailingslashit(get_template_directory() . '/inc/admin/settings');
            $this->define_tabs();
            $this->init_hooks();
        }

        private function init_hooks() {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_notices', array($this, 'admin_notices'));
            add_filter('seokar_theme_options_tabs', array($this, 'filter_available_tabs'));
        }

        private function define_tabs() {
            $this->tabs = array(
                'general' => array(
                    'title'    => __('تنظیمات عمومی', 'seokar'),
                    'icon'     => 'dashicons-admin-settings',
                    'file'     => 'general-settings.php',
                    'priority' => 10
                ),
                'seo' => array(
                    'title'    => __('بهینه‌سازی (SEO)', 'seokar'),
                    'icon'     => 'dashicons-search',
                    'file'     => 'seo-settings.php',
                    'priority' => 20
                ),
                'ai' => array(
                    'title'    => __('هوش مصنوعی', 'seokar'),
                    'icon'     => 'dashicons-art',
                    'file'     => 'ai-settings.php',
                    'priority' => 30,
                    'require'  => defined('SEOKAR_AI_MODULE_ENABLED')
                ),
                'api' => array(
                    'title'    => __('اتصال API', 'seokar'),
                    'icon'     => 'dashicons-rest-api',
                    'file'     => 'api-settings.php',
                    'priority' => 40
                ),
                'advanced' => array(
                    'title'    => __('تنظیمات پیشرفته', 'seokar'),
                    'icon'     => 'dashicons-admin-tools',
                    'file'     => 'advanced-settings.php',
                    'priority' => 50,
                    'cap'      => 'manage_network_options'
                ),
                'analytics' => array(
                    'title'    => __('آمار و ردیابی', 'seokar'),
                    'icon'     => 'dashicons-chart-bar',
                    'file'     => 'analytics-settings.php',
                    'priority' => 60
                ),
                'header-footer' => array(
                    'title'    => __('هدر و فوتر', 'seokar'),
                    'icon'     => 'dashicons-editor-kitchensink',
                    'file'     => 'header-footer-settings.php',
                    'priority' => 70
                ),
                'schema' => array(
                    'title'    => __('نشانه‌گذاری Schema', 'seokar'),
                    'icon'     => 'dashicons-editor-code',
                    'file'     => 'schema-settings.php',
                    'priority' => 80
                ),
                'ads' => array(
                    'title'    => __('تبلیغات و کدها', 'seokar'),
                    'icon'     => 'dashicons-money-alt',
                    'file'     => 'ads-settings.php',
                    'priority' => 90
                ),
                'email' => array(
                    'title'    => __('ایمیل و اعلان‌ها', 'seokar'),
                    'icon'     => 'dashicons-email-alt',
                    'file'     => 'email-settings.php',
                    'priority' => 100
                ),
                'license' => array(
                    'title'    => __('فعالسازی لایسنس', 'seokar'),
                    'icon'     => 'dashicons-lock',
                    'file'     => 'license-settings.php',
                    'priority' => 110,
                    'require'  => is_admin()
                ),
                'support' => array(
                    'title'    => __('پشتیبانی', 'seokar'),
                    'icon'     => 'dashicons-sos',
                    'file'     => 'support-settings.php',
                    'priority' => 120
                )
            );
        }

        public function filter_available_tabs($tabs) {
            $filtered_tabs = array();
            
            foreach ($tabs as $tab_id => $tab_config) {
                // بررسی شرایط مورد نیاز برای نمایش تب
                if (isset($tab_config['require']) && !$tab_config['require']) {
                    continue;
                }
                
                // بررسی سطح دسترسی
                if (isset($tab_config['cap']) && !current_user_can($tab_config['cap'])) {
                    continue;
                }
                
                $filtered_tabs[$tab_id] = $tab_config;
            }
            
            return $filtered_tabs;
        }

        public function add_admin_menu() {
            add_menu_page(
                __('تنظیمات قالب سئوکار', 'seokar'),
                __('سئوکار', 'seokar'),
                'manage_options',
                'seokar-theme-options',
                array($this, 'render_options_page'),
                'dashicons-admin-generic',
                61
            );
        }

        public function register_settings() {
            $this->current_tab = $this->get_current_tab();
            $filtered_tabs = apply_filters('seokar_theme_options_tabs', $this->tabs);

            if (isset($filtered_tabs[$this->current_tab])) {
                $file_path = $this->get_tab_file_path($this->current_tab);
                
                if ($this->is_valid_settings_file($file_path)) {
                    require_once $file_path;
                    $class_name = $this->get_tab_class_name($this->current_tab);
                    
                    if (class_exists($class_name)) {
                        $settings_class = new $class_name();
                        
                        if (method_exists($settings_class, 'register')) {
                            $settings_class->register();
                        } else {
                            error_log("Seokar Theme: register method missing in {$class_name}");
                        }
                    } else {
                        error_log("Seokar Theme: Settings class {$class_name} not found");
                    }
                }
            }
        }

        public function enqueue_admin_assets($hook) {
            if ($hook === 'toplevel_page_seokar-theme-options') {
                // استایل‌ها
                wp_enqueue_style(
                    'seokar-admin-options',
                    get_template_directory_uri() . '/assets/css/admin-options.min.css',
                    array('wp-color-picker'),
                    filemtime(get_template_directory() . '/assets/css/admin-options.min.css')
                );

                // اسکریپت‌ها
                wp_enqueue_script(
                    'seokar-admin-options-js',
                    get_template_directory_uri() . '/assets/js/admin-options.min.js',
                    array('jquery', 'wp-color-picker', 'jquery-ui-tabs'),
                    filemtime(get_template_directory() . '/assets/js/admin-options.min.js'),
                    true
                );

                // محلی‌سازی اسکریپت
                wp_localize_script(
                    'seokar-admin-options-js',
                    'seokarOptions',
                    array(
                        'ajax_url'   => admin_url('admin-ajax.php'),
                        'nonce'      => wp_create_nonce('seokar_admin_nonce'),
                        'confirmMsg' => __('آیا از ذخیره تغییرات مطمئن هستید؟', 'seokar'),
                        'currentTab' => $this->get_current_tab(),
                        'i18n'       => array(
                            'save'    => __('ذخیره', 'seokar'),
                            'saving'  => __('در حال ذخیره...', 'seokar'),
                            'success' => __('تنظیمات با موفقیت ذخیره شد', 'seokar'),
                            'error'   => __('خطا در ذخیره تنظیمات', 'seokar')
                        )
                    )
                );
            }
        }

        public function admin_notices() {
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __('تنظیمات با موفقیت ذخیره شدند.', 'seokar') . '</p>';
                echo '</div>';
            }
        }

        public function render_options_page() {
            $this->current_tab = $this->get_current_tab();
            $filtered_tabs = apply_filters('seokar_theme_options_tabs', $this->tabs);
            ?>
            <div class="wrap seokar-settings-wrap">
                <header class="seokar-settings-header">
                    <h1 class="seokar-settings-title">
                        <i class="dashicons dashicons-admin-generic"></i>
                        <?php echo __('تنظیمات قالب سئوکار', 'seokar'); ?>
                    </h1>
                    <div class="seokar-settings-version">
                        <?php echo sprintf(
                            __('نسخه %s', 'seokar'),
                            defined('SEOKAR_THEME_VERSION') ? esc_html(SEOKAR_THEME_VERSION) : '1.0.0'
                        ); ?>
                    </div>
                </header>

                <nav class="seokar-settings-tabs">
                    <?php foreach ($filtered_tabs as $tab => $config): ?>
                        <a href="?page=seokar-theme-options&tab=<?php echo esc_attr($tab); ?>" 
                           class="seokar-settings-tab <?php echo $this->current_tab === $tab ? 'active' : ''; ?>">
                            <i class="dashicons <?php echo esc_attr($config['icon']); ?>"></i>
                            <?php echo esc_html($config['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="seokar-settings-content">
                    <?php
                    if (isset($filtered_tabs[$this->current_tab])) {
                        $file_path = $this->get_tab_file_path($this->current_tab);
                        
                        if ($this->is_valid_settings_file($file_path)) {
                            include $file_path;
                            $class_name = $this->get_tab_class_name($this->current_tab);
                            
                            if (class_exists($class_name)) {
                                $settings_class = new $class_name();
                                
                                if (method_exists($settings_class, 'render')) {
                                    $settings_class->render();
                                } else {
                                    echo '<div class="error"><p>' . 
                                         __('متد render در کلاس تنظیمات وجود ندارد.', 'seokar') . 
                                         '</p></div>';
                                }
                            } else {
                                echo '<div class="error"><p>' . 
                                     __('کلاس تنظیمات یافت نشد.', 'seokar') . 
                                     '</p></div>';
                            }
                        } else {
                            echo '<div class="error"><p>' . 
                                 __('فایل تنظیمات معتبر نیست.', 'seokar') . 
                                 '</p></div>';
                        }
                    }
                    ?>
                </div>

                <footer class="seokar-settings-footer">
                    <p>
                        <?php echo sprintf(
                            __('قالب سئوکار &copy; %s - توسعه داده شده توسط تیم سئوکار', 'seokar'),
                            date('Y')
                        ); ?>
                    </p>
                </footer>
            </div>
            <?php
        }

        private function get_current_tab() {
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
            $filtered_tabs = apply_filters('seokar_theme_options_tabs', $this->tabs);
            return array_key_exists($tab, $filtered_tabs) ? $tab : 'general';
        }

        private function get_tab_file_path($tab) {
            $filtered_tabs = apply_filters('seokar_theme_options_tabs', $this->tabs);
            return $this->settings_path . $filtered_tabs[$tab]['file'];
        }

        private function is_valid_settings_file($file_path) {
            $valid_path = realpath($this->settings_path);
            $file_realpath = realpath($file_path);
            
            return ($file_realpath && strpos($file_realpath, $valid_path) === 0 && file_exists($file_path));
        }

        private function get_tab_class_name($tab) {
            $class_name = str_replace('-', ' ', $tab);
            $class_name = ucwords($class_name);
            $class_name = str_replace(' ', '_', $class_name);
            return 'Seokar_' . $class_name . '_Settings';
        }
    }

    new Seokar_Theme_Options();
}
