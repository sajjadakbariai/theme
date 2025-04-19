<?php
/**
 * SEOKAR for WordPress Themes - Image SEO Module
 * 
 * @package    SeoKar
 * @subpackage Image
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Image implements SEOKAR_Module_Interface {

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
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Frontend hooks
        add_filter('wp_get_attachment_image_attributes', [$this, 'optimize_image_attributes'], 10, 3);
        
        // Admin hooks
        add_filter('attachment_fields_to_edit', [$this, 'add_image_seo_fields'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'save_image_seo_fields'], 10, 2);
        
        // Automatic optimization
        add_action('add_attachment', [$this, 'auto_optimize_image']);
        add_action('edit_attachment', [$this, 'auto_optimize_image']);
    }

    /**
     * Optimize image attributes
     *
     * @param array $attr
     * @param WP_Post $attachment
     * @param string|array $size
     * @return array
     */
    public function optimize_image_attributes($attr, $attachment, $size) {
        // Ensure alt attribute exists
        if (empty($attr['alt'])) {
            $attr['alt'] = $this->generate_alt_text($attachment->ID);
        }

        // Add title attribute if missing
        if (empty($attr['title'])) {
            $attr['title'] = get_the_title($attachment->ID);
        }

        // Lazy loading
        $options = get_option('seokar_options', []);
        if (!empty($options['lazy_load_images'])) {
            $attr['loading'] = 'lazy';
        }

        return $attr;
    }

    /**
     * Generate alt text for image
     *
     * @param int $attachment_id
     * @return string
     */
    private function generate_alt_text($attachment_id) {
        // Check for custom alt text
        $custom_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($custom_alt)) {
            return $custom_alt;
        }

        // Generate from filename
        $filename = get_post_meta($attachment_id, '_wp_attached_file', true);
        if ($filename) {
            $filename = basename($filename);
            $filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
            $filename = str_replace(['-', '_'], ' ', $filename);
            return ucfirst($filename);
        }

        // Fallback to image title
        return get_the_title($attachment_id);
    }

    /**
     * Add SEO fields to image editor
     *
     * @param array $form_fields
     * @param WP_Post $post
     * @return array
     */
    public function add_image_seo_fields($form_fields, $post) {
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }

        $form_fields['seokar_alt_text'] = [
            'label' => __('Alt Text', 'seokar'),
            'input' => 'text',
            'value' => get_post_meta($post->ID, '_wp_attachment_image_alt', true),
            'helps' => __('Alternative text for screen readers and search engines.', 'seokar')
        ];

        $form_fields['seokar_title_text'] = [
            'label' => __('Title Attribute', 'seokar'),
            'input' => 'text',
            'value' => $post->post_title,
            'helps' => __('Title attribute shown on hover.', 'seokar')
        ];

        $form_fields['seokar_image_keywords'] = [
            'label' => __('Keywords', 'seokar'),
            'input' => 'text',
            'value' => get_post_meta($post->ID, '_seokar_image_keywords', true),
            'helps' => __('Comma separated keywords for this image.', 'seokar')
        ];

        return $form_fields;
    }

    /**
     * Save image SEO fields
     *
     * @param WP_Post $post
     * @param array $attachment
     * @return WP_Post
     */
    public function save_image_seo_fields($post, $attachment) {
        if (!wp_attachment_is_image($post['ID'])) {
            return $post;
        }

        // Save alt text
        if (isset($attachment['seokar_alt_text'])) {
            update_post_meta($post['ID'], '_wp_attachment_image_alt', sanitize_text_field($attachment['seokar_alt_text']));
        }

        // Save title
        if (isset($attachment['seokar_title_text'])) {
            wp_update_post([
                'ID' => $post['ID'],
                'post_title' => sanitize_text_field($attachment['seokar_title_text'])
            ]);
        }

        // Save keywords
        if (isset($attachment['seokar_image_keywords'])) {
            update_post_meta($post['ID'], '_seokar_image_keywords', sanitize_text_field($attachment['seokar_image_keywords']));
        }

        return $post;
    }

    /**
     * Automatically optimize image on upload/edit
     *
     * @param int $attachment_id
     */
    public function auto_optimize_image($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        $options = get_option('seokar_options', []);

        // Auto generate alt text if empty
        if (!empty($options['auto_alt_text'])) {
            $current_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (empty($current_alt)) {
                $alt_text = $this->generate_alt_text($attachment_id);
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
            }
        }

        // Auto compress image
        if (!empty($options['auto_compress_images'])) {
            $this->compress_image($attachment_id);
        }
    }

    /**
     * Compress image
     *
     * @param int $attachment_id
     * @return bool
     */
    private function compress_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            return false;
        }

        // Check if image is already compressed
        if (get_post_meta($attachment_id, '_seokar_image_compressed', true)) {
            return false;
        }

        // Use WordPress Image Editor
        $editor = wp_get_image_editor($file_path);
        
        if (is_wp_error($editor)) {
            return false;
        }

        $result = $editor->save($file_path);
        
        if (is_wp_error($result)) {
            return false;
        }

        update_post_meta($attachment_id, '_seokar_image_compressed', 1);
        return true;
    }

    /**
     * Get image sitemap data
     *
     * @param int $post_id
     * @return array
     */
    public function get_image_sitemap_data($post_id) {
        $images = [];
        $post = get_post($post_id);

        // Featured image
        if (has_post_thumbnail($post_id)) {
            $attachment_id = get_post_thumbnail_id($post_id);
            $images[] = $this->prepare_image_data($attachment_id, $post);
        }

        // Content images
        $content_images = $this->extract_content_images($post->post_content);
        foreach ($content_images as $image_url) {
            $attachment_id = attachment_url_to_postid($image_url);
            if ($attachment_id) {
                $images[] = $this->prepare_image_data($attachment_id, $post);
            }
        }

        return $images;
    }

    /**
     * Extract images from content
     *
     * @param string $content
     * @return array
     */
    private function extract_content_images($content) {
        $images = [];
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        if (isset($matches[1])) {
            foreach ($matches[1] as $image_url) {
                if (strpos($image_url, home_url()) === 0) {
                    $images[] = $image_url;
                }
            }
        }

        return array_unique($images);
    }

    /**
     * Prepare image data for sitemap
     *
     * @param int $attachment_id
     * @param WP_Post $post
     * @return array
     */
    private function prepare_image_data($attachment_id, $post) {
        $image = [
            'url' => wp_get_attachment_url($attachment_id),
            'title' => get_the_title($attachment_id),
            'caption' => wp_get_attachment_caption($attachment_id)
        ];

        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($alt_text)) {
            $image['alt'] = $alt_text;
        }

        return $image;
    }
}
