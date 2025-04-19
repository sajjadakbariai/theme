<?php
/**
 * SEOKAR for WordPress Themes - Sitemap Module
 * 
 * @package    SeoKar
 * @subpackage Sitemap
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Sitemap implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Sitemap settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->setup_hooks();
        $this->init_settings();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('rewrite_rules_array', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'render_sitemap']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('seokar_daily_tasks', [$this, 'generate_sitemap_file']);
    }

    /**
     * Initialize settings
     */
    private function init_settings() {
        $this->settings = wp_parse_args(
            get_option('seokar_sitemap_options', []),
            $this->get_default_settings()
        );
    }

    /**
     * Get default settings
     *
     * @return array
     */
    private function get_default_settings() {
        return [
            'enable_index' => 1,
            'enable_xml' => 1,
            'enable_html' => 1,
            'enable_news' => 0,
            'enable_video' => 0,
            'enable_image' => 1,
            'post_types' => ['post', 'page'],
            'taxonomies' => ['category', 'post_tag'],
            'exclude_posts' => [],
            'exclude_terms' => [],
            'priority' => [
                'post' => 0.8,
                'page' => 0.7,
                'category' => 0.6,
                'post_tag' => 0.5
            ],
            'frequency' => [
                'post' => 'weekly',
                'page' => 'monthly',
                'category' => 'weekly',
                'post_tag' => 'weekly'
            ],
            'lastmod' => 'modified',
            'include_lastmod' => 1,
            'sitemap_name' => 'sitemap',
            'items_per_page' => 1000,
            'ping_search_engines' => 1
        ];
    }

    /**
     * Register rewrite rules
     */
    public function register_rewrite_rules() {
        add_rewrite_rule('^' . $this->settings['sitemap_name'] . '\.xml$', 'index.php?seokar_sitemap=index', 'top');
        add_rewrite_rule('^' . $this->settings['sitemap_name'] . '-([a-z]+)?-?([0-9]+)?\.xml$', 'index.php?seokar_sitemap=$matches[1]&seokar_sitemap_page=$matches[2]', 'top');
        add_rewrite_rule('^' . $this->settings['sitemap_name'] . '\.html$', 'index.php?seokar_sitemap=html', 'top');
    }

    /**
     * Add rewrite rules
     *
     * @param array $rules
     * @return array
     */
    public function add_rewrite_rules($rules) {
        $new_rules = [
            '^' . $this->settings['sitemap_name'] . '\.xml$' => 'index.php?seokar_sitemap=index',
            '^' . $this->settings['sitemap_name'] . '-([a-z]+)?-?([0-9]+)?\.xml$' => 'index.php?seokar_sitemap=$matches[1]&seokar_sitemap_page=$matches[2]',
            '^' . $this->settings['sitemap_name'] . '\.html$' => 'index.php?seokar_sitemap=html'
        ];

        return $new_rules + $rules;
    }

    /**
     * Render sitemap
     */
    public function render_sitemap() {
        $type = get_query_var('seokar_sitemap');
        $page = (int) get_query_var('seokar_sitemap_page') ?: 1;

        if (empty($type)) {
            return;
        }

        switch ($type) {
            case 'index':
                $this->render_sitemap_index();
                break;
            case 'html':
                $this->render_html_sitemap();
                break;
            case 'news':
                $this->render_news_sitemap();
                break;
            case 'video':
                $this->render_video_sitemap();
                break;
            default:
                $this->render_xml_sitemap($type, $page);
        }

        exit;
    }

    /**
     * Render sitemap index
     */
    private function render_sitemap_index() {
        if (!$this->settings['enable_index']) {
            return;
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Main sitemaps
        if ($this->settings['enable_xml']) {
            foreach ($this->settings['post_types'] as $post_type) {
                $count = $this->count_items($post_type);
                $pages = ceil($count / $this->settings['items_per_page']);

                for ($i = 1; $i <= $pages; $i++) {
                    echo '<sitemap>' . "\n";
                    echo '<loc>' . esc_url($this->get_sitemap_url($post_type, $i)) . '</loc>' . "\n";
                    echo '<lastmod>' . esc_html($this->get_lastmod_date()) . '</lastmod>' . "\n";
                    echo '</sitemap>' . "\n";
                }
            }

            foreach ($this->settings['taxonomies'] as $taxonomy) {
                $count = $this->count_terms($taxonomy);
                $pages = ceil($count / $this->settings['items_per_page']);

                for ($i = 1; $i <= $pages; $i++) {
                    echo '<sitemap>' . "\n";
                    echo '<loc>' . esc_url($this->get_sitemap_url($taxonomy, $i)) . '</loc>' . "\n";
                    echo '<lastmod>' . esc_html($this->get_lastmod_date()) . '</lastmod>' . "\n";
                    echo '</sitemap>' . "\n";
                }
            }
        }

        // Special sitemaps
        if ($this->settings['enable_news']) {
            echo '<sitemap>' . "\n";
            echo '<loc>' . esc_url($this->get_sitemap_url('news')) . '</loc>' . "\n";
            echo '<lastmod>' . esc_html($this->get_lastmod_date()) . '</lastmod>' . "\n";
            echo '</sitemap>' . "\n";
        }

        if ($this->settings['enable_video']) {
            echo '<sitemap>' . "\n";
            echo '<loc>' . esc_url($this->get_sitemap_url('video')) . '</loc>' . "\n";
            echo '<lastmod>' . esc_html($this->get_lastmod_date()) . '</lastmod>' . "\n";
            echo '</sitemap>' . "\n";
        }

        echo '</sitemapindex>';
    }

    /**
     * Render XML sitemap
     *
     * @param string $type
     * @param int $page
     */
    private function render_xml_sitemap($type, $page = 1) {
        if (!$this->settings['enable_xml']) {
            return;
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        
        if ($this->settings['enable_image']) {
            echo ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }
        
        echo '>' . "\n";

        if (post_type_exists($type)) {
            $this->render_post_type_sitemap($type, $page);
        } elseif (taxonomy_exists($type)) {
            $this->render_taxonomy_sitemap($type, $page);
        }

        echo '</urlset>';
    }

    /**
     * Render post type sitemap
     *
     * @param string $post_type
     * @param int $page
     */
    private function render_post_type_sitemap($post_type, $page = 1) {
        $posts = $this->get_posts($post_type, $page);

        foreach ($posts as $post) {
            echo '<url>' . "\n";
            echo '<loc>' . esc_url(get_permalink($post)) . '</loc>' . "\n";
            
            if ($this->settings['include_lastmod']) {
                echo '<lastmod>' . esc_html($this->get_post_lastmod($post)) . '</lastmod>' . "\n";
            }
            
            echo '<changefreq>' . esc_html($this->get_post_frequency($post)) . '</changefreq>' . "\n";
            echo '<priority>' . esc_html($this->get_post_priority($post)) . '</priority>' . "\n";
            
            // Image sitemap
            if ($this->settings['enable_image']) {
                $this->render_post_images($post);
            }
            
            echo '</url>' . "\n";
        }
    }

    /**
     * Render taxonomy sitemap
     *
     * @param string $taxonomy
     * @param int $page
     */
    private function render_taxonomy_sitemap($taxonomy, $page = 1) {
        $terms = $this->get_terms($taxonomy, $page);

        foreach ($terms as $term) {
            echo '<url>' . "\n";
            echo '<loc>' . esc_url(get_term_link($term)) . '</loc>' . "\n";
            
            if ($this->settings['include_lastmod']) {
                echo '<lastmod>' . esc_html($this->get_term_lastmod($term)) . '</lastmod>' . "\n";
            }
            
            echo '<changefreq>' . esc_html($this->get_term_frequency($term)) . '</changefreq>' . "\n";
            echo '<priority>' . esc_html($this->get_term_priority($term)) . '</priority>' . "\n";
            echo '</url>' . "\n";
        }
    }

    /**
     * Render post images for sitemap
     *
     * @param WP_Post $post
     */
    private function render_post_images($post) {
        $images = $this->get_post_images($post);
        
        foreach ($images as $image) {
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

    /**
     * Render HTML sitemap
     */
    private function render_html_sitemap() {
        if (!$this->settings['enable_html']) {
            return;
        }

        header('Content-Type: text/html; charset=UTF-8');
        
        echo '<!DOCTYPE html>';
        echo '<html lang="' . esc_attr(get_locale()) . '">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . esc_html__('HTML Sitemap', 'seokar') . ' - ' . esc_html(get_bloginfo('name')) . '</title>';
        echo '<style>' . $this->get_sitemap_styles() . '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="seokar-sitemap-container">';
        echo '<h1>' . esc_html__('HTML Sitemap', 'seokar') . '</h1>';
        
        // Post types
        foreach ($this->settings['post_types'] as $post_type) {
            $posts = $this->get_posts($post_type, -1);
            $obj = get_post_type_object($post_type);
            
            if (empty($posts)) {
                continue;
            }
            
            echo '<div class="seokar-sitemap-section">';
            echo '<h2>' . esc_html($obj->labels->name) . '</h2>';
            echo '<ul class="seokar-sitemap-list">';
            
            foreach ($posts as $post) {
                echo '<li>';
                echo '<a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a>';
                
                if ($this->settings['include_lastmod']) {
                    echo ' <span class="seokar-sitemap-date">' . esc_html($this->get_post_lastmod($post, get_option('date_format'))) . '</span>';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        // Taxonomies
        foreach ($this->settings['taxonomies'] as $taxonomy) {
            $terms = $this->get_terms($taxonomy, -1);
            $obj = get_taxonomy($taxonomy);
            
            if (empty($terms)) {
                continue;
            }
            
            echo '<div class="seokar-sitemap-section">';
            echo '<h2>' . esc_html($obj->labels->name) . '</h2>';
            echo '<ul class="seokar-sitemap-list">';
            
            foreach ($terms as $term) {
                echo '<li>';
                echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</body>';
        echo '</html>';
    }

    /**
     * Get sitemap styles
     *
     * @return string
     */
    private function get_sitemap_styles() {
        return '
            .seokar-sitemap-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
                font-family: Arial, sans-serif;
                line-height: 1.6;
            }
            .seokar-sitemap-section {
                margin-bottom: 30px;
            }
            .seokar-sitemap-section h2 {
                color: #333;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }
            .seokar-sitemap-list {
                list-style: none;
                padding: 0;
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 10px;
            }
            .seokar-sitemap-list li {
                margin: 5px 0;
            }
            .seokar-sitemap-list a {
                text-decoration: none;
                color: #0066cc;
            }
            .seokar-sitemap-list a:hover {
                text-decoration: underline;
            }
            .seokar-sitemap-date {
                color: #666;
                font-size: 0.9em;
                margin-left: 10px;
            }
        ';
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('seokar_sitemap_settings', 'seokar_sitemap_options', [$this, 'sanitize_settings']);

        add_settings_section(
            'seokar_sitemap_main_section',
            __('Sitemap Settings', 'seokar'),
            [$this, 'render_main_section'],
            'seokar-sitemap'
        );

        add_settings_field(
            'enable_xml',
            __('Enable XML Sitemap', 'seokar'),
            [$this, 'render_enable_xml_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );

        add_settings_field(
            'enable_html',
            __('Enable HTML Sitemap', 'seokar'),
            [$this, 'render_enable_html_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );

        add_settings_field(
            'post_types',
            __('Included Post Types', 'seokar'),
            [$this, 'render_post_types_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );

        add_settings_field(
            'taxonomies',
            __('Included Taxonomies', 'seokar'),
            [$this, 'render_taxonomies_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );

        add_settings_field(
            'priority',
            __('Default Priorities', 'seokar'),
            [$this, 'render_priority_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );

        add_settings_field(
            'frequency',
            __('Change Frequencies', 'seokar'),
            [$this, 'render_frequency_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );

        add_settings_field(
            'ping_search_engines',
            __('Ping Search Engines', 'seokar'),
            [$this, 'render_ping_field'],
            'seokar-sitemap',
            'seokar_sitemap_main_section'
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $output = $this->get_default_settings();

        // Simple checkboxes
        $checkboxes = [
            'enable_index',
            'enable_xml',
            'enable_html',
            'enable_news',
            'enable_video',
            'enable_image',
            'include_lastmod',
            'ping_search_engines'
        ];

        foreach ($checkboxes as $checkbox) {
            $output[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
        }

        // Post types and taxonomies
        $output['post_types'] = [];
        if (!empty($input['post_types']) && is_array($input['post_types'])) {
            foreach ($input['post_types'] as $post_type) {
                if (post_type_exists($post_type)) {
                    $output['post_types'][] = $post_type;
                }
            }
        }

        $output['taxonomies'] = [];
        if (!empty($input['taxonomies']) && is_array($input['taxonomies'])) {
            foreach ($input['taxonomies'] as $taxonomy) {
                if (taxonomy_exists($taxonomy)) {
                    $output['taxonomies'][] = $taxonomy;
                }
            }
        }

        // Priorities
        if (!empty($input['priority']) && is_array($input['priority'])) {
            foreach ($input['priority'] as $type => $value) {
                $value = (float) $value;
                if ($value >= 0 && $value <= 1) {
                    $output['priority'][$type] = $value;
                }
            }
        }

        // Frequencies
        $frequencies = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        if (!empty($input['frequency']) && is_array($input['frequency'])) {
            foreach ($input['frequency'] as $type => $value) {
                if (in_array($value, $frequencies)) {
                    $output['frequency'][$type] = $value;
                }
            }
        }

        // Excluded items
        $output['exclude_posts'] = [];
        if (!empty($input['exclude_posts']) && is_array($input['exclude_posts'])) {
            $output['exclude_posts'] = array_map('intval', $input['exclude_posts']);
        }

        $output['exclude_terms'] = [];
        if (!empty($input['exclude_terms']) && is_array($input['exclude_terms'])) {
            $output['exclude_terms'] = array_map('intval', $input['exclude_terms']);
        }

        // Other fields
        if (!empty($input['sitemap_name'])) {
            $output['sitemap_name'] = sanitize_title($input['sitemap_name']);
        }

        if (!empty($input['items_per_page'])) {
            $output['items_per_page'] = max(1, (int) $input['items_per_page']);
        }

        if (!empty($input['lastmod'])) {
            $output['lastmod'] = in_array($input['lastmod'], ['created', 'modified']) ? $input['lastmod'] : 'modified';
        }

        return $output;
    }

    /**
     * Generate sitemap file
     */
    public function generate_sitemap_file() {
        if (!$this->settings['enable_xml']) {
            return;
        }

        $sitemap_content = $this->get_sitemap_content();
        $file_path = ABSPATH . $this->settings['sitemap_name'] . '.xml';
        
        if (file_put_contents($file_path, $sitemap_content)) {
            $this->ping_search_engines();
        }
    }

    /**
     * Ping search engines
     */
    private function ping_search_engines() {
        if (!$this->settings['ping_search_engines']) {
            return;
        }

        $sitemap_url = home_url('/' . $this->settings['sitemap_name'] . '.xml');
        
        // Google
        wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url));
        
        // Bing
        wp_remote_get('https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url));
    }

    // Additional helper methods for getting posts, terms, priorities, etc.
    // These would include methods like get_posts(), get_terms(), get_post_priority(), etc.
    // They would handle the data retrieval and processing for the sitemap generation.
}
