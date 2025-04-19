<?php
/**
 * یکپارچه سازی با صفحه‌سازهای محبوب وردپرس
 * 
 * @package    SeoKar
 * @subpackage PageBuilders
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_PageBuilders {

    /**
     * @var array لیست صفحه‌سازهای پشتیبانی شده
     */
    private $supported_builders = [
        'elementor' => 'Elementor',
        'divi' => 'Divi Builder',
        'wpbakery' => 'WPBakery',
        'beaver' => 'Beaver Builder',
        'brizy' => 'Brizy',
        'oxygen' => 'Oxygen'
    ];

    /**
     * سازنده کلاس
     */
    public function __construct() {
        $this->detect_active_builder();
        
        // افزودن فیلدهای سئو به صفحه‌سازها
        add_action('init', [$this, 'add_seo_fields_to_builders']);
        
        // پردازش محتوای صفحه‌سازها
        add_filter('the_content', [$this, 'process_builder_content'], 5);
        
        // ذخیره داده‌های سئو
        add_action('save_post', [$this, 'save_builder_seo_data'], 20, 2);
    }

    /**
     * تشخیص صفحه‌ساز فعال
     */
    private function detect_active_builder() {
        foreach ($this->supported_builders as $slug => $name) {
            if (did_action($slug . '_loaded') || defined(strtoupper($slug) . '_VERSION')) {
                $this->active_builder = $slug;
                break;
            }
        }
    }

    /**
     * افزودن فیلدهای سئو به صفحه‌سازها
     */
    public function add_seo_fields_to_builders() {
        if (!$this->active_builder) {
            return;
        }

        switch ($this->active_builder) {
            case 'elementor':
                add_action('elementor/documents/register_controls', [$this, 'add_elementor_seo_controls']);
                break;
                
            case 'divi':
                add_action('et_builder_ready', [$this, 'add_divi_seo_fields']);
                break;
                
            case 'wpbakery':
                add_action('vc_after_init', [$this, 'add_wpbakery_seo_fields']);
                break;
                
            case 'beaver':
                add_filter('fl_builder_register_settings_form', [$this, 'add_beaver_seo_fields'], 10, 2);
                break;
        }
    }

    /**
     * افزودن کنترل‌های سئو به المنتور
     *
     * @param \Elementor\Core\DocumentTypes\PageBase $document
     */
    public function add_elementor_seo_controls($document) {
        if (!$document instanceof \Elementor\Core\DocumentTypes\PageBase) {
            return;
        }

        $document->start_controls_section(
            'seokar_seo_section',
            [
                'label' => __('تنظیمات سئو', 'seokar'),
                'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'seokar_meta_title',
            [
                'label' => __('عنوان سئو', 'seokar'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => __('عنوانی که در نتایج جستجو نمایش داده می‌شود', 'seokar'),
            ]
        );

        $document->add_control(
            'seokar_meta_description',
            [
                'label' => __('توضیحات متا', 'seokar'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'description' => __('توضیحاتی که در نتایج جستجو نمایش داده می‌شود', 'seokar'),
            ]
        );

        $document->end_controls_section();
    }

    /**
     * افزودن فیلدهای سئو به دیوی بیلدر
     */
    public function add_divi_seo_fields() {
        if (!class_exists('ET_Builder_Module')) {
            return;
        }

        $fields = [
            'seokar_meta_title' => [
                'label' => __('عنوان سئو', 'seokar'),
                'type' => 'text',
                'option_category' => 'basic_option',
                'description' => __('عنوانی که در نتایج جستجو نمایش داده می‌شود', 'seokar'),
                'tab_slug' => 'custom_css',
                'toggle_slug' => 'seo'
            ],
            'seokar_meta_description' => [
                'label' => __('توضیحات متا', 'seokar'),
                'type' => 'textarea',
                'option_category' => 'basic_option',
                'description' => __('توضیحاتی که در نتایج جستجو نمایش داده می‌شود', 'seokar'),
                'tab_slug' => 'custom_css',
                'toggle_slug' => 'seo'
            ]
        ];

        foreach ($fields as $field => $config) {
            et_builder_add_main_elements_option($field, $config);
        }
    }

    /**
     * پردازش محتوای صفحه‌سازها
     *
     * @param string $content
     * @return string
     */
    public function process_builder_content($content) {
        if (!$this->active_builder || !is_singular()) {
            return $content;
        }

        $post_id = get_the_ID();
        
        // بهینه‌سازی تصاویر
        if ($this->active_builder === 'elementor') {
            $content = $this->optimize_elementor_images($content, $post_id);
        }
        
        // بهینه‌سازی لینک‌ها
        $content = $this->optimize_links($content);
        
        // افزودن schema markup
        $content = $this->add_builder_schema($content, $post_id);
        
        return $content;
    }

    /**
     * بهینه‌سازی تصاویر المنتور
     *
     * @param string $content
     * @param int $post_id
     * @return string
     */
    private function optimize_elementor_images($content, $post_id) {
        if (strpos($content, 'elementor-widget-image') === false) {
            return $content;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            if (strpos($img->getAttribute('class'), 'elementor-widget-image') !== false) {
                // افزودن ویژگی loading="lazy"
                $img->setAttribute('loading', 'lazy');
                
                // افزودن متن alt اگر خالی است
                if (!$img->getAttribute('alt')) {
                    $img->setAttribute('alt', get_the_title($post_id));
                }
            }
        }
        
        return $dom->saveHTML();
    }

    /**
     * ذخیره داده‌های سئو صفحه‌سازها
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_builder_seo_data($post_id, $post) {
        if (!$this->active_builder || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $meta_fields = [
            'seokar_meta_title',
            'seokar_meta_description',
            'seokar_builder_content'
        ];

        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * افزودن اسکیما مارکاپ به محتوای صفحه‌سازها
     *
     * @param string $content
     * @param int $post_id
     * @return string
     */
    private function add_builder_schema($content, $post_id) {
        if (strpos($content, 'data-schema="true"') === false) {
            return $content;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'headline' => get_the_title($post_id),
            'description' => wp_strip_all_tags(get_the_excerpt($post_id)),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            ]
        ];

        $schema_json = '<script type="application/ld+json">' . 
                      json_encode($schema, JSON_UNESCAPED_SLASHES) . 
                      '</script>';

        return $content . $schema_json;
    }

    /**
     * بهینه‌سازی لینک‌ها در محتوا
     *
     * @param string $content
     * @return string
     */
    private function optimize_links($content) {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            // افزودن rel="nofollow" به لینک‌های خارجی
            if ($href && strpos($href, home_url()) === false) {
                $rel = $link->getAttribute('rel');
                if (strpos($rel, 'nofollow') === false) {
                    $link->setAttribute('rel', trim($rel . ' nofollow'));
                }
            }
            
            // افزودن title اگر خالی است
            if (!$link->getAttribute('title')) {
                $link->setAttribute('title', $link->textContent ?: __('جزئیات بیشتر', 'seokar'));
            }
        }
        
        return $dom->saveHTML();
    }

    /**
     * دریافت تنظیمات سئو از صفحه‌ساز
     *
     * @param int $post_id
     * @return array
     */
    public function get_builder_seo_data($post_id) {
        $data = [];
        
        if ($this->active_builder === 'elementor') {
            $document = \Elementor\Plugin::$instance->documents->get($post_id);
            if ($document) {
                $data['title'] = $document->get_settings('seokar_meta_title');
                $data['description'] = $document->get_settings('seokar_meta_description');
            }
        }
        
        return array_filter($data);
    }
}
