<?php
/**
 * SEOKAR for WordPress Themes - Core SEO Module
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

class SEOKAR_Core implements SEOKAR_Module_Interface {
    
    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;
    
    /**
     * Meta title separator
     *
     * @var string
     */
    private $separator = '|';
    
    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->setup_hooks();
        $this->init();
    }
    
    /**
     * Initialize module
     */
    public function init() {
        $this->separator = $this->get_separator();
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Frontend hooks
        add_action('wp_head', [$this, 'output_meta_tags'], 1);
        add_filter('pre_get_document_title', [$this, 'filter_document_title'], 15);
        add_filter('wp_title', [$this, 'filter_wp_title'], 15, 3);
        
        // Admin hooks
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_data'], 10, 2);
        add_action('edit_category', [$this, 'save_taxonomy_meta']);
        add_action('edited_term', [$this, 'save_taxonomy_meta'], 10, 3);
        
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_fields']);
    }
    
    /**
     * Get meta title separator
     *
     * @return string
     */
    public function get_separator() {
        $options = get_option('seokar_options');
        return $options['separator'] ?? $this->separator;
    }
    
    /**
     * Filter document title
     *
     * @param string $title
     * @return string
     */
    public function filter_document_title($title) {
        if (is_feed()) {
            return $title;
        }
        
        return $this->generate_title();
    }
    
    /**
     * Filter wp_title
     *
     * @param string $title
     * @param string $sep
     * @param string $seplocation
     * @return string
     */
    public function filter_wp_title($title, $sep, $seplocation) {
        if (is_feed()) {
            return $title;
        }
        
        return $this->generate_title();
    }
    
    /**
     * Generate meta title
     *
     * @param int|null $post_id
     * @return string
     */
    public function generate_title($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Custom title
        $custom_title = $this->get_meta_value('title', $post_id);
        if (!empty($custom_title)) {
            return $this->process_title($custom_title, $post_id);
        }
        
        // Default patterns
        if (is_singular()) {
            $title = single_post_title('', false);
        } elseif (is_home() || is_front_page()) {
            $title = get_bloginfo('name');
            $description = get_bloginfo('description');
            if (!empty($description)) {
                $title .= " {$this->separator} {$description}";
            }
            return $title;
        } elseif (is_category()) {
            $title = single_cat_title('', false);
        } elseif (is_tag()) {
            $title = single_tag_title('', false);
        } elseif (is_tax()) {
            $title = single_term_title('', false);
        } elseif (is_post_type_archive()) {
            $title = post_type_archive_title('', false);
        } elseif (is_author()) {
            $title = get_the_author();
        } elseif (is_year()) {
            $title = get_the_date(_x('Y', 'yearly archives date format'));
        } elseif (is_month()) {
            $title = get_the_date(_x('F Y', 'monthly archives date format'));
        } elseif (is_day()) {
            $title = get_the_date(_x('F j, Y', 'daily archives date format'));
        } elseif (is_search()) {
            $title = sprintf(__('Search Results for: %s', 'seokar'), get_search_query());
        } elseif (is_404()) {
            $title = __('Page not found', 'seokar');
        } else {
            $title = get_bloginfo('name');
        }
        
        // Add site name if not empty
        if (!empty($title)) {
            $title .= " {$this->separator} " . get_bloginfo('name');
        } else {
            $title = get_bloginfo('name');
        }
        
        return $this->process_title($title, $post_id);
    }
    
    /**
     * Process title variables
     *
     * @param string $title
     * @param int $post_id
     * @return string
     */
    private function process_title($title, $post_id) {
        $replacements = [
            '%%sitename%%' => get_bloginfo('name'),
            '%%sitedesc%%' => get_bloginfo('description'),
            '%%title%%' => get_the_title($post_id),
            '%%page%%' => get_query_var('paged') ? sprintf(__('Page %s', 'seokar'), get_query_var('paged')) : '',
            '%%primary_category%%' => $this->get_primary_category($post_id),
            '%%category%%' => $this->get_category_name(),
            '%%tag%%' => $this->get_tag_name(),
            '%%term%%' => $this->get_term_name(),
            '%%search%%' => get_search_query(),
            '%%date%%' => get_the_date('', $post_id),
            '%%author%%' => get_the_author_meta('display_name', get_post_field('post_author', $post_id)),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $title);
    }
    
    /**
     * Output meta tags
     */
    public function output_meta_tags() {
        // Title tag is handled by WordPress filters
        $this->output_meta_description();
        $this->output_meta_keywords();
        $this->output_canonical_url();
        $this->output_robots_meta();
    }
    
    /**
     * Output meta description
     */
    private function output_meta_description() {
        $description = $this->generate_description();
        if (!empty($description)) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    /**
     * Generate meta description
     *
     * @param int|null $post_id
     * @return string
     */
    public function generate_description($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Custom description
        $custom_desc = $this->get_meta_value('description', $post_id);
        if (!empty($custom_desc)) {
            return SEOKAR_Sanitize::description($custom_desc);
        }
        
        // Default descriptions
        if (is_singular()) {
            $post = get_post($post_id);
            $description = wp_strip_all_tags($post->post_excerpt ?: $post->post_content);
            $description = str_replace(["\r", "\n"], ' ', $description);
            return SEOKAR_Sanitize::description($description);
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            return SEOKAR_Sanitize::description(term_description($term->term_id, $term->taxonomy));
        } elseif (is_author()) {
            return SEOKAR_Sanitize::description(get_the_author_meta('description', get_queried_object_id()));
        } elseif (is_post_type_archive()) {
            $post_type = get_post_type_object(get_query_var('post_type'));
            return SEOKAR_Sanitize::description($post_type->description);
        }
        
        return '';
    }
    
    /**
     * Output meta keywords
     */
    private function output_meta_keywords() {
        $keywords = $this->generate_keywords();
        if (!empty($keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
    }
    
    /**
     * Generate meta keywords
     *
     * @param int|null $post_id
     * @return string
     */
    public function generate_keywords($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Custom keywords
        $custom_keywords = $this->get_meta_value('keywords', $post_id);
        if (!empty($custom_keywords)) {
            return SEOKAR_Sanitize::keywords($custom_keywords);
        }
        
        // Default keywords
        $keywords = [];
        
        if (is_singular()) {
            // Post tags
            $tags = get_the_tags($post_id);
            if ($tags) {
                foreach ($tags as $tag) {
                    $keywords[] = $tag->name;
                }
            }
            
            // Categories
            $categories = get_the_category($post_id);
            if ($categories) {
                foreach ($categories as $category) {
                    $keywords[] = $category->name;
                }
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $keywords[] = $term->name;
        }
        
        // Global keywords
        $options = get_option('seokar_options');
        if (!empty($options['global_keywords'])) {
            $global_keywords = explode(',', $options['global_keywords']);
            $keywords = array_merge($keywords, $global_keywords);
        }
        
        return !empty($keywords) ? SEOKAR_Sanitize::keywords(implode(', ', $keywords)) : '';
    }
    
    /**
     * Output canonical URL
     */
    private function output_canonical_url() {
        $canonical = $this->get_canonical_url();
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        }
    }
    
    /**
     * Get canonical URL
     *
     * @return string
     */
    public function get_canonical_url() {
        global $wp;
        
        // Custom canonical
        $custom_canonical = $this->get_meta_value('canonical');
        if (!empty($custom_canonical)) {
            return $custom_canonical;
        }
        
        // Default canonical
        if (is_singular()) {
            $canonical = get_permalink(get_queried_object_id());
            
            // Handle pagination
            $page = get_query_var('page');
            if ($page > 1) {
                $canonical = trailingslashit($canonical) . user_trailingslashit($page, 'single_paged');
            }
            
            return $canonical;
        } elseif (is_home() || is_front_page()) {
            return home_url('/');
        } elseif (is_archive()) {
            return get_post_type_archive_link(get_post_type());
        } elseif (is_search()) {
            return home_url('/?s=' . get_search_query());
        }
        
        return home_url($wp->request);
    }
    
    /**
     * Output robots meta
     */
    private function output_robots_meta() {
        $robots = $this->get_robots_meta();
        if (!empty($robots)) {
            echo '<meta name="robots" content="' . esc_attr(implode(',', $robots)) . '">' . "\n";
        }
    }
    
    /**
     * Get robots meta
     *
     * @return array
     */
    public function get_robots_meta() {
        $robots = [];
        
        // Custom robots
        $custom_robots = $this->get_meta_value('robots');
        if (!empty($custom_robots)) {
            return explode(',', $custom_robots);
        }
        
        // Default robots
        if (is_category() || is_tag() || is_tax() || is_post_type_archive() || is_date() || is_author()) {
            $options = get_option('seokar_options');
            $robots[] = $options['noindex_archives'] ?? false ? 'noindex' : 'index';
        } elseif (is_search() || is_404()) {
            $robots[] = 'noindex';
        } else {
            $robots[] = 'index';
        }
        
        $robots[] = 'follow';
        
        return $robots;
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_meta_box',
                __('SEO Settings', 'seokar'),
                [$this, 'render_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Render meta box
     *
     * @param WP_Post $post
     */
    public function render_meta_box($post) {
        wp_nonce_field('seokar_save_meta', 'seokar_meta_nonce');
        
        $values = [
            'title' => $this->get_meta_value('title', $post->ID),
            'description' => $this->get_meta_value('description', $post->ID),
            'keywords' => $this->get_meta_value('keywords', $post->ID),
            'canonical' => $this->get_meta_value('canonical', $post->ID),
            'robots' => $this->get_meta_value('robots', $post->ID),
        ];
        
        ?>
        <div class="seokar-meta-box">
            <div class="seokar-field">
                <label for="seokar_title"><?php _e('Title', 'seokar'); ?></label>
                <input type="text" id="seokar_title" name="seokar[title]" 
                       value="<?php echo esc_attr($values['title']); ?>" 
                       class="widefat" />
                <p class="description"><?php _e('Custom title for this page', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_description"><?php _e('Description', 'seokar'); ?></label>
                <textarea id="seokar_description" name="seokar[description]" 
                          class="widefat" rows="3"><?php echo esc_textarea($values['description']); ?></textarea>
                <p class="description"><?php _e('Custom meta description', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_keywords"><?php _e('Keywords', 'seokar'); ?></label>
                <input type="text" id="seokar_keywords" name="seokar[keywords]" 
                       value="<?php echo esc_attr($values['keywords']); ?>" 
                       class="widefat" />
                <p class="description"><?php _e('Comma separated list of keywords', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_canonical"><?php _e('Canonical URL', 'seokar'); ?></label>
                <input type="url" id="seokar_canonical" name="seokar[canonical]" 
                       value="<?php echo esc_url($values['canonical']); ?>" 
                       class="widefat" />
                <p class="description"><?php _e('Override the canonical URL for this page', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_robots"><?php _e('Robots Meta', 'seokar'); ?></label>
                <select id="seokar_robots" name="seokar[robots]" class="widefat">
                    <option value=""><?php _e('Default', 'seokar'); ?></option>
                    <option value="index,follow" <?php selected($values['robots'], 'index,follow'); ?>>
                        <?php _e('Index, Follow', 'seokar'); ?>
                    </option>
                    <option value="noindex,follow" <?php selected($values['robots'], 'noindex,follow'); ?>>
                        <?php _e('Noindex, Follow', 'seokar'); ?>
                    </option>
                    <option value="index,nofollow" <?php selected($values['robots'], 'index,nofollow'); ?>>
                        <?php _e('Index, Nofollow', 'seokar'); ?>
                    </option>
                    <option value="noindex,nofollow" <?php selected($values['robots'], 'noindex,nofollow'); ?>>
                        <?php _e('Noindex, Nofollow', 'seokar'); ?>
                    </option>
                </select>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta data
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_meta_data($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['seokar_meta_nonce']) || !wp_verify_nonce($_POST['seokar_meta_nonce'], 'seokar_save_meta')) {
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save data
        if (isset($_POST['seokar'])) {
            $data = $_POST['seokar'];
            
            foreach ($data as $key => $value) {
                $value = sanitize_text_field($value);
                
                if (empty($value)) {
                    delete_post_meta($post_id, "_seokar_{$key}");
                } else {
                    update_post_meta($post_id, "_seokar_{$key}", $value);
                }
            }
        }
    }
    
    /**
     * Save taxonomy meta
     *
     * @param int $term_id
     */
    public function save_taxonomy_meta($term_id) {
        if (!isset($_POST['seokar_tax_nonce']) || !wp_verify_nonce($_POST['seokar_tax_nonce'], 'seokar_save_tax_meta')) {
            return;
        }
        
        if (isset($_POST['seokar'])) {
            $data = $_POST['seokar'];
            
            foreach ($data as $key => $value) {
                $value = sanitize_text_field($value);
                
                if (empty($value)) {
                    delete_term_meta($term_id, "_seokar_{$key}");
                } else {
                    update_term_meta($term_id, "_seokar_{$key}", $value);
                }
            }
        }
    }
    
    /**
     * Register REST fields
     */
    public function register_rest_fields() {
        register_rest_field(
            ['post', 'page'],
            'seokar_meta',
            [
                'get_callback' => [$this, 'get_rest_meta'],
                'update_callback' => [$this, 'update_rest_meta'],
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'keywords' => ['type' => 'string'],
                        'canonical' => ['type' => 'string'],
                        'robots' => ['type' => 'string'],
                    ],
                ],
            ]
        );
    }
    
    /**
     * Get meta value
     *
     * @param string $key
     * @param int|null $post_id
     * @return mixed
     */
    private function get_meta_value($key, $post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (is_singular()) {
            return get_post_meta($post_id, "_seokar_{$key}", true);
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            return get_term_meta($term->term_id, "_seokar_{$key}", true);
        }
        
        return '';
    }
    
    /**
     * Get primary category
     *
     * @param int $post_id
     * @return string
     */
    private function get_primary_category($post_id) {
        $categories = get_the_category($post_id);
        if (empty($categories)) {
            return '';
        }
        
        // Check for Yoast primary category
        $primary = get_post_meta($post_id, '_yoast_wpseo_primary_category', true);
        if ($primary) {
            foreach ($categories as $category) {
                if ($category->term_id == $primary) {
                    return $category->name;
                }
            }
        }
        
        return $categories[0]->name;
    }
    
    /**
     * Get category name
     *
     * @return string
     */
    private function get_category_name() {
        if (is_category()) {
            return single_cat_title('', false);
        }
        return '';
    }
    
    /**
     * Get tag name
     *
     * @return string
     */
    private function get_tag_name() {
        if (is_tag()) {
            return single_tag_title('', false);
        }
        return '';
    }
    
    /**
     * Get term name
     *
     * @return string
     */
    private function get_term_name() {
        if (is_tax()) {
            return single_term_title('', false);
        }
        return '';
    }
}
