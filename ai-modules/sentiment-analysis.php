<?php
/**
 * ماژول هوشمند تحلیل احساسات نظرات با قابلیت یادگیری
 * 
 * @package    AI_Sentiment_Analysis
 * @author     Your Name
 * @version    3.1.0
 * @license    GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Sentiment_Analyzer {

    private $lexicons = [
        'positive' => [
            'عالی', 'خوب', 'ممنون', 'فوقالعاده', 'بینظیر', 'عالیه',
            'دوست دارم', 'محشره', 'کامل', 'عالیست', 'پیشنهاد می‌کنم', 
            'عالی بود', 'خیلی خوب', 'کاربردی', 'مفید', 'پشتیبانی عالی',
            'رضایت', 'خوشحال', 'سپاس', 'ممنونم', 'تشکر', 'قدردانی'
        ],
        'negative' => [
            'بد', 'ضعیف', 'بی‌کیفیت', 'ناراحت', 'بدترین', 'افتضاح', 
            'بی‌مصرف', 'خراب', 'مشکل', 'انتقاد', 'ناراضی', 'بد بود',
            'به درد نمی‌خورد', 'پشتیبانی بد', 'نارضایتی', 'اعتراض',
            'انتقاد', 'ناراحت', 'عصبانی', 'ناامید', 'بی‌فایده'
        ],
        'neutral' => [
            'معمولی', 'متوسط', 'نه خوب نه بد', 'سوال', 'پرسش', 'نظر',
            'می‌خواهم', 'چرا', 'چطور', 'چگونه', 'کدام', 'کجا', 'چه',
            'کی', 'کسی', 'چیزی', 'سوالی', 'پاسخ', 'جواب'
        ]
    ];

    private $word_weights = [
        'فوقالعاده' => 2,
        'بینظیر' => 2,
        'بدترین' => -2,
        'افتضاح' => -2,
        'ممنون' => 1,
        'ناراضی' => -1.5,
        'پشتیبانی عالی' => 1.5,
        'پشتیبانی بد' => -1.5,
        'قدردانی' => 1.2,
        'اعتراض' => -1.3
    ];

    public function __construct() {
        $this->init_hooks();
        $this->maybe_load_adaptive_lexicons();
    }

    private function init_hooks() {
        add_filter('preprocess_comment', [$this, 'analyze_comment'], 10, 2);
        add_action('add_meta_boxes_comment', [$this, 'add_sentiment_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('comment_text', [$this, 'display_sentiment_badge'], 20, 2);
        add_action('wp_ajax_ai_save_sentiment_feedback', [$this, 'save_sentiment_feedback']);
    }

    private function maybe_load_adaptive_lexicons() {
        $adaptive_data = get_option('ai_sentiment_adaptive_data', []);
        
        if (!empty($adaptive_data['positive'])) {
            $this->lexicons['positive'] = array_unique(array_merge(
                $this->lexicons['positive'],
                $adaptive_data['positive']
            ));
        }
        
        if (!empty($adaptive_data['negative'])) {
            $this->lexicons['negative'] = array_unique(array_merge(
                $this->lexicons['negative'],
                $adaptive_data['negative']
            ));
        }
    }

    public function analyze_comment($comment_data, $comment_id) {
        if (empty($comment_data['comment_content'])) {
            return $comment_data;
        }

        $text = $this->normalize_text($comment_data['comment_content']);
        $score = $this->calculate_sentiment_score($text);
        $sentiment = $this->determine_sentiment($score);

        update_comment_meta($comment_id, '_ai_sentiment', sanitize_text_field($sentiment));
        update_comment_meta($comment_id, '_ai_sentiment_score', floatval($score));
        update_comment_meta($comment_id, '_ai_word_count', $this->count_persian_words($text));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Sentiment Analysis - Comment ID: {$comment_id}, Score: {$score}, Sentiment: {$sentiment}");
        }

        return $comment_data;
    }

    private function normalize_text($text) {
        $text = strip_tags($text);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        return $text;
    }

    private function calculate_sentiment_score($text) {
        $score = 0;
        $word_stats = ['positive' => 0, 'negative' => 0, 'neutral' => 0];

        foreach ($this->lexicons as $type => $words) {
            foreach ($words as $word) {
                $weight = $this->word_weights[$word] ?? 1;
                if ($this->word_exists_in_text($word, $text)) {
                    $score += ($type === 'positive') ? $weight : (($type === 'negative') ? -$weight : 0);
                    $word_stats[$type]++;
                }
            }
        }

        update_comment_meta(get_comment_ID(), '_ai_positive_words', $word_stats['positive']);
        update_comment_meta(get_comment_ID(), '_ai_negative_words', $word_stats['negative']);
        update_comment_meta(get_comment_ID(), '_ai_neutral_words', $word_stats['neutral']);

        $word_count = $this->count_persian_words($text);
        $score = $this->adjust_score_by_length($score, $word_count, $word_stats);

        if (preg_match('/\?|چرا|چطور|چگونه/u', $text)) {
            $score *= 0.7;
        }

        return round($score, 2);
    }

    private function word_exists_in_text($word, $text) {
        $pattern = '/\b' . preg_quote($word, '/') . '\b/u';
        return preg_match($pattern, $text);
    }

    private function count_persian_words($text) {
        preg_match_all('/[\p{Arabic}\p{Persian}\p{L}]+/u', $text, $matches);
        return count($matches[0]);
    }

    private function adjust_score_by_length($score, $word_count, $word_stats) {
        // کامنت‌های بسیار کوتاه (1-4 کلمه)
        if ($word_count >= 1 && $word_count <= 4) {
            // افزایش حساسیت برای کامنت‌های کوتاه
            $score *= 1.8;
        } 
        // کامنت‌های کوتاه (5-9 کلمه)
        elseif ($word_count >= 5 && $word_count <= 9) {
            $score *= 1.3;
        }
        // کامنت‌های طولانی (50+ کلمه)
        elseif ($word_count > 50) {
            $total_significant = $word_stats['positive'] + $word_stats['negative'];
            if ($total_significant > 0) {
                $ratio = ($word_stats['positive'] - $word_stats['negative']) / $total_significant;
                $score = $ratio * sqrt($word_count);
            } else {
                $score = 0;
            }
        }
        // کامنت‌های با طول متوسط (10-49 کلمه)
        else {
            if ($word_count > 0) {
                $score = $score / sqrt($word_count);
            }
        }

        return $score;
    }

    private function determine_sentiment($score) {
        if ($score > 0.5) {
            return 'positive';
        } elseif ($score < -0.5) {
            return 'negative';
        }
        return 'neutral';
    }

    public function add_sentiment_meta_box() {
        add_meta_box(
            'ai-sentiment-box',
            __('تحلیل احساسات هوشمند', 'ai-sentiment'),
            [$this, 'render_sentiment_meta_box'],
            'comment',
            'normal',
            'high'
        );
    }

    public function render_sentiment_meta_box($comment) {
        if (!isset($comment->comment_ID)) {
            echo '<p>کامنت معتبر نیست.</p>';
            return;
        }

        $sentiment = get_comment_meta($comment->comment_ID, '_ai_sentiment', true);
        $score = get_comment_meta($comment->comment_ID, '_ai_sentiment_score', true);
        $word_count = get_comment_meta($comment->comment_ID, '_ai_word_count', true);

        if (empty($sentiment)) {
            echo '<p>هنوز تحلیل انجام نشده است.</p>';
            return;
        }

        wp_nonce_field('ai_sentiment_meta_box', 'ai_sentiment_nonce');

        $colors = [
            'positive' => '#4CAF50',
            'negative' => '#F44336',
            'neutral' => '#2196F3'
        ];

        $icons = [
            'positive' => 'dashicons-smiley',
            'negative' => 'dashicons-frown',
            'neutral' => 'dashicons-neutral'
        ];

        $labels = [
            'positive' => 'مثبت',
            'negative' => 'منفی',
            'neutral' => 'خنثی'
        ];

        echo '<div class="ai-sentiment-result" style="padding: 15px; background: ' . esc_attr($colors[$sentiment]) . '; color: #FFF; border-radius: 4px;">';
        echo '<span class="dashicons ' . esc_attr($icons[$sentiment]) . '" style="vertical-align: middle; font-size: 24px;"></span>';
        echo '<div style="display: inline-block; vertical-align: middle; margin-left: 10px;">';
        echo '<h3 style="margin: 0 0 5px 0;">' . esc_html($labels[$sentiment]) . '</h3>';
        echo '<p style="margin: 0; font-size: 13px;">امتیاز: <strong>' . esc_html($score) . '</strong> | تعداد کلمات: <strong>' . esc_html($word_count) . '</strong></p>';
        echo '</div>';
        echo '</div>';

        $this->render_sentiment_chart($comment->comment_ID);
        $this->render_feedback_section($comment->comment_ID, $sentiment);
    }

    private function render_sentiment_chart($comment_id) {
        $word_stats = [
            'positive' => get_comment_meta($comment_id, '_ai_positive_words', true) ?: 0,
            'negative' => get_comment_meta($comment_id, '_ai_negative_words', true) ?: 0,
            'neutral' => get_comment_meta($comment_id, '_ai_neutral_words', true) ?: 0
        ];
        
        $total = array_sum($word_stats);
        $percentages = $total > 0 ? [
            'positive' => round(($word_stats['positive'] / $total) * 100),
            'negative' => round(($word_stats['negative'] / $total) * 100),
            'neutral' => 100 - round(($word_stats['positive'] / $total) * 100) - round(($word_stats['negative'] / $total) * 100)
        ] : ['positive' => 0, 'negative' => 0, 'neutral' => 0];
        
        echo '<div id="ai-sentiment-chart-container" style="margin-top: 20px;">';
        echo '<canvas id="ai-sentiment-chart-' . esc_attr($comment_id) . '" width="300" height="300"></canvas>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var ctx = document.getElementById("ai-sentiment-chart-' . esc_attr($comment_id) . '").getContext("2d");
                var chart = new Chart(ctx, {
                    type: "doughnut",
                    data: {
                        labels: ["کلمات مثبت", "کلمات منفی", "کلمات خنثی"],
                        datasets: [{
                            data: [' . $percentages['positive'] . ', ' . $percentages['negative'] . ', ' . $percentages['neutral'] . '],
                            backgroundColor: ["#4CAF50", "#F44336", "#2196F3"],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "70%",
                        plugins: {
                            legend: {
                                position: "bottom",
                                rtl: true,
                                labels: {
                                    boxWidth: 12,
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            });
        </script>';
        echo '</div>';
    }

    private function render_feedback_section($comment_id, $current_sentiment) {
        echo '<div class="ai-sentiment-feedback" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">';
        echo '<h4 style="margin-top: 0;">اصلاح تحلیل</h4>';
        echo '<p style="margin-bottom: 10px;">اگر تحلیل نادرست است، می‌توانید اصلاح کنید:</p>';
        
        echo '<select id="ai-sentiment-correction-' . esc_attr($comment_id) . '" style="margin-bottom: 10px;">';
        echo '<option value="positive"' . selected($current_sentiment, 'positive', false) . '>مثبت</option>';
        echo '<option value="negative"' . selected($current_sentiment, 'negative', false) . '>منفی</option>';
        echo '<option value="neutral"' . selected($current_sentiment, 'neutral', false) . '>خنثی</option>';
        echo '</select>';
        
        echo '<button type="button" class="button button-primary" onclick="aiSubmitSentimentCorrection(' . esc_attr($comment_id) . ')">ذخیره تغییرات</button>';
        echo '<p class="description" style="margin-top: 10px;">با ذخیره تغییرات، سیستم کلمات کلیدی این نظر را یاد می‌گیرد.</p>';
        echo '</div>';
    }

    public function save_sentiment_feedback() {
        check_ajax_referer('ai_sentiment_feedback', '_wpnonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }
        
        $comment_id = absint($_POST['comment_id']);
        $new_sentiment = sanitize_text_field($_POST['sentiment']);
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            wp_send_json_error('کامنت یافت نشد');
        }
        
        // ذخیره اصلاح کاربر
        update_comment_meta($comment_id, '_ai_sentiment', $new_sentiment);
        
        // یادگیری کلمات جدید
        $this->learn_from_feedback($comment->comment_content, $new_sentiment);
        
        wp_send_json_success();
    }

    private function learn_from_feedback($comment_text, $correct_sentiment) {
        $text = $this->normalize_text($comment_text);
        $words = preg_split('/\s+/u', $text);
        $adaptive_data = get_option('ai_sentiment_adaptive_data', [
            'positive' => [],
            'negative' => []
        ]);
        
        foreach ($words as $word) {
            if (mb_strlen($word) > 2) {
                if (!in_array($word, $this->lexicons['positive']) && 
                    !in_array($word, $this->lexicons['negative']) && 
                    !in_array($word, $this->lexicons['neutral'])) {
                    
                    if ($correct_sentiment === 'positive') {
                        $adaptive_data['positive'][] = $word;
                    } elseif ($correct_sentiment === 'negative') {
                        $adaptive_data['negative'][] = $word;
                    }
                }
            }
        }
        
        $adaptive_data['positive'] = array_unique($adaptive_data['positive']);
        $adaptive_data['negative'] = array_unique($adaptive_data['negative']);
        
        update_option('ai_sentiment_adaptive_data', $adaptive_data, false);
    }

    public function display_sentiment_badge($comment_text, $comment = null) {
        if (!is_admin() && $comment instanceof WP_Comment && isset($comment->comment_ID)) {
            $sentiment = get_comment_meta($comment->comment_ID, '_ai_sentiment', true);
            
            if ($sentiment) {
                $badges = [
                    'positive' => '<span class="ai-sentiment-badge positive" style="background: #4CAF50; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 10px; display: inline-block;">مثبت</span>',
                    'negative' => '<span class="ai-sentiment-badge negative" style="background: #F44336; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 10px; display: inline-block;">منفی</span>',
                    'neutral' => '<span class="ai-sentiment-badge neutral" style="background: #2196F3; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 10px; display: inline-block;">خنثی</span>'
                ];
                
                $comment_text .= ' ' . $badges[$sentiment];
            }
        }
        
        return $comment_text;
    }

    public function enqueue_admin_assets($hook) {
        if ('comment.php' === $hook || 'edit-comments.php' === $hook) {
            // استایل‌ها
            wp_enqueue_style(
                'ai-sentiment-admin',
                $this->get_css_url('sentiment-admin.css'),
                [],
                filemtime($this->get_css_path('sentiment-admin.css'))
            );
            
            // کتابخانه Chart.js
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '3.7.1',
                true
            );
            
            // اسکریپت اختصاصی
            wp_enqueue_script(
                'ai-sentiment-admin',
                $this->get_js_url('sentiment-admin.js'),
                ['jquery', 'chart-js'],
                filemtime($this->get_js_path('sentiment-admin.js')),
                true
            );
            
            // محلی‌سازی اسکریپت
            wp_localize_script(
                'ai-sentiment-admin',
                'aiSentimentData',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ai_sentiment_feedback')
                ]
            );
        }
    }

    private function get_css_url($file) {
        return get_stylesheet_directory_uri() . '/ai-modules/css/' . $file;
    }

    private function get_css_path($file) {
        return get_stylesheet_directory() . '/ai-modules/css/' . $file;
    }
    
    private function get_js_url($file) {
        return get_stylesheet_directory_uri() . '/ai-modules/js/' . $file;
    }
    
    private function get_js_path($file) {
        return get_stylesheet_directory() . '/ai-modules/js/' . $file;
    }
}

new AI_Sentiment_Analyzer();
