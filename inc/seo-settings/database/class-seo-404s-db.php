<?php
/**
 * مدیریت خطاهای 404 و رکورد آنها در دیتابیس
 * 
 * @package    SeoKar
 * @subpackage Database
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOKAR_404s_DB {

    /**
     * @var string نام جدول خطاهای 404
     */
    private $table_name;

    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'seokar_404s';
    }

    /**
     * ایجاد جدول خطاهای 404
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            referrer varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            hit_count int(11) NOT NULL DEFAULT 1,
            first_hit datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_hit datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            resolved tinyint(1) NOT NULL DEFAULT 0,
            redirect_to varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY url (url),
            KEY hit_count (hit_count),
            KEY resolved (resolved)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * حذف جدول خطاهای 404
     */
    public function drop_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }

    /**
     * ثبت خطای 404 جدید یا افزایش تعداد بازدید
     *
     * @param array $data
     * @return int|false
     */
    public function record_404($data) {
        global $wpdb;
        
        $defaults = [
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // بررسی وجود URL در رکوردهای موجود
        $existing = $this->get_404_by_url($data['url']);
        
        if ($existing) {
            // افزایش تعداد بازدید
            return $this->increment_404_hits($existing['id']);
        } else {
            // ثبت رکورد جدید
            return $wpdb->insert(
                $this->table_name,
                $data,
                ['%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * افزایش تعداد بازدید یک خطای 404
     *
     * @param int $id
     * @return bool
     */
    public function increment_404_hits($id) {
        global $wpdb;
        return (bool) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} 
                SET hit_count = hit_count + 1, 
                    last_hit = CURRENT_TIMESTAMP 
                WHERE id = %d",
                $id
            )
        );
    }

    /**
     * دریافت خطای 404 بر اساس URL
     *
     * @param string $url
     * @return array|null
     */
    public function get_404_by_url($url) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE url = %s",
                $url
            ),
            ARRAY_A
        );
    }

    /**
     * دریافت تمام خطاهای 404
     *
     * @param array $args
     * @return array
     */
    public function get_404s($args = []) {
        global $wpdb;
        
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'hit_count',
            'order' => 'DESC',
            'search' => '',
            'resolved' => 0,
            'days' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($args['search'])) {
            $where[] = '(url LIKE %s OR referrer LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if ($args['resolved'] !== 'all') {
            $where[] = 'resolved = %d';
            $params[] = (int) $args['resolved'];
        }
        
        if ($args['days'] > 0) {
            $where[] = 'last_hit >= DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)';
            $params[] = (int) $args['days'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $order_sql = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']),
            strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = $wpdb->prepare(
            'LIMIT %d, %d',
            ($args['page'] - 1) * $args['per_page'],
            $args['per_page']
        );
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_sql} {$order_sql} {$limit_sql}";
        
        if ($params) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * دریافت تعداد کل خطاهای 404
     *
     * @param array $args
     * @return int
     */
    public function get_total_404s($args = []) {
        global $wpdb;
        
        $defaults = [
            'search' => '',
            'resolved' => 0,
            'days' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($args['search'])) {
            $where[] = '(url LIKE %s OR referrer LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if ($args['resolved'] !== 'all') {
            $where[] = 'resolved = %d';
            $params[] = (int) $args['resolved'];
        }
        
        if ($args['days'] > 0) {
            $where[] = 'last_hit >= DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)';
            $params[] = (int) $args['days'];
        }
        
        $where_sql = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        
        if ($params) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * علامت گذاری خطای 404 به عنوان حل شده
     *
     * @param int $id
     * @param string $redirect_to
     * @return bool
     */
    public function mark_as_resolved($id, $redirect_to = '') {
        global $wpdb;
        
        return (bool) $wpdb->update(
            $this->table_name,
            [
                'resolved' => 1,
                'redirect_to' => $redirect_to
            ],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * حذف خطای 404
     *
     * @param int $id
     * @return bool
     */
    public function delete_404($id) {
        global $wpdb;
        return (bool) $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
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
     * پاکسازی رکوردهای قدیمی
     *
     * @param int $days_old
     * @return int
     */
    public function cleanup_old_404s($days_old = 30) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE last_hit < DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)
                AND resolved = 0",
                $days_old
            )
        );
    }
}
