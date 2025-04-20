<?php
/**
 * Plugin Name: Backup Theme Settings - SEO Pro
 * Description: ابزار حرفه‌ای پشتیبان‌گیری و بازیابی تنظیمات قالب وردپرس (بهینه‌شده برای سئو)
 * Version: 1.2.0
 * Author: سئوکار حرفه‌ای
 * Author URI: https://seokar.pro
 * License: GPLv2 or later
 * Text Domain: seokar-theme-backup
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKar_Theme_Backup {
    
    private $backup_dir;
    private $backup_url;
    private $max_backups = 5;
    private $theme_name;
    
    public function __construct() {
        $this->theme_name = get_option('stylesheet');
        $upload_dir = wp_upload_dir();
        $this->backup_dir = trailingslashit($upload_dir['basedir']) . 'seokar-theme-backups/';
        $this->backup_url = trailingslashit($upload_dir['baseurl']) . 'seokar-theme-backups/';
        
        $this->init_hooks();
        $this->check_backup_dir();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_seokar_backup_theme_settings', array($this, 'ajax_backup_settings'));
        add_action('wp_ajax_seokar_restore_theme_settings', array($this, 'ajax_restore_settings'));
        add_action('wp_ajax_seokar_delete_backup', array($this, 'ajax_delete_backup'));
    }
    
    private function check_backup_dir() {
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            $this->protect_directory();
        }
    }
    
    private function protect_directory() {
        $htaccess = $this->backup_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'deny from all');
        }
        
        $index = $this->backup_dir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('appearance_page_seokar-theme-backup' !== $hook) {
            return;
        }
        
        wp_enqueue_style('seokar-backup-css', plugins_url('assets/css/admin.css', __FILE__), array(), '1.2.0');
        wp_enqueue_script('seokar-backup-js', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), '1.2.0', true);
        
        wp_localize_script('seokar-backup-js', 'seokarBackup', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seokar_backup_nonce'),
            'confirm_restore' => __('آیا مطمئن هستید می‌خواهید این پشتیبان را بازیابی کنید؟ تمام تنظیمات فعلی جایگزین خواهند شد.', 'seokar-theme-backup'),
            'confirm_delete' => __('آیا مطمئن هستید می‌خواهید این پشتیبان را حذف کنید؟ این عمل برگشت‌ناپذیر است.', 'seokar-theme-backup'),
            'backup_success' => __('پشتیبان با موفقیت ایجاد شد.', 'seokar-theme-backup'),
            'backup_failed' => __('خطا در ایجاد پشتیبان.', 'seokar-theme-backup'),
            'restore_success' => __('تنظیمات با موفقیت بازیابی شدند.', 'seokar-theme-backup'),
            'restore_failed' => __('خطا در بازیابی تنظیمات.', 'seokar-theme-backup'),
            'delete_success' => __('پشتیبان با موفقیت حذف شد.', 'seokar-theme-backup'),
            'delete_failed' => __('خطا در حذف پشتیبان.', 'seokar-theme-backup')
        ));
    }
    
    public function add_admin_menu() {
        add_theme_page(
            __('پشتیبان تنظیمات قالب', 'seokar-theme-backup'),
            __('پشتیبان سئو', 'seokar-theme-backup'),
            'manage_options',
            'seokar-theme-backup',
            array($this, 'render_admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('seokar_backup_settings', 'seokar_backup_options');
    }
    
    public function render_admin_page() {
        $backups = $this->get_backup_list();
        ?>
        <div class="wrap seokar-backup-wrap">
            <h1><?php _e('پشتیبان‌گیری و بازیابی تنظیمات قالب', 'seokar-theme-backup'); ?></h1>
            <p><?php _e('از این بخش می‌توانید از تنظیمات فعلی قالب خود پشتیبان بگیرید یا تنظیمات قبلی را بازیابی کنید.', 'seokar-theme-backup'); ?></p>
            
            <div class="seokar-backup-card">
                <h2><?php _e('ایجاد پشتیبان جدید', 'seokar-theme-backup'); ?></h2>
                <form id="seokar-create-backup">
                    <div class="form-group">
                        <label for="backup-name"><?php _e('نام پشتیبان (اختیاری)', 'seokar-theme-backup'); ?></label>
                        <input type="text" id="backup-name" class="regular-text" placeholder="<?php _e('مثلا: تنظیمات قبل از تغییرات سئو', 'seokar-theme-backup'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="backup-desc"><?php _e('توضیحات (اختیاری)', 'seokar-theme-backup'); ?></label>
                        <textarea id="backup-desc" rows="3" class="large-text" placeholder="<?php _e('توضیحات درباره این پشتیبان...', 'seokar-theme-backup'); ?>"></textarea>
                    </div>
                    <button type="submit" class="button button-primary">
                        <?php _e('ایجاد پشتیبان', 'seokar-theme-backup'); ?>
                    </button>
                </form>
            </div>
            
            <div class="seokar-backup-card">
                <h2><?php _e('پشتیبان‌های موجود', 'seokar-theme-backup'); ?></h2>
                <?php if (empty($backups)) : ?>
                    <p><?php _e('هیچ پشتیبانی یافت نشد.', 'seokar-theme-backup'); ?></p>
                <?php else : ?>
                    <div class="seokar-backup-list">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('نام فایل', 'seokar-theme-backup'); ?></th>
                                    <th><?php _e('تاریخ ایجاد', 'seokar-theme-backup'); ?></th>
                                    <th><?php _e('اندازه', 'seokar-theme-backup'); ?></th>
                                    <th><?php _e('توضیحات', 'seokar-theme-backup'); ?></th>
                                    <th><?php _e('عملیات', 'seokar-theme-backup'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup) : ?>
                                    <tr>
                                        <td><?php echo esc_html($backup['name']); ?></td>
                                        <td><?php echo date_i18n('Y/m/d H:i:s', $backup['time']); ?></td>
                                        <td><?php echo size_format($backup['size']); ?></td>
                                        <td><?php echo esc_html($backup['desc']); ?></td>
                                        <td>
                                            <a href="#" class="button button-secondary seokar-restore-backup" data-file="<?php echo esc_attr($backup['file']); ?>">
                                                <?php _e('بازیابی', 'seokar-theme-backup'); ?>
                                            </a>
                                            <a href="<?php echo esc_url($backup['download']); ?>" class="button button-secondary" download>
                                                <?php _e('دانلود', 'seokar-theme-backup'); ?>
                                            </a>
                                            <a href="#" class="button button-danger seokar-delete-backup" data-file="<?php echo esc_attr($backup['file']); ?>">
                                                <?php _e('حذف', 'seokar-theme-backup'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="seokar-backup-card">
                <h2><?php _e('بازیابی از فایل', 'seokar-theme-backup'); ?></h2>
                <form id="seokar-upload-backup" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="backup-file"><?php _e('فایل پشتیبان', 'seokar-theme-backup'); ?></label>
                        <input type="file" id="backup-file" accept=".json,.backup">
                    </div>
                    <button type="submit" class="button button-primary">
                        <?php _e('آپلود و بازیابی', 'seokar-theme-backup'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
    
    private function get_backup_list() {
        $backups = array();
        
        if (!file_exists($this->backup_dir)) {
            return $backups;
        }
        
        $files = scandir($this->backup_dir, SCANDIR_SORT_DESCENDING);
        
        foreach ($files as $file) {
            if (preg_match('/^' . preg_quote($this->theme_name, '/') . '_\d{14}(?:_(.*))?\.backup$/', $file, $matches)) {
                $file_path = $this->backup_dir . $file;
                $backup_data = $this->read_backup_file($file_path);
                
                $backups[] = array(
                    'file' => $file,
                    'name' => isset($backup_data['meta']['name']) ? $backup_data['meta']['name'] : __('پشتیبان بدون نام', 'seokar-theme-backup'),
                    'desc' => isset($backup_data['meta']['desc']) ? $backup_data['meta']['desc'] : '',
                    'time' => filemtime($file_path),
                    'size' => filesize($file_path),
                    'download' => $this->backup_url . $file
                );
            }
        }
        
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return $b['time'] - $a['time'];
        });
        
        // Limit number of backups
        if (count($backups) > $this->max_backups) {
            $old_backups = array_slice($backups, $this->max_backups);
            foreach ($old_backups as $old_backup) {
                $this->delete_backup($old_backup['file']);
            }
            $backups = array_slice($backups, 0, $this->max_backups);
        }
        
        return $backups;
    }
    
    private function read_backup_file($file_path) {
        $content = file_get_contents($file_path);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data;
    }
    
    public function ajax_backup_settings() {
        check_ajax_referer('seokar_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('شما مجوز انجام این عمل را ندارید.', 'seokar-theme-backup'), 403);
        }
        
        $backup_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $backup_desc = isset($_POST['desc']) ? sanitize_textarea_field($_POST['desc']) : '';
        
        $timestamp = current_time('YmdHis');
        $filename = $this->theme_name . '_' . $timestamp;
        
        if (!empty($backup_name)) {
            $filename .= '_' . sanitize_title($backup_name);
        }
        
        $filename .= '.backup';
        $filepath = $this->backup_dir . $filename;
        
        // Get all theme mods
        $theme_mods = get_theme_mods();
        
        // Get theme options (if any)
        $theme_options = array();
        $option_name = 'theme_mods_' . $this->theme_name;
        $theme_options[$option_name] = get_option($option_name);
        
        // Get customizer settings
        $customizer_settings = get_option('customizer_backup_' . $this->theme_name, array());
        
        // Prepare backup data
        $backup_data = array(
            'meta' => array(
                'name' => $backup_name,
                'desc' => $backup_desc,
                'date' => current_time('mysql'),
                'theme' => $this->theme_name,
                'version' => wp_get_theme()->get('Version'),
                'wp_version' => get_bloginfo('version'),
                'url' => home_url()
            ),
            'theme_mods' => $theme_mods,
            'theme_options' => $theme_options,
            'customizer_settings' => $customizer_settings
        );
        
        $result = file_put_contents($filepath, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            wp_send_json_error(__('خطا در ذخیره فایل پشتیبان.', 'seokar-theme-backup'));
        }
        
        // Update backup list
        $backups = $this->get_backup_list();
        
        wp_send_json_success(array(
            'message' => __('پشتیبان با موفقیت ایجاد شد.', 'seokar-theme-backup'),
            'backups' => $backups
        ));
    }
    
    public function ajax_restore_settings() {
        check_ajax_referer('seokar_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('شما مجوز انجام این عمل را ندارید.', 'seokar-theme-backup'), 403);
        }
        
        if (isset($_POST['file'])) {
            // Restore from existing backup
            $file = sanitize_file_name($_POST['file']);
            $filepath = $this->backup_dir . $file;
            
            if (!file_exists($filepath)) {
                wp_send_json_error(__('فایل پشتیبان یافت نشد.', 'seokar-theme-backup'));
            }
            
            $backup_data = $this->read_backup_file($filepath);
            
            if (!$backup_data) {
                wp_send_json_error(__('فایل پشتیبان معتبر نیست.', 'seokar-theme-backup'));
            }
        } elseif (isset($_FILES['backup_file'])) {
            // Restore from uploaded file
            if (!empty($_FILES['backup_file']['error'])) {
                wp_send_json_error(__('خطا در آپلود فایل.', 'seokar-theme-backup'));
            }
            
            if ($_FILES['backup_file']['type'] !== 'application/json' && 
                $_FILES['backup_file']['type'] !== 'text/plain') {
                wp_send_json_error(__('نوع فایل نامعتبر است. فقط فایل‌های پشتیبان معتبر قابل قبول هستند.', 'seokar-theme-backup'));
            }
            
            $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);
            $backup_data = json_decode($file_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(__('فایل پشتیبان معتبر نیست.', 'seokar-theme-backup'));
            }
        } else {
            wp_send_json_error(__('هیچ فایل پشتیبانی ارائه نشده است.', 'seokar-theme-backup'));
        }
        
        // Verify backup data
        if (!isset($backup_data['theme_mods']) || !isset($backup_data['theme_options'])) {
            wp_send_json_error(__('داده‌های پشتیبان ناقص هستند.', 'seokar-theme-backup'));
        }
        
        // Create a backup before restoring (safety measure)
        $this->ajax_backup_settings();
        
        // Restore theme mods
        $theme_mods = $backup_data['theme_mods'];
        if (is_array($theme_mods)) {
            foreach ($theme_mods as $key => $value) {
                set_theme_mod($key, $value);
            }
        }
        
        // Restore theme options
        $theme_options = $backup_data['theme_options'];
        if (is_array($theme_options)) {
            foreach ($theme_options as $option_name => $option_value) {
                update_option($option_name, $option_value);
            }
        }
        
        // Restore customizer settings if available
        if (isset($backup_data['customizer_settings']) && is_array($backup_data['customizer_settings'])) {
            update_option('customizer_backup_' . $this->theme_name, $backup_data['customizer_settings']);
        }
        
        wp_send_json_success(__('تنظیمات قالب با موفقیت بازیابی شدند.', 'seokar-theme-backup'));
    }
    
    public function ajax_delete_backup() {
        check_ajax_referer('seokar_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('شما مجوز انجام این عمل را ندارید.', 'seokar-theme-backup'), 403);
        }
        
        if (!isset($_POST['file'])) {
            wp_send_json_error(__('فایل پشتیبان مشخص نشده است.', 'seokar-theme-backup'));
        }
        
        $file = sanitize_file_name($_POST['file']);
        $success = $this->delete_backup($file);
        
        if ($success) {
            $backups = $this->get_backup_list();
            wp_send_json_success(array(
                'message' => __('پشتیبان با موفقیت حذف شد.', 'seokar-theme-backup'),
                'backups' => $backups
            ));
        } else {
            wp_send_json_error(__('خطا در حذف فایل پشتیبان.', 'seokar-theme-backup'));
        }
    }
    
    private function delete_backup($filename) {
        $filepath = $this->backup_dir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
}

new SEOKar_Theme_Backup();
