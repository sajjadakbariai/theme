<?php
/**
 * SEOKAR for WordPress Themes - Social Media Module
 * 
 * @package    SeoKar
 * @subpackage Social
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Social implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Social media platforms
     *
     * @var array
     */
    private $platforms = [
        'facebook' => [
            'og' => true,
            'twitter' => false,
            'fields' => ['title', 'description', 'image', 'url']
        ],
        'twitter' => [
            'og' => false,
            'twitter' => true,
            'fields' => ['title', 'description', 'image', 'card', 'creator']
        ]
    ];

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('wp_head', [$this, 'generate_social_meta'], 1);
        add_action('add_meta_boxes', [$this, 'add_social_meta_box']);
        add_action('save_post', [$this, 'save_social_meta'], 10, 2);
        add_filter('seokar_social_meta', [$this, 'filter_social_meta'], 10, 2);
    }

    /**
     * Generate social meta tags
     */
    public function generate_social_meta() {
        if (is_singular()) {
            $this->generate_og_tags();
            $this->generate_twitter_card();
        } elseif (is_home() || is_front_page()) {
            $this->generate_homepage_social_meta();
        }
    }

    /**
     * Generate Open Graph meta tags
     */
    private function generate_og_tags() {
        $meta = $this->get_social_meta();

        echo '<!-- Open Graph Meta -->' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '" />' . "\n";
        echo '<meta property="og:type" content="' . esc_attr($this->get_og_type()) . '" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($meta['title']) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($meta['url']) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";

        if (!empty($meta['image'])) {
            echo '<meta property="og:image" content="' . esc_url($meta['image']) . '" />' . "\n";
            echo '<meta property="og:image:width" content="1200" />' . "\n";
            echo '<meta property="og:image:height" content="630" />' . "\n";
            echo '<meta property="og:image:alt" content="' . esc_attr($meta['title']) . '" />' . "\n";
        }

        // Article specific tags
        if (is_singular('post')) {
            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '" />' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '" />' . "\n";
            
            // Article author and section
            $author = get_the_author_meta('display_name');
            if ($author) {
                echo '<meta property="article:author" content="' . esc_attr($author) . '" />' . "\n";
            }

            $categories = get_the_category();
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    echo '<meta property="article:section" content="' . esc_attr($category->name) . '" />' . "\n";
                }
            }

            $tags = get_the_tags();
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '" />' . "\n";
                }
            }
        }
    }

    /**
     * Generate Twitter Card meta tags
     */
    private function generate_twitter_card() {
        $meta = $this->get_social_meta();
        $options = get_option('seokar_options');

        echo '<!-- Twitter Card Meta -->' . "\n";
        echo '<meta name="twitter:card" content="' . esc_attr($this->get_twitter_card_type()) . '" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($meta['title']) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '" />' . "\n";

        if (!empty($meta['image'])) {
            echo '<meta name="twitter:image" content="' . esc_url($meta['image']) . '" />' . "\n";
            echo '<meta name="twitter:image:alt" content="' . esc_attr($meta['title']) . '" />' . "\n";
        }

        // Twitter site and creator
        if (!empty($options['twitter_site'])) {
            echo '<meta name="twitter:site" content="' . esc_attr($options['twitter_site']) . '" />' . "\n";
        }

        if (is_singular('post') && !empty($options['twitter_creator'])) {
            $author_twitter = get_the_author_meta('twitter', get_the_author_meta('ID'));
            if ($author_twitter) {
                echo '<meta name="twitter:creator" content="' . esc_attr($author_twitter) . '" />' . "\n";
            } elseif (!empty($options['twitter_creator'])) {
                echo '<meta name="twitter:creator" content="' . esc_attr($options['twitter_creator']) . '" />' . "\n";
            }
        }
    }

    /**
     * Generate homepage social meta
     */
    private function generate_homepage_social_meta() {
        $options = get_option('seokar_options');

        echo '<!-- Open Graph Meta -->' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(get_bloginfo('description')) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(home_url('/')) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";

        if (!empty($options['social_image'])) {
            echo '<meta property="og:image" content="' . esc_url($options['social_image']) . '" />' . "\n";
            echo '<meta property="og:image:width" content="1200" />' . "\n";
            echo '<meta property="og:image:height" content="630" />' . "\n";
        }

        echo '<!-- Twitter Card Meta -->' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(get_bloginfo('description')) . '" />' . "\n";

        if (!empty($options['social_image'])) {
            echo '<meta name="twitter:image" content="' . esc_url($options['social_image']) . '" />' . "\n";
        }

        if (!empty($options['twitter_site'])) {
            echo '<meta name="twitter:site" content="' . esc_attr($options['twitter_site']) . '" />' . "\n";
        }
    }

    /**
     * Get social meta data
     *
     * @return array
     */
    private function get_social_meta() {
        $post_id = get_the_ID();
        $defaults = [
            'title' => $this->seokar->get_module('core')->generate_title($post_id),
            'description' => $this->seokar->get_module('core')->generate_description($post_id),
            'url' => get_permalink($post_id),
            'image' => $this->get_social_image($post_id)
        ];

        // Get custom social meta
        $custom_meta = [
            'title' => get_post_meta($post_id, '_seokar_social_title', true),
            'description' => get_post_meta($post_id, '_seokar_social_description', true),
            'image' => get_post_meta($post_id, '_seokar_social_image', true)
        ];

        // Merge defaults with custom meta
        $meta = [];
        foreach ($defaults as $key => $value) {
            $meta[$key] = !empty($custom_meta[$key]) ? $custom_meta[$key] : $value;
        }

        return apply_filters('seokar_social_meta', $meta, $post_id);
    }

    /**
     * Filter social meta
     *
     * @param array $meta
     * @param int $post_id
     * @return array
     */
    public function filter_social_meta($meta, $post_id) {
        // Trim title to 60 characters for social
        $meta['title'] = mb_substr($meta['title'], 0, 60);

        // Trim description to 160 characters for social
        $meta['description'] = mb_substr($meta['description'], 0, 160);

        return $meta;
    }

    /**
     * Get social image
     *
     * @param int $post_id
     * @return string
     */
    private function get_social_image($post_id) {
        // Custom social image
        $custom_image = get_post_meta($post_id, '_seokar_social_image', true);
        if (!empty($custom_image)) {
            return $custom_image;
        }

        // Featured image
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'seokar-social');
        }

        // First image in content
        $first_image = $this->get_first_content_image($post_id);
        if (!empty($first_image)) {
            return $first_image;
        }

        // Default social image
        $options = get_option('seokar_options');
        if (!empty($options['social_image'])) {
            return $options['social_image'];
        }

        return '';
    }

    /**
     * Get first image from post content
     *
     * @param int $post_id
     * @return string
     */
    private function get_first_content_image($post_id) {
        $post = get_post($post_id);
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
        
        if (isset($matches[1][0])) {
            return $matches[1][0];
        }

        return '';
    }

    /**
     * Get Open Graph type
     *
     * @return string
     */
    private function get_og_type() {
        if (is_singular('post')) {
            return 'article';
        } elseif (is_singular('page')) {
            return 'website';
        } elseif (is_singular('product')) {
            return 'product';
        } else {
            return 'website';
        }
    }

    /**
     * Get Twitter Card type
     *
     * @return string
     */
    private function get_twitter_card_type() {
        $image = $this->get_social_image(get_the_ID());
        $options = get_option('seokar_options');

        if (!empty($image) && (!isset($options['twitter_card_type']) || $options['twitter_card_type'] === 'summary_large_image')) {
            return 'summary_large_image';
        }

        return 'summary';
    }

    /**
     * Add social meta box
     */
    public function add_social_meta_box() {
        $post_types = get_post_types(['public' => true]);

        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_social_meta_box',
                __('Social Media Settings', 'seokar'),
                [$this, 'render_social_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render social meta box
     *
     * @param WP_Post $post
     */
    public function render_social_meta_box($post) {
        wp_nonce_field('seokar_save_social_meta', 'seokar_social_meta_nonce');

        $values = [
            'title' => get_post_meta($post->ID, '_seokar_social_title', true),
            'description' => get_post_meta($post->ID, '_seokar_social_description', true),
            'image' => get_post_meta($post->ID, '_seokar_social_image', true),
        ];

        ?>
        <div class="seokar-social-meta-box">
            <div class="seokar-field">
                <label for="seokar_social_title"><?php _e('Social Title', 'seokar'); ?></label>
                <input type="text" id="seokar_social_title" name="seokar_social[title]" 
                       value="<?php echo esc_attr($values['title']); ?>" 
                       class="widefat" />
                <p class="description"><?php _e('Custom title for social sharing (max 60 chars)', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_social_description"><?php _e('Social Description', 'seokar'); ?></label>
                <textarea id="seokar_social_description" name="seokar_social[description]" 
                          class="widefat" rows="3"><?php echo esc_textarea($values['description']); ?></textarea>
                <p class="description"><?php _e('Custom description for social sharing (max 160 chars)', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_social_image"><?php _e('Social Image', 'seokar'); ?></label>
                <input type="text" id="seokar_social_image" name="seokar_social[image]" 
                       value="<?php echo esc_url($values['image']); ?>" 
                       class="widefat" />
                <button type="button" class="button seokar-media-upload" 
                        data-target="#seokar_social_image"><?php _e('Upload Image', 'seokar'); ?></button>
                <p class="description"><?php _e('Recommended size: 1200x630 pixels', 'seokar'); ?></p>
                
                <?php if (!empty($values['image'])) : ?>
                    <div class="seokar-image-preview">
                        <img src="<?php echo esc_url($values['image']); ?>" style="max-width: 100%; height: auto;" />
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save social meta data
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_social_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['seokar_social_meta_nonce']) || !wp_verify_nonce($_POST['seokar_social_meta_nonce'], 'seokar_save_social_meta')) {
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
        if (isset($_POST['seokar_social'])) {
            $data = $_POST['seokar_social'];

            foreach ($data as $key => $value) {
                if ($key === 'image') {
                    $value = esc_url_raw($value);
                } else {
                    $value = sanitize_text_field($value);
                }

                if (empty($value)) {
                    delete_post_meta($post_id, "_seokar_social_{$key}");
                } else {
                    update_post_meta($post_id, "_seokar_social_{$key}", $value);
                }
            }
        }
    }
}
