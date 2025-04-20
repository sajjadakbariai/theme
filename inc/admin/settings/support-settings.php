<?php
/**
 * Plugin Name: SEOKar Theme Support System
 * Description: سیستم جامع پشتیبانی و مدیریت تنظیمات قالب وردپرس با قابلیت‌های سئو
 * Version: 1.3.0
 * Author: تیم سئوکار
 * Author URI: https://seokar.pro
 * License: GPLv2 or later
 * Text Domain: seokar-theme-support
 */

if (!defined('ABSPATH')) {
    exit; // خروج در صورت دسترسی مستقیم
}

class SEOKar_Theme_Support {

    private $support_settings;
    private $theme_name;
    private $support_page_slug = 'seokar-theme-support';
    private $remote_support_url = 'https://support.seokar.pro/api/v1/';

    public function __construct() {
        $this->theme_name = get_option('stylesheet');
        $this->support_settings = get_option('seokar_theme_support_settings', array());

        $this->init_hooks();
        $this->check_dependencies();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_seokar_support_action', array($this, 'handle_ajax_requests'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
    }

    private function check_dependencies() {
        if (!function_exists('get_theme_mod')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                _e('قالب فعلی از سفارشی‌ساز وردپرس پشتیبانی نمی‌کند. سیستم پشتیبانی SEOKar نیاز به این قابلیت دارد.', 'seokar-theme-support');
                echo '</p></div>';
            });
            return;
        }
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, $this->support_page_slug) === false) {
            return;
        }

        // CSS
        wp_enqueue_style('seokar-support-admin', plugins_url('assets/css/admin-support.css', __FILE__), array(), '1.3.0');
        wp_enqueue_style('wp-color-picker');

        // JS
        wp_enqueue_script('seokar-support-admin', plugins_url('assets/js/admin-support.js', __FILE__), 
            array('jquery', 'wp-color-picker', 'jquery-ui-tabs'), '1.3.0', true);

        wp_localize_script('seokar-support-admin', 'seokarSupport', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seokar_support_nonce'),
            'translations' => array(
                'sending_request' => __('در حال ارسال درخواست...', 'seokar-theme-support'),
                'request_failed' => __('خطا در ارسال درخواست. لطفاً دوباره تلاش کنید.', 'seokar-theme-support'),
                'confirm_action' => __('آیا از انجام این عمل مطمئن هستید؟', 'seokar-theme-support'),
                'reset_confirm' => __('با این کار تمام تنظیمات به حالت پیش‌فرض بازمی‌گردند. آیا ادامه می‌دهید؟', 'seokar-theme-support')
            )
        ));
    }

    public function add_admin_menu() {
        add_theme_page(
            __('پشتیبانی قالب SEOKar', 'seokar-theme-support'),
            __('پشتیبانی سئو', 'seokar-theme-support'),
            'manage_options',
            $this->support_page_slug,
            array($this, 'render_support_page')
        );
    }

    public function register_settings() {
        register_setting('seokar_support_settings_group', 'seokar_theme_support_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        // بخش اصلی تنظیمات
        add_settings_section(
            'seokar_support_main_section',
            __('تنظیمات اصلی پشتیبانی', 'seokar-theme-support'),
            array($this, 'render_main_section'),
            $this->support_page_slug
        );

        add_settings_field(
            'enable_remote_support',
            __('پشتیبانی آنلاین', 'seokar-theme-support'),
            array($this, 'render_checkbox_field'),
            $this->support_page_slug,
            'seokar_support_main_section',
            array(
                'id' => 'enable_remote_support',
                'label' => __('فعال کردن پشتیبانی آنلاین', 'seokar-theme-support'),
                'description' => __('با فعال کردن این گزینه، تیم پشتیبانی می‌تواند به صورت آنلاین به سایت شما دسترسی داشته باشد.', 'seokar-theme-support')
            )
        );

        add_settings_field(
            'support_email',
            __('ایمیل پشتیبانی', 'seokar-theme-support'),
            array($this, 'render_text_field'),
            $this->support_page_slug,
            'seokar_support_main_section',
            array(
                'id' => 'support_email',
                'type' => 'email',
                'description' => __('ایمیل مورد استفاده برای ارسال پیام‌های پشتیبانی', 'seokar-theme-support')
            )
        );

        // بخش تنظیمات پیشرفته
        add_settings_section(
            'seokar_support_advanced_section',
            __('تنظیمات پیشرفته', 'seokar-theme-support'),
            array($this, 'render_advanced_section'),
            $this->support_page_slug
        );

        add_settings_field(
            'debug_mode',
            __('حالت دیباگ', 'seokar-theme-support'),
            array($this, 'render_checkbox_field'),
            $this->support_page_slug,
            'seokar_support_advanced_section',
            array(
                'id' => 'debug_mode',
                'label' => __('فعال کردن حالت دیباگ', 'seokar-theme-support'),
                'description' => __('در این حالت اطلاعات بیشتری برای خطایابی ثبت می‌شود.', 'seokar-theme-support')
            )
        );

        add_settings_field(
            'error_logging',
            __('ذخیره گزارش خطاها', 'seokar-theme-support'),
            array($this, 'render_select_field'),
            $this->support_page_slug,
            'seokar_support_advanced_section',
            array(
                'id' => 'error_logging',
                'options' => array(
                    'none' => __('غیرفعال', 'seokar-theme-support'),
                    'errors' => __('فقط خطاها', 'seokar-theme-support'),
                    'all' => __('همه رویدادها', 'seokar-theme-support')
                ),
                'description' => __('سطح ذخیره گزارش رویدادهای سیستم', 'seokar-theme-support')
            )
        );
    }

    public function sanitize_settings($input) {
        $output = array();

        // اعتبارسنجی ایمیل
        if (isset($input['support_email']) && !empty($input['support_email'])) {
            $output['support_email'] = sanitize_email($input['support_email']);
            if (!is_email($output['support_email'])) {
                add_settings_error(
                    'seokar_theme_support_settings',
                    'invalid-email',
                    __('ایمیل وارد شده معتبر نیست.', 'seokar-theme-support'),
                    'error'
                );
                $output['support_email'] = '';
            }
        }

        // مقادیر بولین
        $boolean_fields = array('enable_remote_support', 'debug_mode');
        foreach ($boolean_fields as $field) {
            $output[$field] = isset($input[$field]) ? (bool) $input[$field] : false;
        }

        // فیلدهای انتخابی
        $select_fields = array(
            'error_logging' => array('none', 'errors', 'all')
        );
        foreach ($select_fields as $field => $options) {
            if (isset($input[$field]) && in_array($input[$field], $options)) {
                $output[$field] = $input[$field];
            } else {
                $output[$field] = $options[0];
            }
        }

        return $output;
    }

    public function render_support_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'seokar-theme-support'));
        }

        // دریافت اطلاعات سیستم
        $system_info = $this->get_system_info();
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'support';
        ?>
        <div class="wrap seokar-support-wrap">
            <h1><?php _e('سیستم پشتیبانی قالب SEOKar', 'seokar-theme-support'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo $this->support_page_slug; ?>&tab=support" class="nav-tab <?php echo $active_tab === 'support' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('پشتیبانی', 'seokar-theme-support'); ?>
                </a>
                <a href="?page=<?php echo $this->support_page_slug; ?>&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('تنظیمات', 'seokar-theme-support'); ?>
                </a>
                <a href="?page=<?php echo $this->support_page_slug; ?>&tab=system" class="nav-tab <?php echo $active_tab === 'system' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('اطلاعات سیستم', 'seokar-theme-support'); ?>
                </a>
                <a href="?page=<?php echo $this->support_page_slug; ?>&tab=tools" class="nav-tab <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('ابزارها', 'seokar-theme-support'); ?>
                </a>
            </h2>
            
            <div class="seokar-support-content">
                <?php if ($active_tab === 'support') : ?>
                    <div class="seokar-support-card">
                        <h2><?php _e('ارسال درخواست پشتیبانی', 'seokar-theme-support'); ?></h2>
                        <form id="seokar-support-form" method="post">
                            <div class="form-group">
                                <label for="support-subject"><?php _e('موضوع', 'seokar-theme-support'); ?></label>
                                <input type="text" id="support-subject" name="support-subject" required class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="support-message"><?php _e('پیام', 'seokar-theme-support'); ?></label>
                                <textarea id="support-message" name="support-message" rows="5" required class="large-text"></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include-system-info" checked>
                                    <?php _e('ارسال اطلاعات سیستم با این درخواست', 'seokar-theme-support'); ?>
                                </label>
                            </div>
                            <button type="submit" class="button button-primary">
                                <?php _e('ارسال درخواست', 'seokar-theme-support'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <div class="seokar-support-card">
                        <h2><?php _e('پشتیبانی آنلاین', 'seokar-theme-support'); ?></h2>
                        <?php if ($this->support_settings['enable_remote_support'] ?? false) : ?>
                            <div class="remote-support-active">
                                <p><?php _e('پشتیبانی آنلاین فعال است. تیم پشتیبانی می‌تواند به سایت شما دسترسی داشته باشد.', 'seokar-theme-support'); ?></p>
                                <button id="seokar-disable-remote" class="button button-danger">
                                    <?php _e('غیرفعال کردن پشتیبانی آنلاین', 'seokar-theme-support'); ?>
                                </button>
                            </div>
                        <?php else : ?>
                            <div class="remote-support-inactive">
                                <p><?php _e('پشتیبانی آنلاین غیرفعال است. برای فعال کردن دکمه زیر را کلیک کنید.', 'seokar-theme-support'); ?></p>
                                <button id="seokar-enable-remote" class="button button-primary">
                                    <?php _e('فعال کردن پشتیبانی آنلاین', 'seokar-theme-support'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($active_tab === 'settings') : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('seokar_support_settings_group');
                        do_settings_sections($this->support_page_slug);
                        submit_button();
                        ?>
                    </form>
                    
                <?php elseif ($active_tab === 'system') : ?>
                    <div class="seokar-support-card">
                        <h2><?php _e('اطلاعات سیستم', 'seokar-theme-support'); ?></h2>
                        <div class="system-info-container">
                            <table class="widefat striped">
                                <tbody>
                                    <?php foreach ($system_info as $key => $value) : ?>
                                        <tr>
                                            <th width="30%"><?php echo esc_html($key); ?></th>
                                            <td><?php echo esc_html($value); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button id="seokar-copy-system-info" class="button button-secondary">
                            <?php _e('کپی اطلاعات سیستم', 'seokar-theme-support'); ?>
                        </button>
                    </div>
                    
                <?php elseif ($active_tab === 'tools') : ?>
                    <div class="seokar-support-card">
                        <h2><?php _e('ابزارهای قالب', 'seokar-theme-support'); ?></h2>
                        
                        <div class="tool-card">
                            <h3><?php _e('بازنشانی تنظیمات قالب', 'seokar-theme-support'); ?></h3>
                            <p><?php _e('با این کار تمام تنظیمات سفارشی‌ساز قالب به حالت پیش‌فرض بازمی‌گردد.', 'seokar-theme-support'); ?></p>
                            <button id="seokar-reset-theme" class="button button-danger">
                                <?php _e('بازنشانی تنظیمات قالب', 'seokar-theme-support'); ?>
                            </button>
                        </div>
                        
                        <div class="tool-card">
                            <h3><?php _e('بررسی سلامت قالب', 'seokar-theme-support'); ?></h3>
                            <p><?php _e('قالب را از نظر خطاها و مشکلات احتمالی بررسی می‌کند.', 'seokar-theme-support'); ?></p>
                            <button id="seokar-theme-diagnosis" class="button button-primary">
                                <?php _e('اجرای بررسی سلامت', 'seokar-theme-support'); ?>
                            </button>
                            <div id="diagnosis-results" class="hidden"></div>
                        </div>
                        
                        <div class="tool-card">
                            <h3><?php _e('بهینه‌سازی سئو', 'seokar-theme-support'); ?></h3>
                            <p><?php _e('تنظیمات سئو قالب را بررسی و بهینه‌سازی می‌کند.', 'seokar-theme-support'); ?></p>
                            <button id="seokar-seo-optimize" class="button button-primary">
                                <?php _e('اجرای بهینه‌ساز', 'seokar-theme-support'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function handle_ajax_requests() {
        check_ajax_referer('seokar_support_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('شما مجوز انجام این عمل را ندارید.', 'seokar-theme-support'), 403);
        }

        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';

        switch ($action) {
            case 'send_support_request':
                $this->handle_support_request();
                break;
                
            case 'toggle_remote_support':
                $this->toggle_remote_support();
                break;
                
            case 'reset_theme_settings':
                $this->reset_theme_settings();
                break;
                
            case 'run_diagnosis':
                $this->run_theme_diagnosis();
                break;
                
            case 'optimize_seo':
                $this->optimize_seo_settings();
                break;
                
            case 'copy_system_info':
                wp_send_json_success($this->get_system_info());
                break;
                
            default:
                wp_send_json_error(__('عملیات نامعتبر است.', 'seokar-theme-support'), 400);
        }
    }

    private function handle_support_request() {
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $include_system_info = isset($_POST['include_system_info']) ? (bool) $_POST['include_system_info'] : false;

        if (empty($subject) || empty($message)) {
            wp_send_json_error(__('موضوع و پیام نمی‌توانند خالی باشند.', 'seokar-theme-support'), 400);
        }

        $support_email = !empty($this->support_settings['support_email']) ? 
            $this->support_settings['support_email'] : get_bloginfo('admin_email');

        $email_content = "موضوع: $subject\n\n";
        $email_content .= "پیام:\n$message\n\n";
        $email_content .= "آدرس سایت: " . home_url() . "\n";

        if ($include_system_info) {
            $system_info = $this->get_system_info();
            $email_content .= "\nاطلاعات سیستم:\n";
            foreach ($system_info as $key => $value) {
                $email_content .= "$key: $value\n";
            }
        }

        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );

        $sent = wp_mail(
            $support_email,
            '[پشتیبانی SEOKar] ' . $subject,
            $email_content,
            $headers
        );

        if ($sent) {
            $this->log_event('support_request', 'درخواست پشتیبانی ارسال شد: ' . $subject);
            wp_send_json_success(__('درخواست پشتیبانی با موفقیت ارسال شد.', 'seokar-theme-support'));
        } else {
            $this->log_event('support_request_failed', 'خطا در ارسال درخواست پشتیبانی');
            wp_send_json_error(__('خطا در ارسال درخواست پشتیبانی. لطفاً دوباره تلاش کنید.', 'seokar-theme-support'));
        }
    }

    private function toggle_remote_support() {
        $enable = isset($_POST['enable']) ? (bool) $_POST['enable'] : false;
        
        $this->support_settings['enable_remote_support'] = $enable;
        update_option('seokar_theme_support_settings', $this->support_settings);
        
        $event = $enable ? 'remote_support_enabled' : 'remote_support_disabled';
        $this->log_event($event, 'پشتیبانی آنلاین ' . ($enable ? 'فعال' : 'غیرفعال') . ' شد');
        
        wp_send_json_success(array(
            'message' => $enable ? 
                __('پشتیبانی آنلاین فعال شد.', 'seokar-theme-support') : 
                __('پشتیبانی آنلاین غیرفعال شد.', 'seokar-theme-support'),
            'enabled' => $enable
        ));
    }

    private function reset_theme_settings() {
        // حذف تمام تنظیمات سفارشی‌ساز قالب
        remove_theme_mods();
        
        // حذف تنظیمات خاص قالب
        $option_name = 'theme_mods_' . $this->theme_name;
        delete_option($option_name);
        
        $this->log_event('theme_reset', 'تنظیمات قالب بازنشانی شدند');
        wp_send_json_success(__('تنظیمات قالب با موفقیت به حالت پیش‌فرض بازگشت.', 'seokar-theme-support'));
    }

    private function run_theme_diagnosis() {
        $diagnosis = array();
        
        // بررسی نسخه وردپرس
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            $diagnosis[] = array(
                'status' => 'warning',
                'message' => __('نسخه وردپرس شما قدیمی است. برای بهترین عملکرد، وردپرس را به روزرسانی کنید.', 'seokar-theme-support')
            );
        }
        
        // بررسی تنظیمات خوانایی
        if (!get_theme_mod('seokar_content_width', false)) {
            $diagnosis[] = array(
                'status' => 'notice',
                'message' => __('عرض محتوا در قالب تعریف نشده است. این ممکن است بر نمایش صحیح محتوا تأثیر بگذارد.', 'seokar-theme-support')
            );
        }
        
        // بررسی پشتیبانی از تگ‌های سئو
        if (!current_theme_supports('title-tag')) {
            $diagnosis[] = array(
                'status' => 'error',
                'message' => __('قالب شما از تگ عنوان خودکار پشتیبانی نمی‌کند. این یک مشکل جدی برای سئو است.', 'seokar-theme-support')
            );
        }
        
        // بررسی وضعیت کش
        if (!wp_cache_get('seokar_theme_check')) {
            wp_cache_set('seokar_theme_check', 'test', '', 60);
            $cache_works = wp_cache_get('seokar_theme_check') === 'test';
            
            if (!$cache_works) {
                $diagnosis[] = array(
                    'status' => 'warning',
                    'message' => __('به نظر می‌رسد کش سرور به درستی کار نمی‌کند. این ممکن است بر عملکرد سایت تأثیر بگذارد.', 'seokar-theme-support')
                );
            }
        }
        
        $this->log_event('theme_diagnosis', 'بررسی سلامت قالب انجام شد');
        wp_send_json_success(array(
            'results' => $diagnosis,
            'message' => __('بررسی سلامت قالب با موفقیت انجام شد.', 'seokar-theme-support')
        ));
    }

    private function optimize_seo_settings() {
        $optimizations = array();
        
        // فعال کردن تگ عنوان اگر پشتیبانی می‌شود اما فعال نیست
        if (current_theme_supports('title-tag') && !has_action('wp_head', '_wp_render_title_tag')) {
            add_theme_support('title-tag');
            $optimizations[] = __('پشتیبانی از تگ عنوان فعال شد.', 'seokar-theme-support');
        }
        
        // افزودن پشتیبانی از HTML5
        if (!current_theme_supports('html5')) {
            add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption'));
            $optimizations[] = __('پشتیبانی از HTML5 برای عناصر مختلف فعال شد.', 'seokar-theme-support');
        }
        
        // تنظیمات اولیه برای تصاویر
        if (!get_theme_mod('seokar_image_optimization', false)) {
            set_theme_mod('seokar_image_optimization', true);
            $optimizations[] = __('تنظیمات بهینه‌سازی تصاویر اعمال شد.', 'seokar-theme-support');
        }
        
        // تنظیمات اسکیما
        if (!get_theme_mod('seokar_schema_markup', false)) {
            set_theme_mod('seokar_schema_markup', true);
            $optimizations[] = __('تنظیمات نشانه‌گذاری اسکیما فعال شد.', 'seokar-theme-support');
        }
        
        $this->log_event('seo_optimization', 'بهینه‌سازی سئو انجام شد');
        wp_send_json_success(array(
            'optimizations' => $optimizations,
            'message' => __('بهینه‌سازی سئو با موفقیت انجام شد.', 'seokar-theme-support')
        ));
    }

    private function get_system_info() {
        global $wpdb;
        
        $theme = wp_get_theme();
        $active_plugins = get_option('active_plugins');
        $plugins = array();
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugins[] = $plugin_data['Name'] . ' ' . $plugin_data['Version'];
        }
        
        return array(
            __('نسخه وردپرس', 'seokar-theme-support') => get_bloginfo('version'),
            __('آدرس سایت', 'seokar-theme-support') => home_url(),
            __('قالب فعلی', 'seokar-theme-support') => $theme->get('Name') . ' ' . $theme->get('Version'),
            __('پدر قالب', 'seokar-theme-support') => $theme->parent() ? $theme->parent()->get('Name') : __('ندارد', 'seokar-theme-support'),
            __('پی‌اچ‌پی', 'seokar-theme-support') => phpversion(),
            __('مای‌اسکیوال', 'seokar-theme-support') => $wpdb->db_version(),
            __('پلاگین‌های فعال', 'seokar-theme-support') => implode("\n", $plugins),
            __('حافظه پی‌اچ‌پی', 'seokar-theme-support') => ini_get('memory_limit'),
            __('پست‌ها', 'seokar-theme-support') => wp_count_posts()->publish,
            __('صفحات', 'seokar-theme-support') => wp_count_posts('page')->publish,
            __('کاربران', 'seokar-theme-support') => count_users()['total_users'],
            __('زبان سایت', 'seokar-theme-support') => get_locale(),
            __('زمان سرور', 'seokar-theme-support') => current_time('mysql'),
            __('آپاچی/انجین‌اکس', 'seokar-theme-support') => $_SERVER['SERVER_SOFTWARE'] ?? __('نامشخص', 'seokar-theme-support')
        );
    }

    private function log_event($type, $message) {
        if (!isset($this->support_settings['error_logging']) {
            return;
        }
        
        $should_log = false;
        
        switch ($this->support_settings['error_logging']) {
            case 'all':
                $should_log = true;
                break;
            case 'errors':
                $should_log = in_array($type, array('error', 'support_request_failed'));
                break;
            default:
                $should_log = false;
        }
        
        if ($should_log) {
            $log_file = WP_CONTENT_DIR . '/seokar-support.log';
            $timestamp = current_time('mysql');
            $log_message = "[$timestamp] [$type] $message\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
    }

    public function show_admin_notices() {
        if (isset($_GET['seokar_notice'])) {
            $notice_type = sanitize_text_field($_GET['seokar_notice']);
            $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
            
            if ($notice_type && $message) {
                echo '<div class="notice notice-' . esc_attr($notice_type) . ' is-dismissible">';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</div>';
            }
        }
    }

    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('themes.php?page=' . $this->support_page_slug) . '">' . 
            __('تنظیمات پشتیبانی', 'seokar-theme-support') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    // توابع رندر فیلدها
    public function render_main_section() {
        echo '<p>' . __('تنظیمات اصلی سیستم پشتیبانی قالب SEOKar', 'seokar-theme-support') . '</p>';
    }

    public function render_advanced_section() {
        echo '<p>' . __('تنظیمات پیشرفته برای توسعه‌دهندگان و کاربران حرفه‌ای', 'seokar-theme-support') . '</p>';
    }

    public function render_text_field($args) {
        $value = isset($this->support_settings[$args['id']]) ? esc_attr($this->support_settings[$args['id']]) : '';
        $type = isset($args['type']) ? $args['type'] : 'text';
        ?>
        <input type="<?php echo $type; ?>" id="<?php echo $args['id']; ?>" 
               name="seokar_theme_support_settings[<?php echo $args['id']; ?>]" 
               value="<?php echo $value; ?>" class="regular-text">
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo $args['description']; ?></p>
        <?php endif;
    }

    public function render_checkbox_field($args) {
        $checked = isset($this->support_settings[$args['id']]) ? (bool) $this->support_settings[$args['id']] : false;
        ?>
        <label>
            <input type="checkbox" id="<?php echo $args['id']; ?>" 
                   name="seokar_theme_support_settings[<?php echo $args['id']; ?>]" 
                   value="1" <?php checked($checked); ?>>
            <?php echo $args['label']; ?>
        </label>
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo $args['description']; ?></p>
        <?php endif;
    }

    public function render_select_field($args) {
        $selected = isset($this->support_settings[$args['id']]) ? $this->support_settings[$args['id']] : '';
        ?>
        <select id="<?php echo $args['id']; ?>" name="seokar_theme_support_settings[<?php echo $args['id']; ?>]">
            <?php foreach ($args['options'] as $value => $label) : ?>
                <option value="<?php echo $value; ?>" <?php selected($selected, $value); ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo $args['description']; ?></p>
        <?php endif;
    }
}

new SEOKar_Theme_Support();
