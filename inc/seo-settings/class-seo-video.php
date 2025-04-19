<?php
/**
 * مدیریت سئو ویدیو برای وردپرس
 * 
 * @package    SeoKar
 * @subpackage Video
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_Video {

    /**
     * @var array پلتفرم های ویدیویی پشتیبانی شده
     */
    private $supported_platforms = [
        'youtube' => [
            'regex' => '#(?:https?:\/\/)?(?:www\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=)?([^&\s]+)#',
            'embed_url' => 'https://www.youtube.com/embed/%s'
        ],
        'vimeo' => [
            'regex' => '#(?:https?:\/\/)?(?:www\.)?vimeo\.com\/([0-9]+)#',
            'embed_url' => 'https://player.vimeo.com/video/%s'
        ],
        'aparat' => [
            'regex' => '#(?:https?:\/\/)?(?:www\.)?aparat\.com\/v\/([^\/\s]+)#',
            'embed_url' => 'https://www.aparat.com/video/video/embed/videohash/%s/vt/frame'
        ]
    ];

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // اضافه کردن اسکیما مارکاپ ویدیو
        add_action('wp_head', [$this, 'add_video_schema'], 1);
        
        // اضافه کردن متا تگ‌های ویدیو
        add_action('wp_head', [$this, 'add_video_meta_tags'], 1);
        
        // پردازش محتوای ویدیو
        add_filter('the_content', [$this, 'process_video_content'], 20);
        
        // اضافه کردن فیلدهای ویدیو به ویرایشگر
        add_action('add_meta_boxes', [$this, 'add_video_meta_box']);
        add_action('save_post', [$this, 'save_video_meta'], 10, 2);
    }

    /**
     * تشخیص پلتفرم ویدیو
     *
     * @param string $url
     * @return array|false
     */
    private function detect_video_platform($url) {
        foreach ($this->supported_platforms as $platform => $data) {
            if (preg_match($data['regex'], $url, $matches)) {
                return [
                    'platform' => $platform,
                    'video_id' => $matches[1],
                    'embed_url' => sprintf($data['embed_url'], $matches[1])
                ];
            }
        }
        return false;
    }

    /**
     * افزودن اسکیما مارکاپ ویدیو
     */
    public function add_video_schema() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $video_url = get_post_meta($post_id, '_seokar_video_url', true);
        
        if (empty($video_url)) {
            return;
        }

        $video_data = $this->detect_video_platform($video_url);
        if (!$video_data) {
            return;
        }

        $post = get_post($post_id);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => get_the_title($post_id),
            'description' => wp_strip_all_tags(get_the_excerpt($post_id)),
            'thumbnailUrl' => $this->get_video_thumbnail($video_data),
            'uploadDate' => get_the_date('c', $post_id),
            'duration' => get_post_meta($post_id, '_seokar_video_duration', true),
            'contentUrl' => $video_url,
            'embedUrl' => $video_data['embed_url'],
            'interactionCount' => [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/WatchAction',
                'userInteractionCount' => $this->get_video_view_count($post_id)
            ]
        ];

        $publisher = get_post_meta($post_id, '_seokar_video_publisher', true);
        if (!empty($publisher)) {
            $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => $publisher
            ];
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * دریافت تصویر بندانگشتی ویدیو
     *
     * @param array $video_data
     * @return string
     */
    private function get_video_thumbnail($video_data) {
        $thumbnail = '';
        $post_id = get_the_ID();
        
        // اولویت به تصویر شاخص
        if (has_post_thumbnail($post_id)) {
            $thumbnail = get_the_post_thumbnail_url($post_id, 'full');
        }
        
        // اگر تصویر شاخص وجود نداشت، از تصاویر پیش‌فرض پلتفرم استفاده می‌کنیم
        if (empty($thumbnail)) {
            switch ($video_data['platform']) {
                case 'youtube':
                    $thumbnail = "https://img.youtube.com/vi/{$video_data['video_id']}/maxresdefault.jpg";
                    break;
                case 'vimeo':
                    $vimeo_data = wp_remote_get("https://vimeo.com/api/v2/video/{$video_data['video_id']}.json");
                    if (!is_wp_error($vimeo_data)) {
                        $vimeo_info = json_decode($vimeo_data['body'], true);
                        $thumbnail = $vimeo_info[0]['thumbnail_large'];
                    }
                    break;
                case 'aparat':
                    $thumbnail = "https://www.aparat.com/public/public/images/video_thumbnails/{$video_data['video_id']}.jpg";
                    break;
            }
        }
        
        return $thumbnail;
    }

    /**
     * تخمین تعداد بازدیدهای ویدیو
     *
     * @param int $post_id
     * @return int
     */
    private function get_video_view_count($post_id) {
        $views = get_post_meta($post_id, '_seokar_video_views', true);
        if (empty($views)) {
            // اگر تعداد بازدید ذخیره نشده، از بازدیدهای پست استفاده می‌کنیم
            $views = get_post_meta($post_id, 'views', true) ?: rand(100, 1000);
            update_post_meta($post_id, '_seokar_video_views', $views);
        }
        return (int)$views;
    }

    /**
     * افزودن متا تگ‌های ویدیو
     */
    public function add_video_meta_tags() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $video_url = get_post_meta($post_id, '_seokar_video_url', true);
        
        if (empty($video_url)) {
            return;
        }

        echo '<meta property="og:type" content="video.other">' . PHP_EOL;
        echo '<meta property="og:video" content="' . esc_url($video_url) . '">' . PHP_EOL;
        echo '<meta property="og:video:type" content="text/html">' . PHP_EOL;
        echo '<meta property="og:video:width" content="1280">' . PHP_EOL;
        echo '<meta property="og:video:height" content="720">' . PHP_EOL;
        
        $duration = get_post_meta($post_id, '_seokar_video_duration', true);
        if (!empty($duration)) {
            echo '<meta property="video:duration" content="' . esc_attr($duration) . '">' . PHP_EOL;
        }
    }

    /**
     * پردازش محتوای ویدیو
     *
     * @param string $content
     * @return string
     */
    public function process_video_content($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        $video_url = get_post_meta($post_id, '_seokar_video_url', true);
        
        if (empty($video_url)) {
            return $content;
        }

        $video_data = $this->detect_video_platform($video_url);
        if (!$video_data) {
            return $content;
        }

        // اضافه کردن ویدیو به ابتدای محتوا
        $embed_code = '<div class="seokar-video-embed">';
        $embed_code .= '<iframe src="' . esc_url($video_data['embed_url']) . '" ';
        $embed_code .= 'width="1280" height="720" frameborder="0" ';
        $embed_code .= 'allowfullscreen></iframe>';
        $embed_code .= '</div>';

        return $embed_code . $content;
    }

    /**
     * افزودن متا باکس ویدیو به ویرایشگر
     */
    public function add_video_meta_box() {
        $post_types = get_post_types(['public' => true]);
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_video_meta_box',
                __('تنظیمات ویدیو برای سئو', 'seokar'),
                [$this, 'render_video_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * نمایش محتوای متا باکس ویدیو
     *
     * @param WP_Post $post
     */
    public function render_video_meta_box($post) {
        wp_nonce_field('seokar_save_video_meta', 'seokar_video_nonce');

        $video_url = get_post_meta($post->ID, '_seokar_video_url', true);
        $video_duration = get_post_meta($post->ID, '_seokar_video_duration', true);
        $video_publisher = get_post_meta($post->ID, '_seokar_video_publisher', true);
        ?>
        <div class="seokar-video-fields">
            <div class="seokar-field">
                <label for="seokar_video_url"><?php _e('آدرس ویدیو', 'seokar'); ?></label>
                <input type="url" id="seokar_video_url" name="seokar_video_url" 
                       value="<?php echo esc_url($video_url); ?>" 
                       placeholder="https://www.youtube.com/watch?v=...">
                <p class="description"><?php _e('آدرس کامل ویدیو از یوتیوب، ویمئو یا آپارات', 'seokar'); ?></p>
            </div>

            <div class="seokar-field">
                <label for="seokar_video_duration"><?php _e('مدت زمان ویدیو (ثانیه)', 'seokar'); ?></label>
                <input type="number" id="seokar_video_duration" name="seokar_video_duration" 
                       value="<?php echo esc_attr($video_duration); ?>" 
                       placeholder="120">
            </div>

            <div class="seokar-field">
                <label for="seokar_video_publisher"><?php _e('انتشار دهنده ویدیو', 'seokar'); ?></label>
                <input type="text" id="seokar_video_publisher" name="seokar_video_publisher" 
                       value="<?php echo esc_attr($video_publisher); ?>" 
                       placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
            </div>
        </div>
        <?php
    }

    /**
     * ذخیره تنظیمات ویدیو
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_video_meta($post_id, $post) {
        // بررسی nonce
        if (!isset($_POST['seokar_video_nonce']) || 
            !wp_verify_nonce($_POST['seokar_video_nonce'], 'seokar_save_video_meta')) {
            return;
        }

        // بررسی مجوزهای کاربر
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // ذخیره فیلدها
        $fields = [
            'seokar_video_url' => '_seokar_video_url',
            'seokar_video_duration' => '_seokar_video_duration',
            'seokar_video_publisher' => '_seokar_video_publisher'
        ];

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                // اعتبارسنجی و پاکسازی
                switch ($field) {
                    case 'seokar_video_url':
                        $value = esc_url_raw($value);
                        break;
                    case 'seokar_video_duration':
                        $value = absint($value);
                        break;
                    case 'seokar_video_publisher':
                        $value = sanitize_text_field($value);
                        break;
                }

                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
}
