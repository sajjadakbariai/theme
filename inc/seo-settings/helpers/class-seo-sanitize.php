<?php
/**
 * اعتبارسنجی و پاکسازی داده‌های سئو
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

class SEOKAR_Sanitize {

    /**
     * پاکسازی عنوان سئو
     *
     * @param string $title
     * @return string
     */
    public static function title($title) {
        $title = strip_tags($title);
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $title = preg_replace('/[\r\n\t ]+/', ' ', $title);
        $title = trim($title);
        $title = mb_substr($title, 0, 60, 'UTF-8'); // محدودیت طول عنوان
        
        return $title;
    }

    /**
     * پاکسازی توضیحات متا
     *
     * @param string $description
     * @return string
     */
    public static function description($description) {
        $description = strip_tags($description);
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
        $description = preg_replace('/[\r\n\t ]+/', ' ', $description);
        $description = trim($description);
        $description = mb_substr($description, 0, 160, 'UTF-8'); // محدودیت طول توضیحات
        
        return $description;
    }

    /**
     * پاکسازی کلمات کلیدی
     *
     * @param string $keywords
     * @return string
     */
    public static function keywords($keywords) {
        $keywords = strip_tags($keywords);
        $keywords = html_entity_decode($keywords, ENT_QUOTES, 'UTF-8');
        $keywords = preg_replace('/[^\w\s,]/u', '', $keywords);
        $keywords = preg_replace('/\s*,\s*/', ', ', $keywords);
        $keywords = trim($keywords, ', ');
        
        return $keywords;
    }

    /**
     * پاکسازی URL
     *
     * @param string $url
     * @return string
     */
    public static function url($url) {
        $url = trim($url);
        $url = str_replace([' ', "'", '"', '`'], '', $url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        return esc_url_raw($url);
    }

    /**
     * پاکسازی محتوای HTML برای اسکیما
     *
     * @param string $html
     * @return string
     */
    public static function schema_html($html) {
        $allowed_tags = [
            'a' => ['href' => [], 'title' => []],
            'br' => [],
            'em' => [],
            'strong' => [],
            'span' => [],
            'div' => [],
            'p' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => []
        ];
        
        return wp_kses($html, $allowed_tags);
    }

    /**
     * پاکسازی مقدار عددی
     *
     * @param mixed $number
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function number($number, $min = 0, $max = 9999) {
        $number = intval($number);
        $number = max($min, min($max, $number));
        
        return $number;
    }

    /**
     * پاکسازی کد رهگیری
     *
     * @param string $tracking_code
     * @return string
     */
    public static function tracking_code($tracking_code) {
        $tracking_code = strip_tags($tracking_code);
        $tracking_code = htmlentities($tracking_code, ENT_QUOTES, 'UTF-8');
        
        return $tracking_code;
    }

    /**
     * پاکسازی محتوای JSON
     *
     * @param string $json
     * @return string
     */
    public static function json($json) {
        $json = wp_unslash($json);
        $json = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
        
        return wp_json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * پاکسازی متن برای استفاده در ویژگی‌های HTML
     *
     * @param string $text
     * @return string
     */
    public static function attribute($text) {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = esc_attr($text);
        
        return $text;
    }

    /**
     * پاکسازی محتوای متا ربات‌ها
     *
     * @param string $robots
     * @return string
     */
    public static function robots($robots) {
        $allowed_values = ['index', 'noindex', 'follow', 'nofollow', 'noarchive'];
        $values = explode(',', $robots);
        $clean_values = [];
        
        foreach ($values as $value) {
            $value = trim($value);
            if (in_array($value, $allowed_values)) {
                $clean_values[] = $value;
            }
        }
        
        return implode(', ', array_unique($clean_values));
    }
}
