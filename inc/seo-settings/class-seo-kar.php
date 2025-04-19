<?php
/**
 * SEOKAR for WordPress Themes - Core Loader
 * 
 * @package    SeoKar
 * @subpackage Core
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class SEOKAR {
    
    /**
     * Instance of this class
     *
     * @var null|object
     */
    private static $instance = null;
    
    /**
     * Array of loaded modules
     *
     * @var array
     */
    private $modules = [];
    
    /**
     * Core settings
     *
     * @var array
     */
    private $settings = [
        'version' => '3.0.0',
        'db_version' => '1.0',
        'min_php' => '7.4',
        'min_wp' => '5.6',
    ];
    
    /**
     * Database versions and migration handlers
     *
     * @var array
     */
    private $db_migrations = [
        '1.0' => 'initial_version',
        // Add future versions here
    ];
    
    /**
     * Singleton instance
     *
     * @return SEOKAR
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->check_requirements();
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Check system requirements
     */
    private function check_requirements() {
        if (version_compare(PHP_VERSION, $this->settings['min_php'], '<')) {
            add_action('admin_notices', [$this, 'php_version_notice']);
            return;
        }
        
        if (version_compare(get_bloginfo('version'), $this->settings['min_wp'], '<')) {
            add_action('admin_notices', [$this, 'wp_version_notice']);
            return;
        }
    }
    
    /**
     * Define constants
     */
    private function define_constants() {
        define('SEOKAR_VERSION', $this->settings['version']);
        define('SEOKAR_PATH', get_template_directory() . '/inc/seo-settings');
        define('SEOKAR_URL', get_template_directory_uri() . '/inc/seo-settings');
        define('SEOKAR_DB_VERSION', $this->settings['db_version']);
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core files
        require_once SEOKAR_PATH . '/helpers/class-seo-utils.php';
        require_once SEOKAR_PATH . '/helpers/class-seo-sanitize.php';
        require_once SEOKAR_PATH . '/helpers/class-seo-validators.php';
        
        // Interfaces
        require_once SEOKAR_PATH . '/interfaces/interface-seo-module.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Theme activation/deactivation hooks
        add_action('after_switch_theme', [$this, 'activate']);
        add_action('switch_theme', [$this, 'deactivate']);
        
        // Load textdomain
        add_action('after_setup_theme', [$this, 'load_textdomain']);
        
        // Load modules
        add_action('init', [$this, 'load_modules']);
        
        // Handle database upgrades
        add_action('init', [$this, 'maybe_upgrade_db']);
    }
    
    /**
     * Theme activation
     */
    public function activate() {
        // Create database tables
        require_once SEOKAR_PATH . '/database/class-seo-redirects-db.php';
        SEOKAR_Redirects_DB::create_table();
        
        // Set default options
        $options = get_option('seokar_options', []);
        
        if (empty($options)) {
            $options = [
                'version' => SEOKAR_VERSION,
                'db_version' => '1.0', // Start with initial version
                'first_install' => current_time('mysql'),
            ];
            update_option('seokar_options', $options);
        }
        
        // Check if we need to run any migrations
        $this->maybe_upgrade_db();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('seokar_daily_tasks')) {
            wp_schedule_event(time(), 'daily', 'seokar_daily_tasks');
        }
    }
    
    /**
     * Theme deactivation
     */
    public function deactivate() {
        // Clear cron jobs
        wp_clear_scheduled_hook('seokar_daily_tasks');
    }
    
    /**
     * Check and run database upgrades if needed
     */
    public function maybe_upgrade_db() {
        $options = get_option('seokar_options', []);
        $current_db_version = $options['db_version'] ?? '0';
        
        if (version_compare($current_db_version, SEOKAR_DB_VERSION, '<')) {
            $this->run_db_upgrades($current_db_version);
        }
    }
    
    /**
     * Run database upgrades
     *
     * @param string $current_version
     */
    private function run_db_upgrades($current_version) {
        $options = get_option('seokar_options', []);
        
        foreach ($this->db_migrations as $version => $upgrade_method) {
            if (version_compare($current_version, $version, '<')) {
                if (method_exists($this, $upgrade_method)) {
                    $this->$upgrade_method($current_version);
                }
                
                // Update the version after each successful migration
                $options['db_version'] = $version;
                update_option('seokar_options', $options);
                $current_version = $version;
            }
        }
    }
    
    /**
     * Initial database version setup
     */
    private function initial_version() {
        // Initial database setup if needed
        require_once SEOKAR_PATH . '/database/class-seo-redirects-db.php';
        SEOKAR_Redirects_DB::create_table();
    }
    
    /**
     * Load textdomain
     */
    public function load_textdomain() {
        load_theme_textdomain('seokar', get_template_directory() . '/languages');
    }
    
    /**
     * Load active modules
     */
    public function load_modules() {
        $default_modules = [
            'core' => 'SEOKAR_Core',
            'social' => 'SEOKAR_Social',
            'schema' => 'SEOKAR_Schema',
            'settings' => 'SEOKAR_Settings',
        ];
        
        foreach ($default_modules as $module => $class) {
            $this->load_module($module);
        }
    }
    
    /**
     * Load specific module
     *
     * @param string $module
     * @return bool
     */
    public function load_module($module) {
        if (isset($this->modules[$module])) {
            return true;
        }
        
        $module_file = SEOKAR_PATH . "/modules/class-seo-{$module}.php";
        $class_name = "SEOKAR_" . ucfirst($module);
        
        if (!file_exists($module_file)) {
            error_log("SEOKAR: Module file '{$module_file}' not found.");
            return false;
        }
        
        require_once $module_file;
        
        if (!class_exists($class_name)) {
            error_log("SEOKAR: Class '{$class_name}' not found in module '{$module}'.");
            return false;
        }
        
        try {
            $this->modules[$module] = new $class_name($this);
            return true;
        } catch (Exception $e) {
            error_log("SEOKAR: Error loading module '{$module}': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get loaded module
     *
     * @param string $module
     * @return object|false
     */
    public function get_module($module) {
        return $this->modules[$module] ?? false;
    }
    
    /**
     * Display PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('SEO Kar requires PHP version %s or later. Please update your PHP version.', 'seokar'),
            $this->settings['min_php']
        );
        echo '</p></div>';
    }
    
    /**
     * Display WordPress version notice
     */
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('SEO Kar requires WordPress version %s or later. Please update WordPress.', 'seokar'),
            $this->settings['min_wp']
        );
        echo '</p></div>';
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'seokar'), '3.0');
    }
}

/**
 * Main instance of SEOKAR
 *
 * @return SEOKAR
 */
function SEOKAR() {
    return SEOKAR::instance();
}

// Initialize after theme setup
add_action('after_setup_theme', 'SEOKAR');
