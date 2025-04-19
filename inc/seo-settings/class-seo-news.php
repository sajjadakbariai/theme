<?php
/**
 * مدیریت سئو اخبار و مقالات خبری
 * 
 * @package    SeoKar
 * @subpackage News
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_News {

    /**
     * @var array تنظیمات پیش‌فرض برای مقالات خبری
     */
    private $default_settings = [
        'news_keywords' => '',
        'news_publication' => '',
        'news_section' => 'general',
        'news_expiry' => ''
    ];

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // ثبت اسکیما مارکاپ مقالات خبری
        add_action('wp_head', [$this, 'add_news_schema'], 1);
        
        // اضافه کردن متا تگ‌های خبری
        add_action('wp_head', [$this, 'add_news_meta_tags'], 1);
        
        // اضافه کردن متا باکس به پست‌های خبری
        add_action('add_meta_boxes', [$this, 'add_news_meta_box']);
        add_action('save_post', [$this, 'save_news_meta'], 10, 2);
        
        // تنظیمات تیتر اخبار
        add_filter('the_title', [$this, 'filter_news_title'], 10, 2);
        
        // ثبت نوع پست خبری
        add_action('init', [$this, 'register_news_post_type']);
    }

    /**
     * ثبت نوع پست خبری اختصاصی
     */
    public function register_news_post_type() {
        register_post_type('seokar_news',
            [
                'labels' => [
                    'name' => __('اخبار', 'seokar'),
                    'singular_name' => __('خبر', 'seokar')
                ],
                'public' => true,
                'has_archive' => true,
                'rewrite' => ['slug' => 'news'],
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
                'show_in_rest' => true,
                'taxonomies' => ['category', 'post_tag']
            ]
        );
    }

    /**
     * افزودن اسکیما مارکاپ مقالات خبری
     */
    public function add_news_schema() {
        if (!$this->is_news_article()) {
            return;
        }

        $post_id = get_the_ID();
        $post = get_post($post_id);
        $author = get_userdata($post->post_author);
        $settings = $this->get_news_settings($post_id);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => get_the_title($post_id),
            'description' => wp_strip_all_tags(get_the_excerpt($post_id)),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name' => $author->display_name
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => !empty($settings['news_publication']) ? $settings['news_publication'] : get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->get_publisher_logo()
                ]
            ]
        ];

        // تصویر اصلی مقاله
        if (has_post_thumbnail($post_id)) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => get_the_post_thumbnail_url($post_id, 'full'),
                'width' => 1200,
                'height' => 630
            ];
        }

        // بخش خبری
        if (!empty($settings['news_section'])) {
            $schema['articleSection'] = $settings['news_section'];
        }

        // تاریخ انقضا
        if (!empty($settings['news_expiry'])) {
            $schema['expires'] = date('c', strtotime($settings['news_expiry']));
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * دریافت لوگو انتشار دهنده
     *
     * @return string
     */
    private function get_publisher_logo() {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            return $logo_url;
        }
        return get_template_directory_uri() . '/assets/images/logo.png';
    }

    /**
     * افزودن متا تگ‌های خبری
     */
    public function add_news_meta_tags() {
        if (!$this->is_news_article()) {
            return;
        }

        $post_id = get_the_ID();
        $settings = $this->get_news_settings($post_id);

        echo '<meta property="og:type" content="article">' . PHP_EOL;
        echo '<meta property="article:published_time" content="' . get_the_date('c', $post_id) . '">' . PHP_EOL;
        echo '<meta property="article:modified_time" content="' . get_the_modified_date('c', $post_id) . '">' . PHP_EOL;
        
        if (!empty($settings['news_section'])) {
            echo '<meta property="article:section" content="' . esc_attr($settings['news_section']) . '">' . PHP_EOL;
        }

        // نویسندگان
        $authors = $this->get_post_authors($post_id);
        foreach ($authors as $author) {
            echo '<meta property="article:author" content="' . esc_attr($author) . '">' . PHP_EOL;
        }

        // کلمات کلیدی
        if (!empty($settings['news_keywords'])) {
            echo '<meta property="article:tag" content="' . esc_attr($settings['news_keywords']) . '">' . PHP_EOL;
        }
    }

    /**
     * دریافت نویسندگان پست
     *
     * @param int $post_id
     * @return array
     */
    private function get_post_authors($post_id) {
        $authors = [];
        $post = get_post($post_id);
        $main_author = get_userdata($post->post_author);
        
        if ($main_author) {
            $authors[] = $main_author->display_name;
        }

        // اگر پست چند نویسنده دارد
        $coauthors = get_post_meta($post_id, '_seokar_coauthors', true);
        if (!empty($coauthors)) {
            $authors = array_merge($authors, explode(',', $coauthors));
        }

        return array_unique($authors);
    }

    /**
     * بررسی آیا محتوای فعلی یک مقاله خبری است
     *
     * @return bool
     */
    private function is_news_article() {
        if (!is_singular()) {
            return false;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);

        // بررسی انواع پست‌های خبری
        $news_post_types = ['post', 'seokar_news'];
        if (in_array($post_type, $news_post_types)) {
            $categories = get_the_category($post_id);
            foreach ($categories as $category) {
                if ($category->slug === 'news' || $category->name === 'اخبار') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * افزودن متا باکس اخبار به ویرایشگر
     */
    public function add_news_meta_box() {
        $post_types = ['post', 'seokar_news'];
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_news_meta_box',
                __('تنظیمات سئو اخبار', 'seokar'),
                [$this, 'render_news_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * نمایش محتوای متا باکس اخبار
     *
     * @param WP_Post $post
     */
    public function render_news_meta_box($post) {
        wp_nonce_field('seokar_save_news_meta', 'seokar_news_nonce');

        $settings = $this->get_news_settings($post->ID);
        $sections = $this->get_news_sections();
        ?>
        <div class="seokar-news-fields">
            <div class="seokar-field">
                <label for="seokar_news_keywords"><?php _e('کلمات کلیدی خبر', 'seokar'); ?></label>
                <input type="text" id="seokar_news_keywords" name="seokar_news_keywords" 
                       value="<?php echo esc_attr($settings['news_keywords']); ?>" 
                       placeholder="<?php _e('کلمات کلیدی مرتبط با خبر', 'seokar'); ?>">
                <p class="description"><?php _e('کلمات کلیدی را با کاما جدا کنید', 'seokar'); ?></p>
            </div>

            <div class="seokar-field">
                <label for="seokar_news_publication"><?php _e('نام انتشار دهنده', 'seokar'); ?></label>
                <input type="text" id="seokar_news_publication" name="seokar_news_publication" 
                       value="<?php echo esc_attr($settings['news_publication']); ?>" 
                       placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
            </div>

            <div class="seokar-field">
                <label for="seokar_news_section"><?php _e('بخش خبری', 'seokar'); ?></label>
                <select id="seokar_news_section" name="seokar_news_section">
                    <?php foreach ($sections as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['news_section'], $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="seokar-field">
                <label for="seokar_news_expiry"><?php _e('تاریخ انقضا', 'seokar'); ?></label>
                <input type="date" id="seokar_news_expiry" name="seokar_news_expiry" 
                       value="<?php echo esc_attr($settings['news_expiry']); ?>">
                <p class="description"><?php _e('تاریخی که خبر منقضی می‌شود (اختیاری)', 'seokar'); ?></p>
            </div>

            <div class="seokar-field">
                <label for="seokar_coauthors"><?php _e('نویسندگان مشترک', 'seokar'); ?></label>
                <input type="text" id="seokar_coauthors" name="seokar_coauthors" 
                       value="<?php echo esc_attr(get_post_meta($post->ID, '_seokar_coauthors', true)); ?>" 
                       placeholder="<?php _e('نام نویسندگان را با کاما جدا کنید', 'seokar'); ?>">
            </div>
        </div>
        <?php
    }

    /**
     * دریافت تنظیمات خبر
     *
     * @param int $post_id
     * @return array
     */
    private function get_news_settings($post_id) {
        $settings = [];
        foreach ($this->default_settings as $key => $default) {
            $settings[$key] = get_post_meta($post_id, '_seokar_' . $key, true) ?: $default;
        }
        return $settings;
    }

    /**
     * دریافت بخش‌های خبری
     *
     * @return array
     */
    private function get_news_sections() {
        return [
            'general' => __('عمومی', 'seokar'),
            'politics' => __('سیاسی', 'seokar'),
            'economy' => __('اقتصادی', 'seokar'),
            'sports' => __('ورزشی', 'seokar'),
            'technology' => __('فناوری', 'seokar'),
            'health' => __('سلامت', 'seokar'),
            'entertainment' => __('سرگرمی', 'seokar'),
            'science' => __('علمی', 'seokar')
        ];
    }

    /**
     * ذخیره تنظیمات خبر
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_news_meta($post_id, $post) {
        // بررسی nonce
        if (!isset($_POST['seokar_news_nonce']) || 
            !wp_verify_nonce($_POST['seokar_news_nonce'], 'seokar_save_news_meta')) {
            return;
        }

        // بررسی مجوزهای کاربر
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // ذخیره فیلدهای اصلی
        foreach ($this->default_settings as $key => $default) {
            $meta_key = '_seokar_' . $key;
            $value = isset($_POST['seokar_' . $key]) ? sanitize_text_field($_POST['seokar_' . $key]) : $default;
            update_post_meta($post_id, $meta_key, $value);
        }

        // ذخیره نویسندگان مشترک
        if (isset($_POST['seokar_coauthors'])) {
            update_post_meta($post_id, '_seokar_coauthors', sanitize_text_field($_POST['seokar_coauthors']));
        }
    }

    /**
     * فیلتر عنوان اخبار
     *
     * @param string $title
     * @param int $post_id
     * @return string
     */
    public function filter_news_title($title, $post_id = null) {
        if (!$post_id || !$this->is_news_article()) {
            return $title;
        }

        $settings = $this->get_news_settings($post_id);
        $publication = !empty($settings['news_publication']) ? $settings['news_publication'] : get_bloginfo('name');

        // اضافه کردن نام انتشار دهنده به عنوان خبر
        return $title . ' | ' . $publication;
    }
}
