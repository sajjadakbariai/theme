<?php
/**
 * ماژول تولید خودکار متا تگ‌های سئو
 * 
 * این ماژول متا تگ‌های description و Open Graph را به صورت خودکار تولید می‌کند.
 * 
 * @package    seokar
 * @subpackage SEO
 * @version    1.0.0
 * @author     sajjad akbari
 * @license    GPL-2.0+
 */

defined('ABSPATH') || exit;

/**
 * تولید و نمایش متا تگ‌های سئو
 * 
 * @return void
 */
function ai_generate_meta_tags() {
    // فقط برای مطالب منفرد اجرا شود
    if (!is_singular()) {
        return;
    }

    $post = get_queried_object();
    
    // تولید توضیحات متا
    $description = generate_meta_description($post);
    
    // خروجی متا تگ‌ها
    output_meta_tags($description, $post);
}

/**
 * تولید توضیحات متا از محتوای پست
 * 
 * @param WP_Post $post شیء پست جاری
 * @return string متن توضیحات متا
 */
function generate_meta_description($post) {
    $content = strip_tags($post->post_content);
    $content = str_replace(["\n", "\r", "\t"], ' ', $content);
    
    // اگر توضیحات دستی وجود داشت از آن استفاده شود
    $manual_description = get_post_meta($post->ID, '_ai_meta_description', true);
    
    return !empty($manual_description) 
        ? $manual_description
        : wp_trim_words($content, 25, '...');
}

/**
 * نمایش متا تگ‌ها در هدر
 * 
 * @param string $description توضیحات متا
 * @param WP_Post $post شیء پست جاری
 * @return void
 */
function output_meta_tags($description, $post) {
    $title = get_the_title($post);
    $url = get_permalink($post);
    
    echo "\n<!-- Auto Generated Meta Tags -->\n";
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    
    // اگر تصویر شاخص وجود داشت اضافه شود
    if (has_post_thumbnail($post)) {
        $thumbnail_url = get_the_post_thumbnail_url($post, 'full');
        echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '">' . "\n";
    }
    
    echo "<!-- End Auto Generated Meta Tags -->\n\n";
}

// با اولویت پایین‌تر اضافه شود تا امکان بازنویسی توسط پلاگین‌ها وجود داشته باشد
add_action('wp_head', 'ai_generate_meta_tags', 5);
