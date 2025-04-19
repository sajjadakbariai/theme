<?php
/**
 * مدیریت ریدایرکت‌های 301 و 302 در دیتابیس
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

class SEOKAR_Redirects_DB {

    /**
     * @var string نام جدول ریدایرکت‌ها
     */
    private $table_name;

    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'seokar_redirects';
    }

    /**
     * ایجاد جدول ریدایرکت‌ها
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_url varchar(255) NOT NULL,
            target_url varchar(255) NOT NULL,
            status_code smallint(3) NOT NULL DEFAULT 301,
            hits bigint(20) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY source_url (source_url),
            KEY status_code (status_code)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * حذف جدول ریدایرکت‌ها
     */
    public function drop_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }

    /**
     * افزودن ریدایرکت جدید
     *
     * @param array $data
     * @return int|false
     */
    public function insert_redirect($data) {
        global $wpdb;
        
        $defaults = [
            'status_code' => 301,
            'hits' => 0,
            'created_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // اعتبارسنجی داده‌ها
        if (!$this->validate_redirect_data($data)) {
            return false;
        }
        
        // نرمالایز کردن URLها
        $data['source_url'] = $this->normalize_url($data['source_url']);
        $data['target_url'] = $this->normalize_url($data['target_url']);
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%s', '%s', '%d', '%d', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * به‌روزرسانی ریدایرکت موجود
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_redirect($id, $data) {
        global $wpdb;
        
        if (!$this->validate_redirect_data($data)) {
            return false;
        }
        
        if (isset($data['source_url'])) {
            $data['source_url'] = $this->normalize_url($data['source_url']);
        }
        
        if (isset($data['target_url'])) {
            $data['target_url'] = $this->normalize_url($data['target_url']);
        }
        
        return (bool) $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            ['%s', '%s', '%d'],
            ['%d']
        );
    }

    /**
     * حذف ریدایرکت
     *
     * @param int $id
     * @return bool
     */
    public function delete_redirect($id) {
        global $wpdb;
        return (bool) $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * دریافت ریدایرکت بر اساس آدرس مبدأ
     *
     * @param string $source_url
     * @return array|null
     */
    public function get_redirect($source_url) {
        global $wpdb;
        
        $source_url = $this->normalize_url($source_url);
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE source_url = %s",
                $source_url
            ),
            ARRAY_A
        );
    }

    /**
     * دریافت تمام ریدایرکت‌ها
     *
     * @param array $args
     * @return array
     */
    public function get_redirects($args = []) {
        global $wpdb;
        
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'id',
            'order' => 'DESC',
            'search' => '',
            'status_code' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = [];
        $params = [];
        
        if (!empty($args['search'])) {
            $where[] = '(source_url LIKE %s OR target_url LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($args['status_code'])) {
            $where[] = 'status_code = %d';
            $params[] = $args['status_code'];
        }
        
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
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
        
        $query = "SELECT * FROM {$this->table_name} {$where_sql} {$order_sql} {$limit_sql}";
        
        if ($params) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * افزایش شمارنده بازدیدهای ریدایرکت
     *
     * @param int $id
     * @return bool
     */
    public function increment_redirect_hits($id) {
        global $wpdb;
        return (bool) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET hits = hits + 1 WHERE id = %d",
                $id
            )
        );
    }

    /**
     * اعتبارسنجی داده‌های ریدایرکت
     *
     * @param array $data
     * @return bool
     */
    private function validate_redirect_data($data) {
        if (empty($data['source_url']) || empty($data['target_url'])) {
            return false;
        }
        
        if (!in_array($data['status_code'], [301, 302, 307, 410])) {
            return false;
        }
        
        if ($data['source_url'] === $data['target_url']) {
            return false;
        }
        
        return true;
    }

    /**
     * نرمالایز کردن URL
     *
     * @param string $url
     * @return string
     */
    private function normalize_url($url) {
        $url = trim($url);
        $url = str_replace(home_url(), '', $url);
        $url = ltrim($url, '/');
        return $url;
    }

    /**
     * دریافت تعداد کل ریدایرکت‌ها
     *
     * @param array $args
     * @return int
     */
    public function get_total_redirects($args = []) {
        global $wpdb;
        
        $where = [];
        $params = [];
        
        if (!empty($args['search'])) {
            $where[] = '(source_url LIKE %s OR target_url LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($args['status_code'])) {
            $where[] = 'status_code = %d';
            $params[] = $args['status_code'];
        }
        
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";
        
        if ($params) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }
}
