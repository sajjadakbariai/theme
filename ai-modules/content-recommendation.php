<?php
/**
 * ماژول پیشنهاد محتوای هوشمند - نسخه نهایی
 * با قابلیت‌های: کش بهینه، سازگاری AMP، پشتیبانی از اسلایدر، تنظیمات انعطاف‌پذیر
 * آدرس: /wp-content/themes/your-theme/ai-modules/content-recommendation.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// ثبت استایل‌ها و اسکریپت‌ها
function ai_register_related_posts_assets() {
    wp_register_style(
        'ai-related-posts-css',
        get_template_directory_uri() . '/ai-modules/css/related-posts.css',
        array(),
        filemtime(get_template_directory() . '/ai-modules/css/related-posts.css')
    );
    
    // استایل مخصوص AMP
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        wp_add_inline_style('ai-related-posts-css', '
            .ai-related-posts__thumbnail {
                object-fit: cover;
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'ai_register_related_posts_assets');

/**
 * دریافت مطالب مرتبط با سیستم کش بهینه‌شده
 */
function ai_get_related_posts($post_id, $posts_per_page = null) {
    $posts_per_page = apply_filters('ai_related_posts_per_page', $posts_per_page ?: 3);
    $transient_key = 'ai_related_posts_' . $post_id . '_' . $posts_per_page;
    
    // دریافت از کش (فقط IDها)
    $cached_ids = get_transient($transient_key);
    
    if (false !== $cached_ids) {
        if (empty($cached_ids)) {
            return false;
        }
        return new WP_Query(array(
            'post__in' => $cached_ids,
            'posts_per_page' => $posts_per_page,
            'orderby' => 'post__in',
            'ignore_sticky_posts' => 1
        ));
    }

    $tags = wp_get_post_tags($post_id);
    $categories = wp_get_post_categories($post_id);
    
    $args = array(
        'post__not_in' => array($post_id),
        'posts_per_page' => $posts_per_page,
        'ignore_sticky_posts' => 1,
        'orderby' => 'rand',
        'fields' => 'ids',
    );

    if (!empty($tags)) {
        $args['tag__in'] = wp_list_pluck($tags, 'term_id');
    }

    if (!empty($categories)) {
        $args['category__in'] = $categories;
    }

    $args = apply_filters('ai_related_posts_query_args', $args, $post_id);
    
    $query = new WP_Query($args);
    $post_ids = $query->have_posts() ? $query->posts : array();
    
    // ذخیره فقط IDها در کش
    $cache_time = apply_filters('ai_related_posts_cache_time', 12 * HOUR_IN_SECONDS);
    set_transient($transient_key, $post_ids, $cache_time);
    
    // برگرداندن WP_Query جدید بر اساس IDها
    if (empty($post_ids)) {
        return false;
    }
    
    return new WP_Query(array(
        'post__in' => $post_ids,
        'posts_per_page' => $posts_per_page,
        'orderby' => 'post__in',
        'ignore_sticky_posts' => 1
    ));
}

/**
 * نمایش مطالب مرتبط با پشتیبانی از AMP
 */
function ai_display_related_posts($post_id, $posts_per_page = null) {
    if (is_single()) {
        wp_enqueue_style('ai-related-posts-css');
    }
    
    $related_posts = ai_get_related_posts($post_id, $posts_per_page);
    
    if (!$related_posts) {
        return;
    }
    
    $title = apply_filters('ai_related_posts_title', __('مطالب پیشنهادی برای شما', 'textdomain'));
    $layout = apply_filters('ai_related_posts_layout', 'grid'); // grid یا slider
    
    $output = '<div class="ai-related-posts ai-related-posts--' . esc_attr($layout) . '">';
    $output .= sprintf('<h3 class="ai-related-posts__title">%s</h3>', esc_html($title));
    
    if ($related_posts->have_posts()) {
        $output .= '<div class="ai-related-posts__container">';
        
        while ($related_posts->have_posts()) {
            $related_posts->the_post();
            
            // ویژگی‌های مخصوص AMP
            $amp_attrs = '';
            if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
                $amp_attrs = ' layout="responsive" sizes="(min-width: 600px) 600px, 100vw"';
            }
            
            $output .= sprintf(
                '<article class="ai-related-posts__item">
                    <a href="%s" class="ai-related-posts__link">
                        <div class="ai-related-posts__thumbnail-wrapper">
                            %s
                        </div>
                        <h4 class="ai-related-posts__item-title">%s</h4>
                    </a>
                </article>',
                esc_url(get_permalink()),
                get_the_post_thumbnail(null, 'medium', array(
                    'class' => 'ai-related-posts__thumbnail',
                    'loading' => (function_exists('is_amp_endpoint') && is_amp_endpoint()) ? false : 'lazy',
                    'alt' => esc_attr(get_the_title()),
                    'data-amp-layout' => (function_exists('is_amp_endpoint') && is_amp_endpoint()) ? 'responsive' : ''
                )),
                esc_html(get_the_title())
            );
        }
        
        $output .= '</div>';
    } else {
        $no_posts_message = apply_filters('ai_related_posts_empty_message', 
            __('در حال حاضر مطلب مرتبطی وجود ندارد.', 'textdomain'));
        $output .= sprintf('<p class="ai-related-posts__empty">%s</p>', esc_html($no_posts_message));
    }
    
    $output .= '</div>';
    
    wp_reset_postdata();
    
    return $output;
}

/**
 * اضافه کردن مطالب مرتبط به محتوای پست
 */
function ai_add_related_posts_to_content($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $content .= ai_display_related_posts(get_the_ID());
    }
    return $content;
}
add_filter('the_content', 'ai_add_related_posts_to_content');

/**
 * پاک کردن کش هنگام ذخیره یا به‌روزرسانی پست
 */
function ai_clear_related_posts_cache($post_id) {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // پاک کردن کش برای تمام حالت‌های ممکن (1 تا 20)
    for ($i = 1; $i <= 20; $i++) {
        delete_transient('ai_related_posts_' . $post_id . '_' . $i);
    }
    
    // اکشن برای مواقعی که نیاز به پاکسازی دستی است
    do_action('ai_related_posts_cache_cleared', $post_id);
}
add_action('save_post', 'ai_clear_related_posts_cache');
add_action('delete_post', 'ai_clear_related_posts_cache');

/**
 * پاک کردن تمام کش‌های مرتبط هنگام تغییر تنظیمات
 */
function ai_clear_all_related_posts_cache() {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ai_related_posts_%'"
    );
    $wpdb->query(
        "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_ai_related_posts_%'"
    );
}
add_action('switch_theme', 'ai_clear_all_related_posts_cache');
add_action('ai_clear_all_related_posts_cache', 'ai_clear_all_related_posts_cache');
