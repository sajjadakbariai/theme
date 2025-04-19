<?php
/**
 * تنظیمات پیشرفته عمومی قالب سئوکار - نسخه حرفه‌ای
 * 
 * @package    SeoKar
 * @subpackage General Settings
 * @version    3.2.0
 */

if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم
}

class Seokar_General_Settings {

    const OPTION_GROUP = 'seokar_general_settings';
    const OPTION_NAME  = 'seokar_general_settings';
    const CACHE_KEY    = 'seokar_general_settings_cache';

    private $settings_schema;

    public function __construct() {
        $this->init_settings_schema();
        $this->init_hooks();
    }

    private function init_settings_schema() {
        $this->settings_schema = apply_filters('seokar_general_settings_schema', [
            'site_logo' => [
                'type'        => 'media',
                'label'       => __('لوگو سایت', 'seokar'),
                'description' => __('تصویر لوگو با کیفیت بالا (پیشنهاد: 250x100 پیکسل)', 'seokar'),
                'default'     => '',
                'mime_types'  => ['image/jpeg', 'image/png', 'image/svg+xml']
            ],
            'site_title' => [
                'type'        => 'text',
                'label'       => __('عنوان سایت', 'seokar'),
                'description' => __('عنوان اصلی سایت برای SEO', 'seokar'),
                'default'     => get_bloginfo('name'),
                'max_length'  => 100
            ],
            'site_description' => [
                'type'        => 'textarea',
                'label'       => __('توضیحات سایت', 'seokar'),
                'description' => __('توضیحات متا برای موتورهای جستجو (حداکثر 160 کاراکتر)', 'seokar'),
                'default'     => get_bloginfo('description'),
                'max_length'  => 160
            ],
            'primary_color' => [
                'type'        => 'color',
                'label'       => __('رنگ اصلی', 'seokar'),
                'description' => __('رنگ اصلی قالب (کد HEX)', 'seokar'),
                'default'     => '#3a7bd5'
            ],
            'maintenance_mode' => [
                'type'        => 'switch',
                'label'       => __('حالت نگهداری', 'seokar'),
                'description' => __('فعال کردن حالت تعمیرات سایت', 'seokar'),
                'default'     => false
            ],
            'google_analytics' => [
                'type'        => 'code',
                'label'       => __('کد Google Analytics', 'seokar'),
                'description' => __('کد رهگیری GA4 یا Universal Analytics', 'seokar'),
                'default'     => '',
                'language'    => 'html'
            ]
        ]);
    }

    private function init_hooks() {
        add_action('admin_init', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'inject_analytics_code'], 99);
        add_action('init', [$this, 'handle_maintenance_mode']);
        add_action('update_option_' . self::OPTION_NAME, [$this, 'clear_cache']);
    }

    public function enqueue_assets($hook) {
        if ($hook === 'toplevel_page_seokar-theme-options') {
            wp_enqueue_code_editor(['type' => 'text/html']);
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
            wp_enqueue_script(
                'seokar-general-settings',
                get_template_directory_uri() . '/assets/js/admin-general-settings.js',
                ['jquery', 'wp-color-picker'],
                filemtime(get_template_directory() . '/assets/js/admin-general-settings.js'),
                true
            );
        }
    }

    public function register() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'show_in_rest'      => true,
                'type'              => 'object'
            ]
        );

        add_settings_section(
            'seokar_general_section',
            __('تنظیمات اصلی سایت', 'seokar'),
            [$this, 'render_section_intro'],
            'seokar-theme-options'
        );

        foreach ($this->settings_schema as $field_id => $schema) {
            add_settings_field(
                $field_id,
                $schema['label'],
                [$this, 'render_field'],
                'seokar-theme-options',
                'seokar_general_section',
                [
                    'field_id' => $field_id,
                    'schema'   => $schema
                ]
            );
        }
    }

    public function render_section_intro() {
        echo '<p>' . __('در این بخش می‌توانید تنظیمات اصلی سایت خود را انجام دهید.', 'seokar') . '</p>';
    }

    public function render_field($args) {
        $field_id = $args['field_id'];
        $schema   = $args['schema'];
        $value    = $this->get_setting($field_id, $schema['default']);
        
        switch ($schema['type']) {
            case 'media':
                echo $this->render_media_field($field_id, $value, $schema);
                break;
            case 'color':
                echo $this->render_color_field($field_id, $value, $schema);
                break;
            case 'textarea':
                echo $this->render_textarea_field($field_id, $value, $schema);
                break;
            case 'switch':
                echo $this->render_switch_field($field_id, $value, $schema);
                break;
            case 'code':
                echo $this->render_code_field($field_id, $value, $schema);
                break;
            default: // text
                echo $this->render_text_field($field_id, $value, $schema);
        }

        if (!empty($schema['description'])) {
            echo '<p class="description">' . esc_html($schema['description']) . '</p>';
        }
    }

    public function sanitize_settings($input) {
        $output = [];
        $errors = new WP_Error();
        
        foreach ($this->settings_schema as $field_id => $schema) {
            if (!isset($input[$field_id])) {
                $output[$field_id] = $schema['default'];
                continue;
            }
            
            try {
                $output[$field_id] = $this->validate_field($input[$field_id], $schema);
            } catch (Exception $e) {
                $errors->add($field_id, $e->getMessage());
                $output[$field_id] = $schema['default'];
            }
        }

        if ($errors->has_errors()) {
            set_transient('seokar_settings_errors', $errors->get_error_messages(), 45);
        }
        
        $this->clear_cache();
        
        return apply_filters('seokar_sanitized_general_settings', $output, $input);
    }

    private function validate_field($value, $schema) {
        switch ($schema['type']) {
            case 'media':
                return esc_url_raw($value);
            case 'color':
                return sanitize_hex_color($value);
            case 'textarea':
                $value = sanitize_textarea_field($value);
                if (isset($schema['max_length']) && mb_strlen($value) > $schema['max_length']) {
                    throw new Exception(__('حداکثر طول مجاز رعایت نشده است.', 'seokar'));
                }
                return $value;
            case 'switch':
                return (bool) $value;
            case 'code':
                return wp_kses_post($value);
            default: // text
                $value = sanitize_text_field($value);
                if (isset($schema['max_length']) && mb_strlen($value) > $schema['max_length']) {
                    throw new Exception(__('حداکثر طول مجاز رعایت نشده است.', 'seokar'));
                }
                return $value;
        }
    }

    public function get_setting($key, $default = '') {
        $settings = wp_cache_get(self::CACHE_KEY);
        
        if (false === $settings) {
            $settings = get_option(self::OPTION_NAME, []);
            wp_cache_set(self::CACHE_KEY, $settings, '', 12 * HOUR_IN_SECONDS);
        }
        
        return $settings[$key] ?? $default;
    }

    public function clear_cache() {
        wp_cache_delete(self::CACHE_KEY);
    }

    public function handle_maintenance_mode() {
        if ($this->get_setting('maintenance_mode') && !current_user_can('manage_options')) {
            wp_die(
                '<h1>' . __('سایت در حال تعمیرات است.', 'seokar') . '</h1><p>' . __('به زودی بازخواهیم گشت.', 'seokar') . '</p>',
                __('سایت در حال تعمیرات است.', 'seokar'),
                ['response' => 503]
            );
        }
    }

    public function inject_analytics_code() {
        if ($code = $this->get_setting('google_analytics')) {
            echo "<!-- Google Analytics by SeoKar -->\n";
            echo wp_kses($code, [
                'script' => [],
                'noscript' => [],
                'iframe' => [
                    'src' => [],
                    'height' => [],
                    'width' => [],
                    'frameborder' => [],
                    'style' => []
                ]
            ]);
        }
    }

    private function render_media_field($field_id, $value, $schema) {
        ob_start();
        ?>
        <div class="seokar-media-uploader">
            <input type="text" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr(self::OPTION_NAME . "[$field_id]"); ?>" 
                   value="<?php echo esc_url($value); ?>" 
                   class="regular-text seokar-media-url">
            <button type="button" class="button seokar-media-upload"
                    data-uploader-title="<?php echo esc_attr(__('انتخاب تصویر', 'seokar')); ?>"
                    data-uploader-button-text="<?php echo esc_attr(__('استفاده به عنوان لوگو', 'seokar')); ?>"
                    data-mime-types='<?php echo esc_attr(json_encode($schema['mime_types'])); ?>'>
                <?php _e('انتخاب تصویر', 'seokar'); ?>
            </button>
            <?php if ($value) : ?>
                <button type="button" class="button seokar-media-remove"><?php _e('حذف', 'seokar'); ?></button>
                <div class="seokar-media-preview">
                    <img src="<?php echo esc_url($value); ?>" style="max-height: 80px; margin-top: 10px;">
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_color_field($field_id, $value, $schema) {
        ob_start();
        ?>
        <input type="text"
               id="<?php echo esc_attr($field_id); ?>"
               name="<?php echo esc_attr(self::OPTION_NAME . "[$field_id]"); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="seokar-color-picker"
               data-default-color="<?php echo esc_attr($schema['default']); ?>">
        <?php
        return ob_get_clean();
    }

    private function render_textarea_field($field_id, $value, $schema) {
        ob_start();
        ?>
        <textarea id="<?php echo esc_attr($field_id); ?>"
                  name="<?php echo esc_attr(self::OPTION_NAME . "[$field_id]"); ?>"
                  rows="5" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <?php
        return ob_get_clean();
    }

    private function render_switch_field($field_id, $value, $schema) {
        ob_start();
        ?>
        <label class="seokar-switch">
            <input type="checkbox"
                   id="<?php echo esc_attr($field_id); ?>"
                   name="<?php echo esc_attr(self::OPTION_NAME . "[$field_id]"); ?>"
                   value="1" <?php checked($value, true); ?>>
            <span class="seokar-slider round"></span>
        </label>
        <?php
        return ob_get_clean();
    }

    private function render_code_field($field_id, $value, $schema) {
        ob_start();
        ?>
        <textarea id="<?php echo esc_attr($field_id); ?>"
                  name="<?php echo esc_attr(self::OPTION_NAME . "[$field_id]"); ?>"
                  class="seokar-code-editor"
                  data-language="<?php echo esc_attr($schema['language']); ?>"
                  rows="5"><?php echo esc_textarea($value); ?></textarea>
        <?php
        return ob_get_clean();
    }

    private function render_text_field($field_id, $value, $schema) {
        ob_start();
        ?>
        <input type="text"
               id="<?php echo esc_attr($field_id); ?>"
               name="<?php echo esc_attr(self::OPTION_NAME . "[$field_id]"); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text">
        <?php
        return ob_get_clean();
    }

    public function render() {
        if ($errors = get_transient('seokar_settings_errors')) {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
            delete_transient('seokar_settings_errors');
        }
        ?>
        <div class="seokar-settings-container">
            <form method="post" action="options.php" class="seokar-settings-form">
                <?php 
                settings_fields(self::OPTION_GROUP);
                do_settings_sections('seokar-theme-options');
                submit_button(__('ذخیره تنظیمات', 'seokar'));
                ?>
                <button type="button" class="button button-secondary seokar-reset-section">
                    <?php _e('بازنشانی بخش', 'seokar'); ?>
                </button>
            </form>
            <div class="seokar-settings-sidebar">
                <div class="seokar-settings-card">
                    <h3><?php _e('راهنما', 'seokar'); ?></h3>
                    <p><?php _e('در این بخش می‌توانید تنظیمات قالب را مدیریت کنید.', 'seokar'); ?></p>
                </div>
                <div class="seokar-settings-card">
                    <h3><?php _e('سیستم', 'seokar'); ?></h3>
                    <p><?php _e('اطلاعات سیستم و تنظیمات پیشرفته.', 'seokar'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
