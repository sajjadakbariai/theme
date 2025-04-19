<?php
/**
 * SEOKAR for WordPress Themes - AMP Module
 * 
 * @package    SeoKar
 * @subpackage AMP
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_AMP implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * AMP options
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->options = get_option('seokar_amp_options', []);
        
        if (!$this->is_amp_enabled()) {
            return;
        }

        $this->setup_hooks();
    }

    /**
     * Check if AMP is enabled
     *
     * @return bool
     */
    private function is_amp_enabled() {
        return function_exists('amp_is_enabled') && amp_is_enabled();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // AMP compatibility
        add_action('amp_post_template_head', [$this, 'add_amp_meta_tags']);
        add_filter('amp_post_template_data', [$this, 'add_amp_schema']);
        add_filter('amp_post_template_metadata', [$this, 'modify_amp_metadata'], 10, 2);
        
        // Content filters
        add_filter('the_content', [$this, 'filter_amp_content'], 20);
        
        // Admin hooks
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_amp_meta_box']);
    }

    /**
     * Add AMP meta tags
     */
    public function add_amp_meta_tags() {
        if (is_singular()) {
            $this->output_canonical_tag();
            $this->output_robots_meta();
        }
    }

    /**
     * Output canonical tag for AMP
     */
    private function output_canonical_tag() {
        $canonical = $this->seokar->get_module('core')->get_canonical_url();
        echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
    }

    /**
     * Output robots meta for AMP
     */
    private function output_robots_meta() {
        $robots = $this->seokar->get_module('core')->get_robots_meta();
        if (!empty($robots)) {
            echo '<meta name="robots" content="' . esc_attr(implode(',', $robots)) . '">' . "\n";
        }
    }

    /**
     * Add AMP schema
     *
     * @param array $data
     * @return array
     */
    public function add_amp_schema($data) {
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }

        if (is_singular()) {
            $schema = $this->seokar->get_module('schema')->generate_content_schema();
            if (!empty($schema)) {
                $data['metadata'] = array_merge($data['metadata'], $schema);
            }
        }

        return $data;
    }

    /**
     * Modify AMP metadata
     *
     * @param array $metadata
     * @param WP_Post $post
     * @return array
     */
    public function modify_amp_metadata($metadata, $post) {
        // Ensure image is set
        if (empty($metadata['image'])) {
            $image_url = $this->get_post_image($post->ID);
            if ($image_url) {
                $metadata['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                    'height' => 1200,
                    'width' => 800
                ];
            }
        }

        // Add publisher info
        $metadata['publisher'] = [
            '@type' => 'Organization',
            'name' => get_bloginfo('name')
        ];

        return $metadata;
    }

    /**
     * Get post image for AMP
     *
     * @param int $post_id
     * @return string
     */
    private function get_post_image($post_id) {
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'full');
        }

        $content = get_post_field('post_content', $post_id);
        preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
        
        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Filter AMP content
     *
     * @param string $content
     * @return string
     */
    public function filter_amp_content($content) {
        if (!is_amp_endpoint()) {
            return $content;
        }

        // Remove unsupported tags
        $content = preg_replace('/<iframe.*?<\/iframe>/i', '', $content);
        $content = preg_replace('/<form.*?<\/form>/i', '', $content);

        // Convert images to amp-img
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'convert_img_to_amp'],
            $content
        );

        return $content;
    }

    /**
     * Convert img tags to amp-img
     *
     * @param array $matches
     * @return string
     */
    private function convert_img_to_amp($matches) {
        $attrs = $matches[1];
        
        // Get width and height
        preg_match('/width=["\']([^"\']+)["\']/i', $attrs, $width);
        preg_match('/height=["\']([^"\']+)["\']/i', $attrs, $height);
        
        $width = isset($width[1]) ? $width[1] : '600';
        $height = isset($height[1]) ? $height[1] : '400';
        
        // Get src
        preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $src);
        $src = isset($src[1]) ? $src[1] : '';
        
        // Get alt
        preg_match('/alt=["\']([^"\']+)["\']/i', $attrs, $alt);
        $alt = isset($alt[1]) ? $alt[1] : '';
        
        return sprintf(
            '<amp-img src="%s" width="%s" height="%s" alt="%s" layout="responsive"></amp-img>',
            esc_url($src),
            esc_attr($width),
            esc_attr($height),
            esc_attr($alt)
        );
    }

    /**
     * Register AMP settings
     */
    public function register_settings() {
        register_setting('seokar_amp_options_group', 'seokar_amp_options', [$this, 'sanitize_options']);

        add_settings_section(
            'seokar_amp_main_section',
            __('AMP Settings', 'seokar'),
            [$this, 'render_main_section'],
            'seokar-amp'
        );

        add_settings_field(
            'enable_amp',
            __('Enable AMP', 'seokar'),
            [$this, 'render_enable_amp_field'],
            'seokar-amp',
            'seokar_amp_main_section'
        );

        add_settings_field(
            'amp_analytics',
            __('AMP Analytics', 'seokar'),
            [$this, 'render_amp_analytics_field'],
            'seokar-amp',
            'seokar_amp_main_section'
        );

        add_settings_field(
            'amp_ads',
            __('AMP Ads', 'seokar'),
            [$this, 'render_amp_ads_field'],
            'seokar-amp',
            'seokar_amp_main_section'
        );
    }

    /**
     * Sanitize AMP options
     *
     * @param array $input
     * @return array
     */
    public function sanitize_options($input) {
        $output = [];

        $output['enable_amp'] = isset($input['enable_amp']) ? 1 : 0;
        $output['amp_analytics'] = isset($input['amp_analytics']) ? sanitize_textarea_field($input['amp_analytics']) : '';
        $output['amp_ads'] = isset($input['amp_ads']) ? sanitize_textarea_field($input['amp_ads']) : '';

        return $output;
    }

    /**
     * Add AMP meta box
     */
    public function add_amp_meta_box() {
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_amp_meta_box',
                __('AMP Settings', 'seokar'),
                [$this, 'render_amp_meta_box'],
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Render AMP meta box
     *
     * @param WP_Post $post
     */
    public function render_amp_meta_box($post) {
        wp_nonce_field('seokar_save_amp_meta', 'seokar_amp_meta_nonce');

        $disable_amp = get_post_meta($post->ID, '_seokar_disable_amp', true);
        $custom_amp_css = get_post_meta($post->ID, '_seokar_amp_css', true);
        ?>
        <div class="seokar-amp-meta-box">
            <div class="seokar-field">
                <label for="seokar_disable_amp">
                    <input type="checkbox" id="seokar_disable_amp" name="seokar_amp[disable_amp]" value="1" <?php checked($disable_amp, 1); ?>>
                    <?php _e('Disable AMP for this content', 'seokar'); ?>
                </label>
            </div>
            
            <div class="seokar-field">
                <label for="seokar_amp_css"><?php _e('Custom AMP CSS', 'seokar'); ?></label>
                <textarea id="seokar_amp_css" name="seokar_amp[custom_css]" class="widefat" rows="5"><?php echo esc_textarea($custom_amp_css); ?></textarea>
                <p class="description"><?php _e('Custom CSS styles for AMP version only', 'seokar'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Save AMP meta data
     *
     * @param int $post_id
     */
    public function save_amp_meta($post_id) {
        if (!isset($_POST['seokar_amp_meta_nonce']) || !wp_verify_nonce($_POST['seokar_amp_meta_nonce'], 'seokar_save_amp_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['seokar_amp'])) {
            $data = $_POST['seokar_amp'];
            
            // Disable AMP
            $disable_amp = isset($data['disable_amp']) ? 1 : 0;
            update_post_meta($post_id, '_seokar_disable_amp', $disable_amp);
            
            // Custom CSS
            if (isset($data['custom_css'])) {
                $custom_css = sanitize_textarea_field($data['custom_css']);
                update_post_meta($post_id, '_seokar_amp_css', $custom_css);
            }
        }
    }

    /**
     * Render main settings section
     */
    public function render_main_section() {
        echo '<p>' . __('Configure Accelerated Mobile Pages (AMP) settings.', 'seokar') . '</p>';
    }

    /**
     * Render enable AMP field
     */
    public function render_enable_amp_field() {
        $enabled = isset($this->options['enable_amp']) ? $this->options['enable_amp'] : 0;
        ?>
        <label>
            <input type="checkbox" name="seokar_amp_options[enable_amp]" value="1" <?php checked($enabled, 1); ?>>
            <?php _e('Enable AMP support', 'seokar'); ?>
        </label>
        <p class="description"><?php _e('Generate AMP versions of your content', 'seokar'); ?></p>
        <?php
    }

    /**
     * Render AMP analytics field
     */
    public function render_amp_analytics_field() {
        $analytics = isset($this->options['amp_analytics']) ? $this->options['amp_analytics'] : '';
        ?>
        <textarea name="seokar_amp_options[amp_analytics]" class="large-text" rows="5"><?php echo esc_textarea($analytics); ?></textarea>
        <p class="description"><?php _e('Enter your AMP analytics script (JSON format)', 'seokar'); ?></p>
        <?php
    }

    /**
     * Render AMP ads field
     */
    public function render_amp_ads_field() {
        $ads = isset($this->options['amp_ads']) ? $this->options['amp_ads'] : '';
        ?>
        <textarea name="seokar_amp_options[amp_ads]" class="large-text" rows="5"><?php echo esc_textarea($ads); ?></textarea>
        <p class="description"><?php _e('Enter your AMP ads configuration (JSON format)', 'seokar'); ?></p>
        <?php
    }
}
