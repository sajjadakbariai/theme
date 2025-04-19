<?php
/**
 * اعتبارسنجی داده‌های سئو
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

class SEOKAR_Validators {

    /**
     * اعتبارسنجی عنوان سئو
     *
     * @param string $title
     * @return bool
     */
    public static function validate_title($title) {
        if (empty($title)) {
            return false;
        }

        $length = mb_strlen($title, 'UTF-8');
        return $length >= 10 && $length <= 60;
    }

    /**
     * اعتبارسنجی توضیحات متا
     *
     * @param string $description
     * @return bool
     */
    public static function validate_description($description) {
        if (empty($description)) {
            return false;
        }

        $length = mb_strlen($description, 'UTF-8');
        return $length >= 50 && $length <= 160;
    }

    /**
     * اعتبارسنجی کلمات کلیدی
     *
     * @param string $keywords
     * @return bool
     */
    public static function validate_keywords($keywords) {
        if (empty($keywords)) {
            return false;
        }

        $keyword_list = explode(',', $keywords);
        $keyword_list = array_filter(array_map('trim', $keyword_list));

        return count($keyword_list) >= 1 && count($keyword_list) <= 10;
    }

    /**
     * اعتبارسنجی URL
     *
     * @param string $url
     * @return bool
     */
    public static function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * اعتبارسنجی کد رهگیری
     *
     * @param string $tracking_code
     * @return bool
     */
    public static function validate_tracking_code($tracking_code) {
        if (empty($tracking_code)) {
            return false;
        }

        // الگوهای رایج کدهای رهگیری گوگل آنالیتیکس، گوگل تگ منیجر و ...
        $patterns = [
            '/^UA-\d{4,10}-\d{1,4}$/i', // Google Analytics
            '/^GTM-[A-Z0-9]{1,10}$/i', // Google Tag Manager
            '/^AW-\d{1,10}$/i', // Google Ads
            '/^fbq\(\'init\', \'\d{1,20}\'\)$/i' // Facebook Pixel
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tracking_code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * اعتبارسنجی کد اسکیما
     *
     * @param string $schema
     * @return bool
     */
    public static function validate_schema($schema) {
        if (empty($schema)) {
            return false;
        }

        $decoded = json_decode($schema);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * اعتبارسنجی تنظیمات ربات‌ها
     *
     * @param string $robots
     * @return bool
     */
    public static function validate_robots($robots) {
        if (empty($robots)) {
            return false;
        }

        $allowed_values = ['index', 'noindex', 'follow', 'nofollow', 'noarchive'];
        $values = explode(',', $robots);
        $values = array_filter(array_map('trim', $values));

        foreach ($values as $value) {
            if (!in_array($value, $allowed_values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * اعتبارسنجی داده‌های OpenGraph
     *
     * @param array $og_data
     * @return bool
     */
    public static function validate_og_data($og_data) {
        $required_fields = ['og:title', 'og:type', 'og:url', 'og:image'];
        
        foreach ($required_fields as $field) {
            if (!isset($og_data[$field]) || empty($og_data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * اعتبارسنجی داده‌های Twitter Cards
     *
     * @param array $twitter_data
     * @return bool
     */
    public static function validate_twitter_data($twitter_data) {
        $required_fields = ['twitter:card', 'twitter:title'];
        
        foreach ($required_fields as $field) {
            if (!isset($twitter_data[$field]) || empty($twitter_data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * اعتبارسنجی کدهای HTML/JS
     *
     * @param string $code
     * @return bool
     */
    public static function validate_code($code) {
        if (empty($code)) {
            return false;
        }

        // بررسی تگ‌های اسکریپت و iframe
        if (preg_match('/<script[^>]*>[^<]*<\/script>/i', $code) || 
            preg_match('/<iframe[^>]*>[^<]*<\/iframe>/i', $code)) {
            return false;
        }

        return true;
    }

    /**
     * اعتبارسنجی مقدار عددی در محدوده مشخص
     *
     * @param mixed $number
     * @param int $min
     * @param int $max
     * @return bool
     */
    public static function validate_number($number, $min = 0, $max = 100) {
        if (!is_numeric($number)) {
            return false;
        }

        $number = floatval($number);
        return $number >= $min && $number <= $max;
    }
}
