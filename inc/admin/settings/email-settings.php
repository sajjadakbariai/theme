<?php
/**
 * Plugin Name: SEOKar Email Settings
 * Description: سیستم پیشرفته مدیریت تنظیمات ایمیل برای قالب وردپرس
 * Version: 1.4.0
 * Author: تیم سئوکار
 * Author URI: https://seokar.pro
 * License: GPLv2 or later
 * Text Domain: seokar-email
 */

if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم
}

class SEOKar_Email_Settings {

    private $settings;
    private $email_options;
    private $page_slug = 'seokar-email-settings';

    public function __construct() {
        $this->email_options = get_option('seokar_email_settings', array());
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_mail_failed', array($this, 'log_email_errors'), 10, 1);
        add_filter('wp_mail_from', array($this, 'set_custom_email_from'));
        add_filter('wp_mail_from_name', array($this, 'set_custom_email_from_name'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
    }

    public function add_admin_menu() {
        add_options_page(
            __('تنظیمات ایمیل SEOKar', 'seokar-email'),
            __('تنظیمات ایمیل سئو', 'seokar-email'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('seokar_email_group', 'seokar_email_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        );

        // بخش اصلی تنظیمات ایمیل
        add_settings_section(
            'seokar_email_main_section',
            __('تنظیمات اصلی ایمیل', 'seokar-email'),
            array($this, 'render_main_section'),
            $this->page_slug
        );

        add_settings_field(
            'enable_custom_smtp',
            __('فعال کردن SMTP سفارشی', 'seokar-email'),
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'seokar_email_main_section',
            array(
                'id' => 'enable_custom_smtp',
                'label' => __('استفاده از سرور SMTP اختصاصی', 'seokar-email'),
                'description' => __('با فعال کردن این گزینه می‌توانید از سرور ایمیل شخصی خود استفاده کنید.', 'seokar-email')
            )
        );

        add_settings_field(
            'from_email',
            __('آدرس ایمیل فرستنده', 'seokar-email'),
            array($this, 'render_text_field'),
            $this->page_slug,
            'seokar_email_main_section',
            array(
                'id' => 'from_email',
                'type' => 'email',
                'description' => __('آدرس ایمیلی که می‌خواهید به عنوان فرستنده نمایش داده شود.', 'seokar-email')
            )
        );

        add_settings_field(
            'from_name',
            __('نام فرستنده', 'seokar-email'),
            array($this, 'render_text_field'),
            $this->page_slug,
            'seokar_email_main_section',
            array(
                'id' => 'from_name',
                'description' => __('نامی که می‌خواهید به عنوان فرستنده نمایش داده شود.', 'seokar-email')
            )
        );

        add_settings_field(
            'email_content_type',
            __('نوع محتوای ایمیل', 'seokar-email'),
            array($this, 'render_select_field'),
            $this->page_slug,
            'seokar_email_main_section',
            array(
                'id' => 'email_content_type',
                'options' => array(
                    'text/html' => __('HTML', 'seokar-email'),
                    'text/plain' => __('متن ساده', 'seokar-email')
                ),
                'description' => __('نوع محتوای ایمیل‌های ارسالی از سایت', 'seokar-email')
            )
        );

        // بخش تنظیمات SMTP
        add_settings_section(
            'seokar_smtp_section',
            __('تنظیمات سرور SMTP', 'seokar-email'),
            array($this, 'render_smtp_section'),
            $this->page_slug
        );

        add_settings_field(
            'smtp_host',
            __('آدرس سرور SMTP', 'seokar-email'),
            array($this, 'render_text_field'),
            $this->page_slug,
            'seokar_smtp_section',
            array(
                'id' => 'smtp_host',
                'description' => __('مثال: smtp.example.com', 'seokar-email')
            )
        );

        add_settings_field(
            'smtp_port',
            __('پورت SMTP', 'seokar-email'),
            array($this, 'render_text_field'),
            $this->page_slug,
            'seokar_smtp_section',
            array(
                'id' => 'smtp_port',
                'type' => 'number',
                'description' => __('پورت معمول: 25 (غیررمزنگاری), 465 (SSL), 587 (TLS)', 'seokar-email')
            )
        );

        add_settings_field(
            'smtp_encryption',
            __('نوع رمزنگاری', 'seokar-email'),
            array($this, 'render_select_field'),
            $this->page_slug,
            'seokar_smtp_section',
            array(
                'id' => 'smtp_encryption',
                'options' => array(
                    'none' => __('بدون رمزنگاری', 'seokar-email'),
                    'ssl' => __('SSL', 'seokar-email'),
                    'tls' => __('TLS', 'seokar-email')
                ),
                'description' => __('نوع اتصال امن به سرور ایمیل', 'seokar-email')
            )
        );

        add_settings_field(
            'smtp_username',
            __('نام کاربری SMTP', 'seokar-email'),
            array($this, 'render_text_field'),
            $this->page_slug,
            'seokar_smtp_section',
            array(
                'id' => 'smtp_username',
                'description' => __('نام کاربری حساب ایمیل شما', 'seokar-email')
            )
        );

        add_settings_field(
            'smtp_password',
            __('رمز عبور SMTP', 'seokar-email'),
            array($this, 'render_password_field'),
            $this->page_slug,
            'seokar_smtp_section',
            array(
                'id' => 'smtp_password',
                'description' => __('رمز عبور حساب ایمیل شما', 'seokar-email')
            )
        );

        add_settings_field(
            'smtp_auth',
            __('احراز هویت SMTP', 'seokar-email'),
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'seokar_smtp_section',
            array(
                'id' => 'smtp_auth',
                'label' => __('نیاز به احراز هویت دارد', 'seokar-email'),
                'description' => __('معمولاً باید فعال باشد', 'seokar-email')
            )
        );

        // بخش تست ایمیل
        add_settings_section(
            'seokar_test_section',
            __('تست تنظیمات ایمیل', 'seokar-email'),
            array($this, 'render_test_section'),
            $this->page_slug
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'seokar-email'));
        }
        ?>
        <div class="wrap seokar-email-wrap">
            <h1><?php _e('تنظیمات پیشرفته ایمیل SEOKar', 'seokar-email'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('seokar_email_group');
                do_settings_sections($this->page_slug);
                submit_button(__('ذخیره تنظیمات', 'seokar-email'));
                ?>
            </form>
            
            <div class="seokar-test-email">
                <h2><?php _e('ارسال ایمیل تست', 'seokar-email'); ?></h2>
                <p><?php _e('پس از ذخیره تنظیمات، می‌توانید یک ایمیل آزمایشی ارسال کنید:', 'seokar-email'); ?></p>
                
                <form id="seokar-test-email-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="test_email_address"><?php _e('آدرس ایمیل دریافت کننده', 'seokar-email'); ?></label></th>
                            <td>
                                <input type="email" id="test_email_address" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('admin_email')); ?>" required>
                                <p class="description"><?php _e('آدرس ایمیلی که می‌خواهید ایمیل تست به آن ارسال شود.', 'seokar-email'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="test_email_subject"><?php _e('موضوع ایمیل', 'seokar-email'); ?></label></th>
                            <td>
                                <input type="text" id="test_email_subject" class="regular-text" 
                                       value="<?php _e('این یک ایمیل آزمایشی از سایت شماست', 'seokar-email'); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="test_email_message"><?php _e('متن پیام', 'seokar-email'); ?></label></th>
                            <td>
                                <textarea id="test_email_message" rows="5" class="large-text" required><?php 
                                    printf(__('این یک ایمیل آزمایشی است که در %s از سایت %s ارسال شده است.', 'seokar-email'), 
                                        date_i18n('Y/m/d H:i:s'), 
                                        get_bloginfo('name')); 
                                ?></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" id="seokar-send-test" class="button button-primary">
                            <?php _e('ارسال ایمیل تست', 'seokar-email'); ?>
                        </button>
                        <span id="seokar-test-result" class="hidden"></span>
                    </p>
                </form>
            </div>
            
            <div class="seokar-email-logs">
                <h2><?php _e('گزارش ارسال ایمیل‌ها', 'seokar-email'); ?></h2>
                <?php $this->render_email_logs(); ?>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_' . $this->page_slug) {
            return;
        }
        
        wp_enqueue_style('seokar-email-admin', plugins_url('assets/css/email-admin.css', __FILE__), array(), '1.4.0');
        wp_enqueue_script('seokar-email-admin', plugins_url('assets/js/email-admin.js', __FILE__), 
            array('jquery'), '1.4.0', true);
        
        wp_localize_script('seokar-email-admin', 'seokarEmail', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seokar_email_nonce'),
            'sending' => __('در حال ارسال ایمیل...', 'seokar-email'),
            'success' => __('ایمیل با موفقیت ارسال شد.', 'seokar-email'),
            'failed' => __('خطا در ارسال ایمیل. جزئیات را در گزارش‌ها بررسی کنید.', 'seokar-email')
        ));
    }

    public function sanitize_settings($input) {
        $output = array();
        
        // اعتبارسنجی ایمیل فرستنده
        if (!empty($input['from_email'])) {
            $output['from_email'] = sanitize_email($input['from_email']);
            if (!is_email($output['from_email'])) {
                add_settings_error(
                    'seokar_email_settings',
                    'invalid-email',
                    __('آدرس ایمیل وارد شده معتبر نیست.', 'seokar-email'),
                    'error'
                );
                $output['from_email'] = '';
            }
        }
        
        // اعتبارسنجی نام فرستنده
        if (!empty($input['from_name'])) {
            $output['from_name'] = sanitize_text_field($input['from_name']);
        }
        
        // اعتبارسنجی تنظیمات SMTP
        if (!empty($input['enable_custom_smtp'])) {
            $output['enable_custom_smtp'] = (bool) $input['enable_custom_smtp'];
            
            if ($output['enable_custom_smtp']) {
                $required_fields = array('smtp_host', 'smtp_port', 'smtp_username');
                foreach ($required_fields as $field) {
                    if (empty($input[$field])) {
                        add_settings_error(
                            'seokar_email_settings',
                            'missing-field',
                            sprintf(__('برای فعال کردن SMTP، فیلد %s الزامی است.', 'seokar-email'), $field),
                            'error'
                        );
                        $output['enable_custom_smtp'] = false;
                        break;
                    }
                }
                
                if (!empty($input['smtp_host'])) {
                    $output['smtp_host'] = sanitize_text_field($input['smtp_host']);
                }
                
                if (!empty($input['smtp_port'])) {
                    $output['smtp_port'] = absint($input['smtp_port']);
                }
                
                if (!empty($input['smtp_username'])) {
                    $output['smtp_username'] = sanitize_text_field($input['smtp_username']);
                }
                
                if (!empty($input['smtp_password'])) {
                    $output['smtp_password'] = $this->encrypt_password($input['smtp_password']);
                }
                
                if (!empty($input['smtp_encryption'])) {
                    $output['smtp_encryption'] = in_array($input['smtp_encryption'], array('none', 'ssl', 'tls')) 
                        ? $input['smtp_encryption'] 
                        : 'none';
                }
                
                $output['smtp_auth'] = !empty($input['smtp_auth']) ? (bool) $input['smtp_auth'] : false;
            }
        }
        
        // نوع محتوای ایمیل
        if (!empty($input['email_content_type'])) {
            $output['email_content_type'] = in_array($input['email_content_type'], array('text/html', 'text/plain')) 
                ? $input['email_content_type'] 
                : 'text/html';
        }
        
        return $output;
    }

    private function encrypt_password($password) {
        if (empty($password)) {
            return '';
        }
        
        // اگر رمز قبلاً رمزنگاری شده، تغییر نده
        if (!empty($this->email_options['smtp_password']) && 
            strpos($this->email_options['smtp_password'], 'seokar_enc:') === 0) {
            return $this->email_options['smtp_password'];
        }
        
        // استفاده از رمزنگاری ساده (در محیط واقعی از روش‌های امن‌تر استفاده کنید)
        $key = defined('AUTH_KEY') ? AUTH_KEY : 'seokar_default_key';
        $encrypted = base64_encode(openssl_encrypt($password, 'AES-128-ECB', $key));
        return 'seokar_enc:' . $encrypted;
    }

    private function decrypt_password($encrypted) {
        if (empty($encrypted) || strpos($encrypted, 'seokar_enc:') !== 0) {
            return $encrypted;
        }
        
        $encrypted = substr($encrypted, 11);
        $key = defined('AUTH_KEY') ? AUTH_KEY : 'seokar_default_key';
        return openssl_decrypt(base64_decode($encrypted), 'AES-128-ECB', $key);
    }

    public function set_custom_email_from($original_email) {
        $custom_email = $this->email_options['from_email'] ?? '';
        return !empty($custom_email) ? $custom_email : $original_email;
    }

    public function set_custom_email_from_name($original_name) {
        $custom_name = $this->email_options['from_name'] ?? '';
        return !empty($custom_name) ? $custom_name : $original_name;
    }

    public function init_smtp($phpmailer) {
        if (!($this->email_options['enable_custom_smtp'] ?? false)) {
            return;
        }
        
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->email_options['smtp_host'];
        $phpmailer->Port = $this->email_options['smtp_port'];
        
        if ($this->email_options['smtp_encryption'] !== 'none') {
            $phpmailer->SMTPSecure = $this->email_options['smtp_encryption'];
        }
        
        if ($this->email_options['smtp_auth']) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $this->email_options['smtp_username'];
            $phpmailer->Password = $this->decrypt_password($this->email_options['smtp_password']);
        }
        
        // تنظیم نوع محتوا
        if (!empty($this->email_options['email_content_type'])) {
            $phpmailer->ContentType = $this->email_options['email_content_type'];
        }
    }

    public function log_email_errors($wp_error) {
        $log_file = WP_CONTENT_DIR . '/seokar-email-errors.log';
        $timestamp = current_time('mysql');
        $message = "[$timestamp] " . $wp_error->get_error_message() . "\n";
        file_put_contents($log_file, $message, FILE_APPEND);
    }

    public function render_email_logs() {
        $log_file = WP_CONTENT_DIR . '/seokar-email-errors.log';
        
        if (!file_exists($log_file)) {
            echo '<p>' . __('هیچ گزارشی موجود نیست.', 'seokar-email') . '</p>';
            return;
        }
        
        $logs = file_get_contents($log_file);
        $logs = array_filter(explode("\n", $logs));
        
        if (empty($logs)) {
            echo '<p>' . __('هیچ گزارشی موجود نیست.', 'seokar-email') . '</p>';
            return;
        }
        
        echo '<div class="email-logs-container">';
        echo '<pre>';
        foreach (array_reverse($logs) as $log) {
            echo esc_html($log) . "\n";
        }
        echo '</pre>';
        
        echo '<p class="submit">';
        echo '<button id="seokar-clear-logs" class="button button-secondary">';
        _e('پاک کردن گزارش‌ها', 'seokar-email');
        echo '</button>';
        echo '</p>';
        
        echo '</div>';
    }

    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=' . $this->page_slug) . '">' . 
            __('تنظیمات ایمیل', 'seokar-email') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    // توابع رندر بخش‌ها و فیلدها
    public function render_main_section() {
        echo '<p>' . __('تنظیمات اصلی مربوط به ارسال ایمیل از سایت شما.', 'seokar-email') . '</p>';
    }

    public function render_smtp_section() {
        echo '<p>' . __('تنظیمات سرور SMTP برای ارسال ایمیل‌ها.', 'seokar-email') . '</p>';
    }

    public function render_test_section() {
        echo '<p>' . __('پس از ذخیره تنظیمات، می‌توانید یک ایمیل آزمایشی ارسال کنید.', 'seokar-email') . '</p>';
    }

    public function render_text_field($args) {
        $value = isset($this->email_options[$args['id']]) ? esc_attr($this->email_options[$args['id']]) : '';
        $type = isset($args['type']) ? $args['type'] : 'text';
        ?>
        <input type="<?php echo $type; ?>" id="<?php echo $args['id']; ?>" 
               name="seokar_email_settings[<?php echo $args['id']; ?>]" 
               value="<?php echo $value; ?>" class="regular-text">
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo $args['description']; ?></p>
        <?php endif;
    }

    public function render_password_field($args) {
        $value = isset($this->email_options[$args['id']]) ? esc_attr($this->decrypt_password($this->email_options[$args['id']])) : '';
        ?>
        <input type="password" id="<?php echo $args['id']; ?>" 
               name="seokar_email_settings[<?php echo $args['id']; ?>]" 
               value="<?php echo $value; ?>" class="regular-text" autocomplete="new-password">
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo $args['description']; ?></p>
        <?php endif;
    }

    public function render_checkbox_field($args) {
        $checked = isset($this->email_options[$args['id']]) ? (bool) $this->email_options[$args['id']] : false;
        ?>
        <label>
            <input type="checkbox" id="<?php echo $args['id']; ?>" 
                   name="seokar_email_settings[<?php echo $args['id']; ?>]" 
                   value="1" <?php checked($checked); ?>>
            <?php echo $args['label']; ?>
        </label>
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo $args['description']; ?></p>
        <?php endif;
    }

    public function render_select_field($args) {
        $selected = isset($this->email_options[$args['id']]) ? $this->email_options[$args['id']] : '';
        ?>
        <select id="<?php echo $args['id']; ?>" name="seokar_email_settings[<?php echo $args['id']; ?>]">
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

// راه‌اندازی سیستم ایمیل
$seokar_email_settings = new SEOKar_Email_Settings();

// تنظیم SMTP اگر فعال باشد
add_action('phpmailer_init', array($seokar_email_settings, 'init_smtp'));

// افزودن فیلتر برای نوع محتوای ایمیل
add_filter('wp_mail_content_type', function() use ($seokar_email_settings) {
    return $seokar_email_settings->email_options['email_content_type'] ?? 'text/html';
});

// مدیریت درخواست‌های AJAX
add_action('wp_ajax_seokar_send_test_email', function() {
    check_ajax_referer('seokar_email_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('شما مجوز انجام این عمل را ندارید.', 'seokar-email'), 403);
    }
    
    $to = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    if (!is_email($to)) {
        wp_send_json_error(__('آدرس ایمیل معتبر نیست.', 'seokar-email'), 400);
    }
    
    if (empty($subject) || empty($message)) {
        wp_send_json_error(__('موضوع و پیام نمی‌توانند خالی باشند.', 'seokar-email'), 400);
    }
    
    $sent = wp_mail($to, $subject, $message);
    
    if ($sent) {
        wp_send_json_success(__('ایمیل با موفقیت ارسال شد.', 'seokar-email'));
    } else {
        global $phpmailer;
        $error = $phpmailer ? $phpmailer->ErrorInfo : __('خطای ناشناخته در ارسال ایمیل', 'seokar-email');
        wp_send_json_error($error, 500);
    }
});

// پاک کردن گزارش‌ها
add_action('wp_ajax_seokar_clear_email_logs', function() {
    check_ajax_referer('seokar_email_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('شما مجوز انجام این عمل را ندارید.', 'seokar-email'), 403);
    }
    
    $log_file = WP_CONTENT_DIR . '/seokar-email-errors.log';
    if (file_exists($log_file)) {
        unlink($log_file);
    }
    
    wp_send_json_success(__('گزارش‌ها با موفقیت پاک شدند.', 'seokar-email'));
});
