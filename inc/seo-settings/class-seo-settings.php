<?php
/**
 * SEOKAR for WordPress Themes - Settings Module
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

class SEOKAR_Settings implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Settings tabs
     *
     * @var array
     */
    private $tabs = [];

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->setup_tabs();
        $this->setup_hooks();
    }

    /**
     * Setup tabs
     */
    private function setup_tabs() {
        $this->tabs = [
            'general' => [
                'title' => __('General', 'seokar'),
                'sections' => [
                    'basic' => [
                        'title' => __('Basic Settings', 'seokar'),
                        'fields' => [
                            'separator' => [
                                'title' => __('Title Separator', 'seokar'),
                                'type' => 'text',
                                'default' => '|',
                                'sanitize' => 'sanitize_text_field'
                            ],
                            'global_keywords' => [
                                'title' => __('Global Keywords', 'seokar'),
                                'type' => 'textarea',
                                'default' => '',
                                'sanitize' => 'sanitize_text_field',
                                'description' => __('Comma separated list of global keywords', 'seokar')
                            ]
                        ]
                    ],
                    'advanced' => [
                        'title' => __('Advanced Settings', 'seokar'),
                        'fields' => [
                            'noindex_archives' => [
                                'title' => __('Noindex Archives', 'seokar'),
                                'type' => 'checkbox',
                                'default' => 0,
                                'label' => __('Add noindex to archive pages', 'seokar')
                            ],
                            'remove_emoji' => [
                                'title' => __('Remove Emoji', 'seokar'),
                                'type' => 'checkbox',
                                'default' => 1,
                                'label' => __('Remove WordPress emoji scripts', 'seokar')
                            ]
                        ]
                    ]
                ]
            ],
            'social' => [
                'title' => __('Social', 'seokar'),
                'sections' => [
                    'profiles' => [
                        'title' => __('Social Profiles', 'seokar'),
                        'fields' => [
                            'social_profiles' => [
                                'title' => __('Profile URLs', 'seokar'),
                                'type' => 'textarea',
                                'default' => '',
                                'sanitize' => 'esc_url_raw',
                                'description' => __('One URL per line', 'seokar')
                            ]
                        ]
                    ],
                    'facebook' => [
                        'title' => __('Facebook', 'seokar'),
                        'fields' => [
                            'fb_app_id' => [
                                'title' => __('App ID', 'seokar'),
                                'type' => 'text',
                                'default' => '',
                                'sanitize' => 'sanitize_text_field'
                            ],
                            'fb_admin_id' => [
                                'title' => __('Admin ID', 'seokar'),
                                'type' => 'text',
                                'default' => '',
                                'sanitize' => 'sanitize_text_field'
                            ]
                        ]
                    ],
                    'twitter' => [
                        'title' => __('Twitter', 'seokar'),
                        'fields' => [
                            'twitter_card_type' => [
                                'title' => __('Card Type', 'seokar'),
                                'type' => 'select',
                                'default' => 'summary_large_image',
                                'options' => [
                                    'summary' => __('Summary', 'seokar'),
                                    'summary_large_image' => __('Summary with Large Image', 'seokar')
                                ]
                            ],
                            'twitter_site' => [
                                'title' => __('Site Username', 'seokar'),
                                'type' => 'text',
                                'default' => '',
                                'sanitize' => 'sanitize_text_field',
                                'description' => __('@username without @', 'seokar')
                            ],
                            'twitter_creator' => [
                                'title' => __('Default Creator', 'seokar'),
                                'type' => 'text',
                                'default' => '',
                                'sanitize' => 'sanitize_text_field',
                                'description' => __('@username without @', 'seokar')
                            ]
                        ]
                    ]
                ]
            ],
            'tools' => [
                'title' => __('Tools', 'seokar'),
                'sections' => [
                    'import_export' => [
                        'title' => __('Import/Export', 'seokar'),
                        'fields' => [
                            'import' => [
                                'title' => __('Import Settings', 'seokar'),
                                'type' => 'import_export',
                                'callback' => 'render_import_field'
                            ],
                            'export' => [
                                'title' => __('Export Settings', 'seokar'),
                                'type' => 'import_export',
                                'callback' => 'render_export_field'
                            ]
                        ]
                    ],
                    'reset' => [
                        'title' => __('Reset', 'seokar'),
                        'fields' => [
                            'reset' => [
                                'title' => __('Reset to Defaults', 'seokar'),
                                'type' => 'reset',
                                'callback' => 'render_reset_field'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_post_seokar_import_settings', [$this, 'handle_import']);
        add_action('admin_post_seokar_reset_settings', [$this, 'handle_reset']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('SEO Settings', 'seokar'),
            __('SEO', 'seokar'),
            'manage_options',
            'seokar-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            80
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('seokar_options_group', 'seokar_options', [$this, 'sanitize_options']);

        foreach ($this->tabs as $tab_id => $tab) {
            foreach ($tab['sections'] as $section_id => $section) {
                add_settings_section(
                    "seokar_{$tab_id}_{$section_id}_section",
                    $section['title'],
                    [$this, 'render_section'],
                    'seokar-settings'
                );

                foreach ($section['fields'] as $field_id => $field) {
                    $field['id'] = $field_id;
                    $field['tab'] = $tab_id;
                    $field['section'] = $section_id;

                    add_settings_field(
                        "seokar_{$field_id}_field",
                        $field['title'],
                        isset($field['callback']) ? [$this, $field['callback']] : [$this, 'render_field'],
                        'seokar-settings',
                        "seokar_{$tab_id}_{$section_id}_section",
                        $field
                    );
                }
            }
        }
    }

    /**
     * Sanitize options
     *
     * @param array $input
     * @return array
     */
    public function sanitize_options($input) {
        $output = get_option('seokar_options', []);
        $current_tab = $_POST['current_tab'] ?? 'general';

        if (!isset($this->tabs[$current_tab])) {
            return $output;
        }

        foreach ($this->tabs[$current_tab]['sections'] as $section) {
            foreach ($section['fields'] as $field_id => $field) {
                if (!isset($input[$field_id])) {
                    continue;
                }

                $value = $input[$field_id];

                if (isset($field['sanitize'])) {
                    if (is_callable($field['sanitize'])) {
                        $output[$field_id] = call_user_func($field['sanitize'], $value);
                    } elseif (is_callable([$this, $field['sanitize']])) {
                        $output[$field_id] = call_user_func([$this, $field['sanitize']], $value);
                    } else {
                        $output[$field_id] = sanitize_text_field($value);
                    }
                } else {
                    $output[$field_id] = sanitize_text_field($value);
                }
            }
        }

        return $output;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = $_GET['tab'] ?? 'general';
        ?>
        <div class="wrap seokar-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'seokar'); ?></p>
                </div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper">
                <?php foreach ($this->tabs as $tab_id => $tab) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seokar-settings&tab=' . $tab_id)); ?>" 
                       class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" enctype="multipart/form-data">
                <input type="hidden" name="current_tab" value="<?php echo esc_attr($active_tab); ?>">
                
                <?php
                settings_fields('seokar_options_group');
                do_settings_sections('seokar-settings');
                submit_button(__('Save Settings', 'seokar'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render section
     *
     * @param array $args
     */
    public function render_section($args) {
        $tab_id = str_replace(['seokar_', '_section'], '', $args['id']);
        $section_id = str_replace(['seokar_', '_section'], '', $args['id']);
        
        if (isset($this->tabs[$tab_id]['sections'][$section_id]['description'])) {
            echo '<p class="description">' . esc_html($this->tabs[$tab_id]['sections'][$section_id]['description']) . '</p>';
        }
    }

    /**
     * Render field
     *
     * @param array $args
     */
    public function render_field($args) {
        $options = get_option('seokar_options', []);
        $value = $options[$args['id']] ?? $args['default'] ?? '';
        $name = "seokar_options[{$args['id']}]";

        switch ($args['type']) {
            case 'text':
                echo '<input type="text" id="' . esc_attr($args['id']) . '" name="' . esc_attr($name) . '" 
                      value="' . esc_attr($value) . '" class="regular-text">';
                break;
                
            case 'textarea':
                echo '<textarea id="' . esc_attr($args['id']) . '" name="' . esc_attr($name) . '" 
                      class="large-text" rows="5">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'checkbox':
                echo '<label><input type="checkbox" id="' . esc_attr($args['id']) . '" name="' . esc_attr($name) . '" 
                      value="1" ' . checked(1, $value, false) . '> ' . esc_html($args['label']) . '</label>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($args['id']) . '" name="' . esc_attr($name) . '" class="regular-text">';
                foreach ($args['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected($option_value, $value, false) . '>' 
                         . esc_html($option_label) . '</option>';
                }
                echo '</select>';
                break;
        }

        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Render import field
     */
    public function render_import_field() {
        ?>
        <div class="seokar-import-export">
            <input type="file" name="seokar_import_file" accept=".json">
            <?php wp_nonce_field('seokar_import_settings', 'seokar_import_nonce'); ?>
            <input type="submit" name="seokar_import" class="button button-secondary" 
                   value="<?php esc_attr_e('Import Settings', 'seokar'); ?>">
            <p class="description">
                <?php _e('Import settings from a JSON file', 'seokar'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render export field
     */
    public function render_export_field() {
        $export_url = add_query_arg([
            'action' => 'seokar_export_settings',
            '_wpnonce' => wp_create_nonce('seokar_export_settings')
        ], admin_url('admin-post.php'));
        ?>
        <div class="seokar-import-export">
            <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">
                <?php _e('Export Settings', 'seokar'); ?>
            </a>
            <p class="description">
                <?php _e('Export current settings to a JSON file', 'seokar'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render reset field
     */
    public function render_reset_field() {
        ?>
        <div class="seokar-reset">
            <?php wp_nonce_field('seokar_reset_settings', 'seokar_reset_nonce'); ?>
            <input type="submit" name="seokar_reset" class="button button-secondary" 
                   value="<?php esc_attr_e('Reset to Defaults', 'seokar'); ?>"
                   onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset all settings to defaults?', 'seokar'); ?>')">
            <p class="description">
                <?php _e('Reset all settings to their default values', 'seokar'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Handle settings import
     */
    public function handle_import() {
        if (!isset($_POST['seokar_import_nonce']) || !wp_verify_nonce($_POST['seokar_import_nonce'], 'seokar_import_settings')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        if (!isset($_FILES['seokar_import_file']) || $_FILES['seokar_import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('error', 'import', admin_url('admin.php?page=seokar-settings&tab=tools')));
            exit;
        }

        $file = $_FILES['seokar_import_file']['tmp_name'];
        $content = file_get_contents($file);
        $settings = json_decode($content, true);

        if (empty($settings) || !is_array($settings)) {
            wp_redirect(add_query_arg('error', 'invalid', admin_url('admin.php?page=seokar-settings&tab=tools')));
            exit;
        }

        update_option('seokar_options', $settings);
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=seokar-settings&tab=tools')));
        exit;
    }

    /**
     * Handle settings reset
     */
    public function handle_reset() {
        if (!isset($_POST['seokar_reset_nonce']) || !wp_verify_nonce($_POST['seokar_reset_nonce'], 'seokar_reset_settings')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        $defaults = [];
        foreach ($this->tabs as $tab) {
            foreach ($tab['sections'] as $section) {
                foreach ($section['fields'] as $field_id => $field) {
                    if (isset($field['default'])) {
                        $defaults[$field_id] = $field['default'];
                    }
                }
            }
        }

        update_option('seokar_options', $defaults);
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=seokar-settings')));
        exit;
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_seokar-settings') {
            return;
        }

        wp_enqueue_style(
            'seokar-admin',
            SEOKAR_URL . '/admin/assets/css/admin.css',
            [],
            SEOKAR_VERSION
        );

        wp_enqueue_script(
            'seokar-admin',
            SEOKAR_URL . '/admin/assets/js/admin.js',
            ['jquery'],
            SEOKAR_VERSION,
            true
        );
    }
}
