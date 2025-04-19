<?php
/**
 * امنیت و حفاظت برای سئو
 * 
 * @package    SeoKar
 * @subpackage Security
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_Security {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // حفاظت در برابر اسکرپینگ
        add_action('init', [$this, 'prevent_content_scraping']);
        
        // جلوگیری از ایندکس شدن صفحات حساس
        add_action('wp_head', [$this, 'noindex_sensitive_pages'], 1);
        
        // امنیت هدرها
        add_action('send_headers', [$this, 'security_headers']);
        
        // حفاظت از فیدها
        add_filter('the_content_feed', [$this, 'protect_feed_content']);
        add_filter('the_excerpt_rss', [$this, 'protect_feed_content']);
        
        // جلوگیری از اسپم در نظرات
        add_filter('preprocess_comment', [$this, 'prevent_comment_spam']);
        
        // غیرفعال کردن REST API برای کاربران غیروارد شده
        add_filter('rest_authentication_errors', [$this, 'restrict_rest_api']);
    }

    /**
     * جلوگیری از اسکرپینگ محتوا
     */
    public function prevent_content_scraping() {
        if ($this->is_scraper_request()) {
            $this->log_scraping_attempt();
            wp_die(__('دسترسی به این محتوا مجاز نیست.', 'seokar'), __('دسترسی محدود', 'seokar'), 403);
        }
    }

    /**
     * تشخیص درخواست اسکرپینگ
     *
     * @return bool
     */
    private function is_scraper_request() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // لیست عوامل شناخته شده اسکرپینگ
        $scrapers = [
            'scraper', 'crawler', 'spider', 'bot', 'curl', 'wget', 
            'httrack', 'archive.org', 'python-requests', 'java/'
        ];
        
        // بررسی User-Agent
        foreach ($scrapers as $scraper) {
            if (stripos($user_agent, $scraper) !== false && 
                !$this->is_allowed_bot($user_agent)) {
                return true;
            }
        }
        
        // بررسی الگوهای درخواست غیرعادی
        $suspicious_patterns = [
            '/wp-content/', '/wp-includes/', '/feed/', '/comments/',
            '/xmlrpc.php', '/.env', '/wp-config.php'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (strpos($request_uri, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * بررسی اینکه آیا ربات مجاز است یا نه
     *
     * @param string $user_agent
     * @return bool
     */
    private function is_allowed_bot($user_agent) {
        $allowed_bots = [
            'googlebot', 'bingbot', 'yandexbot', 'duckduckbot',
            'slurp', 'facebookexternalhit', 'twitterbot'
        ];
        
        foreach ($allowed_bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ثبت تلاش برای اسکرپینگ
     */
    private function log_scraping_attempt() {
        $log_data = [
            'time' => current_time('mysql'),
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        $log_file = WP_CONTENT_DIR . '/seokar-security.log';
        file_put_contents($log_file, json_encode($log_data) . PHP_EOL, FILE_APPEND);
    }

    /**
     * دریافت IP کاربر
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP', 
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_FORWARDED', 
            'HTTP_X_CLUSTER_CLIENT_IP', 
            'HTTP_FORWARDED_FOR', 
            'HTTP_FORWARDED', 
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        
        return 'UNKNOWN';
    }

    /**
     * جلوگیری از ایندکس شدن صفحات حساس
     */
    public function noindex_sensitive_pages() {
        if (is_admin() || is_login_page() || is_feed() || is_robots() || 
            is_trackback() || is_search() || is_404() || is_attachment()) {
            echo '<meta name="robots" content="noindex,nofollow">' . PHP_EOL;
        }
        
        // صفحات ورود و مدیریت
        if (is_user_logged_in()) {
            echo '<meta name="robots" content="noindex,nofollow">' . PHP_EOL;
        }
    }

    /**
     * امنیت هدرها
     */
    public function security_headers() {
        // جلوگیری از MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // سیاست امنیتی محتوا
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https:",
            "style-src 'self' 'unsafe-inline' https:",
            "img-src 'self' data: https:",
            "font-src 'self' https:",
            "frame-src 'self' https:",
            "connect-src 'self' https:"
        ];
        
        header("Content-Security-Policy: " . implode('; ', $csp));
        
        // X-Frame-Options
        header('X-Frame-Options: SAMEORIGIN');
        
        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer-Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature-Policy
        $features = [
            "geolocation 'none'",
            "midi 'none'",
            "camera 'none'",
            "usb 'none'",
            "magnetometer 'none'",
            "gyroscope 'none'",
            "fullscreen 'self'",
            "payment 'none'"
        ];
        
        header("Feature-Policy: " . implode('; ', $features));
    }

    /**
     * حفاظت از محتوای فید
     *
     * @param string $content
     * @return string
     */
    public function protect_feed_content($content) {
        if (is_feed()) {
            $content .= PHP_EOL . PHP_EOL . __('این محتوا از سایت ' . get_bloginfo('name') . ' گرفته شده است.', 'seokar');
            $content .= PHP_EOL . __('لطفاً برای مشاهده محتوای کامل به سایت اصلی مراجعه کنید: ') . get_permalink();
        }
        return $content;
    }

    /**
     * جلوگیری از اسپم نظرات
     *
     * @param array $comment_data
     * @return array
     */
    public function prevent_comment_spam($comment_data) {
        // بررسی لینک‌های زیاد
        $link_count = preg_match_all('/<a[^>]*>/i', $comment_data['comment_content'], $matches);
        if ($link_count > 2) {
            wp_die(__('نظرات حاوی لینک‌های زیاد مجاز نیستند.', 'seokar'));
        }
        
        // بررسی کلمات اسپمی
        $spam_words = ['خرید', 'فروش', 'تبلیغات', 'لینک', 'سئو', 'ارزان', 'تخفیف'];
        foreach ($spam_words as $word) {
            if (stripos($comment_data['comment_content'], $word) !== false) {
                wp_die(__('نظر شما حاوی کلمات غیرمجاز است.', 'seokar'));
            }
        }
        
        return $comment_data;
    }

    /**
     * محدود کردن دسترسی به REST API
     *
     * @param WP_Error|null $result
     * @return WP_Error|null
     */
    public function restrict_rest_api($result) {
        if (!is_user_logged_in() && !$this->is_rest_request_allowed()) {
            return new WP_Error(
                'rest_unauthorized',
                __('دسترسی به REST API محدود شده است.', 'seokar'),
                ['status' => 401]
            );
        }
        return $result;
    }

    /**
     * بررسی اینکه آیا درخواست REST مجاز است یا نه
     *
     * @return bool
     */
    private function is_rest_request_allowed() {
        $allowed_routes = [
            '/wp/v2/posts',
            '/wp/v2/pages',
            '/wp/v2/categories'
        ];
        
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        
        foreach ($allowed_routes as $route) {
            if (strpos($rest_route, $route) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * بررسی صفحه ورود
     *
     * @return bool
     */
    private function is_login_page() {
        return in_array(
            $GLOBALS['pagenow'],
            ['wp-login.php', 'wp-register.php'],
            true
        );
    }
}
