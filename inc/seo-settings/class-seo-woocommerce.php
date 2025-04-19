<?php
/**
 * SEOKAR for WordPress Themes - WooCommerce Module
 * 
 * @package    SeoKar
 * @subpackage WooCommerce
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_WooCommerce implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        
        if (!class_exists('WooCommerce')) {
            return;
        }

        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Product Schema
        add_action('woocommerce_single_product_summary', [$this, 'output_product_schema'], 1);
        
        // SEO Meta
        add_filter('seokar_meta_title', [$this, 'filter_product_title'], 10, 2);
        add_filter('seokar_meta_description', [$this, 'filter_product_description'], 10, 2);
        
        // Breadcrumbs
        add_filter('seokar_breadcrumb_items', [$this, 'filter_product_breadcrumbs'], 10, 2);
        
        // Admin
        add_action('add_meta_boxes', [$this, 'add_product_meta_box'], 30);
        add_action('save_post_product', [$this, 'save_product_meta'], 10, 2);
        
        // Category/Tag SEO
        add_action('product_cat_add_form_fields', [$this, 'add_taxonomy_fields']);
        add_action('product_cat_edit_form_fields', [$this, 'edit_taxonomy_fields'], 10);
        add_action('edited_product_cat', [$this, 'save_taxonomy_fields'], 10, 1);
        add_action('create_product_cat', [$this, 'save_taxonomy_fields'], 10, 1);
    }

    /**
     * Output product schema markup
     */
    public function output_product_schema() {
        global $product;

        if (!$product) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            '@id' => get_permalink() . '#product',
            'name' => $this->get_product_title($product),
            'description' => $this->get_product_description($product),
            'url' => get_permalink(),
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->get_product_brand($product)
            ],
            'sku' => $product->get_sku(),
            'offers' => [
                '@type' => 'Offer',
                'url' => get_permalink(),
                'priceCurrency' => get_woocommerce_currency(),
                'price' => $product->get_price(),
                'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $product->is_in_stock() ? 
                    'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
            ]
        ];

        // Product image
        if ($product->get_image_id()) {
            $schema['image'] = wp_get_attachment_image_url($product->get_image_id(), 'full');
        }

        // Product reviews
        if ($product->get_review_count() > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_review_count()
            ];
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Filter product title
     *
     * @param string $title
     * @param int $post_id
     * @return string
     */
    public function filter_product_title($title, $post_id) {
        if ('product' !== get_post_type($post_id)) {
            return $title;
        }

        $product = wc_get_product($post_id);
        return $this->get_product_title($product);
    }

    /**
     * Get product title
     *
     * @param WC_Product $product
     * @return string
     */
    private function get_product_title($product) {
        $custom_title = get_post_meta($product->get_id(), '_seokar_title', true);
        
        if (!empty($custom_title)) {
            return $custom_title;
        }

        return $product->get_name();
    }

    /**
     * Filter product description
     *
     * @param string $description
     * @param int $post_id
     * @return string
     */
    public function filter_product_description($description, $post_id) {
        if ('product' !== get_post_type($post_id)) {
            return $description;
        }

        $product = wc_get_product($post_id);
        return $this->get_product_description($product);
    }

    /**
     * Get product description
     *
     * @param WC_Product $product
     * @return string
     */
    private function get_product_description($product) {
        $custom_desc = get_post_meta($product->get_id(), '_seokar_description', true);
        
        if (!empty($custom_desc)) {
            return $custom_desc;
        }

        $description = $product->get_short_description() ?: $product->get_description();
        return wp_strip_all_tags($description);
    }

    /**
     * Get product brand
     *
     * @param WC_Product $product
     * @return string
     */
    private function get_product_brand($product) {
        $brands = wp_get_post_terms($product->get_id(), 'product_brand');
        
        if (!empty($brands) && !is_wp_error($brands)) {
            return $brands[0]->name;
        }

        return get_bloginfo('name');
    }

    /**
     * Filter product breadcrumbs
     *
     * @param array $items
     * @param int $post_id
     * @return array
     */
    public function filter_product_breadcrumbs($items, $post_id) {
        if ('product' !== get_post_type($post_id)) {
            return $items;
        }

        $product = wc_get_product($post_id);
        $categories = wc_get_product_terms($post_id, 'product_cat', ['orderby' => 'parent']);

        if (!empty($categories)) {
            $main_category = $categories[0];
            $category_chain = get_ancestors($main_category->term_id, 'product_cat');
            $category_chain = array_reverse($category_chain);
            $category_chain[] = $main_category->term_id;

            foreach ($category_chain as $category_id) {
                $category = get_term($category_id, 'product_cat');
                $items[] = [
                    'title' => $category->name,
                    'url' => get_term_link($category)
                ];
            }
        }

        $items[] = [
            'title' => $product->get_name(),
            'url' => get_permalink($post_id)
        ];

        return $items;
    }

    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'seokar_product_meta',
            __('SEO Settings', 'seokar'),
            [$this, 'render_product_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render product meta box
     *
     * @param WP_Post $post
     */
    public function render_product_meta_box($post) {
        wp_nonce_field('seokar_save_product_meta', 'seokar_product_meta_nonce');

        $values = [
            'title' => get_post_meta($post->ID, '_seokar_title', true),
            'description' => get_post_meta($post->ID, '_seokar_description', true),
            'keywords' => get_post_meta($post->ID, '_seokar_keywords', true),
            'canonical' => get_post_meta($post->ID, '_seokar_canonical', true),
            'robots' => get_post_meta($post->ID, '_seokar_robots', true)
        ];
        ?>
        <div class="seokar-meta-box">
            <div class="seokar-field">
                <label for="seokar_title"><?php _e('SEO Title', 'seokar'); ?></label>
                <input type="text" id="seokar_title" name="seokar[title]" 
                       value="<?php echo esc_attr($values['title']); ?>" 
                       class="widefat" />
                <p class="description"><?php _e('Custom title for search engines', 'seokar'); ?></p>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_description"><?php _e('SEO Description', 'seokar'); ?></label>
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
                <p class="description"><?php _e('Override the canonical URL for this product', 'seokar'); ?></p>
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
     * Save product meta
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_product_meta($post_id, $post) {
        if (!isset($_POST['seokar_product_meta_nonce']) || 
            !wp_verify_nonce($_POST['seokar_product_meta_nonce'], 'seokar_save_product_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['seokar'])) {
            $data = $_POST['seokar'];

            foreach ($data as $key => $value) {
                if (in_array($key, ['title', 'description', 'keywords', 'canonical', 'robots'])) {
                    $value = sanitize_text_field($value);
                    
                    if (empty($value)) {
                        delete_post_meta($post_id, "_seokar_{$key}");
                    } else {
                        update_post_meta($post_id, "_seokar_{$key}", $value);
                    }
                }
            }
        }
    }

    /**
     * Add taxonomy fields
     *
     * @param string $taxonomy
     */
    public function add_taxonomy_fields($taxonomy) {
        ?>
        <div class="form-field">
            <label for="seokar_title"><?php _e('SEO Title', 'seokar'); ?></label>
            <input type="text" name="seokar[title]" id="seokar_title" />
            <p class="description"><?php _e('Custom title for search engines', 'seokar'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="seokar_description"><?php _e('SEO Description', 'seokar'); ?></label>
            <textarea name="seokar[description]" id="seokar_description" rows="5"></textarea>
            <p class="description"><?php _e('Custom meta description', 'seokar'); ?></p>
        </div>
        <?php
    }

    /**
     * Edit taxonomy fields
     *
     * @param WP_Term $term
     */
    public function edit_taxonomy_fields($term) {
        $title = get_term_meta($term->term_id, '_seokar_title', true);
        $description = get_term_meta($term->term_id, '_seokar_description', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="seokar_title"><?php _e('SEO Title', 'seokar'); ?></label>
            </th>
            <td>
                <input type="text" name="seokar[title]" id="seokar_title" 
                       value="<?php echo esc_attr($title); ?>" />
                <p class="description"><?php _e('Custom title for search engines', 'seokar'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="seokar_description"><?php _e('SEO Description', 'seokar'); ?></label>
            </th>
            <td>
                <textarea name="seokar[description]" id="seokar_description" 
                          rows="5"><?php echo esc_textarea($description); ?></textarea>
                <p class="description"><?php _e('Custom meta description', 'seokar'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save taxonomy fields
     *
     * @param int $term_id
     */
    public function save_taxonomy_fields($term_id) {
        if (!isset($_POST['seokar'])) {
            return;
        }

        $data = $_POST['seokar'];

        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description'])) {
                $value = sanitize_text_field($value);
                
                if (empty($value)) {
                    delete_term_meta($term_id, "_seokar_{$key}");
                } else {
                    update_term_meta($term_id, "_seokar_{$key}", $value);
                }
            }
        }
    }
}
