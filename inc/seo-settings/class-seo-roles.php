<?php
/**
 * مدیریت دسترسی‌های سئو برای نقش‌های کاربری
 * 
 * @package    SeoKar
 * @subpackage Roles
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_Roles {

    /**
     * @var array قابلیت‌های سئو
     */
    private $capabilities = [
        'seokar_basic' => 'مدیریت سئو پایه',
        'seokar_advanced' => 'مدیریت سئو پیشرفته',
        'seokar_tools' => 'دسترسی به ابزارهای سئو',
        'seokar_settings' => 'تغییر تنظیمات سئو'
    ];

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // افزودن قابلیت‌ها به نقش‌ها هنگام فعال‌سازی
        register_activation_hook(__FILE__, [$this, 'add_seo_capabilities']);
        
        // حذف قابلیت‌ها هنگام غیرفعال‌سازی
        register_deactivation_hook(__FILE__, [$this, 'remove_seo_capabilities']);
        
        // محدود کردن دسترسی به منوها
        add_action('admin_init', [$this, 'restrict_admin_access']);
        
        // محدود کردن دسترسی به متا باکس‌ها
        add_filter('seokar_meta_box_capability', [$this, 'meta_box_capability']);
    }

    /**
     * افزودن قابلیت‌های سئو به نقش‌ها
     */
    public function add_seo_capabilities() {
        $roles = $this->get_editable_roles();
        
        // مدیران دسترسی کامل دارند
        $admin_role = get_role('administrator');
        foreach ($this->capabilities as $cap => $name) {
            $admin_role->add_cap($cap);
        }
        
        // ویرایشگران دسترسی پایه دارند
        $editor_role = get_role('editor');
        $editor_role->add_cap('seokar_basic');
        $editor_role->add_cap('seokar_tools');
        
        // نویسندگان فقط دسترسی پایه دارند
        $author_role = get_role('author');
        $author_role->add_cap('seokar_basic');
    }

    /**
     * حذف قابلیت‌های سئو از نقش‌ها
     */
    public function remove_seo_capabilities() {
        $roles = $this->get_editable_roles();
        
        foreach ($roles as $role_name => $role_info) {
            $role = get_role($role_name);
            foreach ($this->capabilities as $cap => $name) {
                $role->remove_cap($cap);
            }
        }
    }

    /**
     * دریافت نقش‌های قابل ویرایش
     *
     * @return array
     */
    private function get_editable_roles() {
        $roles = wp_roles()->roles;
        unset($roles['subscriber']);
        unset($roles['contributor']);
        return $roles;
    }

    /**
     * محدود کردن دسترسی به بخش مدیریت
     */
    public function restrict_admin_access() {
        // اگر کاربر به تنظیمات سئو دسترسی ندارد
        if (current_user_can('seokar_settings')) {
            return;
        }
        
        global $pagenow;
        $restricted_pages = [
            'admin.php?page=seokar-settings',
            'admin.php?page=seokar-tools',
            'admin.php?page=seokar-redirects'
        ];
        
        foreach ($restricted_pages as $page) {
            if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === $page) {
                wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'seokar'));
            }
        }
    }

    /**
     * تعیین قابلیت مورد نیاز برای متا باکس‌ها
     *
     * @param string $capability
     * @return string
     */
    public function meta_box_capability($capability) {
        if (current_user_can('seokar_advanced')) {
            return 'edit_posts';
        }
        return 'seokar_basic';
    }

    /**
     * بررسی دسترسی کاربر به قابلیت سئو
     *
     * @param string $capability
     * @return bool
     */
    public function current_user_can($capability) {
        if (!array_key_exists($capability, $this->capabilities)) {
            return false;
        }
        
        return current_user_can($capability);
    }

    /**
     * افزودن صفحه مدیریت نقش‌های سئو
     */
    public function add_roles_admin_page() {
        add_submenu_page(
            'seokar-settings',
            __('نقش‌های سئو', 'seokar'),
            __('نقش‌ها', 'seokar'),
            'seokar_settings',
            'seokar-roles',
            [$this, 'render_roles_admin_page']
        );
    }

    /**
     * نمایش صفحه مدیریت نقش‌ها
     */
    public function render_roles_admin_page() {
        if (!current_user_can('seokar_settings')) {
            wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'seokar'));
        }
        
        $roles = $this->get_editable_roles();
        ?>
        <div class="wrap">
            <h1><?php _e('مدیریت نقش‌های سئو', 'seokar'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('seokar_roles_settings'); ?>
                <?php do_settings_sections('seokar-roles'); ?>
                
                <table class="form-table">
                    <thead>
                        <tr>
                            <th><?php _e('نقش', 'seokar'); ?></th>
                            <?php foreach ($this->capabilities as $cap => $name): ?>
                                <th><?php echo esc_html($name); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role_name => $role_info): ?>
                            <?php $role = get_role($role_name); ?>
                            <tr>
                                <td><?php echo esc_html($role_info['name']); ?></td>
                                <?php foreach ($this->capabilities as $cap => $name): ?>
                                    <td>
                                        <input type="checkbox" 
                                               name="seokar_roles[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]" 
                                               value="1" <?php checked($role->has_cap($cap)); ?> 
                                               <?php disabled($role_name === 'administrator'); ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php submit_button(__('ذخیره تغییرات', 'seokar')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * ذخیره تنظیمات نقش‌ها
     */
    public function save_role_settings() {
        if (!current_user_can('seokar_settings') || !isset($_POST['seokar_roles'])) {
            return;
        }
        
        $roles_settings = $_POST['seokar_roles'];
        $roles = $this->get_editable_roles();
        
        foreach ($roles as $role_name => $role_info) {
            $role = get_role($role_name);
            
            foreach ($this->capabilities as $cap => $name) {
                if (isset($roles_settings[$role_name][$cap]) {
                    $role->add_cap($cap);
                } else {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}
