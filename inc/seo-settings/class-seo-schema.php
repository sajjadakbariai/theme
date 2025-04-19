<?php
/**
 * SEOKAR for WordPress Themes - Schema Markup Module
 * 
 * @package    SeoKar
 * @subpackage Schema
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Schema implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Schema types
     *
     * @var array
     */
    private $schema_types = [
        'Article' => ['post'],
        'WebPage' => ['page'],
        'Product' => ['product'],
        'LocalBusiness' => [],
        'Person' => [],
        'Organization' => [],
        'BreadcrumbList' => [],
        'WebSite' => []
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
        add_action('wp_head', [$this, 'output_schema_markup'], 1);
        add_action('add_meta_boxes', [$this, 'add_schema_meta_box']);
        add_action('save_post', [$this, 'save_schema_meta'], 10, 2);
        add_filter('seokar_schema_data', [$this, 'filter_schema_data'], 10, 2);
    }

    /**
     * Output schema markup
     */
    public function output_schema_markup() {
        $schema = $this->generate_schema();
        
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }

    /**
     * Generate schema data
     *
     * @return array
     */
    private function generate_schema() {
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];

        // Website schema
        $schema['@graph'][] = $this->generate_website_schema();

        // Organization/Person schema
        $schema['@graph'][] = $this->generate_publisher_schema();

        // Breadcrumb schema
        if (!is_front_page()) {
            $breadcrumb_schema = $this->generate_breadcrumb_schema();
            if (!empty($breadcrumb_schema)) {
                $schema['@graph'][] = $breadcrumb_schema;
            }
        }

        // Content specific schema
        $content_schema = $this->generate_content_schema();
        if (!empty($content_schema)) {
            $schema['@graph'][] = $content_schema;
        }

        // Custom schema
        $custom_schema = $this->get_custom_schema();
        if (!empty($custom_schema)) {
            $schema['@graph'] = array_merge($schema['@graph'], $custom_schema);
        }

        return apply_filters('seokar_schema_data', $schema);
    }

    /**
     * Generate website schema
     *
     * @return array
     */
    private function generate_website_schema() {
        $options = get_option('seokar_options');

        $schema = [
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url('/'),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'publisher' => [
                '@id' => home_url('/#publisher')
            ],
            'potentialAction' => [
                [
                    '@type' => 'SearchAction',
                    'target' => home_url('/?s={search_term_string}'),
                    'query-input' => 'required name=search_term_string'
                ]
            ]
        ];

        return $schema;
    }

    /**
     * Generate publisher schema (Organization/Person)
     *
     * @return array
     */
    private function generate_publisher_schema() {
        $options = get_option('seokar_options');
        $schema_type = (!empty($options['schema_type']) && $options['schema_type'] === 'Person') ? 'Person' : 'Organization';

        $schema = [
            '@type' => $schema_type,
            '@id' => home_url('/#publisher'),
            'name' => get_bloginfo('name'),
            'url' => home_url('/')
        ];

        if ($schema_type === 'Organization') {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => !empty($options['logo']) ? $options['logo'] : '',
                'width' => !empty($options['logo_width']) ? $options['logo_width'] : '',
                'height' => !empty($options['logo_height']) ? $options['logo_height'] : ''
            ];

            $schema['sameAs'] = [];
            if (!empty($options['social_profiles'])) {
                $profiles = explode("\n", $options['social_profiles']);
                foreach ($profiles as $profile) {
                    $profile = esc_url_raw(trim($profile));
                    if (!empty($profile)) {
                        $schema['sameAs'][] = $profile;
                    }
                }
            }
        } else {
            // Person schema
            $user = get_userdata(1); // Assuming first user is the main person
            if ($user) {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => get_avatar_url($user->ID, ['size' => 256])
                ];
            }
        }

        return $schema;
    }

    /**
     * Generate breadcrumb schema
     *
     * @return array
     */
    private function generate_breadcrumb_schema() {
        $breadcrumbs = $this->get_breadcrumb_items();
        
        if (empty($breadcrumbs)) {
            return [];
        }

        $schema = [
            '@type' => 'BreadcrumbList',
            '@id' => get_permalink() . '#breadcrumb',
            'itemListElement' => []
        ];

        $position = 1;
        foreach ($breadcrumbs as $breadcrumb) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $breadcrumb['title'],
                'item' => $breadcrumb['url']
            ];
            $position++;
        }

        return $schema;
    }

    /**
     * Generate content specific schema
     *
     * @return array
     */
    private function generate_content_schema() {
        if (is_singular('post')) {
            return $this->generate_article_schema();
        } elseif (is_singular('page')) {
            return $this->generate_webpage_schema();
        } elseif (is_singular('product')) {
            return $this->generate_product_schema();
        } elseif (is_author()) {
            return $this->generate_author_schema();
        }

        return [];
    }

    /**
     * Generate article schema
     *
     * @return array
     */
    private function generate_article_schema() {
        $post = get_post();
        $author = get_userdata($post->post_author);
        $image = $this->get_post_image($post->ID);
        $keywords = $this->seokar->get_module('core')->generate_keywords($post->ID);

        $schema = [
            '@type' => 'Article',
            '@id' => get_permalink() . '#article',
            'headline' => get_the_title(),
            'description' => $this->seokar->get_module('core')->generate_description($post->ID),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink()
            ],
            'author' => [
                '@type' => 'Person',
                '@id' => home_url('/#/schema/person/' . $author->user_nicename),
                'name' => $author->display_name
            ],
            'publisher' => [
                '@id' => home_url('/#publisher')
            ]
        ];

        if (!empty($keywords)) {
            $schema['keywords'] = $keywords;
        }

        if (!empty($image)) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $image,
                'width' => 1200,
                'height' => 630
            ];
        }

        // Article categories
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $schema['articleSection'] = [];
            foreach ($categories as $category) {
                $schema['articleSection'][] = $category->name;
            }
        }

        return $schema;
    }

    /**
     * Generate webpage schema
     *
     * @return array
     */
    private function generate_webpage_schema() {
        $post = get_post();

        $schema = [
            '@type' => 'WebPage',
            '@id' => get_permalink() . '#webpage',
            'url' => get_permalink(),
            'name' => get_the_title(),
            'description' => $this->seokar->get_module('core')->generate_description($post->ID),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'isPartOf' => [
                '@id' => home_url('/#website')
            ],
            'about' => [
                '@id' => home_url('/#publisher')
            ]
        ];

        $image = $this->get_post_image($post->ID);
        if (!empty($image)) {
            $schema['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $image
            ];
        }

        return $schema;
    }

    /**
     * Generate author schema
     *
     * @return array
     */
    private function generate_author_schema() {
        $author = get_queried_object();

        $schema = [
            '@type' => 'Person',
            '@id' => home_url('/#/schema/person/' . $author->user_nicename),
            'name' => $author->display_name,
            'description' => get_the_author_meta('description', $author->ID),
            'url' => get_author_posts_url($author->ID)
        ];

        $image = get_avatar_url($author->ID, ['size' => 256]);
        if (!empty($image)) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $image
            ];
        }

        $sameAs = [];
        $social_fields = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest'];
        foreach ($social_fields as $field) {
            $url = get_the_author_meta($field, $author->ID);
            if (!empty($url)) {
                $sameAs[] = $url;
            }
        }

        if (!empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * Get breadcrumb items
     *
     * @return array
     */
    private function get_breadcrumb_items() {
        $items = [];
        $items[] = [
            'title' => __('Home', 'seokar'),
            'url' => home_url('/')
        ];

        if (is_singular('post')) {
            $categories = get_the_category();
            if (!empty($categories)) {
                $primary_cat = $categories[0];
                $items[] = [
                    'title' => $primary_cat->name,
                    'url' => get_category_link($primary_cat)
                ];
            }
            $items[] = [
                'title' => get_the_title(),
                'url' => get_permalink()
            ];
        } elseif (is_category()) {
            $items[] = [
                'title' => single_cat_title('', false),
                'url' => get_category_link(get_queried_object_id())
            ];
        } elseif (is_page()) {
            $ancestors = get_post_ancestors(get_queried_object_id());
            if (!empty($ancestors)) {
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor) {
                    $items[] = [
                        'title' => get_the_title($ancestor),
                        'url' => get_permalink($ancestor)
                    ];
                }
            }
            $items[] = [
                'title' => get_the_title(),
                'url' => get_permalink()
            ];
        }

        return $items;
    }

    /**
     * Get post image for schema
     *
     * @param int $post_id
     * @return string
     */
    private function get_post_image($post_id) {
        // Featured image
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'full');
        }

        // First image in content
        $post = get_post($post_id);
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
        
        if (isset($matches[1][0])) {
            return $matches[1][0];
        }

        return '';
    }

    /**
     * Get custom schema data
     *
     * @return array
     */
    private function get_custom_schema() {
        $post_id = get_the_ID();
        $custom_schema = get_post_meta($post_id, '_seokar_custom_schema', true);

        if (empty($custom_schema)) {
            return [];
        }

        return json_decode($custom_schema, true);
    }

    /**
     * Filter schema data
     *
     * @param array $schema
     * @return array
     */
    public function filter_schema_data($schema) {
        // Remove empty values
        array_walk_recursive($schema, function(&$value, $key) {
            if (empty($value)) {
                $value = null;
            }
        });

        return $schema;
    }

    /**
     * Add schema meta box
     */
    public function add_schema_meta_box() {
        $post_types = get_post_types(['public' => true]);

        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_schema_meta_box',
                __('Schema Markup', 'seokar'),
                [$this, 'render_schema_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render schema meta box
     *
     * @param WP_Post $post
     */
    public function render_schema_meta_box($post) {
        wp_nonce_field('seokar_save_schema_meta', 'seokar_schema_meta_nonce');

        $custom_schema = get_post_meta($post->ID, '_seokar_custom_schema', true);
        $schema_type = get_post_meta($post->ID, '_seokar_schema_type', true);

        ?>
        <div class="seokar-schema-meta-box">
            <div class="seokar-field">
                <label for="seokar_schema_type"><?php _e('Schema Type', 'seokar'); ?></label>
                <select id="seokar_schema_type" name="seokar_schema[type]" class="widefat">
                    <option value=""><?php _e('Auto Detect', 'seokar'); ?></option>
                    <?php foreach ($this->schema_types as $type => $post_types) : ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected($schema_type, $type); ?>>
                            <?php echo esc_html($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_custom_schema"><?php _e('Custom Schema', 'seokar'); ?></label>
                <textarea id="seokar_custom_schema" name="seokar_schema[custom_schema]" 
                          class="widefat" rows="10"><?php echo esc_textarea($custom_schema); ?></textarea>
                <p class="description">
                    <?php _e('Enter custom schema markup in JSON format. This will be merged with the default schema.', 'seokar'); ?>
                </p>
            </div>
            
            <div class="seokar-field">
                <p><strong><?php _e('Current Schema:', 'seokar'); ?></strong></p>
                <pre><?php echo esc_html(wp_json_encode($this->generate_content_schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Save schema meta data
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_schema_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['seokar_schema_meta_nonce']) || !wp_verify_nonce($_POST['seokar_schema_meta_nonce'], 'seokar_save_schema_meta')) {
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
        if (isset($_POST['seokar_schema'])) {
            $data = $_POST['seokar_schema'];

            // Save schema type
            if (!empty($data['type'])) {
                update_post_meta($post_id, '_seokar_schema_type', sanitize_text_field($data['type']));
            } else {
                delete_post_meta($post_id, '_seokar_schema_type');
            }

            // Save custom schema
            if (!empty($data['custom_schema'])) {
                // Validate JSON
                $json = json_decode(stripslashes($data['custom_schema']));
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_post_meta($post_id, '_seokar_custom_schema', wp_slash($data['custom_schema']));
                }
            } else {
                delete_post_meta($post_id, '_seokar_custom_schema');
            }
        }
    }
}
