<?php
/**
 * SEO Framework for WordPress Themes
 * 
 * @package    SeoKar
 * @subpackage Admin
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SeoKar_Framework {

    /**
     * The single instance of the class
     *
     * @var SeoKar_Framework
     */
    private static $_instance = null;

    /**
     * Main instance
     *
     * @return SeoKar_Framework
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        define('SEOKAR_VERSION', '3.0.0');
        define('SEOKAR_PATH', dirname(__FILE__));
        define('SEOKAR_INCLUDES', SEOKAR_PATH . '/includes');
        define('SEOKAR_ADMIN', SEOKAR_PATH . '/admin');
        define('SEOKAR_ASSETS_URL', get_template_directory_uri() . '/inc/seo-framework/assets');
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core functionality
        require_once SEOKAR_INCLUDES . '/class-seokar-core.php';
        require_once SEOKAR_INCLUDES . '/class-seokar-helpers.php';
        
        // Admin functionality
        if (is_admin()) {
            require_once SEOKAR_ADMIN . '/class-seokar-admin.php';
            require_once SEOKAR_ADMIN . '/class-seokar-settings.php';
            require_once SEOKAR_ADMIN . '/class-seokar-meta-boxes.php';
        }
        
        // Frontend functionality
        require_once SEOKAR_INCLUDES . '/class-seokar-frontend.php';
        require_once SEOKAR_INCLUDES . '/class-seokar-schema.php';
        require_once SEOKAR_INCLUDES . '/class-seokar-sitemap.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Load textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('seokar', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize classes
        SeoKar_Core::instance();
        
        if (is_admin()) {
            SeoKar_Admin::instance();
            SeoKar_Settings::instance();
            SeoKar_Meta_Boxes::instance();
        }
        
        SeoKar_Frontend::instance();
        SeoKar_Schema::instance();
        SeoKar_Sitemap::instance();
    }

    /**
     * Activation hook
     */
    public function activate() {
        // Create required database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create required database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}seokar_redirects (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url_from varchar(255) NOT NULL,
            url_to varchar(255) NOT NULL,
            type smallint(2) NOT NULL DEFAULT 301,
            status varchar(20) NOT NULL DEFAULT 'active',
            count bigint(20) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY url_from (url_from),
            KEY status (status)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'seokar_enable_onpage' => 'yes',
            'seokar_enable_schema' => 'yes',
            'seokar_enable_sitemap' => 'yes',
            'seokar_title_template' => '{title} | {sitename}',
            'seokar_description_template' => '{excerpt}',
            'seokar_social_image' => '',
            'seokar_webmaster_tools' => array(),
            'seokar_advanced' => array(
                'noindex_pagination' => 'yes',
                'noindex_search' => 'yes',
                'nofollow_external' => 'no'
            )
        );
        
        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                update_option($key, $value);
            }
        }
    }
}

/**
 * Initialize the SEO framework
 */
function seokar_init() {
    return SeoKar_Framework::instance();
}

// Get it started
seokar_init();

<?php
/**
 * SEO Framework Admin Class
 * 
 * Handles all admin-related functionality including settings pages, meta boxes, and admin UI
 */

class SeoKar_Admin {

    private static $_instance = null;
    private $settings_api;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->settings_api = new SeoKar_Settings_API();

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        $this->settings_api->init();
        
        // Register settings sections
        $this->register_general_settings();
        $this->register_onpage_settings();
        $this->register_schema_settings();
        $this->register_advanced_settings();
        $this->register_tools_settings();
    }

    /**
     * Register admin menu items
     */
    public function admin_menu() {
        $icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>');
        
        add_menu_page(
            __('SEO Settings', 'seokar'),
            __('SEO', 'seokar'),
            'manage_options',
            'seokar-settings',
            array($this, 'settings_page'),
            $icon,
            80
        );
        
        add_submenu_page(
            'seokar-settings',
            __('General Settings', 'seokar'),
            __('General', 'seokar'),
            'manage_options',
            'seokar-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'seokar-settings',
            __('On-Page SEO', 'seokar'),
            __('On-Page', 'seokar'),
            'manage_options',
            'seokar-onpage',
            array($this, 'onpage_page')
        );
        
        add_submenu_page(
            'seokar-settings',
            __('Schema Markup', 'seokar'),
            __('Schema', 'seokar'),
            'manage_options',
            'seokar-schema',
            array($this, 'schema_page')
        );
        
        add_submenu_page(
            'seokar-settings',
            __('Advanced SEO', 'seokar'),
            __('Advanced', 'seokar'),
            'manage_options',
            'seokar-advanced',
            array($this, 'advanced_page')
        );
        
        add_submenu_page(
            'seokar-settings',
            __('SEO Tools', 'seokar'),
            __('Tools', 'seokar'),
            'manage_options',
            'seokar-tools',
            array($this, 'tools_page')
        );
    }

    /**
     * Register general settings section
     */
    private function register_general_settings() {
        $section = 'seokar_general';
        
        $this->settings_api->add_section(array(
            'id' => $section,
            'title' => __('General SEO Settings', 'seokar'),
            'desc' => __('Basic settings for your site SEO', 'seokar'),
            'page' => 'seokar-settings'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'separator',
            'label' => __('Title Separator', 'seokar'),
            'desc' => __('Character to separate site name from page title', 'seokar'),
            'type' => 'text',
            'default' => '|',
            'size' => 5
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'title_template',
            'label' => __('Title Template', 'seokar'),
            'desc' => __('Template for page titles. Available tags: {title}, {sitename}, {sep}, {description}', 'seokar'),
            'type' => 'text',
            'default' => '{title} {sep} {sitename}'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'description_template',
            'label' => __('Meta Description Template', 'seokar'),
            'desc' => __('Template for meta descriptions. Available tags: {excerpt}, {title}, {sitename}, {sep}', 'seokar'),
            'type' => 'textarea',
            'default' => '{excerpt}'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'social_image',
            'label' => __('Default Social Image', 'seokar'),
            'desc' => __('Default image used when sharing on social media', 'seokar'),
            'type' => 'file',
            'options' => array(
                'button_label' => __('Upload Image', 'seokar')
            )
        ));
    }

    /**
     * Register on-page SEO settings
     */
    private function register_onpage_settings() {
        $section = 'seokar_onpage';
        
        $this->settings_api->add_section(array(
            'id' => $section,
            'title' => __('On-Page SEO', 'seokar'),
            'desc' => __('Settings for on-page content optimization', 'seokar'),
            'page' => 'seokar-onpage'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'keyword_analysis',
            'label' => __('Keyword Analysis', 'seokar'),
            'desc' => __('Enable keyword analysis for posts and pages', 'seokar'),
            'type' => 'checkbox',
            'default' => 'on'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'readability_analysis',
            'label' => __('Readability Analysis', 'seokar'),
            'desc' => __('Enable readability analysis (Flesch-Kincaid)', 'seokar'),
            'type' => 'checkbox',
            'default' => 'on'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'link_suggestions',
            'label' => __('Internal Link Suggestions', 'seokar'),
            'desc' => __('Suggest internal links based on content', 'seokar'),
            'type' => 'checkbox',
            'default' => 'on'
        ));
        
        $this->settings_api->add_field($section, array(
            'name' => 'content_analysis',
            'label' => __('Content Analysis Rules', 'seokar'),
            'desc' => __('Configure what to check in content analysis', 'seokar'),
            'type' => 'multicheck',
            'options' => array(
                'heading_structure' => __('Heading structure (H1-H6)', 'seokar'),
                'paragraph_length' => __('Paragraph length', 'seokar'),
                'image_alt' => __('Image alt attributes', 'seokar'),
                'internal_links' => __('Internal links', 'seokar'),
                'external_links' => __('External links', 'seokar'),
                'keyword_density' => __('Keyword density', 'seokar'),
                'meta_length' => __('Meta title/description length', 'seokar')
            ),
            'default' => array(
                'heading_structure' => 'heading_structure',
                'image_alt' => 'image_alt',
                'internal_links' => 'internal_links',
                'meta_length' => 'meta_length'
            )
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'seokar') === false) {
            return;
        }
        
        wp_enqueue_style('seokar-admin', SEOKAR_ASSETS_URL . '/css/admin.css', array(), SEOKAR_VERSION);
        wp_enqueue_script('seokar-admin', SEOKAR_ASSETS_URL . '/js/admin.js', array('jquery', 'wp-util'), SEOKAR_VERSION, true);
        
        wp_localize_script('seokar-admin', 'seokar', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seokar_nonce'),
            'i18n' => array(
                'analyzing' => __('Analyzing...', 'seokar'),
                'error' => __('Error occurred', 'seokar')
            )
        ));
    }

    /**
     * Render settings page
     */
    public function settings_page() {
        echo '<div class="wrap seokar-settings-wrap">';
        echo '<h1>' . esc_html__('SEO Settings', 'seokar') . '</h1>';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>';
    }

    /**
     * Render on-page SEO page
     */
    public function onpage_page() {
        echo '<div class="wrap seokar-settings-wrap">';
        echo '<h1>' . esc_html__('On-Page SEO Settings', 'seokar') . '</h1>';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>';
    }

    /**
     * Render schema markup page
     */
    public function schema_page() {
        echo '<div class="wrap seokar-settings-wrap">';
        echo '<h1>' . esc_html__('Schema Markup Settings', 'seokar') . '</h1>';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>';
    }

    /**
     * Render advanced SEO page
     */
    public function advanced_page() {
        echo '<div class="wrap seokar-settings-wrap">';
        echo '<h1>' . esc_html__('Advanced SEO Settings', 'seokar') . '</h1>';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>';
    }

    /**
     * Render tools page
     */
    public function tools_page() {
        echo '<div class="wrap seokar-settings-wrap">';
        echo '<h1>' . esc_html__('SEO Tools', 'seokar') . '</h1>';
        
        echo '<div class="seokar-tools-grid">';
        
        // Redirect Manager
        echo '<div class="seokar-tool-card">';
        echo '<h2>' . esc_html__('Redirect Manager', 'seokar') . '</h2>';
        echo '<p>' . esc_html__('Manage 301, 302 and other redirects', 'seokar') . '</p>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=seokar-tools&tool=redirects')) . '" class="button button-primary">' . esc_html__('Manage Redirects', 'seokar') . '</a>';
        echo '</div>';
        
        // Broken Link Checker
        echo '<div class="seokar-tool-card">';
        echo '<h2>' . esc_html__('Broken Link Checker', 'seokar') . '</h2>';
        echo '<p>' . esc_html__('Find and fix broken links on your site', 'seokar') . '</p>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=seokar-tools&tool=broken-links')) . '" class="button button-primary">' . esc_html__('Scan Now', 'seokar') . '</a>';
        echo '</div>';
        
        // SEO Health Check
        echo '<div class="seokar-tool-card">';
        echo '<h2>' . esc_html__('SEO Health Check', 'seokar') . '</h2>';
        echo '<p>' . esc_html__('Run a complete SEO audit of your site', 'seokar') . '</p>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=seokar-tools&tool=health-check')) . '" class="button button-primary">' . esc_html__('Run Audit', 'seokar') . '</a>';
        echo '</div>';
        
        // Import/Export
        echo '<div class="seokar-tool-card">';
        echo '<h2>' . esc_html__('Import/Export', 'seokar') . '</h2>';
        echo '<p>' . esc_html__('Export or import your SEO settings', 'seokar') . '</p>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=seokar-tools&tool=import-export')) . '" class="button button-primary">' . esc_html__('Manage Data', 'seokar') . '</a>';
        echo '</div>';
        
        echo '</div>'; // .seokar-tools-grid
        
        echo '</div>'; // .wrap
    }
}

<?php
/**
 * SEO Framework Meta Boxes Class
 * 
 * Handles all meta box functionality for posts, pages and custom post types
 */

class SeoKar_Meta_Boxes {

    private static $_instance = null;
    private $post_types = array();

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->post_types = array_merge(array('post', 'page'), $this->get_public_cpt());
        
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_seokar_analyze_content', array($this, 'ajax_analyze_content'));
    }

    /**
     * Get public custom post types
     */
    private function get_public_cpt() {
        $post_types = get_post_types(array(
            'public' => true,
            '_builtin' => false
        ), 'names');
        
        return array_values($post_types);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        foreach ($this->post_types as $post_type) {
            add_meta_box(
                'seokar_meta_box',
                __('SEO Settings', 'seokar'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render meta box content
     */
    public function render_meta_box($post) {
        wp_nonce_field('seokar_meta_box_nonce', 'seokar_meta_box_nonce');
        
        $values = get_post_meta($post->ID, '_seokar_settings', true);
        $defaults = array(
            'title' => '',
            'description' => '',
            'keywords' => '',
            'canonical' => '',
            'noindex' => '0',
            'nofollow' => '0',
            'noarchive' => '0',
            'disable_schema' => '0',
            'focus_keyword' => '',
            'secondary_keywords' => '',
            'schema_type' => '',
            'schema_data' => array()
        );
        
        $values = wp_parse_args($values, $defaults);
        
        // Get readability score
        $readability = $this->calculate_readability($post->post_content);
        $seo_score = $this->calculate_seo_score($post, $values);
        
        // Get suggestions
        $suggestions = $this->get_content_suggestions($post, $values);
        ?>
        
        <div class="seokar-meta-tabs">
            <ul class="seokar-tab-nav">
                <li class="active"><a href="#seokar-general"><?php _e('General', 'seokar'); ?></a></li>
                <li><a href="#seokar-advanced"><?php _e('Advanced', 'seokar'); ?></a></li>
                <li><a href="#seokar-schema"><?php _e('Schema', 'seokar'); ?></a></li>
                <li><a href="#seokar-analysis"><?php _e('Analysis', 'seokar'); ?></a></li>
            </ul>
            
            <div class="seokar-tab-content">
                <!-- General Tab -->
                <div id="seokar-general" class="seokar-tab-pane active">
                    <div class="seokar-form-row">
                        <label for="seokar_title"><?php _e('SEO Title', 'seokar'); ?></label>
                        <input type="text" id="seokar_title" name="seokar[title]" value="<?php echo esc_attr($values['title']); ?>" class="large-text">
                        <p class="description"><?php _e('Custom title for search engines (leave blank to use default template)', 'seokar'); ?></p>
                        <div class="seokar-counter" data-target="#seokar_title" data-max="60"><?php _e('60 characters remaining', 'seokar'); ?></div>
                    </div>
                    
                    <div class="seokar-form-row">
                        <label for="seokar_description"><?php _e('Meta Description', 'seokar'); ?></label>
                        <textarea id="seokar_description" name="seokar[description]" rows="3" class="large-text"><?php echo esc_textarea($values['description']); ?></textarea>
                        <p class="description"><?php _e('Custom description for search engines (leave blank to use default template)', 'seokar'); ?></p>
                        <div class="seokar-counter" data-target="#seokar_description" data-max="160"><?php _e('160 characters remaining', 'seokar'); ?></div>
                    </div>
                    
                    <div class="seokar-form-row">
                        <label for="seokar_focus_keyword"><?php _e('Focus Keyword', 'seokar'); ?></label>
                        <input type="text" id="seokar_focus_keyword" name="seokar[focus_keyword]" value="<?php echo esc_attr($values['focus_keyword']); ?>" class="regular-text">
                        <p class="description"><?php _e('The main keyword you want to rank for', 'seokar'); ?></p>
                    </div>
                    
                    <div class="seokar-form-row">
                        <label for="seokar_secondary_keywords"><?php _e('Secondary Keywords', 'seokar'); ?></label>
                        <input type="text" id="seokar_secondary_keywords" name="seokar[secondary_keywords]" value="<?php echo esc_attr($values['secondary_keywords']); ?>" class="large-text">
                        <p class="description"><?php _e('Comma separated list of additional keywords', 'seokar'); ?></p>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div id="seokar-advanced" class="seokar-tab-pane">
                    <div class="seokar-form-row">
                        <label for="seokar_canonical"><?php _e('Canonical URL', 'seokar'); ?></label>
                        <input type="url" id="seokar_canonical" name="seokar[canonical]" value="<?php echo esc_url($values['canonical']); ?>" class="large-text">
                        <p class="description"><?php _e('Advanced: Specify a canonical URL for this content', 'seokar'); ?></p>
                    </div>
                    
                    <div class="seokar-form-row">
                        <label><?php _e('Robots Meta', 'seokar'); ?></label>
                        <div class="seokar-checkbox-group">
                            <label>
                                <input type="checkbox" name="seokar[noindex]" value="1" <?php checked($values['noindex'], '1'); ?>>
                                <?php _e('Noindex - Prevent search engines from indexing this page', 'seokar'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" name="seokar[nofollow]" value="1" <?php checked($values['nofollow'], '1'); ?>>
                                <?php _e('Nofollow - Prevent search engines from following links on this page', 'seokar'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" name="seokar[noarchive]" value="1" <?php checked($values['noarchive'], '1'); ?>>
                                <?php _e('Noarchive - Prevent search engines from caching this page', 'seokar'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="seokar-form-row">
                        <label for="seokar_disable_schema"><?php _e('Schema Markup', 'seokar'); ?></label>
                        <label>
                            <input type="checkbox" name="seokar[disable_schema]" value="1" <?php checked($values['disable_schema'], '1'); ?>>
                            <?php _e('Disable schema markup for this content', 'seokar'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Schema Tab -->
                <div id="seokar-schema" class="seokar-tab-pane">
                    <div class="seokar-form-row">
                        <label for="seokar_schema_type"><?php _e('Schema Type', 'seokar'); ?></label>
                        <select id="seokar_schema_type" name="seokar[schema_type]" class="regular-text">
                            <option value=""><?php _e('Default (Auto-detect)', 'seokar'); ?></option>
                            <option value="Article" <?php selected($values['schema_type'], 'Article'); ?>><?php _e('Article', 'seokar'); ?></option>
                            <option value="NewsArticle" <?php selected($values['schema_type'], 'NewsArticle'); ?>><?php _e('News Article', 'seokar'); ?></option>
                            <option value="BlogPosting" <?php selected($values['schema_type'], 'BlogPosting'); ?>><?php _e('Blog Post', 'seokar'); ?></option>
                            <option value="WebPage" <?php selected($values['schema_type'], 'WebPage'); ?>><?php _e('Web Page', 'seokar'); ?></option>
                            <option value="Product" <?php selected($values['schema_type'], 'Product'); ?>><?php _e('Product', 'seokar'); ?></option>
                            <option value="Recipe" <?php selected($values['schema_type'], 'Recipe'); ?>><?php _e('Recipe', 'seokar'); ?></option>
                            <option value="Event" <?php selected($values['schema_type'], 'Event'); ?>><?php _e('Event', 'seokar'); ?></option>
                            <option value="FAQPage" <?php selected($values['schema_type'], 'FAQPage'); ?>><?php _e('FAQ Page', 'seokar'); ?></option>
                            <option value="HowTo" <?php selected($values['schema_type'], 'HowTo'); ?>><?php _e('How-To', 'seokar'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select schema type for this content', 'seokar'); ?></p>
                    </div>
                    
                    <div id="seokar-schema-fields" class="seokar-schema-fields">
                        <?php $this->render_schema_fields($values['schema_type'], $values['schema_data']); ?>
                    </div>
                </div>
                
                <!-- Analysis Tab -->
                <div id="seokar-analysis" class="seokar-tab-pane">
                    <div class="seokar-analysis-summary">
                        <div class="seokar-score-box">
                            <div class="seokar-score-circle" data-score="<?php echo esc_attr($seo_score); ?>">
                                <svg class="seokar-score-circle-bg" viewBox="0 0 36 36">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3" />
                                    <path class="seokar-score-circle-fill" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#4CAF50" stroke-width="3" stroke-dasharray="<?php echo esc_attr($seo_score); ?>, 100" />
                                </svg>
                                <div class="seokar-score-text"><?php echo esc_html($seo_score); ?></div>
                            </div>
                            <h3><?php _e('SEO Score', 'seokar'); ?></h3>
                        </div>
                        
                        <div class="seokar-readability-box">
                            <div class="seokar-score-circle" data-score="<?php echo esc_attr($readability['score']); ?>">
                                <svg class="seokar-score-circle-bg" viewBox="0 0 36 36">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3" />
                                    <path class="seokar-score-circle-fill" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#2196F3" stroke-width="3" stroke-dasharray="<?php echo esc_attr($readability['score']); ?>, 100" />
                                </svg>
                                <div class="seokar-score-text"><?php echo esc_html($readability['score']); ?></div>
                            </div>
                            <h3><?php _e('Readability', 'seokar'); ?></h3>
                            <p><?php echo esc_html($readability['level']); ?></p>
                        </div>
                    </div>
                    
                    <div class="seokar-analysis-details">
                        <h3><?php _e('SEO Analysis', 'seokar'); ?></h3>
                        <ul class="seokar-analysis-list">
                            <?php foreach ($suggestions as $suggestion): ?>
                            <li class="seokar-analysis-item seokar-<?php echo esc_attr($suggestion['status']); ?>">
                                <span class="seokar-analysis-icon"></span>
                                <span class="seokar-analysis-text"><?php echo esc_html($suggestion['text']); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="seokar-analysis-actions">
                        <button type="button" class="button button-secondary seokar-analyze-button" data-post_id="<?php echo esc_attr($post->ID); ?>">
                            <?php _e('Re-analyze Content', 'seokar'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preview Modal -->
        <div id="seokar-preview-modal" class="seokar-modal" style="display:none;">
            <div class="seokar-modal-content">
                <div class="seokar-modal-header">
                    <h3><?php _e('Search Engine Preview', 'seokar'); ?></h3>
                    <button type="button" class="seokar-modal-close">&times;</button>
                </div>
                <div class="seokar-modal-body">
                    <div class="seokar-serp-preview">
                        <div class="seokar-serp-title"></div>
                        <div class="seokar-serp-url"><?php echo esc_url(get_permalink($post->ID)); ?></div>
                        <div class="seokar-serp-description"></div>
                    </div>
                </div>
                <div class="seokar-modal-footer">
                    <button type="button" class="button button-primary seokar-modal-close"><?php _e('Close', 'seokar'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render schema fields based on type
     */
    private function render_schema_fields($type, $data = array()) {
        // Default fields for all types
        ?>
        <div class="seokar-form-row">
            <label for="seokar_schema_name"><?php _e('Name', 'seokar'); ?></label>
            <input type="text" id="seokar_schema_name" name="seokar[schema_data][name]" value="<?php echo esc_attr($data['name'] ?? ''); ?>" class="regular-text">
        </div>
        
        <div class="seokar-form-row">
            <label for="seokar_schema_description"><?php _e('Description', 'seokar'); ?></label>
            <textarea id="seokar_schema_description" name="seokar[schema_data][description]" rows="3" class="large-text"><?php echo esc_textarea($data['description'] ?? ''); ?></textarea>
        </div>
        <?php
        
        // Type-specific fields
        switch ($type) {
            case 'Article':
            case 'NewsArticle':
            case 'BlogPosting':
                ?>
                <div class="seokar-form-row">
                    <label for="seokar_schema_image"><?php _e('Image', 'seokar'); ?></label>
                    <input type="text" id="seokar_schema_image" name="seokar[schema_data][image]" value="<?php echo esc_attr($data['image'] ?? ''); ?>" class="large-text">
                    <button type="button" class="button seokar-media-upload" data-target="#seokar_schema_image"><?php _e('Upload Image', 'seokar'); ?></button>
                </div>
                
                <div class="seokar-form-row">
                    <label for="seokar_schema_published"><?php _e('Published Date', 'seokar'); ?></label>
                    <input type="datetime-local" id="seokar_schema_published" name="seokar[schema_data][datePublished]" value="<?php echo esc_attr($data['datePublished'] ?? ''); ?>" class="regular-text">
                </div>
                
                <div class="seokar-form-row">
                    <label for="seokar_schema_modified"><?php _e('Modified Date', 'seokar'); ?></label>
                    <input type="datetime-local" id="seokar_schema_modified" name="seokar[schema_data][dateModified]" value="<?php echo esc_attr($data['dateModified'] ?? ''); ?>" class="regular-text">
                </div>
                
                <div class="seokar-form-row">
                    <label for="seokar_schema_author"><?php _e('Author Name', 'seokar'); ?></label>
                    <input type="text" id="seokar_schema_author" name="seokar[schema_data][author]" value="<?php echo esc_attr($data['author'] ?? ''); ?>" class="regular-text">
                </div>
                <?php
                break;
                
            case 'Product':
                ?>
                <div class="seokar-form-row">
                    <label for="seokar_schema_brand"><?php _e('Brand', 'seokar'); ?></label>
                    <input type="text" id="seokar_schema_brand" name="seokar[schema_data][brand]" value="<?php echo esc_attr($data['brand'] ?? ''); ?>" class="regular-text">
                </div>
                
                <div class="seokar-form-row">
                    <label for="seokar_schema_sku"><?php _e('SKU', 'seokar'); ?></label>
                    <input type="text" id="seokar_schema_sku" name="seokar[schema_data][sku]" value="<?php echo esc_attr($data['sku'] ?? ''); ?>" class="regular-text">
                </div>
                
                <div class="seokar-form-row">
                    <label for="seokar_schema_price"><?php _e('Price', 'seokar'); ?></label>
                    <input type="number" step="0.01" id="seokar_schema_price" name="seokar[schema_data][price]" value="<?php echo esc_attr($data['price'] ?? ''); ?>" class="small-text">
                </div>
                
                <div class="seokar-form-row">
                    <label for="seokar_schema_currency"><?php _e('Currency', 'seokar'); ?></label>
                    <input type="text" id="seokar_schema_currency" name="seokar[schema_data][priceCurrency]" value="<?php echo esc_attr($data['priceCurrency'] ?? 'USD'); ?>" class="small-text">
                </div>
                <?php
                break;
                
            // Additional schema types can be added here
        }
    }

    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['seokar_meta_box_nonce']) || !wp_verify_nonce($_POST['seokar_meta_box_nonce'], 'seokar_meta_box_nonce')) {
            return $post_id;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        
        // Save data
        if (isset($_POST['seokar'])) {
            $data = $_POST['seokar'];
            
            // Sanitize data
            $sanitized = array(
                'title' => sanitize_text_field($data['title'] ?? ''),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'keywords' => sanitize_text_field($data['keywords'] ?? ''),
                'canonical' => esc_url_raw($data['canonical'] ?? ''),
                'noindex' => isset($data['noindex']) ? '1' : '0',
                'nofollow' => isset($data['nofollow']) ? '1' : '0',
                'noarchive' => isset($data['noarchive']) ? '1' : '0',
                'disable_schema' => isset($data['disable_schema']) ? '1' : '0',
                'focus_keyword' => sanitize_text_field($data['focus_keyword'] ?? ''),
                'secondary_keywords' => sanitize_text_field($data['secondary_keywords'] ?? ''),
                'schema_type' => sanitize_text_field($data['schema_type'] ?? ''),
                'schema_data' => array()
            );
            
            // Sanitize schema data
            if (!empty($data['schema_data']) && is_array($data['schema_data'])) {
                foreach ($data['schema_data'] as $key => $value) {
                    if (is_array($value)) {
                        $sanitized['schema_data'][$key] = array_map('sanitize_text_field', $value);
                    } else {
                        $sanitized['schema_data'][$key] = sanitize_text_field($value);
                    }
                }
            }
            
            update_post_meta($post_id, '_seokar_settings', $sanitized);
        }
    }

    /**
     * Calculate readability score
     */
    private function calculate_readability($content) {
        // Implementation of Flesch-Kincaid readability test
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);
        
        $word_count = str_word_count($content);
        $sentence_count = preg_match_all('/[.!?]+/', $content, $matches);
        $syllable_count = $this->count_syllables($content);
        
        if ($word_count > 0 && $sentence_count > 0) {
            $score = 206.835 - (1.015 * ($word_count / $sentence_count)) - (84.6 * ($syllable_count / $word_count));
            $score = max(0, min(100, $score));
            
            if ($score >= 90) $level = __('Very Easy', 'seokar');
            elseif ($score >= 80) $level = __('Easy', 'seokar');
            elseif ($score >= 70) $level = __('Fairly Easy', 'seokar');
            elseif ($score >= 60) $level = __('Standard', 'seokar');
            elseif ($score >= 50) $level = __('Fairly Difficult', 'seokar');
            elseif ($score >= 30) $level = __('Difficult', 'seokar');
            else $level = __('Very Difficult', 'seokar');
            
            return array(
                'score' => round($score),
                'level' => $level
            );
        }
        
        return array(
            'score' => 0,
            'level' => __('Not enough data', 'seokar')
        );
    }

    /**
     * Count syllables in text
     */
    private function count_syllables($text) {
        // Basic syllable counting (approximation)
        $text = strtolower($text);
        $count = 0;
        
        // Split words
        $words = preg_split('/[^a-z]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($words as $word) {
            $count += max(1, preg_match_all('/[aeiouy]+/', $word));
        }
        
        return $count;
    }

    /**
     * Calculate SEO score
     */
    private function calculate_seo_score($post, $values) {
        $score = 0;
        $max_score = 20; // Points per check
        
        // 1. Title check
        if (!empty($values['title']) && strlen($values['title']) >= 40 && strlen($values['title']) <= 60) {
            $score += $max_score;
        } elseif (!empty($values['title'])) {
            $score += $max_score * 0.5;
        }
        
        // 2. Description check
        if (!empty($values['description']) && strlen($values['description']) >= 120 && strlen($values['description']) <= 160) {
            $score += $max_score;
        } elseif (!empty($values['description'])) {
            $score += $max_score * 0.5;
        }
        
        // 3. Focus keyword check
        if (!empty($values['focus_keyword'])) {
            $score += $max_score;
            
            // Check if keyword appears in title
            if (stripos($values['title'] ?? '', $values['focus_keyword']) !== false) {
                $score += $max_score * 0.5;
            }
            
            // Check if keyword appears in content
            if (stripos($post->post_content, $values['focus_keyword']) !== false) {
                $score += $max_score * 0.5;
            }
        }
        
        // 4. Content length check
        $content_length = strlen(wp_strip_all_tags($post->post_content));
        if ($content_length >= 1500) {
            $score += $max_score;
        } elseif ($content_length >= 800) {
            $score += $max_score * 0.7;
        } elseif ($content_length >= 300) {
            $score += $max_score * 0.3;
        }
        
        // 5. Image check
        $images = preg_match_all('/<img[^>]+>/i', $post->post_content, $matches);
        $has_alt = 0;
        
        if ($images > 0) {
            foreach ($matches[0] as $img) {
                if (preg_match('/alt=["\']([^"\']+)["\']/i', $img)) {
                    $has_alt++;
                }
            }
            
            if ($has_alt == $images) {
                $score += $max_score;
            } elseif ($has_alt > 0) {
                $score += $max_score * ($has_alt / $images);
            }
        }
        
        // Calculate percentage (0-100)
        $total_possible = $max_score * 5;
        $final_score = ($score / $total_possible) * 100;
        
        return round($final_score);
    }

    /**
     * Get content suggestions
     */
    private function get_content_suggestions($post, $values) {
        $suggestions = array();
        
        // Title suggestions
        if (empty($values['title'])) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('SEO title is empty. Using default template.', 'seokar')
            );
        } elseif (strlen($values['title']) < 40) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('SEO title is too short (less than 40 characters).', 'seokar')
            );
        } elseif (strlen($values['title']) > 60) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('SEO title is too long (more than 60 characters).', 'seokar')
            );
        } else {
            $suggestions[] = array(
                'status' => 'good',
                'text' => __('SEO title length is good.', 'seokar')
            );
        }
        
        // Description suggestions
        if (empty($values['description'])) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('Meta description is empty. Using default template.', 'seokar')
            );
        } elseif (strlen($values['description']) < 120) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('Meta description is too short (less than 120 characters).', 'seokar')
            );
        } elseif (strlen($values['description']) > 160) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('Meta description is too long (more than 160 characters).', 'seokar')
            );
        } else {
            $suggestions[] = array(
                'status' => 'good',
                'text' => __('Meta description length is good.', 'seokar')
            );
        }
        
        // Focus keyword suggestions
        if (empty($values['focus_keyword'])) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('No focus keyword set.', 'seokar')
            );
        } else {
            $suggestions[] = array(
                'status' => 'good',
                'text' => __('Focus keyword is set.', 'seokar')
            );
            
            // Check keyword in title
            if (stripos($values['title'] ?? '', $values['focus_keyword']) === false) {
                $suggestions[] = array(
                    'status' => 'warning',
                    'text' => __('Focus keyword does not appear in SEO title.', 'seokar')
                );
            } else {
                $suggestions[] = array(
                    'status' => 'good',
                    'text' => __('Focus keyword appears in SEO title.', 'seokar')
                );
            }
            
            // Check keyword in content
            $content = wp_strip_all_tags($post->post_content);
            $keyword_count = substr_count(strtolower($content), strtolower($values['focus_keyword']));
            
            if ($keyword_count == 0) {
                $suggestions[] = array(
                    'status' => 'bad',
                    'text' => __('Focus keyword does not appear in content.', 'seokar')
                );
            } elseif ($keyword_count < 3) {
                $suggestions[] = array(
                    'status' => 'warning',
                    'text' => __('Focus keyword appears only ' . $keyword_count . ' times in content.', 'seokar')
                );
            } else {
                $suggestions[] = array(
                    'status' => 'good',
                    'text' => __('Focus keyword appears ' . $keyword_count . ' times in content.', 'seokar')
                );
            }
        }
        
        // Content length suggestions
        $content_length = strlen(wp_strip_all_tags($post->post_content));
        if ($content_length < 300) {
            $suggestions[] = array(
                'status' => 'bad',
                'text' => __('Content is very short (less than 300 characters).', 'seokar')
            );
        } elseif ($content_length < 800) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('Content could be longer (less than 800 characters).', 'seokar')
            );
        } elseif ($content_length < 1500) {
            $suggestions[] = array(
                'status' => 'good',
                'text' => __('Content length is good.', 'seokar')
            );
        } else {
            $suggestions[] = array(
                'status' => 'good',
                'text' => __('Content is comprehensive (over 1500 characters).', 'seokar')
            );
        }
        
        // Image suggestions
        $images = preg_match_all('/<img[^>]+>/i', $post->post_content, $matches);
        $has_alt = 0;
        
        if ($images > 0) {
            foreach ($matches[0] as $img) {
                if (preg_match('/alt=["\']([^"\']+)["\']/i', $img)) {
                    $has_alt++;
                }
            }
            
            if ($has_alt == 0) {
                $suggestions[] = array(
                    'status' => 'bad',
                    'text' => __('None of your images have alt text.', 'seokar')
                );
            } elseif ($has_alt < $images) {
                $suggestions[] = array(
                    'status' => 'warning',
                    'text' => sprintf(__('%d out of %d images have alt text.', 'seokar'), $has_alt, $images)
                );
            } else {
                $suggestions[] = array(
                    'status' => 'good',
                    'text' => __('All images have alt text.', 'seokar')
                );
            }
        } else {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => __('No images found in content.', 'seokar')
            );
        }
        
        // Internal links suggestions
        $internal_links = preg_match_all('/<a[^>]+href=["\']' . preg_quote(home_url(), '/') . '[^"\']*["\'][^>]*>/i', $post->post_content, $matches);
        if ($internal_links < 3) {
            $suggestions[] = array(
                'status' => 'warning',
                'text' => sprintf(__('Only %d internal links found. Consider adding more.', 'seokar'), $internal_links)
            );
        } else {
            $suggestions[] = array(
                'status' => 'good',
                'text' => sprintf(__('%d internal links found.', 'seokar'), $internal_links)
            );
        }
        
        return $suggestions;
    }

    /**
     * AJAX content analysis
     */
    public function ajax_analyze_content() {
        check_ajax_referer('seokar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts') || empty($_POST['post_id'])) {
            wp_send_json_error(__('Permission denied', 'seokar'));
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'seokar'));
        }
        
        $values = get_post_meta($post_id, '_seokar_settings', true);
        $defaults = array(
            'title' => '',
            'description' => '',
            'keywords' => '',
            'focus_keyword' => '',
            'secondary_keywords' => ''
        );
        
        $values = wp_parse_args($values, $defaults        $readability = $this->calculate_readability($post->post_content);
        $seo_score = $this->calculate_seo_score($post, $values);
        $suggestions = $this->get_content_suggestions($post, $values);
        
        ob_start();
        ?>
        <div class="seokar-analysis-summary">
            <div class="seokar-score-box">
                <div class="seokar-score-circle" data-score="<?php echo esc_attr($seo_score); ?>">
                    <svg class="seokar-score-circle-bg" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3" />
                        <path class="seokar-score-circle-fill" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#4CAF50" stroke-width="3" stroke-dasharray="<?php echo esc_attr($seo_score); ?>, 100" />
                    </svg>
                    <div class="seokar-score-text"><?php echo esc_html($seo_score); ?></div>
                </div>
                <h3><?php _e('SEO Score', 'seokar'); ?></h3>
            </div>
            
            <div class="seokar-readability-box">
                <div class="seokar-score-circle" data-score="<?php echo esc_attr($readability['score']); ?>">
                    <svg class="seokar-score-circle-bg" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3" />
                        <path class="seokar-score-circle-fill" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#2196F3" stroke-width="3" stroke-dasharray="<?php echo esc_attr($readability['score']); ?>, 100" />
                    </svg>
                    <div class="seokar-score-text"><?php echo esc_html($readability['score']); ?></div>
                </div>
                <h3><?php _e('Readability', 'seokar'); ?></h3>
                <p><?php echo esc_html($readability['level']); ?></p>
            </div>
        </div>
        
        <div class="seokar-analysis-details">
            <h3><?php _e('SEO Analysis', 'seokar'); ?></h3>
            <ul class="seokar-analysis-list">
                <?php foreach ($suggestions as $suggestion): ?>
                <li class="seokar-analysis-item seokar-<?php echo esc_attr($suggestion['status']); ?>">
                    <span class="seokar-analysis-icon"></span>
                    <span class="seokar-analysis-text"><?php echo esc_html($suggestion['text']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'score' => $seo_score,
            'readability' => $readability,
            'html' => $html
        ));
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook) {
        global $post;
        
        if (!in_array($hook, array('post.php', 'post-new.php')) {
            return;
        }
        
        if (!in_array(get_post_type($post), $this->post_types)) {
            return;
        }
        
        wp_enqueue_style('seokar-meta-box', SEOKAR_ASSETS_URL . '/css/meta-box.css', array(), SEOKAR_VERSION);
        wp_enqueue_script('seokar-meta-box', SEOKAR_ASSETS_URL . '/js/meta-box.js', array('jquery', 'wp-util'), SEOKAR_VERSION, true);
        
        wp_localize_script('seokar-meta-box', 'seokar_meta', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'post_id' => $post->ID,
            'nonce' => wp_create_nonce('seokar_nonce'),
            'i18n' => array(
                'analyzing' => __('Analyzing...', 'seokar'),
                'error' => __('Error occurred', 'seokar'),
                'preview' => __('Preview', 'seokar')
            )
        ));
    }
}

/**
 * SEO Framework Frontend Class
 * 
 * Handles all frontend output including meta tags, schema markup, and other SEO elements
 */

class SeoKar_Frontend {

    private static $_instance = null;
    private $options = array();

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->options = get_option('seokar_settings', array());
        
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        add_action('wp_head', array($this, 'output_schema_markup'), 30);
        add_filter('document_title_parts', array($this, 'filter_title_parts'), 20);
        add_filter('wp_title', array($this, 'filter_wp_title'), 20, 3);
        add_action('template_redirect', array($this, 'handle_redirects'));
    }

    /**
     * Output meta tags in head
     */
    public function output_meta_tags() {
        if (is_feed()) {
            return;
        }
        
        // Get current post/page data
        global $post;
        $meta = $this->get_current_meta();
        
        // Robots meta
        $robots = array();
        
        if (!empty($meta['noindex']) || $this->is_noindex()) {
            $robots[] = 'noindex';
        } else {
            $robots[] = 'index';
        }
        
        if (!empty($meta['nofollow'])) {
            $robots[] = 'nofollow';
        } else {
            $robots[] = 'follow';
        }
        
        if (!empty($meta['noarchive'])) {
            $robots[] = 'noarchive';
        }
        
        echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '">' . "\n";
        
        // Canonical URL
        if (!empty($meta['canonical'])) {
            echo '<link rel="canonical" href="' . esc_url($meta['canonical']) . '">' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url($this->get_canonical_url()) . '">' . "\n";
        }
        
        // Meta description
        if (!empty($meta['description'])) {
            echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
        }
        
        // Meta keywords
        if (!empty($meta['keywords'])) {
            echo '<meta name="keywords" content="' . esc_attr($meta['keywords']) . '">' . "\n";
        }
        
        // OpenGraph/Twitter Card meta
        if ($this->options['enable_social_meta'] ?? true) {
            $this->output_social_meta($meta);
        }
    }

    /**
     * Get meta data for current page
     */
    private function get_current_meta() {
        global $post;
        
        $defaults = array(
            'title' => '',
            'description' => '',
            'keywords' => '',
            'canonical' => '',
            'noindex' => '0',
            'nofollow' => '0',
            'noarchive' => '0',
            'disable_schema' => '0',
            'focus_keyword' => '',
            'secondary_keywords' => '',
            'schema_type' => '',
            'schema_data' => array()
        );
        
        // Single post/page
        if (is_singular() && !empty($post)) {
            $meta = get_post_meta($post->ID, '_seokar_settings', true);
            $meta = wp_parse_args($meta, $defaults);
            
            // Use default templates if empty
            if (empty($meta['title'])) {
                $meta['title'] = $this->generate_title($post);
            }
            
            if (empty($meta['description'])) {
                $meta['description'] = $this->generate_description($post);
            }
            
            return $meta;
        }
        
        // Homepage
        if (is_home() || is_front_page()) {
            $meta = array(
                'title' => $this->options['home_title'] ?? '',
                'description' => $this->options['home_description'] ?? '',
                'noindex' => $this->options['noindex_home'] ?? '0'
            );
            
            return wp_parse_args($meta, $defaults);
        }
        
        // Archive pages
        if (is_archive()) {
            $object = get_queried_object();
            $meta = array();
            
            if (is_category() || is_tag() || is_tax()) {
                $meta = array(
                    'title' => $this->options['tax_' . $object->taxonomy . '_title'] ?? '',
                    'description' => term_description($object->term_id, $object->taxonomy),
                    'noindex' => $this->options['noindex_tax_' . $object->taxonomy] ?? '0'
                );
            } elseif (is_author()) {
                $meta = array(
                    'title' => $this->options['author_title'] ?? '',
                    'description' => get_the_author_meta('description', $object->ID),
                    'noindex' => $this->options['noindex_author'] ?? '0'
                );
            } elseif (is_date()) {
                $meta = array(
                    'title' => $this->options['date_title'] ?? '',
                    'noindex' => $this->options['noindex_date'] ?? '1'
                );
            }
            
            return wp_parse_args($meta, $defaults);
        }
        
        // Search results
        if (is_search()) {
            return array(
                'title' => sprintf(__('Search Results for "%s"', 'seokar'), get_search_query()),
                'noindex' => $this->options['noindex_search'] ?? '1'
            );
        }
        
        // 404 page
        if (is_404()) {
            return array(
                'title' => __('Page Not Found', 'seokar'),
                'noindex' => '1'
            );
        }
        
        return $defaults;
    }

    /**
     * Generate title from template
     */
    private function generate_title($post) {
        $template = $this->options['title_template'] ?? '{title} | {sitename}';
        
        $replacements = array(
            '{title}' => get_the_title($post),
            '{sitename}' => get_bloginfo('name'),
            '{sep}' => $this->options['separator'] ?? '|',
            '{description}' => get_bloginfo('description')
        );
        
        $title = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        // Clean up multiple separators
        $title = preg_replace('/\s*\{sep\}\s*/', ' ' . $this->options['separator'] . ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        
        return trim($title);
    }

    /**
     * Generate description from template
     */
    private function generate_description($post) {
        $template = $this->options['description_template'] ?? '{excerpt}';
        
        $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post) : wp_trim_words(strip_shortcodes($post->post_content), 30);
        
        $replacements = array(
            '{excerpt}' => $excerpt,
            '{title}' => get_the_title($post),
            '{sitename}' => get_bloginfo('name'),
            '{sep}' => $this->options['separator'] ?? '|'
        );
        
        $description = str_replace(array_keys($replacements), array_values($replacements), $template);
        $description = wp_trim_words($description, 30);
        
        return trim($description);
    }

    /**
     * Output social meta tags (OpenGraph/Twitter)
     */
    private function output_social_meta($meta) {
        global $post;
        
        $image = '';
        $title = $meta['title'] ?? '';
        $description = $meta['description'] ?? '';
        $url = $this->get_canonical_url();
        
        // Get image
        if (is_singular() && has_post_thumbnail($post->ID)) {
            $image = get_the_post_thumbnail_url($post->ID, 'large');
        } elseif (!empty($this->options['social_image'])) {
            $image = $this->options['social_image'];
        }
        
        // OpenGraph
        echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }
        
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        
        // Twitter Card
        echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        
        if ($image) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        }
    }

    /**
     * Get canonical URL for current page
     */
    private function get_canonical_url() {
        global $wp;
        
        if (is_singular()) {
            return get_permalink();
        }
        
        $url = home_url($wp->request);
        
        if (is_front_page()) {
            $url = home_url('/');
        }
        
        if (is_archive()) {
            $url = get_term_link(get_queried_object());
        }
        
        if (is_search()) {
            $url = get_search_link();
        }
        
        if (is_404()) {
            $url = home_url('/404');
        }
        
        // Add pagination if needed
        $paged = get_query_var('paged');
        if ($paged && $paged > 1) {
            $url = trailingslashit($url) . 'page/' . $paged;
        }
        
        return $url;
    }

    /**
     * Check if current page should be noindex
     */
    private function is_noindex() {
        // Check various archive types
        if (is_category() || is_tag() || is_tax()) {
            $tax = get_taxonomy(get_queried_object()->taxonomy);
            return $this->options['noindex_tax_' . $tax->name] ?? false;
        }
        
        if (is_author()) {
            return $this->options['noindex_author'] ?? false;
        }
        
        if (is_date()) {
            return $this->options['noindex_date'] ?? true;
        }
        
        if (is_search()) {
            return $this->options['noindex_search'] ?? true;
        }
        
        if (is_paged()) {
            return $this->options['noindex_pagination'] ?? false;
        }
        
        return false;
    }

    /**
     * Filter title parts
     */
    public function filter_title_parts($parts) {
        $meta = $this->get_current_meta();
        
        if (!empty($meta['title'])) {
            $parts['title'] = $meta['title'];
        }
        
        return $parts;
    }

    /**
     * Filter wp_title
     */
    public function filter_wp_title($title, $sep, $seplocation) {
        $meta = $this->get_current_meta();
        
        if (!empty($meta['title'])) {
            return $meta['title'];
        }
        
        return $title;
    }

    /**
     * Handle redirects
     */
    public function handle_redirects() {
        global $wpdb;
        
        if (is_admin()) {
            return;
        }
        
        $request = strtok($_SERVER['REQUEST_URI'], '?');
        $request = trailingslashit($request);
        
        // Check for redirects
        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT url_to, type FROM {$wpdb->prefix}seokar_redirects 
            WHERE url_from = %s AND status = 'active'",
            $request
        ));
        
        if ($redirect) {
            // Update hit count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}seokar_redirects 
                SET count = count + 1 
                WHERE url_from = %s",
                $request
            ));
            
            // Perform redirect
            wp_redirect($redirect->url_to, $redirect->type);
            exit;
        }
    }

    /**
     * Output schema markup
     */
    public function output_schema_markup() {
        if (is_feed()) {
            return;
        }
        
        $schema = array();
        $meta = $this->get_current_meta();
        
        // Check if schema is disabled for this page
        if (!empty($meta['disable_schema'])) {
            return;
        }
        
        // Website schema
        $schema['@context'] = 'https://schema.org';
        $schema['@graph'] = array();
        
        // Organization schema
        if (!empty($this->options['organization_name'])) {
            $organization = array(
                '@type' => 'Organization',
                '@id' => home_url('/#organization'),
                'name' => $this->options['organization_name'],
                'url' => home_url('/'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    '@id' => home_url('/#logo'),
                    'url' => $this->options['organization_logo'] ?? '',
                    'width' => $this->options['organization_logo_width'] ?? '',
                    'height' => $this->options['organization_logo_height'] ?? ''
                )
            );
            
            // Social profiles
            if (!empty($this->options['social_profiles'])) {
                $sameAs = array();
                foreach ($this->options['social_profiles'] as $profile) {
                    if (!empty($profile['url'])) {
                        $sameAs[] = $profile['url'];
                    }
                }
                
                if (!empty($sameAs)) {
                    $organization['sameAs'] = $sameAs;
                }
            }
            
            $schema['@graph'][] = $organization;
        }
        
        // WebSite schema
        $website = array(
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url('/'),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'publisher' => array(
                '@id' => home_url('/#organization')
            ),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            )
        );
        
        $schema['@graph'][] = $website;
        
        // Current page schema
        if (is_singular()) {
            global $post;
            
            $post_schema = array(
                '@type' => $meta['schema_type'] ?? 'Article',
                '@id' => get_permalink() . '#article',
                'isPartOf' => array(
                    '@id' => home_url('/#website')
                ),
                'author' => array(
                    '@id' => home_url('/#/schema/person/' . get_the_author_meta('ID'))
                ),
                'headline' => get_the_title(),
                'datePublished' => get_the_date('c'),
                'dateModified' => get_the_modified_date('c'),
                'mainEntityOfPage' => array(
                    '@id' => get_permalink() . '#webpage')
                ),
                'publisher' => array(
                    '@id' => home_url('/#organization')
                )
            );
            
            // Add featured image if available
            if (has_post_thumbnail()) {
                $image_id = get_post_thumbnail_id();
                $image = wp_get_attachment_image_src($image_id, 'full');
                
                $post_schema['image'] = array(
                    '@type' => 'ImageObject',
                    '@id' => get_permalink() . '#primaryimage',
                    'url' => $image[0],
                    'width' => $image[1],
                    'height' => $image[2]
                );
            }
            
            // Add article section if has categories
            $categories = get_the_category();
            if (!empty($categories)) {
                $post_schema['articleSection'] = array();
                foreach ($categories as $category) {
                    $post_schema['articleSection'][] = $category->name;
                }
            }
            
            // Add custom schema data if available
            if (!empty($meta['schema_data'])) {
                $post_schema = array_merge($post_schema, $meta['schema_data']);
            }
            
            $schema['@graph'][] = $post_schema;
        }
        
        // Breadcrumb schema
        if (!is_front_page() && !is_404()) {
            $breadcrumb = array(
                '@type' => 'BreadcrumbList',
                '@id' => get_permalink() . '#breadcrumb',
                'itemListElement' => array()
            );
            
            $position = 1;
            $breadcrumb['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Home', 'seokar'),
                'item' => home_url('/')
            );
            
            if (is_singular()) {
                $post_type = get_post_type_object(get_post_type());
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $post_type->labels->name,
                    'item' => get_post_type_archive_link(get_post_type())
                );
                
                if (is_post_type_hierarchical(get_post_type())) {
                    $ancestors = get_post_ancestors(get_the_ID());
                    if (!empty($ancestors)) {
                        $ancestors = array_reverse($ancestors);
                        foreach ($ancestors as $ancestor) {
                            $breadcrumb['itemListElement'][] = array(
                                '@type' => 'ListItem',
                                'position' => $position++,
                                'name' => get_the_title($ancestor),
                                'item' => get_permalink($ancestor)
                            );
                        }
                    }
                }
                
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => get_the_title(),
                    'item' => get_permalink()
                );
            } elseif (is_category() || is_tag() || is_tax()) {
                $term = get_queried_object();
                $taxonomy = get_taxonomy($term->taxonomy);
                
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $taxonomy->labels->name,
                    'item' => get_post_type_archive_link($taxonomy->object_type[0])
                );
                
                if (is_taxonomy_hierarchical($term->taxonomy) {
                    $ancestors = get_ancestors($term->term_id, $term->taxonomy);
                    if (!empty($ancestors)) {
                        $ancestors = array_reverse($ancestors);
                        foreach ($ancestors as $ancestor) {
                            $ancestor_term = get_term($ancestor, $term->taxonomy);
                            $breadcrumb['itemListElement'][] = array(
                                '@type' => 'ListItem',
                                'position' => $position++,
                                'name' => $ancestor_term->name,
                                'item' => get_term_link($ancestor_term)
                            );
                        }
                    }
                }
                
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $term->name,
                    'item' => get_term_link($term)
                );
            } elseif (is_author()) {
                $author = get_queried_object();
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $author->display_name,
                    'item' => get_author_posts_url($author->ID)
                );
            } elseif (is_date()) {
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => get_the_date(),
                    'item' => get_day_link(get_the_date('Y'), get_the_date('m'), get_the_date('d'))
                );
            } elseif (is_search()) {
                $breadcrumb['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => sprintf(__('Search Results for "%s"', 'seokar'), get_search_query()),
                    'item' => get_search_link()
                );
            }
            
            $schema['@graph'][] = $breadcrumb;
        }
        
        // Output the schema
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
}
<?php
/**
 * SEO Framework Sitemap Class
 * 
 * Handles XML sitemap generation and management
 */

class SeoKar_Sitemap {

    private static $_instance = null;
    private $sitemap_types = array('posts', 'pages', 'products', 'categories', 'tags');

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_action('template_redirect', array($this, 'generate_sitemap'));
        add_action('save_post', array($this, 'flush_sitemap_on_update'));
        add_action('delete_post', array($this, 'flush_sitemap_on_update'));
        add_action('created_term', array($this, 'flush_sitemap_on_term_change'), 10, 3);
        add_action('edited_term', array($this, 'flush_sitemap_on_term_change'), 10, 3);
        add_action('delete_term', array($this, 'flush_sitemap_on_term_change'), 10, 3);
    }

    /**
     * Register rewrite rules for sitemap
     */
    public function register_rewrite_rules() {
        add_rewrite_rule('^sitemap_index\.xml$', 'index.php?seokar_sitemap=index', 'top');
        foreach ($this->sitemap_types as $type) {
            add_rewrite_rule('^sitemap-' . $type . '\.xml$', 'index.php?seokar_sitemap=' . $type, 'top');
        }
    }

    /**
     * Register query vars
     */
    public function register_query_vars($vars) {
        $vars[] = 'seokar_sitemap';
        return $vars;
    }

    /**
     * Generate sitemap XML
     */
    public function generate_sitemap() {
        $sitemap = get_query_var('seokar_sitemap');
        
        if (empty($sitemap)) {
            return;
        }
        
        // Set headers
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow', true);
        
        // Include XML header
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url(SEOKAR_ASSETS_URL . '/css/sitemap.xsl') . '"?>' . "\n";
        
        // Generate appropriate sitemap
        if ($sitemap === 'index') {
            $this->generate_sitemap_index();
        } elseif (in_array($sitemap, $this->sitemap_types)) {
            $this->generate_sitemap_content($sitemap);
        }
        
        exit;
    }

    /**
     * Generate sitemap index
     */
    private function generate_sitemap_index() {
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($this->sitemap_types as $type) {
            $lastmod = $this->get_last_modified($type);
            
            echo '<sitemap>' . "\n";
            echo '<loc>' . esc_url(home_url('/sitemap-' . $type . '.xml')) . '</loc>' . "\n";
            echo '<lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";
            echo '</sitemap>' . "\n";
        }
        
        // Custom post type sitemaps
        $custom_post_types = get_post_types(array(
            'public' => true,
            '_builtin' => false
        ));
        
        foreach ($custom_post_types as $post_type) {
            if ($post_type === 'product' || !$this->is_post_type_included($post_type)) {
                continue;
            }
            
            $lastmod = $this->get_last_modified($post_type);
            
            echo '<sitemap>' . "\n";
            echo '<loc>' . esc_url(home_url('/sitemap-' . $post_type . '.xml')) . '</loc>' . "\n";
            echo '<lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";
            echo '</sitemap>' . "\n";
        }
        
        echo '</sitemapindex>';
    }

    /**
     * Generate content sitemap
     */
    private function generate_sitemap_content($type) {
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        switch ($type) {
            case 'posts':
                $this->add_posts_to_sitemap('post');
                break;
                
            case 'pages':
                $this->add_posts_to_sitemap('page');
                break;
                
            case 'products':
                if (post_type_exists('product')) {
                    $this->add_posts_to_sitemap('product');
                }
                break;
                
            case 'categories':
                $this->add_terms_to_sitemap('category');
                break;
                
            case 'tags':
                $this->add_terms_to_sitemap('post_tag');
                break;
                
            default:
                if (post_type_exists($type)) {
                    $this->add_posts_to_sitemap($type);
                }
                break;
        }
        
        echo '</urlset>';
    }

    /**
     * Add posts to sitemap
     */
    private function add_posts_to_sitemap($post_type) {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => 1000,
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'fields' => 'ids',
            'orderby' => 'modified',
            'order' => 'DESC'
        );
        
        // Exclude noindex posts
        $exclude = $this->get_noindex_post_ids();
        if (!empty($exclude)) {
            $args['post__not_in'] = $exclude;
        }
        
        $query = new WP_Query($args);
        
        foreach ($query->posts as $post_id) {
            $url = array(
                'loc' => get_permalink($post_id),
                'lastmod' => get_post_modified_time('Y-m-d\TH:i:sP', true, $post_id),
                'changefreq' => $this->get_post_frequency($post_id),
                'priority' => $this->get_post_priority($post_id)
            );
            
            // Add images
            $images = $this->get_post_images($post_id);
            if (!empty($images)) {
                $url['images'] = $images;
            }
            
            $this->output_sitemap_url($url);
        }
        
        wp_reset_postdata();
    }

    /**
     * Add terms to sitemap
     */
    private function add_terms_to_sitemap($taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'fields' => 'ids'
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return;
        }
        
        foreach ($terms as $term_id) {
            $term = get_term($term_id, $taxonomy);
            $url = array(
                'loc' => get_term_link($term),
                'lastmod' => $this->get_term_lastmod($term),
                'changefreq' => $this->get_term_frequency($term),
                'priority' => $this->get_term_priority($term)
            );
            
            $this->output_sitemap_url($url);
        }
    }

    /**
     * Output sitemap URL entry
     */
    private function output_sitemap_url($url) {
        echo '<url>' . "\n";
        echo '<loc>' . esc_url($url['loc']) . '</loc>' . "\n";
        echo '<lastmod>' . esc_html($url['lastmod']) . '</lastmod>' . "\n";
        echo '<changefreq>' . esc_html($url['changefreq']) . '</changefreq>' . "\n";
        echo '<priority>' . esc_html($url['priority']) . '</priority>' . "\n";
        
        // Output images if available
        if (!empty($url['images'])) {
            foreach ($url['images'] as $image) {
                echo '<image:image>' . "\n";
                echo '<image:loc>' . esc_url($image['url']) . '</image:loc>' . "\n";
                if (!empty($image['title'])) {
                    echo '<image:title>' . esc_html($image['title']) . '</image:title>' . "\n";
                }
                if (!empty($image['caption'])) {
                    echo '<image:caption>' . esc_html($image['caption']) . '</image:caption>' . "\n";
                }
                echo '</image:image>' . "\n";
            }
        }
        
        echo '</url>' . "\n";
    }

    /**
     * Get post images for sitemap
     */
    private function get_post_images($post_id) {
        $images = array();
        
        // Featured image
        if (has_post_thumbnail($post_id)) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            $image = wp_get_attachment_image_src($thumbnail_id, 'full');
            
            if ($image) {
                $images[] = array(
                    'url' => $image[0],
                    'title' => get_the_title($thumbnail_id),
                    'caption' => wp_get_attachment_caption($thumbnail_id)
                );
            }
        }
        
        // Content images
        $post = get_post($post_id);
        $content = $post->post_content;
        
        if (preg_match_all('/<img[^>]+>/i', $content, $matches)) {
            foreach ($matches[0] as $img) {
                if (preg_match('/src=["\']([^"\']+)["\']/i', $img, $src)) {
                    $url = $src[1];
                    
                    // Skip data URIs
                    if (strpos($url, 'data:') === 0) {
                        continue;
                    }
                    
                    // Make URL absolute if relative
                    if (strpos($url, 'http') !== 0) {
                        $url = home_url($url);
                    }
                    
                    $image = array('url' => $url);
                    
                    // Get alt text
                    if (preg_match('/alt=["\']([^"\']+)["\']/i', $img, $alt)) {
                        $image['title'] = $alt[1];
                    }
                    
                    // Get caption from figure
                    if (preg_match('/<figcaption[^>]*>([^<]+)<\/figcaption>/i', $content, $caption, 0, strpos($content, $img))) {
                        $image['caption'] = trim($caption[1]);
                    }
                    
                    $images[] = $image;
                }
            }
        }
        
        return $images;
    }

    /**
     * Get post change frequency
     */
    private function get_post_frequency($post_id) {
        $post_type = get_post_type($post_id);
        $frequency = get_option('seokar_sitemap_freq_' . $post_type, 'weekly');
        
        // Check if post has custom frequency
        $custom_freq = get_post_meta($post_id, '_seokar_sitemap_freq', true);
        if (!empty($custom_freq)) {
            return $custom_freq;
        }
        
        return $frequency;
    }

    /**
     * Get post priority
     */
    private function get_post_priority($post_id) {
        $post_type = get_post_type($post_id);
        $priority = get_option('seokar_sitemap_priority_' . $post_type, '0.7');
        
        // Check if post has custom priority
        $custom_priority = get_post_meta($post_id, '_seokar_sitemap_priority', true);
        if (!empty($custom_priority)) {
            return $custom_priority;
        }
        
        // Front page has highest priority
        if ($post_id == get_option('page_on_front')) {
            return '1.0';
        }
        
        return $priority;
    }

    /**
     * Get term last modified date
     */
    private function get_term_lastmod($term) {
        $args = array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id
                )
            ),
            'orderby' => 'modified',
            'order' => 'DESC',
            'fields' => 'ids'
        );
        
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            $post_id = $query->posts[0];
            return get_post_modified_time('Y-m-d\TH:i:sP', true, $post_id);
        }
        
        return date('Y-m-d\TH:i:sP');
    }

    /**
     * Get term change frequency
     */
    private function get_term_frequency($term) {
        return get_option('seokar_sitemap_freq_' . $term->taxonomy, 'weekly');
    }

    /**
     * Get term priority
     */
    private function get_term_priority($term) {
        return get_option('seokar_sitemap_priority_' . $term->taxonomy, '0.5');
    }

    /**
     * Get last modified date for sitemap type
     */
    private function get_last_modified($type) {
        if (post_type_exists($type)) {
            $args = array(
                'post_type' => $type,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids'
            );
            
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                $post_id = $query->posts[0];
                return get_post_modified_time('Y-m-d\TH:i:sP', true, $post_id);
            }
        } elseif (taxonomy_exists($type)) {
            $terms = get_terms(array(
                'taxonomy' => $type,
                'number' => 1,
                'orderby' => 'term_id',
                'order' => 'DESC',
                'fields' => 'ids'
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                return $this->get_term_lastmod(get_term($terms[0], $type));
            }
        }
        
        return date('Y-m-d\TH:i:sP');
    }

    /**
     * Check if post type should be included in sitemap
     */
    private function is_post_type_included($post_type) {
        $excluded = get_option('seokar_sitemap_exclude', array());
        return !in_array($post_type, $excluded);
    }

    /**
     * Get post IDs that are set to noindex
     */
    private function get_noindex_post_ids() {
        global $wpdb;
        
        $ids = $wpdb->get_col(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_seokar_settings' 
            AND meta_value LIKE '%\"noindex\";s:1:\"1\"%'"
        );
        
        return $ids ? array_map('intval', $ids) : array();
    }

    /**
     * Flush sitemap cache when content is updated
     */
    public function flush_sitemap_on_update($post_id) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        $this->flush_sitemap_cache();
    }

    /**
     * Flush sitemap cache when terms are updated
     */
    public function flush_sitemap_on_term_change($term_id, $tt_id, $taxonomy) {
        $this->flush_sitemap_cache();
    }

    /**
     * Flush sitemap cache
     */
    public function flush_sitemap_cache() {
        // Clear rewrite rules
        flush_rewrite_rules(false);
        
        // Delete transients
        foreach ($this->sitemap_types as $type) {
            delete_transient('seokar_sitemap_' . $type . '_lastmod');
        }
    }
}

/**
 * SEO Framework Redirects Class
 * 
 * Handles 301/302 redirects and broken link detection
 */

class SeoKar_Redirects {

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('admin_init', array($this, 'init_admin'));
        add_action('wp', array($this, 'check_for_redirects'));
        add_action('deleted_post', array($this, 'auto_create_redirect'));
        add_action('wp_ajax_seokar_check_broken_links', array($this, 'ajax_check_broken_links'));
    }

    /**
     * Initialize admin functionality
     */
    public function init_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seokar-settings',
            __('Redirect Manager', 'seokar'),
            __('Redirects', 'seokar'),
            'manage_options',
            'seokar-redirects',
            array($this, 'render_redirects_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'seo_page_seokar-redirects') {
            return;
        }
        
        wp_enqueue_style('seokar-redirects', SEOKAR_ASSETS_URL . '/css/redirects.css', array(), SEOKAR_VERSION);
        wp_enqueue_script('seokar-redirects', SEOKAR_ASSETS_URL . '/js/redirects.js', array('jquery', 'wp-util'), SEOKAR_VERSION, true);
        
        wp_localize_script('seokar-redirects', 'seokar_redirects', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seokar_redirects_nonce'),
            'i18n' => array(
                'confirm_delete' => __('Are you sure you want to delete this redirect?', 'seokar'),
                'error' => __('An error occurred', 'seokar'),
                'scanning' => __('Scanning for broken links...', 'seokar')
            )
        ));
    }

    /**
     * Render redirects admin page
     */
    public function render_redirects_page() {
        global $wpdb;
        
        // Handle form submissions
        if (isset($_POST['seokar_add_redirect'])) {
            $this->handle_add_redirect();
        }
        
        // Handle bulk actions
        if (isset($_POST['action']) || isset($_POST['action2'])) {
            $this->handle_bulk_actions();
        }
        
        // Get current page
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects");
        
        // Calculate pagination
        $total_pages = ceil($total / $per_page);
        $offset = ($current_page - 1) * $per_page;
        
        // Get redirects
        $redirects = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}seokar_redirects 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        ?>
        <div class="wrap seokar-redirects-wrap">
            <h1><?php _e('Redirect Manager', 'seokar'); ?></h1>
            
            <div class="seokar-redirects-grid">
                <div class="seokar-redirects-main">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=seokar-redirects')); ?>">
                        <div class="seokar-redirects-form">
                            <h2><?php _e('Add New Redirect', 'seokar'); ?></h2>
                            
                            <div class="seokar-form-row">
                                <label for="redirect_from"><?php _e('From URL', 'seokar'); ?></label>
                                <input type="text" id="redirect_from" name="redirect_from" class="regular-text" required>
                                <p class="description"><?php _e('The old URL (relative to site root, e.g. /old-page/)', 'seokar'); ?></p>
                            </div>
                            
                            <div class="seokar-form-row">
                                <label for="redirect_to"><?php _e('To URL', 'seokar'); ?></label>
                                <input type="text" id="redirect_to" name="redirect_to" class="regular-text" required>
                                <p class="description"><?php _e('The new URL (can be relative or absolute)', 'seokar'); ?></p>
                            </div>
                            
                            <div class="seokar-form-row">
                                <label for="redirect_type"><?php _e('Redirect Type', 'seokar'); ?></label>
                                <select id="redirect_type" name="redirect_type" required>
                                    <option value="301">301 - Permanent Redirect</option>
                                    <option value="302">302 - Temporary Redirect</option>
                                    <option value="307">307 - Temporary Redirect (Preserve Method)</option>
                                    <option value="410">410 - Gone</option>
                                    <option value="451">451 - Unavailable For Legal Reasons</option>
                                </select>
                            </div>
                            
                            <div class="seokar-form-row">
                                <label for="redirect_status"><?php _e('Status', 'seokar'); ?></label>
                                <select id="redirect_status" name="redirect_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="seokar-form-row">
                                <?php submit_button(__('Add Redirect', 'seokar'), 'primary', 'seokar_add_redirect', false); ?>
                            </div>
                        </div>
                    </form>
                    
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=seokar-redirects')); ?>">
                        <div class="seokar-redirects-list">
                            <div class="tablenav top">
                                <div class="alignleft actions bulkactions">
                                    <select name="action" required>
                                        <option value=""><?php _e('Bulk Actions', 'seokar'); ?></option>
                                        <option value="activate"><?php _e('Activate', 'seokar'); ?></option>
                                        <option value="deactivate"><?php _e('Deactivate', 'seokar'); ?></option>
                                        <option value="delete"><?php _e('Delete', 'seokar'); ?></option>
                                    </select>
                                    <input type="submit" class="button action" value="<?php _e('Apply', 'seokar'); ?>">
                                </div>
                                
                                <div class="tablenav-pages">
                                    <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total, 'seokar'), number_format_i18n($total)); ?></span>
                                    <span class="pagination-links">
                                        <?php if ($current_page > 1): ?>
                                            <a class="first-page button" href="<?php echo esc_url(remove_query_arg('paged')); ?>"><span class="screen-reader-text"><?php _e('First page', 'seokar'); ?></span><span aria-hidden="true">«</span></a>
                                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', max(1, $current_page - 1))); ?>"><span class="screen-reader-text"><?php _e('Previous page', 'seokar'); ?></span><span aria-hidden="true">‹</span></a>
                                        <?php endif; ?>
                                        
                                        <span class="paging-input">
                                            <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'seokar'); ?></label>
                                            <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo esc_attr($current_page); ?>" size="2" aria-describedby="table-paging">
                                            <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo esc_html($total_pages); ?></span></span>
                                        </span>
                                        
                                        <?php if ($current_page < $total_pages): ?>
                                            <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', min($total_pages, $current_page + 1))); ?>"><span class="screen-reader-text"><?php _e('Next page', 'seokar'); ?></span><span aria-hidden="true">›</span></a>
                                            <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>"><span class="screen-reader-text"><?php _e('Last page', 'seokar'); ?></span><span aria-hidden="true">»</span></a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <td class="manage-column column-cb check-column">
                                            <input type="checkbox" id="cb-select-all">
                                        </td>
                                        <th scope="col" class="manage-column column-primary"><?php _e('From URL', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('To URL', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Type', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Status', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Hits', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Last Hit', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Date', 'seokar'); ?></th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php if (empty($redirects)): ?>
                                        <tr>
                                            <td colspan="8"><?php _e('No redirects found.', 'seokar'); ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($redirects as $redirect): ?>
                                            <tr>
                                                <th scope="row" class="check-column">
                                                    <input type="checkbox" name="redirect_ids[]" value="<?php echo esc_attr($redirect->id); ?>">
                                                </th>
                                                <td class="column-primary">
                                                    <strong><?php echo esc_html($redirect->url_from); ?></strong>
                                                    <div class="row-actions">
                                                        <span class="edit"><a href="#" class="seokar-edit-redirect" data-id="<?php echo esc_attr($redirect->id); ?>"><?php _e('Edit', 'seokar'); ?></a> | </span>
                                                        <span class="delete"><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=seokar-redirects&action=delete&redirect_id=' . $redirect->id), 'delete_redirect_' . $redirect->id)); ?>" class="submitdelete"><?php _e('Delete', 'seokar'); ?></a></span>
                                                    </div>
                                                </td>
                                                <td><?php echo esc_html($redirect->url_to); ?></td>
                                                <td><?php echo esc_html($redirect->type); ?></td>
                                                <td>
                                                    <span class="seokar-status-<?php echo esc_attr($redirect->status); ?>">
                                                        <?php echo esc_html(ucfirst($redirect->status)); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo esc_html($redirect->count); ?></td>
                                                <td><?php echo $redirect->updated_at !== $redirect->created_at ? esc_html(date_i18n(get_option('date_format'), strtotime($redirect->updated_at))) : __('Never', 'seokar'); ?></td>
                                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($redirect->created_at))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                
                                <tfoot>
                                    <tr>
                                        <td class="manage-column column-cb check-column">
                                            <input type="checkbox" id="cb-select-all-2">
                                        </td>
                                        <th scope="col" class="manage-column column-primary"><?php _e('From URL', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('To URL', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Type', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Status', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Hits', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Last Hit', 'seokar'); ?></th>
                                        <th scope="col" class="manage-column"><?php _e('Date', 'seokar'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div class="tablenav bottom">
                                <div class="alignleft actions bulkactions">
                                    <select name="action2" required>
                                        <option value=""><?php _e('Bulk Actions', 'seokar'); ?></option>
                                        <option value="activate"><?php _e('Activate', 'seokar'); ?></option>
                                        <option value="deactivate"><?php _e('Deactivate', 'seokar'); ?></option>
                                        <option value="delete"><?php _e('Delete', 'seokar'); ?></option>
                                    </select>
                                    <input type="submit" class="button action" value="<?php _e('Apply', 'seokar'); ?>">
                                </div>
                                
                                <div class="tablenav-pages">
                                    <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total, 'seokar'), number_format_i18n($total)); ?></span>
                                    <span class="pagination-links">
                                        <?php if ($current_page > 1): ?>
                                            <a class="first-page button" href="<?php echo esc_url(remove_query_arg('paged')); ?>"><span class="screen-reader-text"><?php _e('First page', 'seokar'); ?></span><span aria-hidden="true">«</span></a>
                                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', max(1, $current_page - 1))); ?>"><span class="screen-reader-text"><?php _e('Previous page', 'seokar'); ?></span><span aria-hidden="true">‹</span></a>
                                        <?php endif; ?>
                                        
                                        <span class="paging-input">
                                            <label for="current-page-selector-2" class="screen-reader-text"><?php _e('Current Page', 'seokar'); ?></label>
                                            <input class="current-page" id="current-page-selector-2" type="text" name="paged" value="<?php echo esc_attr($current_page); ?>" size="2" aria-describedby="table-paging">
                                            <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo esc_html($total_pages); ?></span></span>
                                        </span>
                                        
                                        <?php if ($current_page < $total_pages): ?>
                                            <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', min($total_pages, $current_page + 1))); ?>"><span class="screen-reader-text"><?php _e('Next page', 'seokar'); ?></span><span aria-hidden="true">›</span></a>
                                            <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>"><span class="screen-reader-text"><?php _e('Last page', 'seokar'); ?></span><span aria-hidden="true">»</span></a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="seokar-redirects-sidebar">
                    <div class="seokar-redirects-tools">
                        <h2><?php _e('Tools', 'seokar'); ?></h2>
                        
                        <div class="seokar-tool-card">
                            <h3><?php _e('Import Redirects', 'seokar'); ?></h3>
                            <p><?php _e('Import redirects from a CSV file', 'seokar'); ?></p>
                            <form method="post" enctype="multipart/form-data">
                                <input type="file" name="redirects_csv" accept=".csv">
                                <?php submit_button(__('Import', 'seokar'), 'secondary', 'seokar_import_redirects', false); ?>
                            </form>
                        </div>
                        
                        <div class="seokar-tool-card">
                            <h3><?php _e('Export Redirects', 'seokar'); ?></h3>
                            <p><?php _e('Export all redirects to a CSV file', 'seokar'); ?></p>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=seokar-redirects&action=export'), 'export_redirects')); ?>" class="button button-secondary"><?php _e('Export', 'seokar'); ?></a>
                        </div>
                        
                        <div class="seokar-tool-card">
                            <h3><?php _e('Broken Link Checker', 'seokar'); ?></h3>
                            <p><?php _e('Scan your site for broken links', 'seokar'); ?></p>
                                                    <button type="button" id="seokar-scan-broken-links" class="button button-secondary"><?php _e('Scan Now', 'seokar'); ?></button>
                        <div id="seokar-scan-progress" style="display:none; margin-top:10px;">
                            <div class="seokar-progress-bar">
                                <div class="seokar-progress-fill"></div>
                            </div>
                            <p class="seokar-scan-status"><?php _e('Preparing scan...', 'seokar'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="seokar-redirects-stats">
                    <h2><?php _e('Statistics', 'seokar'); ?></h2>
                    <ul>
                        <li>
                            <strong><?php _e('Total Redirects:', 'seokar'); ?></strong>
                            <span><?php echo esc_html($total); ?></span>
                        </li>
                        <li>
                            <strong><?php _e('Active Redirects:', 'seokar'); ?></strong>
                            <span><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects WHERE status = 'active'")); ?></span>
                        </li>
                        <li>
                            <strong><?php _e('Most Redirected:', 'seokar'); ?></strong>
                            <span><?php 
                                $most_redirected = $wpdb->get_row("SELECT url_from, count FROM {$wpdb->prefix}seokar_redirects ORDER BY count DESC LIMIT 1");
                                if ($most_redirected) {
                                    echo esc_html($most_redirected->url_from) . ' (' . esc_html($most_redirected->count) . ' hits)';
                                } else {
                                    _e('N/A', 'seokar');
                                }
                            ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Edit Redirect Modal -->
        <div id="seokar-edit-modal" class="seokar-modal" style="display:none;">
            <div class="seokar-modal-content">
                <div class="seokar-modal-header">
                    <h3><?php _e('Edit Redirect', 'seokar'); ?></h3>
                    <button type="button" class="seokar-modal-close">&times;</button>
                </div>
                <div class="seokar-modal-body">
                    <form id="seokar-edit-form">
                        <input type="hidden" name="redirect_id" id="edit_redirect_id">
                        
                        <div class="seokar-form-row">
                            <label for="edit_redirect_from"><?php _e('From URL', 'seokar'); ?></label>
                            <input type="text" id="edit_redirect_from" name="redirect_from" class="regular-text" required>
                        </div>
                        
                        <div class="seokar-form-row">
                            <label for="edit_redirect_to"><?php _e('To URL', 'seokar'); ?></label>
                            <input type="text" id="edit_redirect_to" name="redirect_to" class="regular-text" required>
                        </div>
                        
                        <div class="seokar-form-row">
                            <label for="edit_redirect_type"><?php _e('Redirect Type', 'seokar'); ?></label>
                            <select id="edit_redirect_type" name="redirect_type" required>
                                <option value="301">301 - Permanent Redirect</option>
                                <option value="302">302 - Temporary Redirect</option>
                                <option value="307">307 - Temporary Redirect (Preserve Method)</option>
                                <option value="410">410 - Gone</option>
                                <option value="451">451 - Unavailable For Legal Reasons</option>
                            </select>
                        </div>
                        
                        <div class="seokar-form-row">
                            <label for="edit_redirect_status"><?php _e('Status', 'seokar'); ?></label>
                            <select id="edit_redirect_status" name="redirect_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="seokar-modal-footer">
                    <button type="button" class="button button-secondary seokar-modal-close"><?php _e('Cancel', 'seokar'); ?></button>
                    <button type="button" id="seokar-save-redirect" class="button button-primary"><?php _e('Save Changes', 'seokar'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Handle add redirect form submission
 */
private function handle_add_redirect() {
    global $wpdb;
    
    check_admin_referer('seokar_redirects_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'seokar'));
    }
    
    $from = sanitize_text_field($_POST['redirect_from']);
    $to = sanitize_text_field($_POST['redirect_to']);
    $type = sanitize_text_field($_POST['redirect_type']);
    $status = sanitize_text_field($_POST['redirect_status']);
    
    // Validate URLs
    if (empty($from) || empty($to)) {
        add_settings_error('seokar_redirects', 'empty_fields', __('Please fill in all required fields.', 'seokar'), 'error');
        return;
    }
    
    // Make sure from URL starts with slash
    if (strpos($from, '/') !== 0) {
        $from = '/' . $from;
    }
    
    // Check if redirect already exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects WHERE url_from = %s",
        $from
    ));
    
    if ($exists) {
        add_settings_error('seokar_redirects', 'redirect_exists', __('A redirect with this "From URL" already exists.', 'seokar'), 'error');
        return;
    }
    
    // Insert new redirect
    $wpdb->insert(
        "{$wpdb->prefix}seokar_redirects",
        array(
            'url_from' => $from,
            'url_to' => $to,
            'type' => $type,
            'status' => $status,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    add_settings_error('seokar_redirects', 'redirect_added', __('Redirect added successfully.', 'seokar'), 'updated');
}

/**
 * Handle bulk actions
 */
private function handle_bulk_actions() {
    global $wpdb;
    
    check_admin_referer('bulk-redirects');
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'seokar'));
    }
    
    if (empty($_POST['redirect_ids'])) {
        return;
    }
    
    $ids = array_map('intval', $_POST['redirect_ids']);
    $ids = implode(',', $ids);
    
    $action = isset($_POST['action']) && $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
    
    switch ($action) {
        case 'activate':
            $wpdb->query("UPDATE {$wpdb->prefix}seokar_redirects SET status = 'active' WHERE id IN ($ids)");
            add_settings_error('seokar_redirects', 'redirects_activated', __('Selected redirects have been activated.', 'seokar'), 'updated');
            break;
            
        case 'deactivate':
            $wpdb->query("UPDATE {$wpdb->prefix}seokar_redirects SET status = 'inactive' WHERE id IN ($ids)");
            add_settings_error('seokar_redirects', 'redirects_deactivated', __('Selected redirects have been deactivated.', 'seokar'), 'updated');
            break;
            
        case 'delete':
            $wpdb->query("DELETE FROM {$wpdb->prefix}seokar_redirects WHERE id IN ($ids)");
            add_settings_error('seokar_redirects', 'redirects_deleted', __('Selected redirects have been deleted.', 'seokar'), 'updated');
            break;
    }
}

/**
 * Check for redirects on frontend
 */
public function check_for_redirects() {
    if (is_admin()) {
        return;
    }
    
    global $wpdb;
    
    $request = strtok($_SERVER['REQUEST_URI'], '?');
    $request = trailingslashit($request);
    
    // Check for redirects
    $redirect = $wpdb->get_row($wpdb->prepare(
        "SELECT url_to, type FROM {$wpdb->prefix}seokar_redirects 
        WHERE url_from = %s AND status = 'active'",
        $request
    ));
    
    if ($redirect) {
        // Update hit count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}seokar_redirects 
            SET count = count + 1, 
            updated_at = %s 
            WHERE url_from = %s",
            current_time('mysql'),
            $request
        ));
        
        // Handle different redirect types
        switch ($redirect->type) {
            case '410':
                header('HTTP/1.1 410 Gone');
                exit;
                
            case '451':
                header('HTTP/1.1 451 Unavailable For Legal Reasons');
                exit;
                
            case '302':
                wp_redirect($redirect->url_to, 302);
                exit;
                
            case '307':
                wp_redirect($redirect->url_to, 307);
                exit;
                
            default: // 301
                wp_redirect($redirect->url_to, 301);
                exit;
        }
    }
}

/**
 * Automatically create redirect when post is deleted
 */
public function auto_create_redirect($post_id) {
    if (!get_option('seokar_auto_redirects', false)) {
        return;
    }
    
    $post = get_post($post_id);
    
    // Only for published posts/pages
    if ($post->post_status !== 'publish') {
        return;
    }
    
    // Get redirect target
    $redirect_to = get_option('seokar_auto_redirect_target', home_url('/'));
    
    global $wpdb;
    
    $wpdb->insert(
        "{$wpdb->prefix}seokar_redirects",
        array(
            'url_from' => str_replace(home_url(), '', get_permalink($post_id)),
            'url_to' => $redirect_to,
            'type' => '301',
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s')
    );
}

/**
 * AJAX check for broken links
 */
public function ajax_check_broken_links() {
    check_ajax_referer('seokar_redirects_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permission denied', 'seokar'));
    }
    
    // Get all posts/pages
    $args = array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    
    $post_ids = get_posts($args);
    $total_posts = count($post_ids);
    $checked = 0;
    $broken_links = array();
    
    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;
        
        // Find all links in content
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                // Skip mailto, tel, etc.
                if (strpos($url, ':') !== false && !preg_match('/^https?:\/\//i', $url)) {
                    continue;
                }
                
                // Make relative URLs absolute
                if (strpos($url, 'http') !== 0) {
                    $url = home_url($url);
                }
                
                // Skip internal links
                if (strpos($url, home_url()) === 0) {
                    continue;
                }
                
                // Check URL
                $response = wp_remote_head($url, array(
                    'timeout' => 5,
                    'redirection' => 2
                ));
                
                if (is_wp_error($response)) {
                    $broken_links[] = array(
                        'url' => $url,
                        'post_id' => $post_id,
                        'error' => $response->get_error_message()
                    );
                } elseif (wp_remote_retrieve_response_code($response) >= 400) {
                    $broken_links[] = array(
                        'url' => $url,
                        'post_id' => $post_id,
                        'error' => wp_remote_retrieve_response_message($response)
                    );
                }
            }
        }
        
        $checked++;
        
        // Send progress update every 5 posts
        if ($checked % 5 === 0) {
            wp_send_json_success(array(
                'progress' => round(($checked / $total_posts) * 100),
                'total' => $total_posts,
                'checked' => $checked,
                'broken' => count($broken_links),
                'continue' => true
            ));
        }
    }
    
    // Send final results
    wp_send_json_success(array(
        'progress' => 100,
        'broken_links' => $broken_links,
        'continue' => false
    ));
}
  <button type="button" id="seokar-scan-broken-links" class="button button-secondary"><?php _e('Scan Now', 'seokar'); ?></button>
                            <div id="seokar-scan-progress" style="display:none; margin-top:10px;">
                                <div class="seokar-progress-bar">
                                    <div class="seokar-progress-fill"></div>
                                </div>
                                <p class="seokar-scan-status"><?php _e('Preparing scan...', 'seokar'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="seokar-redirects-stats">
                        <h2><?php _e('Statistics', 'seokar'); ?></h2>
                        <ul>
                            <li>
                                <strong><?php _e('Total Redirects:', 'seokar'); ?></strong>
                                <span><?php echo esc_html($total); ?></span>
                            </li>
                            <li>
                                <strong><?php _e('Active Redirects:', 'seokar'); ?></strong>
                                <span><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects WHERE status = 'active'")); ?></span>
                            </li>
                            <li>
                                <strong><?php _e('Most Redirected:', 'seokar'); ?></strong>
                                <span><?php 
                                    $most_redirected = $wpdb->get_row("SELECT url_from, count FROM {$wpdb->prefix}seokar_redirects ORDER BY count DESC LIMIT 1");
                                    if ($most_redirected) {
                                        echo esc_html($most_redirected->url_from) . ' (' . esc_html($most_redirected->count) . ' hits)';
                                    } else {
                                        _e('N/A', 'seokar');
                                    }
                                ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Edit Redirect Modal -->
            <div id="seokar-edit-modal" class="seokar-modal" style="display:none;">
                <div class="seokar-modal-content">
                    <div class="seokar-modal-header">
                        <h3><?php _e('Edit Redirect', 'seokar'); ?></h3>
                        <button type="button" class="seokar-modal-close">&times;</button>
                    </div>
                    <div class="seokar-modal-body">
                        <form id="seokar-edit-form">
                            <input type="hidden" name="redirect_id" id="edit_redirect_id">
                            
                            <div class="seokar-form-row">
                                <label for="edit_redirect_from"><?php _e('From URL', 'seokar'); ?></label>
                                <input type="text" id="edit_redirect_from" name="redirect_from" class="regular-text" required>
                            </div>
                            
                            <div class="seokar-form-row">
                                <label for="edit_redirect_to"><?php _e('To URL', 'seokar'); ?></label>
                                <input type="text" id="edit_redirect_to" name="redirect_to" class="regular-text" required>
                            </div>
                            
                            <div class="seokar-form-row">
                                <label for="edit_redirect_type"><?php _e('Redirect Type', 'seokar'); ?></label>
                                <select id="edit_redirect_type" name="redirect_type" required>
                                    <option value="301">301 - Permanent Redirect</option>
                                    <option value="302">302 - Temporary Redirect</option>
                                    <option value="307">307 - Temporary Redirect (Preserve Method)</option>
                                    <option value="410">410 - Gone</option>
                                    <option value="451">451 - Unavailable For Legal Reasons</option>
                                </select>
                            </div>
                            
                            <div class="seokar-form-row">
                                <label for="edit_redirect_status"><?php _e('Status', 'seokar'); ?></label>
                                <select id="edit_redirect_status" name="redirect_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="seokar-modal-footer">
                        <button type="button" class="button button-secondary seokar-modal-close"><?php _e('Cancel', 'seokar'); ?></button>
                        <button type="button" id="seokar-save-redirect" class="button button-primary"><?php _e('Save Changes', 'seokar'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle add redirect form submission
     */
    private function handle_add_redirect() {
        global $wpdb;
        
        check_admin_referer('seokar_redirects_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'seokar'));
        }
        
        $from = sanitize_text_field($_POST['redirect_from']);
        $to = sanitize_text_field($_POST['redirect_to']);
        $type = sanitize_text_field($_POST['redirect_type']);
        $status = sanitize_text_field($_POST['redirect_status']);
        
        // Validate URLs
        if (empty($from) || empty($to)) {
            add_settings_error('seokar_redirects', 'empty_fields', __('Please fill in all required fields.', 'seokar'), 'error');
            return;
        }
        
        // Make sure from URL starts with slash
        if (strpos($from, '/') !== 0) {
            $from = '/' . $from;
        }
        
        // Check if redirect already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects WHERE url_from = %s",
            $from
        ));
        
        if ($exists) {
            add_settings_error('seokar_redirects', 'redirect_exists', __('A redirect with this "From URL" already exists.', 'seokar'), 'error');
            return;
        }
        
        // Insert new redirect
        $wpdb->insert(
            "{$wpdb->prefix}seokar_redirects",
            array(
                'url_from' => $from,
                'url_to' => $to,
                'type' => $type,
                'status' => $status,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        add_settings_error('seokar_redirects', 'redirect_added', __('Redirect added successfully.', 'seokar'), 'updated');
    }

    /**
     * Handle bulk actions
     */
    private function handle_bulk_actions() {
        global $wpdb;
        
        check_admin_referer('bulk-redirects');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'seokar'));
        }
        
        if (empty($_POST['redirect_ids'])) {
            return;
        }
        
        $ids = array_map('intval', $_POST['redirect_ids']);
        $ids = implode(',', $ids);
        
        $action = isset($_POST['action']) && $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
        
        switch ($action) {
            case 'activate':
                $wpdb->query("UPDATE {$wpdb->prefix}seokar_redirects SET status = 'active' WHERE id IN ($ids)");
                add_settings_error('seokar_redirects', 'redirects_activated', __('Selected redirects have been activated.', 'seokar'), 'updated');
                break;
                
            case 'deactivate':
                $wpdb->query("UPDATE {$wpdb->prefix}seokar_redirects SET status = 'inactive' WHERE id IN ($ids)");
                add_settings_error('seokar_redirects', 'redirects_deactivated', __('Selected redirects have been deactivated.', 'seokar'), 'updated');
                break;
                
            case 'delete':
                $wpdb->query("DELETE FROM {$wpdb->prefix}seokar_redirects WHERE id IN ($ids)");
                add_settings_error('seokar_redirects', 'redirects_deleted', __('Selected redirects have been deleted.', 'seokar'), 'updated');
                break;
        }
    }

    /**
     * Check for redirects on frontend
     */
    public function check_for_redirects() {
        if (is_admin()) {
            return;
        }
        
        global $wpdb;
        
        $request = strtok($_SERVER['REQUEST_URI'], '?');
        $request = trailingslashit($request);
        
        // Check for redirects
        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT url_to, type FROM {$wpdb->prefix}seokar_redirects 
            WHERE url_from = %s AND status = 'active'",
            $request
        ));
        
        if ($redirect) {
            // Update hit count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}seokar_redirects 
                SET count = count + 1, 
                updated_at = %s 
                WHERE url_from = %s",
                current_time('mysql'),
                $request
            ));
            
            // Handle different redirect types
            switch ($redirect->type) {
                case '410':
                    header('HTTP/1.1 410 Gone');
                    exit;
                    
                case '451':
                    header('HTTP/1.1 451 Unavailable For Legal Reasons');
                    exit;
                    
                case '302':
                    wp_redirect($redirect->url_to, 302);
                    exit;
                    
                case '307':
                    wp_redirect($redirect->url_to, 307);
                    exit;
                    
                default: // 301
                    wp_redirect($redirect->url_to, 301);
                    exit;
            }
        }
    }

    /**
     * Automatically create redirect when post is deleted
     */
    public function auto_create_redirect($post_id) {
        if (!get_option('seokar_auto_redirects', false)) {
            return;
        }
        
        $post = get_post($post_id);
        
        // Only for published posts/pages
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Get redirect target
        $redirect_to = get_option('seokar_auto_redirect_target', home_url('/'));
        
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}seokar_redirects",
            array(
                'url_from' => str_replace(home_url(), '', get_permalink($post_id)),
                'url_to' => $redirect_to,
                'type' => '301',
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * AJAX check for broken links
     */
    public function ajax_check_broken_links() {
        check_ajax_referer('seokar_redirects_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'seokar'));
        }
        
        // Get all posts/pages
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        $post_ids = get_posts($args);
        $total_posts = count($post_ids);
        $checked = 0;
        $broken_links = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            $content = $post->post_content;
            
            // Find all links in content
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    // Skip mailto, tel, etc.
                    if (strpos($url, ':') !== false && !preg_match('/^https?:\/\//i', $url)) {
                        continue;
                    }
                    
                    // Make relative URLs absolute
                    if (strpos($url, 'http') !== 0) {
                        $url = home_url($url);
                    }
                    
                    // Skip internal links
                    if (strpos($url, home_url()) === 0) {
                        continue;
                    }
                    
                    // Check URL
                    $response = wp_remote_head($url, array(
                        'timeout' => 5,
                        'redirection' => 2
                    ));
                    
                    if (is_wp_error($response)) {
                        $broken_links[] = array(
                            'url' => $url,
                            'post_id' => $post_id,
                            'error' => $response->get_error_message()
                        );
                    } elseif (wp_remote_retrieve_response_code($response) >= 400) {
                        $broken_links[] = array(
                            'url' => $url,
                            'post_id' => $post_id,
                            'error' => wp_remote_retrieve_response_message($response)
                        );
                    }
                }
            }
            
            $checked++;
            
            // Send progress update every 5 posts
            if ($checked % 5 === 0) {
                wp_send_json_success(array(
                    'progress' => round(($checked / $total_posts) * 100),
                    'total' => $total_posts,
                    'checked' => $checked,
                    'broken' => count($broken_links),
                    'continue' => true
                ));
            }
        }
        
        // Send final results
        wp_send_json_success(array(
            'progress' => 100,
            'broken_links' => $broken_links,
            'continue' => false
        ));
    }
}

/**
 * SEO Framework Core Class
 * 
 * Handles core functionality and integrations
 */

class SeoKar_Core {

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        // Initialize integrations
        $this->init_integrations();
        
        // Add image SEO
        add_filter('wp_get_attachment_image_attributes', array($this, 'optimize_image_attributes'), 10, 2);
        
        // Add body class for SEO score
        add_filter('body_class', array($this, 'add_seo_body_class'));
    }

    /**
     * Initialize integrations with other plugins
     */
    private function init_integrations() {
        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            require_once SEOKAR_INCLUDES . '/integrations/class-seokar-woocommerce.php';
            SeoKar_WooCommerce::instance();
        }
        
        // Elementor integration
        if (did_action('elementor/loaded')) {
            require_once SEOKAR_INCLUDES . '/integrations/class-seokar-elementor.php';
            SeoKar_Elementor::instance();
        }
        
        // WPML integration
        if (defined('ICL_SITEPRESS_VERSION')) {
            require_once SEOKAR_INCLUDES . '/integrations/class-seokar-wpml.php';
            SeoKar_WPML::instance();
        }
    }

    /**
     * Optimize image attributes for SEO
     */
    public function optimize_image_attributes($attr, $attachment) {
        // Ensure alt text is set
        if (empty($attr['alt']) && !empty($attachment->ID)) {
            $attr['alt'] = get_the_title($attachment->ID);
        }
        
        // Add title if empty
        if (empty($attr['title']) && !empty($attachment->ID)) {
            $attr['title'] = get_the_title($attachment->ID);
        }
        
        return $attr;
    }

    /**
     * Add SEO score to body class
     */
    public function add_seo_body_class($classes) {
        if (is_singular()) {
            global $post;
            $seo_score = $this->get_seo_score($post->ID);
            
            if ($seo_score >= 80) {
                $classes[] = 'seokar-score-excellent';
            } elseif ($seo_score >= 60) {
                $classes[] = 'seokar-score-good';
            } elseif ($seo_score >= 40) {
                $classes[] = 'seokar-score-fair';
            } else {
                $classes[] = 'seokar-score-poor';
            }
        }
        
        return $classes;
    }

    /**
     * Get SEO score for post
     */
    private function get_seo_score($post_id) {
        $meta = get_post_meta($post_id, '_seokar_settings', true);
        $post = get_post($post_id);
        
        if (empty($meta) || empty($post)) {
            return 0;
        }
        
        $score = 0;
        $max_score = 20; // Points per check
        
        // 1. Title check
        if (!empty($meta['title']) && strlen($meta['title']) >= 40 && strlen($meta['title']) <= 60) {
            $score += $max_score;
        } elseif (!empty($meta['title'])) {
            $score += $max_score * 0.5;
        }
        
        // 2. Description check
        if (!empty($meta['description']) && strlen($meta['description']) >= 120 && strlen($meta['description']) <= 160) {
            $score += $max_score;
        } elseif (!empty($meta['description'])) {
            $score += $max_score * 0.5;
        }
        
        // 3. Focus keyword check
        if (!empty($meta['focus_keyword'])) {
            $score += $max_score;
            
            // Check if keyword appears in title
            if (stripos($meta['title'] ?? '', $meta['focus_keyword']) !== false) {
                $score += $max_score * 0.5;
            }
            
            // Check if keyword appears in content
            if (stripos($post->post_content, $meta['focus_keyword']) !== false) {
                $score += $max_score * 0.5;
            }
        }
        
        // 4. Content length check
        $content_length = strlen(wp_strip_all_tags($post->post_content));
        if ($content_length >= 1500) {
            $score += $max_score;
        } elseif ($content_length >= 800) {
            $score += $max_score * 0.7;
        } elseif ($content_length >= 300) {
            $score += $max_score * 0.3;
        }
        
        // 5. Image check
        $images = preg_match_all('/<img[^>]+>/i', $post->post_content, $matches);
        $has_alt = 0;
        
        if ($images > 0) {
            foreach ($matches[0] as $img) {
                if (preg_match('/alt=["\']([^"\']+)["\']/i', $img)) {
                    $has_alt++;
                }
            }
            
            if ($has_alt == $images) {
                $score += $max_score;
            } elseif ($has_alt > 0) {
                $score += $max_score * ($has_alt / $images);
            }
        }
        
        // Calculate percentage (0-100)
        $total_possible = $max_score * 5;
        $final_score = ($score / $total_possible) * 100;
        
        return round($final_score);
    }
}
