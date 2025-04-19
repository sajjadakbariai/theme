<?php
/**
 * مدیریت چندزبانی و ترجمه‌های سئو
 * 
 * @package    SeoKar
 * @subpackage Multilingual
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_Multilingual {

    /**
     * @var bool وضعیت فعال بودن WPML
     */
    private $wpml_active = false;

    /**
     * @var bool وضعیت فعال بودن Polylang
     */
    private $polylang_active = false;

    /**
     * سازنده کلاس
     */
    public function __construct() {
        $this->detect_multilingual_plugins();
        
        if ($this->wpml_active || $this->polylang_active) {
            add_filter('seokar_meta_title', [$this, 'translate_meta'], 10, 2);
            add_filter('seokar_meta_description', [$this, 'translate_meta'], 10, 2);
            add_filter('seokar_meta_keywords', [$this, 'translate_meta'], 10, 2);
            add_action('wp_head', [$this, 'add_hreflang_tags'], 2);
            add_filter('seokar_canonical_url', [$this, 'translate_canonical'], 10, 2);
        }
    }

    /**
     * تشخیص پلاگین‌های چندزبانه فعال
     */
    private function detect_multilingual_plugins() {
        // بررسی فعال بودن WPML
        $this->wpml_active = defined('ICL_SITEPRESS_VERSION');
        
        // بررسی فعال بودن Polylang
        $this->polylang_active = function_exists('pll_current_language');
    }

    /**
     * افزودن تگ‌های hreflang به هدر
     */
    public function add_hreflang_tags() {
        if (is_singular() || is_category() || is_tag() || is_post_type_archive()) {
            if ($this->wpml_active) {
                $this->add_wpml_hreflang_tags();
            } elseif ($this->polylang_active) {
                $this->add_polylang_hreflang_tags();
            }
        }
    }

    /**
     * افزودن تگ‌های hreflang برای WPML
     */
    private function add_wpml_hreflang_tags() {
        $languages = apply_filters('wpml_active_languages', null, ['skip_missing' => 0]);
        
        if (!empty($languages)) {
            foreach ($languages as $language) {
                $url = $this->get_wpml_translated_url($language['code']);
                if ($url) {
                    echo '<link rel="alternate" hreflang="' . esc_attr($language['language_code']) . '" href="' . esc_url($url) . '" />' . "\n";
                }
            }
            
            // افزودن نسخه x-default
            $default_language = apply_filters('wpml_default_language', null);
            $default_url = $this->get_wpml_translated_url($default_language);
            if ($default_url) {
                echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
            }
        }
    }

    /**
     * افزودن تگ‌های hreflang برای Polylang
     */
    private function add_polylang_hreflang_tags() {
        $languages = pll_the_languages(['raw' => 1, 'hide_if_empty' => 0]);
        
        if (!empty($languages)) {
            foreach ($languages as $language) {
                echo '<link rel="alternate" hreflang="' . esc_attr($language['slug']) . '" href="' . esc_url($language['url']) . '" />' . "\n";
            }
            
            // افزودن نسخه x-default
            $default_language = pll_default_language();
            $default_url = pll_home_url($default_language);
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
        }
    }

    /**
     * دریافت URL ترجمه شده در WPML
     *
     * @param string $lang_code کد زبان
     * @return string|null
     */
    private function get_wpml_translated_url($lang_code) {
        global $wp_query;
        
        $element_id = null;
        $element_type = null;
        
        if (is_singular()) {
            $element_id = get_queried_object_id();
            $element_type = get_post_type($element_id);
        } elseif (is_category() || is_tag() || is_tax()) {
            $element_id = get_queried_object()->term_id;
            $element_type = 'tax_' . get_queried_object()->taxonomy;
        } elseif (is_post_type_archive()) {
            $element_type = 'post_' . get_queried_object()->name;
        }
        
        if ($element_id && $element_type) {
            $translated_id = apply_filters('wpml_object_id', $element_id, $element_type, false, $lang_code);
            
            if ($translated_id) {
                if (strpos($element_type, 'tax_') === 0) {
                    return get_term_link($translated_id);
                } else {
                    return get_permalink($translated_id);
                }
            }
        }
        
        return apply_filters('wpml_home_url', home_url(), $lang_code);
    }

    /**
     * ترجمه متا دیتاها
     *
     * @param string $value مقدار فعلی
     * @param int $post_id آیدی پست
     * @return string
     */
    public function translate_meta($value, $post_id) {
        if (!$post_id) {
            return $value;
        }
        
        if ($this->wpml_active) {
            return $this->translate_wpml_meta($value, $post_id);
        } elseif ($this->polylang_active) {
            return $this->translate_polylang_meta($value, $post_id);
        }
        
        return $value;
    }

    /**
     * ترجمه متا دیتا در WPML
     *
     * @param string $value مقدار فعلی
     * @param int $post_id آیدی پست
     * @return string
     */
    private function translate_wpml_meta($value, $post_id) {
        $translated_id = apply_filters('wpml_object_id', $post_id, get_post_type($post_id), false);
        
        if ($translated_id && $translated_id != $post_id) {
            $meta_key = '';
            
            // تشخیص کلید متا بر اساس فیلتر فعلی
            $current_filter = current_filter();
            switch ($current_filter) {
                case 'seokar_meta_title':
                    $meta_key = '_seokar_title';
                    break;
                case 'seokar_meta_description':
                    $meta_key = '_seokar_description';
                    break;
                case 'seokar_meta_keywords':
                    $meta_key = '_seokar_keywords';
                    break;
            }
            
            if ($meta_key) {
                $translated_value = get_post_meta($translated_id, $meta_key, true);
                if (!empty($translated_value)) {
                    return $translated_value;
                }
            }
        }
        
        return $value;
    }

    /**
     * ترجمه متا دیتا در Polylang
     *
     * @param string $value مقدار فعلی
     * @param int $post_id آیدی پست
     * @return string
     */
    private function translate_polylang_meta($value, $post_id) {
        $translated_id = pll_get_post($post_id);
        
        if ($translated_id && $translated_id != $post_id) {
            $meta_key = '';
            
            // تشخیص کلید متا بر اساس فیلتر فعلی
            $current_filter = current_filter();
            switch ($current_filter) {
                case 'seokar_meta_title':
                    $meta_key = '_seokar_title';
                    break;
                case 'seokar_meta_description':
                    $meta_key = '_seokar_description';
                    break;
                case 'seokar_meta_keywords':
                    $meta_key = '_seokar_keywords';
                    break;
            }
            
            if ($meta_key) {
                $translated_value = get_post_meta($translated_id, $meta_key, true);
                if (!empty($translated_value)) {
                    return $translated_value;
                }
            }
        }
        
        return $value;
    }

    /**
     * ترجمه URL کانونیکال
     *
     * @param string $url آدرس فعلی
     * @param int $post_id آیدی پست
     * @return string
     */
    public function translate_canonical($url, $post_id) {
        if (!$post_id) {
            return $url;
        }
        
        if ($this->wpml_active) {
            $translated_id = apply_filters('wpml_object_id', $post_id, get_post_type($post_id), false);
            if ($translated_id && $translated_id != $post_id) {
                $translated_url = get_post_meta($translated_id, '_seokar_canonical', true);
                if (!empty($translated_url)) {
                    return $translated_url;
                }
                return get_permalink($translated_id);
            }
        } elseif ($this->polylang_active) {
            $translated_id = pll_get_post($post_id);
            if ($translated_id && $translated_id != $post_id) {
                $translated_url = get_post_meta($translated_id, '_seokar_canonical', true);
                if (!empty($translated_url)) {
                    return $translated_url;
                }
                return get_permalink($translated_id);
            }
        }
        
        return $url;
    }

    /**
     * دریافت زبان فعلی
     *
     * @return string
     */
    public function get_current_language() {
        if ($this->wpml_active) {
            return apply_filters('wpml_current_language', null);
        } elseif ($this->polylang_active) {
            return pll_current_language();
        }
        
        return get_locale();
    }

    /**
     * دریافت زبان پیش‌فرض
     *
     * @return string
     */
    public function get_default_language() {
        if ($this->wpml_active) {
            return apply_filters('wpml_default_language', null);
        } elseif ($this->polylang_active) {
            return pll_default_language();
        }
        
        return get_locale();
    }

    /**
     * بررسی آیا محتوای ترجمه شده وجود دارد یا نه
     *
     * @param int $post_id آیدی پست
     * @return bool
     */
    public function has_translation($post_id) {
        if ($this->wpml_active) {
            $translations = apply_filters('wpml_get_element_translations', null, $post_id, 'post_' . get_post_type($post_id));
            return count($translations) > 1;
        } elseif ($this->polylang_active) {
            return count(pll_get_post_translations($post_id)) > 1;
        }
        
        return false;
    }
}
