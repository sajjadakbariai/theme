<?php
/**
 * چارچوب پیشرفته تنظیمات قالب سئوکار 
 *
 * @package    SeoKar
 * @subpackage Admin\Settings
 * @author     Sajjad Akbari <info@sajjadakbari.com> // ایمیل حرفه‌ای‌تر
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 تیم توسعه سئوکار
 * @version    4.0.0 // افزایش نسخه به دلیل بازنویسی
 * @since      3.0.0 // نسخه اولیه این ساختار
 */

declare(strict_types=1); // فعال‌سازی بررسی دقیق تایپ‌ها در PHP 7+

if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم
}

// --- ثابت‌های اصلی ---
define('SEOKAR_SETTINGS_MENU_SLUG', 'seokar-theme-options');
define('SEOKAR_SETTINGS_OPTION_PREFIX', 'seokar_settings_'); // پیشوند برای نام آپشن‌ها
define('SEOKAR_SETTINGS_PATH', trailingslashit(get_template_directory()) . 'inc/admin/settings/'); // مسیر فایل‌های تنظیمات تب‌ها
define('SEOKAR_ADMIN_ASSETS_URL', trailingslashit(get_template_directory_uri()) . 'assets/'); // مسیر فایل‌های استاتیک ادمین

/**
 * Trait Seokar_Settings_Security_Trait
 *
 * توابع کمکی برای بررسی‌های امنیتی رایج در وردپرس.
 *
 * @package SeoKar\Admin\Settings
 * @since   4.0.0
 */
trait Seokar_Settings_Security_Trait {
    /**
     * بررسی Nonce وردپرس.
     *
     * @param string $nonce  مقدار Nonce.
     * @param string $action نام اکشن Nonce.
     * @return bool|int False اگر نامعتبر، 1 یا 2 اگر معتبر.
     */
    protected function verify_nonce(string $nonce, string $action): bool|int {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * بررسی Admin Referer (Nonce).
     *
     * @param string $action نام اکشن Nonce.
     * @return bool|int False اگر نامعتبر، 1 یا 2 اگر معتبر.
     */
    protected function check_admin_referer(string $action): bool|int {
        // -1 به معنی عدم بررسی referer است، فقط nonce چک می‌شود.
        return check_admin_referer($action, '_wpnonce');
    }

    /**
     * بررسی سطح دسترسی کاربر فعلی.
     *
     * @param string $capability نام سطح دسترسی.
     * @return bool True اگر کاربر دسترسی دارد، False در غیر این صورت.
     */
    protected function current_user_can(string $capability): bool {
        return current_user_can($capability);
    }
}

/**
 * Trait Seokar_Settings_Validation_Trait
 *
 * توابع کمکی برای اعتبارسنجی و پاکسازی انواع مختلف فیلدهای تنظیمات.
 * این توابع معمولاً در sanitize_callback مربوط به register_setting استفاده می‌شوند.
 *
 * @package SeoKar\Admin\Settings
 * @since   4.0.0
 */
trait Seokar_Settings_Validation_Trait {
    /**
     * اعتبارسنجی مجموعه‌ای از تنظیمات بر اساس یک اسکیمای تعریف شده.
     *
     * @param array<string, mixed> $input   داده‌های ورودی (معمولاً از $_POST).
     * @param array<string, array<string, mixed>> $settings_schema اسکیمای تنظیمات شامل نوع، پیش‌فرض و گزینه‌ها.
     * @return array<string, mixed> داده‌های پاکسازی شده.
     */
    protected function sanitize_settings(array $input, array $settings_schema): array {
        $output = [];
        $registered_settings = $this->get_registered_settings_keys($settings_schema); // بهینه‌سازی: دریافت کلیدها یکبار

        foreach ($registered_settings as $key) {
            $schema = $settings_schema[$key] ?? null; // دریافت اسکیمای فیلد

            // اگر اسکیمایی برای این کلید ثبت نشده، نادیده بگیر (جلوگیری از ذخیره داده‌های ناخواسته)
            if (!$schema) {
                continue;
            }

            $value = $input[$key] ?? null;

            // اگر مقدار ارسال نشده یا null است، مقدار پیش‌فرض (در صورت وجود) را اعمال کن
            if (is_null($value)) {
                if (isset($schema['default'])) {
                    $output[$key] = $schema['default'];
                }
                continue; // برو به فیلد بعدی
            }

            // پاکسازی مقدار بر اساس نوع تعریف شده در اسکیما
            $output[$key] = $this->sanitize_field($value, $schema);
        }

        // اطمینان حاصل کنید که تمام کلیدهای تعریف شده در اسکیما در خروجی وجود دارند (با مقدار پیش‌فرض یا null)
        // این کار از خطاهای undefined index در زمان خواندن آپشن جلوگیری می‌کند.
        foreach ($settings_schema as $key => $schema) {
            if (!array_key_exists($key, $output)) {
                 $output[$key] = $schema['default'] ?? null;
            }
        }


        return $output;
    }

    /**
     * پاکسازی یک فیلد خاص بر اساس اسکیمای آن.
     *
     * @param mixed $value  مقدار ورودی برای پاکسازی.
     * @param array<string, mixed> $schema اسکیمای فیلد (شامل type, options, default).
     * @return mixed مقدار پاکسازی شده.
     */
    protected function sanitize_field(mixed $value, array $schema): mixed {
        $type = $schema['type'] ?? 'text'; // پیش‌فرض: text

        switch ($type) {
            case 'color':
                return sanitize_hex_color($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw(trim($value));
            case 'textarea':
                 // اجازه دادن به تگ‌های HTML پایه برای توضیحات
                return wp_kses(trim($value), 'post');
            case 'html': // برای ویرایشگرهای پیشرفته یا کدهای خاص
                // نیاز به تعریف دقیق تگ‌های مجاز دارد، wp_kses_post ممکن است بیش از حد محدودکننده یا باز باشد.
                // بهتر است از wp_kses با آرایه تگ‌های مجاز سفارشی استفاده شود.
                // مثال: return wp_kses($value, $allowed_html_for_this_field);
                return wp_kses_post(trim($value)); // مثال ساده
            case 'number':
                return isset($schema['float']) && $schema['float'] ? floatval($value) : intval($value);
            case 'checkbox':
                // مقدار '1' یا true را به bool تبدیل می‌کند، بقیه false می‌شوند.
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'select':
            case 'radio':
                $options = $schema['options'] ?? [];
                // اطمینان از اینکه مقدار در گزینه‌های مجاز وجود دارد
                return array_key_exists((string)$value, $options) ? (string)$value : ($schema['default'] ?? null);
            case 'multiselect':
            case 'checkbox_group':
                 if (!is_array($value)) {
                    return $schema['default'] ?? [];
                 }
                 $options = $schema['options'] ?? [];
                 $sanitized_values = [];
                 foreach ($value as $single_value) {
                     $single_value_str = (string) $single_value;
                     if (array_key_exists($single_value_str, $options)) {
                         $sanitized_values[] = $single_value_str;
                     }
                 }
                 return $sanitized_values;
            case 'text':
            default:
                return sanitize_text_field(trim($value));
        }
    }

     /**
      * دریافت کلیدهای معتبر از اسکیمای تنظیمات.
      *
      * @param array<string, array<string, mixed>> $settings_schema
      * @return array<int, string>
      */
     private function get_registered_settings_keys(array $settings_schema): array {
         return array_keys($settings_schema);
     }
}

/**
 * Class Seokar_Tab_Manager
 *
 * مدیریت ثبت، بازیابی و مرتب‌سازی تب‌های صفحه تنظیمات.
 *
 * @package SeoKar\Admin\Settings
 * @since   4.0.0
 */
final class Seokar_Tab_Manager {
    /**
     * آرایه‌ای برای نگهداری تنظیمات تب‌ها.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $tabs = [];

    /**
     * ثبت یک تب جدید.
     *
     * @param string $tab_id شناسه منحصر به فرد تب (انگلیسی، حروف کوچک، خط تیره مجاز).
     * @param array<string, mixed> $config تنظیمات تب شامل:
     *        'title' (string) عنوان تب (قابل ترجمه).
     *        'capability' (string) سطح دسترسی مورد نیاز (پیش‌فرض: 'manage_options').
     *        'icon' (string) کلاس آیکون Dashicons (پیش‌فرض: 'dashicons-admin-generic').
     *        'priority' (int) اولویت نمایش تب (عدد کمتر = بالاتر).
     *        'file' (string) نام فایل PHP مربوط به تنظیمات این تب در مسیر SEOKAR_SETTINGS_PATH.
     *        'class' (string) نام کلاس PHP مربوط به این تب (برای بارگذاری و فراخوانی متدها).
     *        'require' (bool|callable) شرط نمایش تب (پیش‌فرض: true). می‌تواند یک تابع باشد.
     *        'settings_schema' (callable|array) تابع یا آرایه‌ای که اسکیمای فیلدهای این تب را برمی‌گرداند.
     *        'sanitize_callback' (callable) تابع یا متدی که برای پاکسازی تنظیمات این تب استفاده می‌شود.
     *
     * @return self برای امکان زنجیره‌سازی (Method Chaining).
     */
    public function register(string $tab_id, array $config): self {
        $defaults = [
            'title'             => __('عنوان تب نامشخص', 'seokar'),
            'capability'        => 'manage_options',
            'icon'              => 'dashicons-admin-generic',
            'priority'          => 50,
            'file'              => $tab_id . '-settings.php', // قرارداد نام‌گذاری فایل
            'class'             => 'Seokar_' . str_replace('-', '_', ucwords($tab_id, '-')) . '_Settings', // قرارداد نام‌گذاری کلاس
            'require'           => true,
            'settings_schema'   => null, // ضروری برای اعتبارسنجی استاندارد
            'sanitize_callback' => null, // ضروری برای ثبت تنظیمات استاندارد
        ];

        // اطمینان از وجود کلیدهای ضروری
        if (empty($config['title']) || empty($config['file']) || empty($config['class'])) {
             trigger_error(sprintf('پیکربندی تب "%s" ناقص است. کلیدهای title, file, class ضروری هستند.', $tab_id), E_USER_WARNING);
             return $this; // از ثبت تب ناقص جلوگیری کن
        }


        $this->tabs[$tab_id] = wp_parse_args($config, $defaults);
        return $this;
    }

    /**
     * دریافت تمام تب‌های ثبت شده و قابل نمایش برای کاربر فعلی، مرتب شده بر اساس اولویت.
     *
     * @return array<string, array<string, mixed>> آرایه‌ای از تنظیمات تب‌ها.
     */
    public function get_all_visible(): array {
        // فیلتر کردن تب‌ها بر اساس شرط require و سطح دسترسی
        $visible_tabs = array_filter($this->tabs, function($tab_config) {
            $required = $tab_config['require'];
            $can_show = true;

            if (is_callable($required)) {
                $can_show = (bool) call_user_func($required);
            } elseif ($required === false) {
                $can_show = false;
            }

            return $can_show && current_user_can($tab_config['capability']);
        });

        // مرتب‌سازی بر اساس اولویت
        uasort($visible_tabs, function($a, $b) {
            return (int)$a['priority'] <=> (int)$b['priority']; // عملگر سفینه فضایی (PHP 7+)
        });

        return $visible_tabs;
    }

    /**
     * دریافت تنظیمات یک تب خاص با شناسه آن.
     *
     * @param string $tab_id شناسه تب.
     * @return array<string, mixed>|null تنظیمات تب یا null اگر یافت نشد.
     */
    public function get(string $tab_id): ?array {
        return $this->tabs[$tab_id] ?? null;
    }

    /**
     * بررسی وجود یک تب با شناسه داده شده.
     *
     * @param string $tab_id شناسه تب.
     * @return bool True اگر تب وجود دارد، False در غیر این صورت.
     */
    public function exists(string $tab_id): bool {
        return isset($this->tabs[$tab_id]);
    }

     /**
      * دریافت تمام تب‌های ثبت شده (بدون فیلتر دسترسی یا require).
      *
      * @return array<string, array<string, mixed>>
      */
     public function get_all_registered(): array {
         return $this->tabs;
     }
}

/**
 * Interface Seokar_Settings_Tab_Interface
 *
 * تعریف ساختار مورد نیاز برای کلاس‌های مدیریت هر تب تنظیمات.
 *
 * @package SeoKar\Admin\Settings
 * @since   4.0.0
 */
interface Seokar_Settings_Tab_Interface {
    /**
     * ثبت بخش‌ها و فیلدهای تنظیمات با استفاده از WordPress Settings API.
     *
     * @param string $option_group نام گروه آپشن (معمولاً 'seokar_settings_{tab_id}').
     * @param string $option_name نام آپشن در دیتابیس (معمولاً 'seokar_settings_{tab_id}').
     */
    public function register_settings_fields(string $option_group, string $option_name): void;

    /**
     * رندر کردن محتوای HTML تب (فرم تنظیمات).
     *
     * @param string $option_group نام گروه آپشن برای استفاده در settings_fields().
     */
    public function render_content(string $option_group): void;

    /**
     * پاکسازی و اعتبارسنجی داده‌های ورودی برای این تب.
     * این متد به عنوان sanitize_callback در register_setting استفاده می‌شود.
     *
     * @param array|null $input داده‌های خام ورودی از فرم.
     * @return array<string, mixed> داده‌های پاکسازی شده و معتبر.
     */
    public function sanitize(?array $input): array;

    /**
     * دریافت اسکیمای تنظیمات برای این تب.
     * اسکیما برای اعتبارسنجی خودکار و تعیین مقادیر پیش‌فرض استفاده می‌شود.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_settings_schema(): array;
}


/**
 * Class Seokar_Theme_Options_Refactored
 *
 * کلاس اصلی مدیریت صفحه تنظیمات قالب سئوکار با استفاده از الگوی Singleton.
 *
 * @package SeoKar\Admin\Settings
 * @since   4.0.0
 */
final class Seokar_Theme_Options_Refactored {
    use Seokar_Settings_Security_Trait, Seokar_Settings_Validation_Trait;

    /**
     * نمونه Singleton کلاس.
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * مدیر تب‌ها.
     * @var Seokar_Tab_Manager
     */
    private Seokar_Tab_Manager $tab_manager;

    /**
     * شناسه تب فعال فعلی.
     * @var string
     */
    private string $current_tab;

    /**
     * کش تنظیمات بارگذاری شده.
     * @var array<string, array<string, mixed>>
     */
    private array $settings_cache = [];

    /**
     * دریافت نمونه Singleton کلاس.
     *
     * @return self
     */
    public static function get_instance(): self {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * سازنده خصوصی برای جلوگیری از ایجاد نمونه مستقیم و پیاده‌سازی Singleton.
     */
    private function __construct() {
        $this->tab_manager = new Seokar_Tab_Manager();
        $this->register_core_tabs(); // ثبت تب‌های پیش‌فرض
        $this->init_hooks();         // اتصال به هوک‌های وردپرس
    }

    /**
     * جلوگیری از کلون کردن نمونه Singleton.
     */
    private function __clone() {}

    /**
     * جلوگیری از unserialize کردن نمونه Singleton.
     * @throws \Exception
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * ثبت تب‌های اصلی قالب.
     * توسعه‌دهندگان می‌توانند تب‌های خود را با استفاده از هوک 'seokar_register_settings_tabs' اضافه کنند.
     */
    private function register_core_tabs(): void {
        // --- تعریف تب‌ها ---
        // نکته: 'sanitize_callback' و 'settings_schema' باید به متدهای مربوطه در کلاس هر تب اشاره کنند.
        // مثال برای تب General:
        $this->tab_manager->register('general', [
            'title'             => __('تنظیمات عمومی', 'seokar'),
            'icon'              => 'dashicons-admin-settings',
            'priority'          => 10,
            'class'             => 'Seokar_General_Settings', // نام کلاس مربوطه
            // 'sanitize_callback' => [$this->get_tab_instance('general'), 'sanitize'], // نمونه: دریافت از نمونه کلاس تب
            // 'settings_schema'   => [$this->get_tab_instance('general'), 'get_settings_schema'], // نمونه
        ]);

        $this->tab_manager->register('seo', [
            'title'             => __('بهینه‌سازی (SEO)', 'seokar'),
            'icon'              => 'dashicons-search',
            'priority'          => 20,
            'class'             => 'Seokar_Seo_Settings',
            // 'sanitize_callback' => ...
            // 'settings_schema'   => ...
        ]);

        $this->tab_manager->register('ai', [
            'title'             => __('هوش مصنوعی', 'seokar'),
            'icon'              => 'dashicons-art',
            'priority'          => 30,
            'require'           => defined('SEOKAR_AI_MODULE_ENABLED') && SEOKAR_AI_MODULE_ENABLED, // شرط دقیق‌تر
            'class'             => 'Seokar_Ai_Settings',
            // ...
        ]);

        $this->tab_manager->register('api', [
            'title'             => __('اتصال API', 'seokar'),
            'icon'              => 'dashicons-rest-api',
            'priority'          => 40,
            'class'             => 'Seokar_Api_Settings',
            // ...
        ]);

        $this->tab_manager->register('advanced', [
            'title'             => __('تنظیمات پیشرفته', 'seokar'),
            'icon'              => 'dashicons-admin-tools',
            'priority'          => 50,
            'capability'        => 'manage_network_options', // یا سطح دسترسی مناسب دیگر
            'class'             => 'Seokar_Advanced_Settings',
            // ...
        ]);

        $this->tab_manager->register('analytics', [
            'title'             => __('آمار و ردیابی', 'seokar'),
            'icon'              => 'dashicons-chart-bar',
            'priority'          => 60,
            'class'             => 'Seokar_Analytics_Settings',
            // ...
        ]);

        $this->tab_manager->register('header-footer', [
            'title'             => __('هدر و فوتر', 'seokar'),
            'icon'              => 'dashicons-editor-kitchensink',
            'priority'          => 70,
            'class'             => 'Seokar_Header_Footer_Settings',
            // ...
        ]);

        $this->tab_manager->register('schema', [
            'title'             => __('نشانه‌گذاری Schema', 'seokar'),
            'icon'              => 'dashicons-editor-code',
            'priority'          => 80,
            'capability'        => 'edit_theme_options',
            'class'             => 'Seokar_Schema_Settings',
            // ...
        ]);

        $this->tab_manager->register('ads', [
            'title'             => __('تبلیغات و کدها', 'seokar'),
            'icon'              => 'dashicons-money-alt',
            'priority'          => 90,
            'class'             => 'Seokar_Ads_Settings',
            // ...
        ]);

        $this->tab_manager->register('email', [
            'title'             => __('ایمیل و اعلان‌ها', 'seokar'),
            'icon'              => 'dashicons-email-alt',
            'priority'          => 100,
            'class'             => 'Seokar_Email_Settings',
            // ...
        ]);

        $this->tab_manager->register('license', [
            'title'             => __('فعالسازی لایسنس', 'seokar'),
            'icon'              => 'dashicons-lock',
            'priority'          => 110,
            'require'           => fn() => is_admin(), // استفاده از تابع ناشناس برای شرط
            'class'             => 'Seokar_License_Settings',
            // ...
        ]);

        $this->tab_manager->register('support', [
            'title'             => __('پشتیبانی', 'seokar'),
            'icon'              => 'dashicons-sos',
            'priority'          => 120,
            'class'             => 'Seokar_Support_Settings',
            // این تب ممکن است فیلد قابل ذخیره‌سازی نداشته باشد
        ]);

        $this->tab_manager->register('backup', [
            'title'             => __('پشتیبان‌گیری', 'seokar'),
            'icon'              => 'dashicons-backup',
            'priority'          => 130,
            'capability'        => 'export',
            'class'             => 'Seokar_Backup_Settings',
            // این تب ممکن است فیلد قابل ذخیره‌سازی نداشته باشد
        ]);

        /**
         * هوک برای افزودن یا تغییر تب‌های تنظیمات سئوکار.
         *
         * @param Seokar_Tab_Manager $tab_manager نمونه‌ای از مدیر تب‌ها.
         */
        do_action('seokar_register_settings_tabs', $this->tab_manager);
    }

    /**
     * ثبت هوک‌های وردپرس مورد نیاز.
     */
    private function init_hooks(): void {
        // منوی ادمین
        add_action('admin_menu', [$this, 'hook_admin_menu']);

        // ثبت تنظیمات و فیلدها
        add_action('admin_init', [$this, 'hook_admin_init']);

        // بارگذاری فایل‌های CSS و JS
        add_action('admin_enqueue_scripts', [$this, 'hook_enqueue_admin_assets']);

        // نمایش اعلان‌ها (مثل ذخیره موفق)
        add_action('admin_notices', [$this, 'hook_admin_notices']);

        // کنترل‌کننده‌های AJAX
        add_action('wp_ajax_seokar_load_tab', [$this, 'ajax_load_tab_content']); // تغییر نام برای وضوح
        add_action('wp_ajax_seokar_export_settings', [$this, 'ajax_export_settings']);
        add_action('wp_ajax_seokar_import_settings', [$this, 'ajax_import_settings']);

        /**
         * هوک پس از مقداردهی اولیه سیستم تنظیمات سئوکار.
         *
         * @param self $this نمونه‌ای از کلاس تنظیمات اصلی.
         */
        do_action('seokar_theme_options_init', $this);
    }

    /**
     * هوک: افزودن صفحه تنظیمات به منوی مدیریت وردپرس.
     * @hooked admin_menu
     */
    public function hook_admin_menu(): void {
        add_menu_page(
            __('تنظیمات قالب سئوکار', 'seokar'), // عنوان صفحه
            __('سئوکار', 'seokar'),             // عنوان منو
            'manage_options',                   // سطح دسترسی لازم
            SEOKAR_SETTINGS_MENU_SLUG,          // اسلاگ منحصر به فرد منو
            [$this, 'render_options_page'],     // تابع نمایش محتوای صفحه
            'dashicons-admin-generic',          // آیکون منو
            61                                  // موقعیت منو
        );
    }

    /**
     * هوک: ثبت تنظیمات، بخش‌ها و فیلدها با استفاده از WordPress Settings API.
     * @hooked admin_init
     */
    public function hook_admin_init(): void {
        $this->current_tab = $this->get_current_tab_id(); // تعیین تب فعلی زودتر

        foreach ($this->tab_manager->get_all_registered() as $tab_id => $tab_config) {
            // بررسی کنید آیا این تب نیاز به ذخیره تنظیمات دارد (ممکن است تبی مثل پشتیبانی نیازی نداشته باشد)
            // این بررسی می‌تواند بر اساس وجود 'sanitize_callback' یا یک فلگ جدید در config باشد.
            if (isset($tab_config['sanitize_callback']) && is_callable($tab_config['sanitize_callback'])) {
                $option_name = $this->get_option_name($tab_id);
                $option_group = $option_name . '_group'; // گروه مجزا برای هر تب

                register_setting(
                    $option_group,                 // نام گروه آپشن (برای settings_fields)
                    $option_name,                  // نام آپشن در جدول wp_options
                    [
                        'sanitize_callback' => $tab_config['sanitize_callback'], // تابع پاکسازی
                        'default'           => $this->get_default_settings($tab_id), // مقادیر پیش‌فرض
                        // 'show_in_rest' => true, // در صورت نیاز به دسترسی از طریق REST API
                    ]
                );
            }

            // ثبت فیلدها و بخش‌ها فقط برای تب فعال (برای بهینگی)
            // یا می‌توان همه را ثبت کرد اگر تداخل ایجاد نمی‌کند.
            // ثبت همه بهتر است زیرا ممکن است ذخیره از تبی غیرفعال انجام شود.
            try {
                $tab_instance = $this->get_tab_instance($tab_id);
                if ($tab_instance instanceof Seokar_Settings_Tab_Interface && method_exists($tab_instance, 'register_settings_fields')) {
                     $option_name = $this->get_option_name($tab_id);
                     $option_group = $option_name . '_group';
                     $tab_instance->register_settings_fields($option_group, $option_name);
                }
            } catch (\Exception $e) {
                // ثبت خطا یا نمایش هشدار به ادمین
                $this->log_error(sprintf('خطا در ثبت فیلدهای تب "%s": %s', $tab_id, $e->getMessage()));
            }

            /**
             * هوک برای اجرای عملیات اضافی هنگام ثبت تنظیمات یک تب خاص.
             *
             * @param string $tab_id شناسه تب.
             * @param array  $tab_config تنظیمات تب.
             * @param self   $this نمونه کلاس اصلی تنظیمات.
             */
            do_action("seokar_admin_init_tab_{$tab_id}", $tab_id, $tab_config, $this);
        }
    }

    /**
     * هوک: بارگذاری فایل‌های CSS و JS مورد نیاز برای صفحه تنظیمات.
     * @hooked admin_enqueue_scripts
     *
     * @param string $hook نام هوک صفحه فعلی مدیریت.
     */
    public function hook_enqueue_admin_assets(string $hook): void {
        // فقط در صفحه تنظیمات خودمان اجرا شود
        // 'toplevel_page_' + menu_slug
        if ($hook !== 'toplevel_page_' . SEOKAR_SETTINGS_MENU_SLUG) {
            return;
        }

        $theme_version = defined('SEOKAR_THEME_VERSION') ? SEOKAR_THEME_VERSION : '4.0.0';
        $css_path = SEOKAR_SETTINGS_PATH . '../assets/css/admin-options.min.css'; // مسیر دقیق‌تر
        $js_path = SEOKAR_SETTINGS_PATH . '../assets/js/admin-options.min.js';   // مسیر دقیق‌تر
        $css_url = SEOKAR_ADMIN_ASSETS_URL . 'css/admin-options.min.css';
        $js_url = SEOKAR_ADMIN_ASSETS_URL . 'js/admin-options.min.js';
        $css_version = file_exists($css_path) ? filemtime($css_path) : $theme_version;
        $js_version = file_exists($js_path) ? filemtime($js_path) : $theme_version;


        // --- CSS ---
        wp_enqueue_style(
            'seokar-admin-options',
            $css_url,
            ['wp-color-picker'], // وابستگی‌ها
            (string)$css_version // ورژن فایل برای Cache Busting
        );

        // --- JS ---
        wp_enqueue_script(
            'seokar-admin-options',
            $js_url,
            ['jquery', 'wp-color-picker', 'jquery-ui-tabs', 'wp-i18n', 'wp-hooks', 'wp-api-fetch'], // وابستگی‌ها (wp-hooks و wp-api-fetch برای تعامل بهتر)
            (string)$js_version,
            true // بارگذاری در فوتر
        );

        // --- محلی‌سازی داده‌ها برای JS ---
        $localized_data = [
            'ajax_url'       => admin_url('admin-ajax.php'),
            'rest_url'       => esc_url_raw(rest_url()), // برای استفاده‌های آتی با REST API
            'nonce'          => wp_create_nonce('seokar_admin_ajax_nonce'), // Nonce عمومی برای AJAX
            'load_tab_nonce' => wp_create_nonce('seokar_load_tab_nonce'),
            'export_nonce'   => wp_create_nonce('seokar_export_settings_nonce'),
            'import_nonce'   => wp_create_nonce('seokar_import_settings_nonce'),
            'current_tab'    => $this->current_tab,
            'tabs'           => array_keys($this->tab_manager->get_all_visible()), // ارسال لیست تب‌های قابل مشاهده
            'option_prefix'  => SEOKAR_SETTINGS_OPTION_PREFIX,
            'i18n'           => [ // رشته‌های قابل ترجمه برای JS
                'saving'          => esc_html__('در حال ذخیره...', 'seokar'),
                'success'         => esc_html__('تنظیمات با موفقیت ذخیره شد.', 'seokar'),
                'error'           => esc_html__('خطا در ذخیره تنظیمات. لطفاً دوباره تلاش کنید.', 'seokar'),
                'confirm_export'  => esc_html__('آیا از خروجی گرفتن تنظیمات مطمئن هستید؟ این فایل شامل تمام تنظیمات تب‌های قابل دسترس شما خواهد بود.', 'seokar'),
                'confirm_import'  => esc_html__('هشدار: این عمل تنظیمات فعلی را با محتوای فایل بازنویسی می‌کند. آیا از ادامه مطمئن هستید؟', 'seokar'),
                'import_success'  => esc_html__('تنظیمات با موفقیت وارد شدند. صفحه به‌روزرسانی می‌شود...', 'seokar'),
                'import_error'    => esc_html__('خطا در وارد کردن تنظیمات.', 'seokar'),
                'invalid_file'    => esc_html__('فایل انتخاب شده معتبر نیست یا مشکلی در خواندن آن وجود دارد.', 'seokar'),
                'no_file'         => esc_html__('لطفاً یک فایل برای وارد کردن انتخاب کنید.', 'seokar'),
                'loading'         => esc_html__('در حال بارگذاری...', 'seokar'),
                'tab_load_error'  => esc_html__('خطا در بارگذاری محتوای تب.', 'seokar'),
            ],
        ];

        // استفاده از wp_add_inline_script برای امنیت بیشتر به جای wp_localize_script
        // wp_localize_script همچنان کار می‌کند اما این روش مدرن‌تر است.
        wp_add_inline_script(
            'seokar-admin-options',
            sprintf('const seokarOptions = %s;', wp_json_encode($localized_data)),
            'before' // قبل از اجرای اسکریپت اصلی
        );

        // اضافه کردن پشتیبانی از ترجمه در JS (اگر فایل .mo برای textdomain 'seokar' وجود داشته باشد)
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(
                'seokar-admin-options', // هندل اسکریپت
                'seokar',               // Text Domain
                get_template_directory() . '/languages' // مسیر فایل‌های ترجمه .json
            );
        }

         /**
          * هوک برای بارگذاری اسکریپت‌ها یا استایل‌های اضافی در صفحه تنظیمات سئوکار.
          *
          * @param string $hook نام هوک صفحه ('toplevel_page_seokar-theme-options').
          */
         do_action('seokar_enqueue_settings_assets', $hook);
    }

    /**
     * هوک: نمایش اعلان‌های ادمین (مثل ذخیره موفق، خطا در واردات).
     * @hooked admin_notices
     */
    public function hook_admin_notices(): void {
        // فقط در صفحه تنظیمات خودمان نمایش داده شود
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_' . SEOKAR_SETTINGS_MENU_SLUG) {
            return;
        }

        // بررسی پارامتر 'settings-updated' که توسط وردپرس بعد از ذخیره موفق اضافه می‌شود
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            // پیام موفقیت استاندارد وردپرس معمولاً کافی است، اما می‌توانیم پیام سفارشی هم نشان دهیم.
            // settings_errors(); // این تابع پیام‌های ثبت شده توسط add_settings_error را نمایش می‌دهد.
            // اگر پیام سفارشی می‌خواهید:
            // printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__('تنظیمات با موفقیت ذخیره شدند.', 'seokar'));
        }

        // بررسی پارامترهای خطا که خودمان در ریدایرکت‌ها تنظیم می‌کنیم (مثلاً بعد از واردات ناموفق)
        if (isset($_GET['seokar_error'])) {
            $error_code = sanitize_key($_GET['seokar_error']);
            $message = '';
            switch ($error_code) {
                case 'import_invalid_file':
                    $message = __('فایل وارد شده معتبر نیست یا فرمت JSON ندارد.', 'seokar');
                    break;
                case 'import_failed':
                    $message = __('خطا در پردازش فایل واردات یا ذخیره تنظیمات.', 'seokar');
                    break;
                case 'import_nonce':
                     $message = __('بررسی امنیتی ناموفق بود. لطفاً دوباره تلاش کنید.', 'seokar');
                     break;
                case 'import_capability':
                     $message = __('شما دسترسی لازم برای وارد کردن تنظیمات را ندارید.', 'seokar');
                     break;
                // سایر کدهای خطا...
                default:
                    $message = __('یک خطای ناشناخته رخ داد.', 'seokar');
            }
            printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($message));
        }

         if (isset($_GET['seokar_success'])) {
             $success_code = sanitize_key($_GET['seokar_success']);
             $message = '';
             switch ($success_code) {
                 case 'import_successful':
                     $imported_count = isset($_GET['imported']) ? (int)$_GET['imported'] : 0;
                     $message = sprintf(
                         esc_html__('%d گروه تنظیمات با موفقیت وارد شد.', 'seokar'),
                         $imported_count
                     );
                     break;
                 // سایر کدهای موفقیت...
             }
             if ($message) {
                 printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
             }
         }

         // نمایش خطاهای ثبت شده توسط Settings API
         settings_errors();
    }

    /**
     * رندر کردن ساختار کلی صفحه تنظیمات (هدر، تب‌ها، محتوا، فوتر).
     */
    public function render_options_page(): void {
        $this->current_tab = $this->get_current_tab_id(); // اطمینان از به‌روز بودن تب فعلی
        $available_tabs = $this->tab_manager->get_all_visible(); // فقط تب‌های قابل مشاهده
        $theme_version = defined('SEOKAR_THEME_VERSION') ? SEOKAR_THEME_VERSION : '4.0.0';
        ?>
        <div class="wrap seokar-settings-wrap">
            <header class="seokar-settings-header">
                <h1 class="seokar-settings-title">
                    <i class="dashicons dashicons-admin-generic" aria-hidden="true"></i>
                    <?php echo esc_html__('تنظیمات قالب سئوکار', 'seokar'); ?>
                </h1>
                <div class="seokar-settings-version">
                    <?php echo esc_html(sprintf(__('نسخه %s', 'seokar'), $theme_version)); ?>
                </div>
            </header>

            <?php // نمایش اعلان‌ها در بالای صفحه ?>
            <?php // $this->hook_admin_notices(); // یا اجازه دهید وردپرس خودش در جای مناسب نمایش دهد ?>

            <nav class="nav-tab-wrapper wp-clearfix seokar-settings-tabs" aria-label="<?php esc_attr_e('تب‌های تنظیمات اصلی', 'seokar'); ?>">
                <?php foreach ($available_tabs as $tab_id => $config): ?>
                    <a href="<?php echo esc_url($this->get_tab_url($tab_id)); ?>"
                       class="nav-tab <?php echo $this->current_tab === $tab_id ? 'nav-tab-active' : ''; ?>"
                       data-tab="<?php echo esc_attr($tab_id); ?>"
                       aria-current="<?php echo $this->current_tab === $tab_id ? 'page' : 'false'; ?>">
                        <i class="dashicons <?php echo esc_attr($config['icon']); ?>" aria-hidden="true"></i>
                        <?php echo esc_html($config['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="seokar-settings-content">
                <?php // محتوای تب فعال در اینجا بارگذاری می‌شود ?>
                <?php $this->render_current_tab_content(); ?>
            </div>

            <footer class="seokar-settings-footer">
                <p>
                    <?php
                    echo wp_kses_post(sprintf(
                        /* translators: %s: Current year */
                        __('قالب سئوکار © %s - توسعه داده شده توسط <a href="https://seokar.click" target="_blank" rel="noopener noreferrer">تیم سئوکار</a>', 'seokar'),
                        date_i18n('Y') // استفاده از سال محلی شده
                    ));
                    ?>
                </p>
            </footer>
        </div>
        <?php
    }

    /**
     * رندر کردن محتوای تب فعال فعلی.
     * این تابع مسئول بارگذاری فایل تب، ایجاد نمونه از کلاس آن و فراخوانی متد render است.
     */
    private function render_current_tab_content(): void {
        $tab_config = $this->tab_manager->get($this->current_tab);

        if (!$tab_config) {
            $this->render_error_message(__('تب انتخاب شده نامعتبر است.', 'seokar'));
            return;
        }

        // بررسی مجدد دسترسی (هرچند در get_all_visible چک شده، برای اطمینان بیشتر)
        if (!$this->current_user_can($tab_config['capability'])) {
            $this->render_error_message(__('شما دسترسی لازم برای مشاهده این تب را ندارید.', 'seokar'));
            return;
        }

        try {
            $tab_instance = $this->get_tab_instance($this->current_tab);

            if (!$tab_instance instanceof Seokar_Settings_Tab_Interface) {
                 throw new \RuntimeException(sprintf(
                     __('کلاس "%s" برای تب "%s" باید اینترفیس Seokar_Settings_Tab_Interface را پیاده‌سازی کند.', 'seokar'),
                     esc_html($tab_config['class']),
                     esc_html($this->current_tab)
                 ));
            }

            // شروع فرم تنظیمات
            // فرم باید فقط برای تب‌هایی نمایش داده شود که تنظیمات قابل ذخیره دارند.
            $option_name = $this->get_option_name($this->current_tab);
            $option_group = $option_name . '_group';

            // بررسی وجود متد register_settings_fields نشان می‌دهد که آیا این تب تنظیماتی برای ذخیره دارد یا خیر
            $has_savable_settings = method_exists($tab_instance, 'register_settings_fields');

            echo '<div class="seokar-tab-content-inner" id="seokar-tab-' . esc_attr($this->current_tab) . '" data-tab-id="' . esc_attr($this->current_tab) . '">';

            if ($has_savable_settings) {
                echo '<form method="post" action="options.php" class="seokar-settings-form">';

                // فیلدهای مخفی لازم برای Settings API وردپرس
                settings_fields($option_group);

                // رندر کردن بخش‌ها و فیلدهای ثبت شده برای این گروه
                // do_settings_sections($option_group); // این تابع محتوای فیلدها را نمایش می‌دهد

                // به جای do_settings_sections، متد render_content کلاس تب را فراخوانی می‌کنیم
                // تا کنترل بیشتری روی چیدمان داشته باشیم.
                $tab_instance->render_content($option_group);


                // دکمه ذخیره (فقط اگر فیلدی برای ذخیره وجود دارد)
                submit_button(__('ذخیره تغییرات', 'seokar'));

                echo '</form>';
            } else {
                 // اگر تبی مثل "پشتیبانی" یا "پشتیبان‌گیری" فقط محتوای نمایشی دارد
                 $tab_instance->render_content($option_group); // option_group ممکن است لازم نباشد
            }

            echo '</div>'; // .seokar-tab-content-inner

        } catch (\Throwable $e) { // گرفتن Exception و Error (PHP 7+)
            $this->render_error_message(sprintf(
                __('خطا در بارگذاری یا نمایش تب "%s": %s', 'seokar'),
                esc_html($tab_config['title']),
                esc_html($e->getMessage())
            ));
            // لاگ کردن خطای کامل برای دیباگ
            $this->log_error('خطا در render_current_tab_content: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * AJAX: بارگذاری محتوای یک تب به صورت دینامیک (اگر نیاز باشد).
     * نکته: در پیاده‌سازی فعلی، کل صفحه با تغییر تب رفرش می‌شود. این تابع برای حالت SPA (Single Page Application) است.
     * اگر می‌خواهید بدون رفرش صفحه تب‌ها عوض شوند، باید JS را برای این کار تنظیم کنید.
     */
    public function ajax_load_tab_content(): void {
        if (!$this->verify_nonce($_POST['nonce'] ?? '', 'seokar_load_tab_nonce')) {
            wp_send_json_error(['message' => __('بررسی امنیتی ناموفق بود.', 'seokar')], 403);
        }

        $tab_id = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : null;

        if (!$tab_id || !$this->tab_manager->exists($tab_id)) {
            wp_send_json_error(['message' => __('شناسه تب نامعتبر است.', 'seokar')], 400);
        }

        $tab_config = $this->tab_manager->get($tab_id);

        if (!$this->current_user_can($tab_config['capability'])) {
            wp_send_json_error(['message' => __('شما دسترسی لازم برای مشاهده این تب را ندارید.', 'seokar')], 403);
        }

        // تنظیم تب فعلی برای رندر صحیح
        $this->current_tab = $tab_id;

        // شروع بافر خروجی برای گرفتن HTML رندر شده
        ob_start();
        $this->render_current_tab_content();
        $content = ob_get_clean();

        if (empty($content)) {
             // بررسی کنید آیا خطایی در render_current_tab_content رخ داده که پیام خطا را چاپ کرده
             // اگر نه، یک پیام خطای عمومی بفرستید
             if (ob_get_length() === 0) { // اگر بافر خالی بود (یعنی render_error_message هم اجرا نشده)
                 wp_send_json_error(['message' => __('محتوایی برای این تب یافت نشد یا خطایی در تولید آن رخ داد.', 'seokar')], 500);
             } else {
                 // اگر render_error_message چیزی چاپ کرده، آن را به عنوان خطا بفرست
                 wp_send_json_error(['html' => $content], 500);
             }
        } else {
            wp_send_json_success(['html' => $content, 'tab' => $tab_id]);
        }
    }


    /**
     * AJAX: خروجی گرفتن از تنظیمات (Export).
     */
    public function ajax_export_settings(): void {
        if (!$this->verify_nonce($_POST['nonce'] ?? '', 'seokar_export_settings_nonce')) {
            wp_send_json_error(['message' => __('بررسی امنیتی ناموفق بود.', 'seokar')], 403);
        }

        if (!$this->current_user_can('export')) { // استفاده از سطح دسترسی استاندارد وردپرس
            wp_send_json_error(['message' => __('شما دسترسی لازم برای خروجی گرفتن از تنظیمات را ندارید.', 'seokar')], 403);
        }

        $tab_to_export = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'all';
        $settings_to_export = [];
        $filename_part = 'all';

        if ($tab_to_export === 'all') {
            $visible_tabs = $this->tab_manager->get_all_visible(); // فقط تب‌های قابل دسترس کاربر
            foreach ($visible_tabs as $tab_id => $config) {
                // فقط تب‌هایی که تنظیمات دارند را خروجی بگیر
                 if (isset($config['sanitize_callback'])) {
                    $option_name = $this->get_option_name($tab_id);
                    $settings_to_export[$option_name] = $this->get_settings($tab_id); // استفاده از متد get_settings با کش
                 }
            }
        } elseif ($this->tab_manager->exists($tab_to_export)) {
            $config = $this->tab_manager->get($tab_to_export);
             // بررسی دسترسی مجدد
             if (!$this->current_user_can($config['capability'])) {
                 wp_send_json_error(['message' => __('شما دسترسی لازم برای خروجی گرفتن از این تب را ندارید.', 'seokar')], 403);
             }
             // بررسی اینکه آیا تب تنظیمات دارد
             if (isset($config['sanitize_callback'])) {
                 $option_name = $this->get_option_name($tab_to_export);
                 $settings_to_export[$option_name] = $this->get_settings($tab_to_export);
                 $filename_part = $tab_to_export;
             } else {
                 wp_send_json_error(['message' => __('این تب تنظیمات قابل خروجی گرفتن ندارد.', 'seokar')], 400);
             }
        } else {
            wp_send_json_error(['message' => __('شناسه تب نامعتبر است.', 'seokar')], 400);
        }

        if (empty($settings_to_export)) {
            wp_send_json_error(['message' => __('هیچ تنظیماتی برای خروجی گرفتن یافت نشد.', 'seokar')], 404);
        }

        // ایجاد نام فایل
        $site_name = sanitize_key(get_bloginfo('name'));
        $date = date('Y-m-d');
        $filename = sprintf('seokar-settings-%s-%s-%s.json', $filename_part, $site_name, $date);

        // ارسال هدرهای لازم برای دانلود فایل
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache'); // سازگاری با مرورگرهای قدیمی‌تر
        header('Expires: 0');      // جلوگیری از کش شدن

        // چاپ محتوای JSON و خروج
        echo wp_json_encode($settings_to_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX: وارد کردن تنظیمات (Import).
     */
    public function ajax_import_settings(): void {
        if (!$this->verify_nonce($_POST['nonce'] ?? '', 'seokar_import_settings_nonce')) {
            wp_send_json_error(['message' => __('بررسی امنیتی ناموفق بود.', 'seokar')], 403);
            // یا ریدایرکت با پارامتر خطا
            // $this->redirect_with_notice('error', 'import_nonce');
        }

        if (!$this->current_user_can('manage_options')) { // یا سطح دسترسی بالاتر اگر لازم است
            wp_send_json_error(['message' => __('شما دسترسی لازم برای وارد کردن تنظیمات را ندارید.', 'seokar')], 403);
            // $this->redirect_with_notice('error', 'import_capability');
        }

        if (empty($_FILES['import_file']) || !isset($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('خطا در آپلود فایل یا فایلی انتخاب نشده است.', 'seokar')], 400);
            // $this->redirect_with_notice('error', 'import_upload_error');
        }

        $file = $_FILES['import_file'];

        // بررسی نوع فایل (باید JSON باشد)
        $mime_type = mime_content_type($file['tmp_name']);
        if ($mime_type !== 'application/json' && $mime_type !== 'text/plain') { // text/plain هم گاهی استفاده می‌شود
             wp_send_json_error(['message' => __('فرمت فایل باید JSON باشد.', 'seokar')], 400);
             // $this->redirect_with_notice('error', 'import_invalid_mime');
        }


        // خواندن محتوای فایل
        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            wp_send_json_error(['message' => __('خطا در خواندن محتوای فایل.', 'seokar')], 500);
            // $this->redirect_with_notice('error', 'import_read_error');
        }

        // پاک کردن فایل موقت آپلود شده
        @unlink($file['tmp_name']);

        // دیکد کردن JSON
        $imported_data = json_decode($content, true);

        // بررسی خطای JSON
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($imported_data)) {
            wp_send_json_error(['message' => __('فایل وارد شده معتبر نیست یا ساختار JSON صحیحی ندارد.', 'seokar')], 400);
            // $this->redirect_with_notice('error', 'import_invalid_file');
        }

        $imported_count = 0;
        $errors = [];

        // پردازش داده‌های وارد شده
        foreach ($imported_data as $option_name => $settings_value) {
            // استخراج شناسه تب از نام آپشن
            if (strpos($option_name, SEOKAR_SETTINGS_OPTION_PREFIX) === 0) {
                $tab_id = substr($option_name, strlen(SEOKAR_SETTINGS_OPTION_PREFIX));

                // بررسی وجود تب و دسترسی کاربر به آن
                if ($this->tab_manager->exists($tab_id)) {
                    $tab_config = $this->tab_manager->get($tab_id);

                    // بررسی دسترسی کاربر به تب مقصد
                    if (!$this->current_user_can($tab_config['capability'])) {
                        $errors[] = sprintf(__('دسترسی لازم برای وارد کردن تنظیمات تب "%s" وجود ندارد.', 'seokar'), $tab_config['title']);
                        continue; // برو به آپشن بعدی
                    }

                    // **مهم: پاکسازی داده‌های وارد شده قبل از ذخیره**
                    // استفاده از همان sanitize_callback که برای ذخیره عادی استفاده می‌شود.
                    $sanitized_value = $settings_value; // مقدار پیش‌فرض اگر پاکسازی ممکن نباشد
                    if (isset($tab_config['sanitize_callback']) && is_callable($tab_config['sanitize_callback'])) {
                        // اطمینان از اینکه ورودی یک آرایه است (حتی اگر فایل JSON فقط یک مقدار داشته باشد)
                        $sanitized_value = call_user_func($tab_config['sanitize_callback'], is_array($settings_value) ? $settings_value : []);
                    } elseif (isset($tab_config['settings_schema']) && is_callable($tab_config['settings_schema'])) {
                         // اگر sanitize_callback مستقیم نبود، از اسکیما و trait استفاده کن
                         $schema = call_user_func($tab_config['settings_schema']);
                         if (is_array($schema)) {
                             $sanitized_value = $this->sanitize_settings(is_array($settings_value) ? $settings_value : [], $schema);
                         }
                    } else {
                         // اگر هیچ روش پاکسازی مشخص نشده، با احتیاط عمل کن (مثلاً فقط مقادیر اسکالر را اجازه بده یا خطا بده)
                         // در اینجا فرض می‌کنیم اگر پاکسازی تعریف نشده، وارد نمی‌کنیم
                         $errors[] = sprintf(__('روش پاکسازی برای تنظیمات تب "%s" تعریف نشده است. واردات انجام نشد.', 'seokar'), $tab_config['title']);
                         continue;
                    }


                    // ذخیره تنظیمات پاکسازی شده
                    if (update_option($option_name, $sanitized_value)) {
                        $imported_count++;
                        $this->clear_settings_cache($tab_id); // پاک کردن کش برای این تب
                    } else {
                        // ممکن است update_option false برگرداند اگر مقدار جدید با مقدار قبلی یکسان باشد
                        // یا اگر خطایی در دیتابیس رخ دهد.
                        // $errors[] = sprintf(__('خطا در ذخیره تنظیمات برای تب "%s".', 'seokar'), $tab_config['title']);
                    }
                } else {
                     $errors[] = sprintf(__('تب مربوط به آپشن "%s" یافت نشد یا غیرفعال است.', 'seokar'), $option_name);
                }
            } else {
                 $errors[] = sprintf(__('نام آپشن "%s" با پیشوند مورد انتظار (%s) مطابقت ندارد.', 'seokar'), $option_name, SEOKAR_SETTINGS_OPTION_PREFIX);
            }
        }

        // ارسال نتیجه به کاربر
        if ($imported_count > 0) {
            $message = sprintf(
                esc_html__('%d گروه تنظیمات با موفقیت وارد و ذخیره شد.', 'seokar'),
                $imported_count
            );
            if (!empty($errors)) {
                $message .= "\n" . esc_html__('خطاهای زیر نیز رخ داد:', 'seokar') . "\n - " . implode("\n - ", array_map('esc_html', $errors));
            }
             wp_send_json_success([
                 'message' => $message,
                 'imported' => $imported_count,
                 'errors' => $errors,
                 'reload' => true // به JS بگویید صفحه را رفرش کند
             ]);
             // $this->redirect_with_notice('success', 'import_successful', ['imported' => $imported_count]);

        } else {
            $error_message = esc_html__('هیچ تنظیماتی وارد نشد.', 'seokar');
            if (!empty($errors)) {
                $error_message .= "\n" . esc_html__('خطاهای رخ داده:', 'seokar') . "\n - " . implode("\n - ", array_map('esc_html', $errors));
            }
            wp_send_json_error(['message' => $error_message, 'errors' => $errors], 400);
            // $this->redirect_with_notice('error', 'import_failed');
        }
    }

    // --- توابع کمکی داخلی ---

    /**
     * نمایش پیام خطا در صفحه تنظیمات.
     *
     * @param string $message پیام خطا.
     */
    private function render_error_message(string $message): void {
        printf('<div class="notice notice-error inline"><p>%s</p></div>', esc_html($message));
    }

    /**
     * دریافت شناسه تب فعال از URL یا مقدار پیش‌فرض.
     *
     * @return string شناسه تب فعال.
     */
    public function get_current_tab_id(): string {
        // اگر قبلاً محاسبه شده، همان را برگردان
        if (!empty($this->current_tab)) {
            return $this->current_tab;
        }

        $default_tab = 'general'; // تب پیش‌فرض
        $tab_id = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

        // بررسی کنید آیا تب درخواستی معتبر و قابل مشاهده است
        $visible_tabs = $this->tab_manager->get_all_visible();
        if (!isset($visible_tabs[$tab_id])) {
            // اگر تب درخواستی معتبر نیست، اولین تب قابل مشاهده را انتخاب کن
            $tab_id = !empty($visible_tabs) ? array_key_first($visible_tabs) : $default_tab;
        }

        $this->current_tab = $tab_id;
        return $this->current_tab;
    }

    /**
     * ساخت URL برای یک تب خاص.
     *
     * @param string $tab_id شناسه تب.
     * @return string URL کامل تب.
     */
    public function get_tab_url(string $tab_id): string {
        return add_query_arg(
            ['tab' => $tab_id],
            admin_url('admin.php?page=' . SEOKAR_SETTINGS_MENU_SLUG)
        );
    }

    /**
     * دریافت نام آپشن ذخیره شده در دیتابیس برای یک تب.
     *
     * @param string $tab_id شناسه تب.
     * @return string نام آپشن (مثال: 'seokar_settings_general').
     */
    public function get_option_name(string $tab_id): string {
        return SEOKAR_SETTINGS_OPTION_PREFIX . $tab_id;
    }

    /**
     * دریافت تنظیمات یک تب از دیتابیس یا کش.
     *
     * @param string $tab_id شناسه تب.
     * @param bool   $force_refresh اگر true باشد، کش نادیده گرفته می‌شود.
     * @return array<string, mixed> آرایه‌ای از تنظیمات.
     */
    public function get_settings(string $tab_id, bool $force_refresh = false): array {
        $option_name = $this->get_option_name($tab_id);
        $cache_key = $option_name; // کلید کش همان نام آپشن است

        if ($force_refresh || !isset($this->settings_cache[$cache_key])) {
            $defaults = $this->get_default_settings($tab_id);
            $db_settings = get_option($option_name, $defaults);

            // اطمینان از اینکه خروجی همیشه یک آرایه است
            if (!is_array($db_settings)) {
                 $db_settings = [];
            }

            // ادغام با پیش‌فرض‌ها برای اطمینان از وجود همه کلیدها
            $this->settings_cache[$cache_key] = wp_parse_args($db_settings, $defaults);
        }

        return $this->settings_cache[$cache_key];
    }

     /**
      * دریافت یک مقدار تنظیمات خاص از یک تب.
      *
      * @param string $tab_id شناسه تب.
      * @param string $key    کلید تنظیمات مورد نظر.
      * @param mixed  $default مقدار پیش‌فرض اگر کلید یافت نشد.
      * @return mixed مقدار تنظیمات.
      */
     public function get_setting(string $tab_id, string $key, mixed $default = null): mixed {
         $settings = $this->get_settings($tab_id);
         return $settings[$key] ?? $default;
     }

    /**
     * دریافت مقادیر پیش‌فرض برای تنظیمات یک تب از اسکیمای آن.
     *
     * @param string $tab_id شناسه تب.
     * @return array<string, mixed> آرایه‌ای از مقادیر پیش‌فرض.
     */
    public function get_default_settings(string $tab_id): array {
        $defaults = [];
        try {
            $tab_instance = $this->get_tab_instance($tab_id);
            if ($tab_instance instanceof Seokar_Settings_Tab_Interface && method_exists($tab_instance, 'get_settings_schema')) {
                $schema = $tab_instance->get_settings_schema();
                if (is_array($schema)) {
                    foreach ($schema as $key => $field_schema) {
                        if (isset($field_schema['default'])) {
                            $defaults[$key] = $field_schema['default'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // خطا در دریافت پیش‌فرض‌ها، آرایه خالی برگردان
            $this->log_error(sprintf('خطا در دریافت تنظیمات پیش‌فرض تب "%s": %s', $tab_id, $e->getMessage()));
        }
        return $defaults;
    }

    /**
     * پاک کردن کش تنظیمات برای یک تب خاص.
     *
     * @param string $tab_id شناسه تب.
     */
    public function clear_settings_cache(string $tab_id): void {
        $cache_key = $this->get_option_name($tab_id);
        unset($this->settings_cache[$cache_key]);
        // در صورت استفاده از کش دائمی وردپرس (Transients/Object Cache)، آن را نیز پاک کنید
        // wp_cache_delete($cache_key, 'options');
    }

    /**
     * بارگذاری فایل و ایجاد نمونه از کلاس مربوط به یک تب.
     *
     * @param string $tab_id شناسه تب.
     * @return Seokar_Settings_Tab_Interface نمونه کلاس تب.
     * @throws \RuntimeException اگر فایل یا کلاس یافت نشد یا معتبر نباشد.
     */
    private function get_tab_instance(string $tab_id): Seokar_Settings_Tab_Interface {
        static $instances = []; // کش کردن نمونه‌های ایجاد شده

        if (isset($instances[$tab_id])) {
            return $instances[$tab_id];
        }

        $tab_config = $this->tab_manager->get($tab_id);
        if (!$tab_config) {
            throw new \RuntimeException(sprintf(__('پیکربندی برای تب "%s" یافت نشد.', 'seokar'), $tab_id));
        }

        $file_path = $this->resolve_tab_file_path($tab_config['file']);
        $class_name = $tab_config['class'];

        if (!$this->is_valid_settings_file($file_path)) {
            throw new \RuntimeException(sprintf(__('فایل تنظیمات "%s" برای تب "%s" یافت نشد یا معتبر نیست.', 'seokar'), $tab_config['file'], $tab_id));
        }

        // بارگذاری فایل فقط یک بار
        if (!class_exists($class_name, false)) { // false = عدم استفاده از autoload
            require_once $file_path;
        }

        if (!class_exists($class_name)) {
            throw new \RuntimeException(sprintf(__('کلاس "%s" در فایل "%s" برای تب "%s" یافت نشد.', 'seokar'), $class_name, $tab_config['file'], $tab_id));
        }

        // ایجاد نمونه از کلاس
        $instance = new $class_name();

        // بررسی اینکه آیا اینترفیس لازم را پیاده‌سازی می‌کند
        if (!$instance instanceof Seokar_Settings_Tab_Interface) {
             throw new \RuntimeException(sprintf(
                 __('کلاس "%s" برای تب "%s" باید اینترفیس Seokar_Settings_Tab_Interface را پیاده‌سازی کند.', 'seokar'),
                 $class_name,
                 $tab_id
             ));
        }

        // ذخیره نمونه در کش استاتیک
        $instances[$tab_id] = $instance;

        return $instance;
    }

    /**
     * بررسی اعتبار مسیر فایل تنظیمات (جلوگیری از Path Traversal).
     *
     * @param string $file_path مسیر کامل فایل.
     * @return bool True اگر فایل معتبر و در مسیر مجاز باشد.
     */
    private function is_valid_settings_file(string $file_path): bool {
        // اطمینان از اینکه مسیر فایل در دایرکتوری تنظیمات تعریف شده قرار دارد
        $real_settings_path = realpath(SEOKAR_SETTINGS_PATH);
        $real_file_path = realpath($file_path);

        // بررسی وجود فایل و قرار داشتن در مسیر مجاز
        return $real_file_path !== false
            && $real_settings_path !== false
            && strpos($real_file_path, $real_settings_path) === 0
            && file_exists($real_file_path);
    }

    /**
     * ساخت مسیر کامل فایل تنظیمات یک تب.
     *
     * @param string $file_name نام فایل (مثال: 'general-settings.php').
     * @return string مسیر کامل فایل.
     */
    private function resolve_tab_file_path(string $file_name): string {
        // جلوگیری از Path Traversal در نام فایل
        $file_name = wp_basename($file_name);
        return SEOKAR_SETTINGS_PATH . $file_name;
    }

     /**
      * ریدایرکت کاربر به صفحه تنظیمات با یک پیام (برای استفاده بعد از عملیاتی مثل واردات).
      *
      * @param string $type    نوع پیام ('success' یا 'error').
      * @param string $code    کد پیام (برای نمایش متن مناسب در hook_admin_notices).
      * @param array  $args    آرگومان‌های اضافی برای اضافه کردن به URL.
      */
     private function redirect_with_notice(string $type, string $code, array $args = []): void {
         $query_args = [
             'page' => SEOKAR_SETTINGS_MENU_SLUG,
             'tab'  => $this->get_current_tab_id(), // بازگشت به تب فعلی
         ];

         if ($type === 'success') {
             $query_args['seokar_success'] = $code;
         } else {
             $query_args['seokar_error'] = $code;
         }

         $query_args = array_merge($query_args, $args);
         $redirect_url = add_query_arg($query_args, admin_url('admin.php'));

         // استفاده از wp_safe_redirect برای امنیت بیشتر
         wp_safe_redirect(esc_url_raw($redirect_url));
         exit;
     }

     /**
      * لاگ کردن خطا (می‌تواند به error_log یا سیستم لاگ دیگری ارسال شود).
      *
      * @param string $message پیام خطا.
      */
     private function log_error(string $message): void {
         if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
             error_log('SeoKar Settings Error: ' . $message);
         }
         // در اینجا می‌توان به سیستم‌های لاگ خارجی یا دیتابیس هم لاگ کرد.
     }

     /**
      * دریافت آبجکت مدیر تب‌ها (برای استفاده خارجی احتمالی).
      *
      * @return Seokar_Tab_Manager
      */
     public function get_tab_manager(): Seokar_Tab_Manager {
         return $this->tab_manager;
     }
}

// --- راه‌اندازی سیستم ---

/**
 * تابع کمکی برای دسترسی آسان به نمونه Singleton تنظیمات سئوکار.
 *
 * @return Seokar_Theme_Options_Refactored
 */
function seokar_theme_options(): Seokar_Theme_Options_Refactored {
    return Seokar_Theme_Options_Refactored::get_instance();
}

// اجرای تابع برای شروع فرآیند ثبت هوک‌ها و تنظیمات
seokar_theme_options();

// --- مثال نحوه پیاده‌سازی یک کلاس تب (مثلاً general-settings.php) ---
/*
<?php
// فایل: inc/admin/settings/general-settings.php

if (!defined('ABSPATH')) exit;

class Seokar_General_Settings implements Seokar_Settings_Tab_Interface {
    use Seokar_Settings_Validation_Trait; // برای استفاده از توابع sanitize_field

    private string $option_name = 'seokar_settings_general';

    public function get_settings_schema(): array {
        return [
            'site_logo' => [
                'type' => 'url',
                'default' => '',
                'label' => __('آدرس لوگوی سایت', 'seokar'),
                'description' => __('آدرس کامل URL لوگو را وارد کنید.', 'seokar'),
            ],
            'enable_feature_x' => [
                'type' => 'checkbox',
                'default' => false,
                'label' => __('فعال‌سازی ویژگی X', 'seokar'),
            ],
            'admin_email' => [
                'type' => 'email',
                'default' => get_option('admin_email'),
                'label' => __('ایمیل مدیر', 'seokar'),
            ],
            'items_per_page' => [
                 'type' => 'number',
                 'default' => 10,
                 'label' => __('تعداد آیتم در هر صفحه', 'seokar'),
                 'min' => 1, // ویژگی اضافی برای اعتبارسنجی
                 'max' => 100,
            ],
            'footer_text' => [
                 'type' => 'textarea',
                 'default' => sprintf(__('حق کپی © %s محفوظ است.', 'seokar'), date('Y')),
                 'label' => __('متن فوتر', 'seokar'),
                 'rows' => 4, // ویژگی اضافی برای رندر
            ]
            // ... سایر فیلدها
        ];
    }

    public function register_settings_fields(string $option_group, string $option_name): void {
        // 1. ثبت بخش (Section)
        add_settings_section(
            'seokar_general_section',           // ID بخش
            __('تنظیمات اصلی', 'seokar'),       // عنوان بخش (قابل نمایش)
            [$this, 'render_section_description'], // تابع نمایش توضیحات بخش (اختیاری)
            $option_group                       // صفحه (گروه) که این بخش در آن نمایش داده می‌شود
        );

        // 2. ثبت فیلدها در بخش
        $schema = $this->get_settings_schema();
        foreach ($schema as $field_id => $field_config) {
            add_settings_field(
                $field_id,                          // ID فیلد
                $field_config['label'] ?? $field_id, // عنوان (Label) فیلد
                [$this, 'render_field'],            // تابع نمایش کنترل فرم (input, select, etc.)
                $option_group,                      // گروه اصلی
                'seokar_general_section',           // بخشی که فیلد به آن تعلق دارد
                [ // آرگومان‌های اضافی که به تابع render_field ارسال می‌شوند
                    'label_for' => $field_id, // برای اتصال label به input
                    'field_id' => $field_id,
                    'option_name' => $this->option_name, // نام آپشن اصلی
                    'config' => $field_config, // تنظیمات کامل فیلد از اسکیما
                ]
            );
        }
    }

    public function render_section_description(): void {
        echo '<p>' . esc_html__('تنظیمات عمومی و اصلی قالب سئوکار را در اینجا پیکربندی کنید.', 'seokar') . '</p>';
    }

    public function render_field(array $args): void {
        $field_id = $args['field_id'];
        $option_name = $args['option_name'];
        $config = $args['config'];
        $type = $config['type'] ?? 'text';

        // دریافت مقدار فعلی فیلد از آپشن ذخیره شده
        // استفاده از get_setting برای دریافت مقدار با در نظر گرفتن پیش‌فرض
        $options = get_option($option_name, []);
        $value = $options[$field_id] ?? $config['default'] ?? null;


        // رندر کردن کنترل فرم بر اساس نوع فیلد
        switch ($type) {
            case 'text':
            case 'url':
            case 'email':
            case 'color': // نیاز به اسکریپت color picker دارد
            case 'number':
                printf(
                    '<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text %s" %s %s>',
                    esc_attr($type === 'color' ? 'text' : $type), // نوع input
                    esc_attr($field_id),                         // id
                    esc_attr($option_name),                      // name (آرایه)
                    esc_attr($field_id),                         // کلید در آرایه name
                    esc_attr($value),                            // مقدار فعلی
                    esc_attr($type === 'color' ? 'seokar-color-picker' : ''), // کلاس اضافی برای color picker
                    isset($config['min']) ? 'min="' . esc_attr((string)$config['min']) . '"' : '', // ویژگی min برای number
                    isset($config['max']) ? 'max="' . esc_attr((string)$config['max']) . '"' : ''  // ویژگی max برای number
                );
                break;
            case 'textarea':
                 printf(
                     '<textarea id="%s" name="%s[%s]" rows="%d" class="large-text">%s</textarea>',
                     esc_attr($field_id),
                     esc_attr($option_name),
                     esc_attr($field_id),
                     isset($config['rows']) ? (int)$config['rows'] : 5,
                     esc_textarea($value) // استفاده از esc_textarea برای امنیت
                 );
                 break;
            case 'checkbox':
                printf(
                    '<label><input type="checkbox" id="%s" name="%s[%s]" value="1" %s> %s</label>',
                    esc_attr($field_id),
                    esc_attr($option_name),
                    esc_attr($field_id),
                    checked(true, (bool)$value, false), // تابع checked وردپرس
                    isset($config['checkbox_label']) ? esc_html($config['checkbox_label']) : '' // لیبل کنار چک‌باکس (اختیاری)
                );
                break;
            case 'select':
                 if (!empty($config['options']) && is_array($config['options'])) {
                     printf('<select id="%s" name="%s[%s]">', esc_attr($field_id), esc_attr($option_name), esc_attr($field_id));
                     foreach ($config['options'] as $option_value => $option_label) {
                         printf(
                             '<option value="%s" %s>%s</option>',
                             esc_attr($option_value),
                             selected($value, $option_value, false), // تابع selected وردپرس
                             esc_html($option_label)
                         );
                     }
                     echo '</select>';
                 }
                 break;
            // ... سایر انواع فیلدها (radio, multiselect, image upload, etc.)
        }

        // نمایش توضیحات فیلد (اختیاری)
        if (!empty($config['description'])) {
            printf('<p class="description">%s</p>', wp_kses_post($config['description'])); // اجازه دادن به HTML محدود در توضیحات
        }
    }

    public function render_content(string $option_group): void {
         // در اینجا می‌توانیم چیدمان دلخواه خود را با استفاده از do_settings_sections پیاده کنیم
         // یا مستقیماً HTML بنویسیم و فیلدها را با get_settings_fields فراخوانی کنیم.
         // استفاده از do_settings_sections ساده‌تر است:
         echo '<h2>' . esc_html__('تنظیمات عمومی', 'seokar') . '</h2>';
         do_settings_sections($option_group); // این تابع بخش‌ها و فیلدهای ثبت شده برای این گروه را نمایش می‌دهد
    }


    public function sanitize(?array $input): array {
        if (is_null($input)) {
            return [];
        }
        $schema = $this->get_settings_schema();
        // استفاده از trait برای پاکسازی استاندارد
        return $this->sanitize_settings($input, $schema);
    }
}
*/
