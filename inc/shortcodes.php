<?php
/**
 * Shortcodes حرفه‌ای برای قالب وردپرس
 * 
 * @package    SEOKAR
 * @subpackage Core
 * @author     Developer Name <developer@example.com>
 * @license    GPL-3.0
 * @link       https://example.com
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم
}

class SEOKAR_Shortcodes {

    /**
     * Constructor - ثبت تمام شورتکدها
     */
    public function __construct() {
        // شورتکدهای اصلی
        add_shortcode('seokar_button', [$this, 'button_shortcode']);
        add_shortcode('seokar_card', [$this, 'card_shortcode']);
        add_shortcode('seokar_accordion', [$this, 'accordion_shortcode']);
        add_shortcode('seokar_accordion_item', [$this, 'accordion_item_shortcode']);
        add_shortcode('seokar_tabs', [$this, 'tabs_shortcode']);
        add_shortcode('seokar_tab', [$this, 'tab_shortcode']);
        add_shortcode('seokar_testimonial', [$this, 'testimonial_shortcode']);
        add_shortcode('seokar_counter', [$this, 'counter_shortcode']);
        add_shortcode('seokar_modal', [$this, 'modal_shortcode']);
        
        // شورتکدهای ویژه
        add_shortcode('seokar_latest_posts', [$this, 'latest_posts_shortcode']);
        add_shortcode('seokar_contact_form', [$this, 'contact_form_shortcode']);
        
        // فیلترهای اضافی
        add_filter('the_content', [$this, 'clean_shortcode_fixes']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * ثبت اسکریپت‌ها و استایل‌های مورد نیاز
     */
    public function register_assets() {
        wp_register_style(
            'seokar-shortcodes',
            get_template_directory_uri() . '/assets/css/shortcodes.css',
            [],
            filemtime(get_template_directory() . '/assets/css/shortcodes.css')
        );
        
        wp_register_script(
            'seokar-shortcodes',
            get_template_directory_uri() . '/assets/js/shortcodes.js',
            ['jquery'],
            filemtime(get_template_directory() . '/assets/js/shortcodes.js'),
            true
        );
    }

    /**
     * شورتکد دکمه
     * 
     * @param array $atts ویژگی‌های شورتکد
     * @param string $content محتوای داخل شورتکد
     * @return string
     */
    public function button_shortcode($atts, $content = null) {
        wp_enqueue_style('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'url' => '#',
            'style' => 'primary', // primary, secondary, outline, ghost
            'size' => 'medium', // small, medium, large
            'target' => '_self',
            'icon' => '',
            'icon_position' => 'left',
            'class' => '',
            'id' => '',
        ], $atts, 'seokar_button');
        
        $icon_html = '';
        if (!empty($atts['icon'])) {
            $icon_html = '<i class="' . esc_attr($atts['icon']) . '"></i>';
        }
        
        $content = $icon_position === 'left' 
            ? $icon_html . do_shortcode($content) 
            : do_shortcode($content) . $icon_html;
        
        return sprintf(
            '<a href="%s" class="seokar-btn seokar-btn-%s seokar-btn-%s %s" id="%s" target="%s">%s</a>',
            esc_url($atts['url']),
            esc_attr($atts['style']),
            esc_attr($atts['size']),
            esc_attr($atts['class']),
            esc_attr($atts['id']),
            esc_attr($atts['target']),
            $content
        );
    }

    /**
     * شورتکد کارت
     */
    public function card_shortcode($atts, $content = null) {
        wp_enqueue_style('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'title' => '',
            'image' => '',
            'style' => 'default', // default, featured, hover
            'class' => '',
            'id' => '',
        ], $atts, 'seokar_card');
        
        $image_html = '';
        if (!empty($atts['image'])) {
            $image_html = sprintf(
                '<div class="seokar-card-image">
                    <img src="%s" alt="%s" loading="lazy">
                </div>',
                esc_url($atts['image']),
                esc_attr($atts['title'])
            );
        }
        
        $title_html = '';
        if (!empty($atts['title'])) {
            $title_html = sprintf(
                '<h3 class="seokar-card-title">%s</h3>',
                esc_html($atts['title'])
            );
        }
        
        return sprintf(
            '<div class="seokar-card seokar-card-%s %s" id="%s">
                %s
                <div class="seokar-card-body">
                    %s
                    <div class="seokar-card-content">%s</div>
                </div>
            </div>',
            esc_attr($atts['style']),
            esc_attr($atts['class']),
            esc_attr($atts['id']),
            $image_html,
            $title_html,
            do_shortcode($content)
        );
    }

    /**
     * شورتکد آکاردئون
     */
    public function accordion_shortcode($atts, $content = null) {
        wp_enqueue_style('seokar-shortcodes');
        wp_enqueue_script('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'style' => 'default',
            'class' => '',
            'id' => '',
            'multiple' => 'false',
        ], $atts, 'seokar_accordion');
        
        static $accordion_id = 0;
        $accordion_id++;
        
        return sprintf(
            '<div class="seokar-accordion %s" id="%s" data-accordion-id="%d" data-multiple="%s">
                %s
            </div>',
            esc_attr($atts['class']),
            esc_attr($atts['id']),
            $accordion_id,
            esc_attr($atts['multiple']),
            do_shortcode($content)
        );
    }

    /**
     * شورتکد آیتم آکاردئون
     */
    public function accordion_item_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'title' => 'عنوان آیتم',
            'active' => 'false',
            'class' => '',
        ], $atts, 'seokar_accordion_item');
        
        return sprintf(
            '<div class="seokar-accordion-item %s %s">
                <button class="seokar-accordion-header">
                    <span>%s</span>
                    <i class="seokar-accordion-icon"></i>
                </button>
                <div class="seokar-accordion-content">
                    <div class="seokar-accordion-body">%s</div>
                </div>
            </div>',
            esc_attr($atts['class']),
            $atts['active'] === 'true' ? 'active' : '',
            esc_html($atts['title']),
            do_shortcode($content)
        );
    }

    /**
     * شورتکد تب‌ها
     */
    public function tabs_shortcode($atts, $content = null) {
        wp_enqueue_style('seokar-shortcodes');
        wp_enqueue_script('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'style' => 'default',
            'class' => '',
            'id' => '',
        ], $atts, 'seokar_tabs');
        
        static $tabs_id = 0;
        $tabs_id++;
        
        return sprintf(
            '<div class="seokar-tabs %s" id="%s" data-tabs-id="%d">
                <div class="seokar-tabs-nav"></div>
                <div class="seokar-tabs-content">%s</div>
            </div>',
            esc_attr($atts['class']),
            esc_attr($atts['id']),
            $tabs_id,
            do_shortcode($content)
        );
    }

    /**
     * شورتکد تب
     */
    public function tab_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'title' => 'عنوان تب',
            'active' => 'false',
            'class' => '',
            'icon' => '',
        ], $atts, 'seokar_tab');
        
        $icon_html = '';
        if (!empty($atts['icon'])) {
            $icon_html = '<i class="' . esc_attr($atts['icon']) . '"></i>';
        }
        
        return sprintf(
            '<div class="seokar-tab %s %s" data-tab-title="%s">
                <div class="seokar-tab-body">%s</div>
            </div>',
            esc_attr($atts['class']),
            $atts['active'] === 'true' ? 'active' : '',
            esc_attr($icon_html . $atts['title']),
            do_shortcode($content)
        );
    }

    /**
     * شورتکد نظرات مشتریان
     */
    public function testimonial_shortcode($atts, $content = null) {
        wp_enqueue_style('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'name' => '',
            'position' => '',
            'avatar' => '',
            'rating' => '5',
            'class' => '',
        ], $atts, 'seokar_testimonial');
        
        $rating_html = '';
        if (!empty($atts['rating'])) {
            $rating = min(5, max(0, (int)$atts['rating']));
            $rating_html = '<div class="seokar-testimonial-rating">';
            for ($i = 1; $i <= 5; $i++) {
                $rating_html .= $i <= $rating 
                    ? '<i class="fas fa-star"></i>' 
                    : '<i class="far fa-star"></i>';
            }
            $rating_html .= '</div>';
        }
        
        $avatar_html = '';
        if (!empty($atts['avatar'])) {
            $avatar_html = sprintf(
                '<div class="seokar-testimonial-avatar">
                    <img src="%s" alt="%s" loading="lazy">
                </div>',
                esc_url($atts['avatar']),
                esc_attr($atts['name'])
            );
        }
        
        return sprintf(
            '<div class="seokar-testimonial %s">
                <div class="seokar-testimonial-content">
                    <div class="seokar-testimonial-text">%s</div>
                    %s
                </div>
                <div class="seokar-testimonial-footer">
                    %s
                    <div class="seokar-testimonial-author">
                        <strong>%s</strong>
                        <span>%s</span>
                    </div>
                </div>
            </div>',
            esc_attr($atts['class']),
            do_shortcode($content),
            $rating_html,
            $avatar_html,
            esc_html($atts['name']),
            esc_html($atts['position'])
        );
    }

    /**
     * شورتکد شمارنده
     */
    public function counter_shortcode($atts) {
        wp_enqueue_style('seokar-shortcodes');
        wp_enqueue_script('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'number' => '100',
            'title' => '',
            'prefix' => '',
            'suffix' => '',
            'duration' => '2000',
            'class' => '',
        ], $atts, 'seokar_counter');
        
        return sprintf(
            '<div class="seokar-counter %s" data-number="%s" data-duration="%s">
                <div class="seokar-counter-number">
                    <span class="seokar-counter-prefix">%s</span>
                    <span class="seokar-counter-value">0</span>
                    <span class="seokar-counter-suffix">%s</span>
                </div>
                %s
            </div>',
            esc_attr($atts['class']),
            esc_attr($atts['number']),
            esc_attr($atts['duration']),
            esc_html($atts['prefix']),
            esc_html($atts['suffix']),
            !empty($atts['title']) ? '<div class="seokar-counter-title">' . esc_html($atts['title']) . '</div>' : ''
        );
    }

    /**
     * شورتکد مودال
     */
    public function modal_shortcode($atts, $content = null) {
        wp_enqueue_style('seokar-shortcodes');
        wp_enqueue_script('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'title' => '',
            'trigger' => 'button', // button, text, custom
            'trigger_text' => 'Open Modal',
            'trigger_class' => '',
            'size' => 'medium', // small, medium, large, full
            'class' => '',
            'id' => '',
        ], $atts, 'seokar_modal');
        
        static $modal_id = 0;
        $modal_id++;
        
        $modal_id = !empty($atts['id']) ? $atts['id'] : 'seokar-modal-' . $modal_id;
        
        $trigger_html = '';
        if ($atts['trigger'] === 'button') {
            $trigger_html = sprintf(
                '<button class="seokar-modal-trigger %s" data-modal="%s">%s</button>',
                esc_attr($atts['trigger_class']),
                esc_attr($modal_id),
                esc_html($atts['trigger_text'])
            );
        } elseif ($atts['trigger'] === 'text') {
            $trigger_html = sprintf(
                '<span class="seokar-modal-trigger %s" data-modal="%s">%s</span>',
                esc_attr($atts['trigger_class']),
                esc_attr($modal_id),
                esc_html($atts['trigger_text'])
            );
        }
        
        return sprintf(
            '%s
            <div class="seokar-modal %s" id="%s" data-modal-size="%s">
                <div class="seokar-modal-overlay"></div>
                <div class="seokar-modal-container">
                    <button class="seokar-modal-close">&times;</button>
                    <div class="seokar-modal-header">
                        <h3>%s</h3>
                    </div>
                    <div class="seokar-modal-body">
                        %s
                    </div>
                </div>
            </div>',
            $trigger_html,
            esc_attr($atts['class']),
            esc_attr($modal_id),
            esc_attr($atts['size']),
            esc_html($atts['title']),
            do_shortcode($content)
        );
    }

    /**
     * شورتکد آخرین مطالب
     */
    public function latest_posts_shortcode($atts) {
        wp_enqueue_style('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'count' => '3',
            'category' => '',
            'style' => 'grid', // grid, list, carousel
            'class' => '',
        ], $atts, 'seokar_latest_posts');
        
        $args = [
            'post_type' => 'post',
            'posts_per_page' => (int)$atts['count'],
            'ignore_sticky_posts' => true,
        ];
        
        if (!empty($atts['category'])) {
            $args['category_name'] = sanitize_text_field($atts['category']);
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>No posts found</p>';
        }
        
        $output = sprintf('<div class="seokar-latest-posts %s seokar-latest-posts-%s">', 
            esc_attr($atts['class']),
            esc_attr($atts['style'])
        );
        
        while ($query->have_posts()) {
            $query->the_post();
            
            $thumbnail = '';
            if (has_post_thumbnail()) {
                $thumbnail = sprintf(
                    '<a href="%s" class="seokar-post-thumbnail">%s</a>',
                    get_permalink(),
                    get_the_post_thumbnail(get_the_ID(), 'medium', ['loading' => 'lazy'])
                );
            }
            
            $output .= sprintf(
                '<article class="seokar-post">
                    %s
                    <div class="seokar-post-content">
                        <h3 class="seokar-post-title"><a href="%s">%s</a></h3>
                        <div class="seokar-post-meta">
                            <time datetime="%s">%s</time>
                        </div>
                        <div class="seokar-post-excerpt">%s</div>
                        <a href="%s" class="seokar-post-read-more">%s</a>
                    </div>
                </article>',
                $thumbnail,
                get_permalink(),
                get_the_title(),
                get_the_date('c'),
                get_the_date(),
                get_the_excerpt(),
                get_permalink(),
                __('Read More', 'seokar')
            );
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }

    /**
     * شورتکد فرم تماس
     */
    public function contact_form_shortcode($atts) {
        wp_enqueue_style('seokar-shortcodes');
        wp_enqueue_script('seokar-shortcodes');
        
        $atts = shortcode_atts([
            'email' => get_option('admin_email'),
            'class' => '',
            'title' => __('Contact Us', 'seokar'),
        ], $atts, 'seokar_contact_form');
        
        ob_start();
        ?>
        <div class="seokar-contact-form <?php echo esc_attr($atts['class']); ?>">
            <?php if (!empty($atts['title'])): ?>
                <h3><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <form class="seokar-contact-form" method="post">
                <div class="seokar-form-group">
                    <label for="name"><?php _e('Name', 'seokar'); ?></label>
                    <input type="text" name="name" id="name" required>
                </div>
                
                <div class="seokar-form-group">
                    <label for="email"><?php _e('Email', 'seokar'); ?></label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="seokar-form-group">
                    <label for="subject"><?php _e('Subject', 'seokar'); ?></label>
                    <input type="text" name="subject" id="subject" required>
                </div>
                
                <div class="seokar-form-group">
                    <label for="message"><?php _e('Message', 'seokar'); ?></label>
                    <textarea name="message" id="message" rows="5" required></textarea>
                </div>
                
                <input type="hidden" name="seokar_contact_email" value="<?php echo esc_attr($atts['email']); ?>">
                <input type="hidden" name="seokar_contact_nonce" value="<?php echo wp_create_nonce('seokar_contact_nonce'); ?>">
                
                <button type="submit" class="seokar-btn seokar-btn-primary">
                    <?php _e('Send Message', 'seokar'); ?>
                </button>
                
                <div class="seokar-form-response"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * رفع مشکلات تگ‌های شورتکد در محتوا
     */
    public function clean_shortcode_fixes($content) {
        $array = [
            '<p>[' => '[',
            ']</p>' => ']',
            ']<br />' => ']',
            ']<br>' => ']'
        ];
        
        $content = strtr($content, $array);
        return $content;
    }
}

// راه‌اندازی کلاس شورتکدها
new SEOKAR_Shortcodes();
