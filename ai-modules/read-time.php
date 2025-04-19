<?php
/**
 * ماژول محاسبه زمان مطالعه
 * آدرس: /seokar/ai-modules/read-time.php
 * نسخه پیشرفته با قابلیت های بیشتر
 */

if (!defined('ABSPATH')) {
    exit;
}

class Read_Time_Calculator {
    /**
     * تعداد کلمات در دقیقه (سرعت مطالعه پیش فرض)
     */
    const WORDS_PER_MINUTE = 200;
    
    /**
     * محاسبه زمان مطالعه بر اساس محتوا
     *
     * @param string $content محتوای مقاله
     * @return int زمان مطالعه به دقیقه (گرد شده به بالا)
     */
    public static function calculate_read_time($content) {
        $clean_content = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_content);
        return max(1, ceil($word_count / self::WORDS_PER_MINUTE));
    }
    
    /**
     * نمایش زمان مطالعه با قالب بندی زیبا
     *
     * @param int $minutes زمان مطالعه
     * @return string HTML خروجی
     */
    public static function display_read_time($minutes) {
        return sprintf(
            '<div class="read-time" aria-label="زمان مطالعه مقاله">
                <span class="read-time__icon">⏱</span>
                <span class="read-time__text">زمان مطالعه: %d دقیقه</span>
            </div>',
            $minutes
        );
    }
}

/**
 * افزودن زمان مطالعه به محتوای مقاله
 *
 * @param string $content محتوای اصلی مقاله
 * @return string محتوای ویرایش شده
 */
function ai_enhanced_read_time_filter($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $read_time = Read_Time_Calculator::calculate_read_time($content);
        $read_time_html = Read_Time_Calculator::display_read_time($read_time);
        
        // اضافه کردن زمان مطالعه قبل از محتوا
        return $read_time_html . $content;
    }
    
    return $content;
}
add_filter('the_content', 'ai_enhanced_read_time_filter', 10);

/**
 * استایل های CSS برای نمایش زیبای زمان مطالعه
 */
function ai_read_time_styles() {
    if (is_single()) {
        echo '
        <style>
            .read-time {
                background: #f8f9fa;
                border-radius: 4px;
                padding: 8px 12px;
                margin-bottom: 20px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 0.9em;
                color: #495057;
                border-left: 3px solid #4dabf7;
            }
            .read-time__icon {
                font-size: 1.1em;
            }
            .read-time__text {
                font-weight: 500;
            }
        </style>';
    }
}
add_action('wp_head', 'ai_read_time_styles');
