<?php
/**
 * نمایش مسیرهای ناوبری (Breadcrumbs) برای سئو
 * 
 * @package    SeoKar
 * @subpackage Templates
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('seokar_breadcrumbs')) :
function seokar_breadcrumbs() {
    // تنظیمات پیش‌فرض
    $delimiter = '<span class="sep">/</span>';
    $home_text = __('خانه', 'seokar');
    $before = '<span class="current">';
    $after = '</span>';
    
    // اگر صفحه اصلی باشد، خروج
    if (is_front_page()) {
        return;
    }
    
    global $post;
    $home_link = home_url('/');
    
    echo '<nav class="seokar-breadcrumbs" aria-label="' . __('مسیر ناوبری', 'seokar') . '">';
    echo '<ol itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    // آیتم خانه
    echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
    echo '<a itemprop="item" href="' . esc_url($home_link) . '">';
    echo '<span itemprop="name">' . esc_html($home_text) . '</span>';
    echo '</a>';
    echo '<meta itemprop="position" content="1" />';
    echo $delimiter;
    echo '</li>';
    
    $position = 2;
    
    // برای مطالب و صفحات
    if (is_single() || is_page()) {
        if (is_single()) {
            // دسته‌بندی‌های مطلب
            $categories = get_the_category();
            if ($categories) {
                $main_category = $categories[0];
                echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                echo '<a itemprop="item" href="' . esc_url(get_category_link($main_category->term_id)) . '">';
                echo '<span itemprop="name">' . esc_html($main_category->name) . '</span>';
                echo '</a>';
                echo '<meta itemprop="position" content="' . $position++ . '" />';
                echo $delimiter;
                echo '</li>';
            }
        }
        
        // عنوان مطلب/صفحه فعلی
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo $before;
        echo '<span itemprop="name">' . esc_html(get_the_title()) . '</span>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $after;
        echo '</li>';
    } 
    // برای آرشیوها
    elseif (is_category()) {
        $current_cat = get_category(get_query_var('cat'), false);
        if ($current_cat->parent != 0) {
            $parent_cats = get_category_parents($current_cat->parent, true, $delimiter);
            echo '<li>' . $parent_cats . '</li>';
        }
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo $before;
        echo '<span itemprop="name">' . single_cat_title('', false) . '</span>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $after;
        echo '</li>';
    }
    // برای جستجو
    elseif (is_search()) {
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo $before;
        echo '<span itemprop="name">' . __('نتایج جستجو برای: ', 'seokar') . get_search_query() . '</span>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $after;
        echo '</li>';
    }
    // برای برچسب‌ها
    elseif (is_tag()) {
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo $before;
        echo '<span itemprop="name">' . single_tag_title('', false) . '</span>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $after;
        echo '</li>';
    }
    // برای نویسنده
    elseif (is_author()) {
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo $before;
        echo '<span itemprop="name">' . get_the_author() . '</span>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $after;
        echo '</li>';
    }
    // برای روز/ماه/سال
    elseif (is_day() || is_month() || is_year()) {
        $year = get_the_time('Y');
        $month = get_the_time('F');
        $day = get_the_time('d');
        
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo '<a itemprop="item" href="' . esc_url(get_year_link($year)) . '">';
        echo '<span itemprop="name">' . $year . '</span>';
        echo '</a>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $delimiter;
        echo '</li>';
        
        if (is_month() || is_day()) {
            echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            echo '<a itemprop="item" href="' . esc_url(get_month_link($year, $month)) . '">';
            echo '<span itemprop="name">' . $month . '</span>';
            echo '</a>';
            echo '<meta itemprop="position" content="' . $position++ . '" />';
            echo $delimiter;
            echo '</li>';
        }
        
        if (is_day()) {
            echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            echo $before;
            echo '<span itemprop="name">' . $day . '</span>';
            echo '<meta itemprop="position" content="' . $position++ . '" />';
            echo $after;
            echo '</li>';
        }
    }
    // برای صفحه 404
    elseif (is_404()) {
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo $before;
        echo '<span itemprop="name">' . __('صفحه پیدا نشد', 'seokar') . '</span>';
        echo '<meta itemprop="position" content="' . $position++ . '" />';
        echo $after;
        echo '</li>';
    }
    
    echo '</ol>';
    echo '</nav>';
}
endif;
