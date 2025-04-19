<?php
/**
 * ابزارهای کمکی برای سئو
 * 
 * @package    SeoKar
 * @subpackage Helpers
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_Utils {

    /**
     * تولید متن جایگزین برای تصاویر
     *
     * @param string $image_url
     * @param int $post_id
     * @return string
     */
    public static function generate_image_alt($image_url, $post_id = null) {
        if (empty($image_url)) {
            return '';
        }

        // اگر پست مشخص شده باشد، از عنوان پست استفاده می‌کنیم
        if ($post_id) {
            $post_title = get_the_title($post_id);
            if (!empty($post_title)) {
                return sprintf(__('تصویر مرتبط با %s', 'seokar'), $post_title);
            }
        }

        // استخراج نام فایل از URL
        $filename = pathinfo($image_url, PATHINFO_FILENAME);
        $filename = str_replace(['-', '_'], ' ', $filename);

        // حذف اعداد و کاراکترهای خاص
        $alt_text = preg_replace('/[0-9]+/', '', $filename);
        $alt_text = trim($alt_text);

        if (!empty($alt_text)) {
            return $alt_text;
        }

        // پیش‌فرض
        return __('تصویر محتوای سایت', 'seokar');
    }

    /**
     * تولید URL بهینه شده
     *
     * @param string $url
     * @param bool $remove_stopwords
     * @return string
     */
    public static function optimize_url($url, $remove_stopwords = true) {
        $url = strtolower($url);
        $url = str_replace([' ', '_', '--'], '-', $url);
        
        if ($remove_stopwords) {
            $stopwords = ['a', 'an', 'the', 'and', 'or', 'but', 'for', 'of', 'to', 'in', 'on', 'at'];
            $url_parts = explode('-', $url);
            $url_parts = array_diff($url_parts, $stopwords);
            $url = implode('-', $url_parts);
        }
        
        return sanitize_title($url);
    }

    /**
     * بررسی سرعت بارگذاری صفحه
     *
     * @param string $url
     * @return array|false
     */
    public static function check_page_speed($url = null) {
        if (null === $url) {
            $url = home_url();
        }

        $api_key = get_option('seokar_google_api_key');
        if (empty($api_key)) {
            return false;
        }

        $api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
        $args = [
            'url' => $url,
            'key' => $api_key,
            'locale' => get_locale(),
            'strategy' => 'desktop'
        ];

        $response = wp_remote_get(add_query_arg($args, $api_url));
        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['lighthouseResult'])) {
            return false;
        }

        return [
            'performance' => $body['lighthouseResult']['categories']['performance']['score'] * 100,
            'seo' => $body['lighthouseResult']['categories']['seo']['score'] * 100,
            'accessibility' => $body['lighthouseResult']['categories']['accessibility']['score'] * 100,
            'best_practices' => $body['lighthouseResult']['categories']['best-practices']['score'] * 100
        ];
    }

    /**
     * تولید نقشه سایت به صورت پویا
     *
     * @param string $sitemap_type
     * @return string
     */
    public static function generate_dynamic_sitemap($sitemap_type = 'posts') {
        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if ($sitemap_type === 'posts') {
            $posts = get_posts([
                'post_type' => ['post', 'page'],
                'post_status' => 'publish',
                'numberposts' => -1
            ]);

            foreach ($posts as $post) {
                $output .= '<url>';
                $output .= '<loc>' . get_permalink($post) . '</loc>';
                $output .= '<lastmod>' . mysql2date('Y-m-d\TH:i:s+00:00', $post->post_modified_gmt) . '</lastmod>';
                $output .= '<changefreq>' . self::get_change_frequency($post) . '</changefreq>';
                $output .= '<priority>' . self::get_priority($post) . '</priority>';
                $output .= '</url>';
            }
        } elseif ($sitemap_type === 'taxonomies') {
            $taxonomies = get_taxonomies(['public' => true]);
            foreach ($taxonomies as $taxonomy) {
                $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
                foreach ($terms as $term) {
                    $output .= '<url>';
                    $output .= '<loc>' . get_term_link($term) . '</loc>';
                    $output .= '<lastmod>' . current_time('Y-m-d\TH:i:s+00:00') . '</lastmod>';
                    $output .= '<changefreq>weekly</changefreq>';
                    $output .= '<priority>0.7</priority>';
                    $output .= '</url>';
                }
            }
        }

        $output .= '</urlset>';
        return $output;
    }

    /**
     * محاسبه فرکانس تغییر محتوا
     *
     * @param WP_Post $post
     * @return string
     */
    private static function get_change_frequency($post) {
        $post_age = time() - strtotime($post->post_date_gmt);
        $days = floor($post_age / (60 * 60 * 24));

        if ($days < 7) {
            return 'daily';
        } elseif ($days < 30) {
            return 'weekly';
        } elseif ($days < 365) {
            return 'monthly';
        } else {
            return 'yearly';
        }
    }

    /**
     * محاسبه اولویت محتوا
     *
     * @param WP_Post $post
     * @return float
     */
    private static function get_priority($post) {
        if (is_front_page()) {
            return 1.0;
        } elseif ($post->post_type === 'page') {
            return 0.9;
        } elseif ($post->post_type === 'post') {
            $comments = get_comments_number($post->ID);
            $priority = 0.6 + min($comments * 0.01, 0.3);
            return round($priority, 1);
        }
        return 0.5;
    }

    /**
     * تحلیل تراکم کلمات کلیدی
     *
     * @param string $content
     * @param string $keyword
     * @return array
     */
    public static function analyze_keyword_density($content, $keyword) {
        $content = strip_tags($content);
        $content = strtolower($content);
        $keyword = strtolower($keyword);

        $total_words = str_word_count($content);
        $keyword_count = substr_count($content, $keyword);

        $density = ($total_words > 0) ? ($keyword_count / $total_words) * 100 : 0;

        return [
            'keyword' => $keyword,
            'count' => $keyword_count,
            'total_words' => $total_words,
            'density' => round($density, 2)
        ];
    }

    /**
     * بررسی وضعیت ایندکس شدن در گوگل
     *
     * @param string $url
     * @return bool
     */
    public static function check_google_index($url) {
        $api_key = get_option('seokar_google_api_key');
        if (empty($api_key)) {
            return false;
        }

        $search_url = 'https://www.googleapis.com/customsearch/v1';
        $args = [
            'key' => $api_key,
            'cx' => get_option('seokar_google_cse_id'),
            'q' => 'site:' . $url,
            'num' => 1
        ];

        $response = wp_remote_get(add_query_arg($args, $search_url));
        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return !empty($body['items']);
    }

    /**
     * تولید داده‌های ساختار یافته برای نمره‌دهی
     *
     * @param int $rating
     * @param int $count
     * @return array
     */
    public static function generate_rating_schema($rating, $count) {
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating,
            'bestRating' => 5,
            'worstRating' => 1,
            'ratingCount' => $count
        ];
    }

    /**
     * تبدیل تاریخ به فرمت ISO 8601
     *
     * @param string $date
     * @return string
     */
    public static function format_iso_date($date) {
        return date('c', strtotime($date));
    }

    /**
     * فشرده سازی HTML خروجی
     *
     * @param string $html
     * @return string
     */
    public static function compress_html($html) {
        $search = [
            '/\>[^\S ]+/s',     // حذف فاصله‌های بعد از تگ‌ها
            '/[^\S ]+\</s',     // حذف فاصله‌های قبل از تگ‌ها
            '/(\s)+/s',         // جایگزینی چند فاصله با یک فاصله
            '/<!--(.|\s)*?-->/' // حذف کامنت‌ها
        ];

        $replace = ['>', '<', '\\1', ''];
        return preg_replace($search, $replace, $html);
    }
}
