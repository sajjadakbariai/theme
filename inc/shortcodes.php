<?php
/**
 * Shortcodes حرفه‌ای برای قالب وردپرس - نسخه بدون نیاز به فایل‌های خارجی
 * 
 * @package    SEOKAR
 * @subpackage Core
 * @license    GPL-3.0
 * @link       https://seokar.click
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
        add_shortcode('seokar_alert', [$this, 'alert_shortcode']);
        add_shortcode('seokar_card', [$this, 'card_shortcode']);
        add_shortcode('seokar_divider', [$this, 'divider_shortcode']);
        add_shortcode('seokar_icon', [$this, 'icon_shortcode']);
        add_shortcode('seokar_latest_posts', [$this, 'latest_posts_shortcode']);
        add_shortcode('seokar_contact_info', [$this, 'contact_info_shortcode']);
        
        // تمیز کردن شورتکدها در محتوا
        add_filter('the_content', [$this, 'clean_shortcode_fixes']);
        
        // افزودن استایل‌های داخلی
        add_action('wp_head', [$this, 'inline_styles'], 100);
    }

    /**
     * استایل‌های داخلی برای شورتکدها
     */
    public function inline_styles() {
        if (!has_shortcode(get_the_content(), 'seokar_')) {
            return;
        }
        
        echo '<style id="seokar-shortcodes-inline-css">
            /* استایل‌های دکمه */
            .seokar-btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                border-radius: 4px;
                font-weight: 500;
                text-align: center;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
                border: 1px solid transparent;
                line-height: 1;
            }
            
            .seokar-btn-primary {
                background-color: #4361ee;
                color: #fff;
            }
            
            .seokar-btn-primary:hover {
                background-color: #3a56d4;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            
            /* استایل‌های کارت */
            .seokar-card {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                background: #fff;
                margin-bottom: 1.5rem;
            }
            
            .seokar-card:hover {
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .seokar-card-image img {
                width: 100%;
                height: auto;
                display: block;
            }
            
            .seokar-card-body {
                padding: 1.5rem;
            }
            
            .seokar-card-title {
                margin-top: 0;
                margin-bottom: 1rem;
                color: #2b2d42;
            }
            
            /* استایل‌های هشدار */
            .seokar-alert {
                padding: 1rem;
                border-radius: 4px;
                margin: 1rem 0;
                border-left: 4px solid;
            }
            
            .seokar-alert-success {
                background-color: #f0fff4;
                border-color: #48bb78;
                color: #2f855a;
            }
            
            .seokar-alert-warning {
                background-color: #fffaf0;
                border-color: #ed8936;
                color: #c05621;
            }
            
            /* استایل‌های جداکننده */
            .seokar-divider {
                border: 0;
                height: 1px;
                background-image: linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,0.1), rgba(0,0,0,0));
                margin: 2rem 0;
            }
            
            /* استایل‌های آیکون */
            .seokar-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: #4361ee;
            }
            
            /* استایل‌های آخرین مطالب */
            .seokar-latest-posts {
                display: grid;
                grid-gap: 1.5rem;
            }
            
            .seokar-post {
                border-bottom: 1px solid #eee;
                padding-bottom: 1rem;
                margin-bottom: 1rem;
            }
            
            .seokar-post-title {
                margin: 0 0 0.5rem;
            }
            
            .seokar-post-meta {
                font-size: 0.875rem;
                color: #718096;
                margin-bottom: 0.5rem;
            }
            
            /* استایل‌های اطلاعات تماس */
            .seokar-contact-info {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .seokar-contact-info li {
                display: flex;
                align-items: center;
                margin-bottom: 0.75rem;
            }
            
            .seokar-contact-info i {
                margin-left: 0.5rem;
                color: #4361ee;
                width: 1.5rem;
                text-align: center;
            }
        </style>';
    }

    /**
     * شورتکد دکمه
     */
    public function button_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'url' => '#',
            'style' => 'primary',
            'size' => 'medium',
            'target' => '_self',
            'icon' => '',
            'class' => '',
        ], $atts, 'seokar_button');
        
        $icon_html = '';
        if (!empty($atts['icon'])) {
            $icon_html = '<i class="' . esc_attr($atts['icon']) . '"></i> ';
        }
        
        return sprintf(
            '<a href="%s" class="seokar-btn seokar-btn-%s %s" target="%s">%s%s</a>',
            esc_url($atts['url']),
            esc_attr($atts['style']),
            esc_attr($atts['class']),
            esc_attr($atts['target']),
            $icon_html,
            do_shortcode($content)
        );
    }

    /**
     * شورتکد هشدار
     */
    public function alert_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'type' => 'success',
            'class' => '',
        ], $atts, 'seokar_alert');
        
        return sprintf(
            '<div class="seokar-alert seokar-alert-%s %s">%s</div>',
            esc_attr($atts['type']),
            esc_attr($atts['class']),
            do_shortcode($content)
        );
    }

    /**
     * شورتکد کارت
     */
    public function card_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'title' => '',
            'image' => '',
            'class' => '',
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
        
        return sprintf(
            '<div class="seokar-card %s">
                %s
                <div class="seokar-card-body">
                    <h3 class="seokar-card-title">%s</h3>
                    <div class="seokar-card-content">%s</div>
                </div>
            </div>',
            esc_attr($atts['class']),
            $image_html,
            esc_html($atts['title']),
            do_shortcode($content)
        );
    }

    /**
     * شورتکد جداکننده
     */
    public function divider_shortcode($atts) {
        $atts = shortcode_atts([
            'class' => '',
        ], $atts, 'seokar_divider');
        
        return sprintf('<hr class="seokar-divider %s">', esc_attr($atts['class']));
    }

    /**
     * شورتکد آیکون
     */
    public function icon_shortcode($atts) {
        $atts = shortcode_atts([
            'name' => 'star',
            'class' => '',
        ], $atts, 'seokar_icon');
        
        return sprintf(
            '<span class="seokar-icon %s"><i class="%s"></i></span>',
            esc_attr($atts['class']),
            esc_attr($atts['name'])
        );
    }

    /**
     * شورتکد آخرین مطالب
     */
    public function latest_posts_shortcode($atts) {
        $atts = shortcode_atts([
            'count' => '3',
            'category' => '',
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
            return '<p>مطلبی یافت نشد</p>';
        }
        
        $output = sprintf('<div class="seokar-latest-posts %s">', esc_attr($atts['class']));
        
        while ($query->have_posts()) {
            $query->the_post();
            
            $output .= sprintf(
                '<article class="seokar-post">
                    <h3 class="seokar-post-title"><a href="%s">%s</a></h3>
                    <div class="seokar-post-meta">%s</div>
                    <div class="seokar-post-excerpt">%s</div>
                </article>',
                get_permalink(),
                get_the_title(),
                get_the_date(),
                get_the_excerpt()
            );
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }

    /**
     * شورتکد اطلاعات تماس
     */
    public function contact_info_shortcode($atts) {
        $atts = shortcode_atts([
            'phone' => '',
            'email' => '',
            'address' => '',
            'class' => '',
        ], $atts, 'seokar_contact_info');
        
        $output = '<ul class="seokar-contact-info ' . esc_attr($atts['class']) . '">';
        
        if (!empty($atts['phone'])) {
            $output .= sprintf(
                '<li><i class="fas fa-phone"></i> %s</li>',
                esc_html($atts['phone'])
            );
        }
        
        if (!empty($atts['email'])) {
            $output .= sprintf(
                '<li><i class="fas fa-envelope"></i> %s</li>',
                esc_html($atts['email'])
            );
        }
        
        if (!empty($atts['address'])) {
            $output .= sprintf(
                '<li><i class="fas fa-map-marker-alt"></i> %s</li>',
                esc_html($atts['address'])
            );
        }
        
        $output .= '</ul>';
        
        return $output;
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
        
        return strtr($content, $array);
    }
}

new SEOKAR_Shortcodes();
