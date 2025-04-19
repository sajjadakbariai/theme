<?php
/**
 * ماژول پیشرفته محاسبه زمان مطالعه
 * نسخه حرفه‌ای با CSS سفارشی
 */

if (!defined('ABSPATH')) {
    exit;
}

class Advanced_Read_Time_Calculator {
    const WORDS_PER_MINUTE = 200;
    const VERSION = '2.0.0';
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_filter('the_content', [$this, 'add_read_time_to_content']);
    }
    
    public function calculate_read_time($content) {
        $clean_content = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_content);
        return max(1, ceil($word_count / self::WORDS_PER_MINUTE));
    }
    
    public function generate_read_time_html($minutes) {
        $icons = ['⏱', '📖', '🕒', '📚', '⌛'];
        $random_icon = $icons[array_rand($icons)];
        
        return sprintf(
            '<div class="art-read-time-container" data-readtime="%d">
                <div class="art-read-time">
                    <span class="art-read-time-icon">%s</span>
                    <span class="art-read-time-text">زمان مطالعه: <strong>%d دقیقه</strong></span>
                    <div class="art-progress-bar">
                        <div class="art-progress-fill" style="width: 0%%"></div>
                    </div>
                </div>
            </div>',
            $minutes,
            $random_icon,
            $minutes
        );
    }
    
    public function enqueue_styles() {
        if (is_single()) {
            wp_enqueue_style(
                'advanced-read-time-css',
                plugin_dir_url(__FILE__) . 'css/read-time.css',
                [],
                self::VERSION
            );
            
            wp_enqueue_script(
                'advanced-read-time-js',
                plugin_dir_url(__FILE__) . 'js/read-time.js',
                ['jquery'],
                self::VERSION,
                true
            );
        }
    }
    
    public function add_read_time_to_content($content) {
        if (is_single() && in_the_loop() && is_main_query()) {
            $read_time = $this->calculate_read_time($content);
            $read_time_html = $this->generate_read_time_html($read_time);
            return $read_time_html . $content;
        }
        return $content;
    }
}

new Advanced_Read_Time_Calculator();
