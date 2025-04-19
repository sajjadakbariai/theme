<?php
/**
 * SEOKAR for WordPress Themes - Redirects Manager Module
 * 
 * @package    SeoKar
 * @subpackage Redirects
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Redirects implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Redirect types
     *
     * @var array
     */
    private $redirect_types = [
        301 => '301 Moved Permanently',
        302 => '302 Found (Temporary)',
        307 => '307 Temporary Redirect',
        410 => '410 Gone',
        451 => '451 Unavailable For Legal Reasons'
    ];

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('template_redirect', [$this, 'handle_redirects'], 1);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_seokar_add_redirect', [$this, 'handle_add_redirect']);
        add_action('admin_post_seokar_edit_redirect', [$this, 'handle_edit_redirect']);
        add_action('admin_post_seokar_delete_redirect', [$this, 'handle_delete_redirect']);
        add_action('admin_post_seokar_import_redirects', [$this, 'handle_import_redirects']);
        add_action('admin_post_seokar_export_redirects', [$this, 'handle_export_redirects']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Handle redirects
     */
    public function handle_redirects() {
        global $wpdb;

        $request_uri = $this->get_request_uri();
        $redirect = $this->get_redirect($request_uri);

        if ($redirect) {
            // Handle regex redirects
            if ($redirect['regex']) {
                $target = preg_replace($redirect['source'], $redirect['target'], $request_uri);
            } else {
                $target = $redirect['target'];
            }

            // Handle query parameters
            if ($redirect['keep_params']) {
                $query_string = $_SERVER['QUERY_STRING'];
                if (!empty($query_string)) {
                    $target .= (strpos($target, '?') === false ? '?' : '&') . $query_string;
                }
            }

            // Do the redirect
            header('HTTP/1.1 ' . $this->redirect_types[$redirect['type']]);
            header('Location: ' . $target, true, $redirect['type']);
            exit;
        }

        // Log 404s if enabled
        $options = get_option('seokar_options');
        if (!empty($options['log_404s'])) {
            $this->log_404($request_uri);
        }
    }

    /**
     * Get request URI
     *
     * @return string
     */
    private function get_request_uri() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_uri = strtok($request_uri, '?'); // Remove query string
        $request_uri = urldecode($request_uri); // Decode URL
        $request_uri = trim($request_uri, '/'); // Trim slashes

        return $request_uri;
    }

    /**
     * Get redirect for a URL
     *
     * @param string $url
     * @return array|false
     */
    public function get_redirect($url) {
        global $wpdb;

        // Check exact matches first
        $redirect = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}seokar_redirects 
                WHERE source = %s AND regex = 0 AND enabled = 1 
                ORDER BY id DESC LIMIT 1",
                $url
            ),
            ARRAY_A
        );

        if ($redirect) {
            return $redirect;
        }

        // Check regex matches
        $redirects = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}seokar_redirects 
            WHERE regex = 1 AND enabled = 1",
            ARRAY_A
        );

        foreach ($redirects as $redirect) {
            if (@preg_match($redirect['source'], $url)) {
                return $redirect;
            }
        }

        return false;
    }

    /**
     * Add redirect
     *
     * @param array $data
     * @return int|false
     */
    public function add_redirect($data) {
        global $wpdb;

        $defaults = [
            'source' => '',
            'target' => '',
            'type' => 301,
            'regex' => 0,
            'keep_params' => 0,
            'enabled' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        // Validate
        if (empty($data['source']) || empty($data['target'])) {
            return false;
        }

        // Sanitize
        $data['source'] = $this->sanitize_source($data['source'], $data['regex']);
        $data['target'] = $this->sanitize_target($data['target']);

        // Check for duplicates
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects 
                WHERE source = %s AND regex = %d",
                $data['source'],
                $data['regex']
            )
        );

        if ($exists) {
            return false;
        }

        // Insert
        $result = $wpdb->insert(
            "{$wpdb->prefix}seokar_redirects",
            $data,
            ['%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s']
        );

        if (!$result) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update redirect
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_redirect($id, $data) {
        global $wpdb;

        $old_redirect = $this->get_redirect_by_id($id);
        if (!$old_redirect) {
            return false;
        }

        $defaults = [
            'source' => $old_redirect['source'],
            'target' => $old_redirect['target'],
            'type' => $old_redirect['type'],
            'regex' => $old_redirect['regex'],
            'keep_params' => $old_redirect['keep_params'],
            'enabled' => $old_redirect['enabled'],
            'updated_at' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        // Validate
        if (empty($data['source']) || empty($data['target'])) {
            return false;
        }

        // Sanitize
        $data['source'] = $this->sanitize_source($data['source'], $data['regex']);
        $data['target'] = $this->sanitize_target($data['target']);

        // Check for duplicates (excluding current redirect)
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects 
                WHERE source = %s AND regex = %d AND id != %d",
                $data['source'],
                $data['regex'],
                $id
            )
        );

        if ($exists) {
            return false;
        }

        // Update
        $result = $wpdb->update(
            "{$wpdb->prefix}seokar_redirects",
            $data,
            ['id' => $id],
            ['%s', '%s', '%d', '%d', '%d', '%d', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete redirect
     *
     * @param int $id
     * @return bool
     */
    public function delete_redirect($id) {
        global $wpdb;

        return $wpdb->delete(
            "{$wpdb->prefix}seokar_redirects",
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Get redirect by ID
     *
     * @param int $id
     * @return array|false
     */
    public function get_redirect_by_id($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}seokar_redirects WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
    }

    /**
     * Get all redirects
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
            'enabled' => null,
            'regex' => null
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$wpdb->prefix}seokar_redirects";
        $where = [];
        $params = [];

        // Search
        if (!empty($args['search'])) {
            $where[] = "(source LIKE %s OR target LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        // Enabled
        if ($args['enabled'] !== null) {
            $where[] = "enabled = %d";
            $params[] = $args['enabled'];
        }

        // Regex
        if ($args['regex'] !== null) {
            $where[] = "regex = %d";
            $params[] = $args['regex'];
        }

        // Build WHERE clause
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        // Order
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Pagination
        if ($args['per_page'] > 0) {
            $offset = ($args['page'] - 1) * $args['per_page'];
            $query .= " LIMIT %d, %d";
            $params[] = $offset;
            $params[] = $args['per_page'];
        }

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Count redirects
     *
     * @param array $args
     * @return int
     */
    public function count_redirects($args = []) {
        global $wpdb;

        $defaults = [
            'search' => '',
            'enabled' => null,
            'regex' => null
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_redirects";
        $where = [];
        $params = [];

        // Search
        if (!empty($args['search'])) {
            $where[] = "(source LIKE %s OR target LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        // Enabled
        if ($args['enabled'] !== null) {
            $where[] = "enabled = %d";
            $params[] = $args['enabled'];
        }

        // Regex
        if ($args['regex'] !== null) {
            $where[] = "regex = %d";
            $params[] = $args['regex'];
        }

        // Build WHERE clause
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Sanitize source URL
     *
     * @param string $source
     * @param bool $regex
     * @return string
     */
    private function sanitize_source($source, $regex = false) {
        if ($regex) {
            // Validate regex
            if (@preg_match($source, null) === false) {
                return '';
            }
            return $source;
        }

        // Normal URL
        $source = trim($source, '/');
        $source = str_replace(home_url(), '', $source);
        $source = trim($source, '/');

        return $source;
    }

    /**
     * Sanitize target URL
     *
     * @param string $target
     * @return string
     */
    private function sanitize_target($target) {
        if (preg_match('/^https?:\/\//i', $target)) {
            return esc_url_raw($target);
        }

        $target = trim($target, '/');
        return $target;
    }

    /**
     * Log 404 requests
     *
     * @param string $url
     * @return bool
     */
    private function log_404($url) {
        global $wpdb;

        // Check if already logged
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_404s 
                WHERE url = %s AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $url
            )
        );

        if ($exists) {
            // Update count for existing entry
            return $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}seokar_404s 
                    SET hits = hits + 1, last_hit = %s 
                    WHERE url = %s",
                    current_time('mysql'),
                    $url
                )
            );
        }

        // Insert new entry
        return $wpdb->insert(
            "{$wpdb->prefix}seokar_404s",
            [
                'url' => $url,
                'hits' => 1,
                'created_at' => current_time('mysql'),
                'last_hit' => current_time('mysql'),
                'referer' => $_SERVER['HTTP_REFERER'] ?? ''
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Get 404 logs
     *
     * @param array $args
     * @return array
     */
    public function get_404s($args = []) {
        global $wpdb;

        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'hits',
            'order' => 'DESC',
            'search' => '',
            'date_from' => '',
            'date_to' => ''
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$wpdb->prefix}seokar_404s";
        $where = [];
        $params = [];

        // Search
        if (!empty($args['search'])) {
            $where[] = "(url LIKE %s OR referer LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        // Date range
        if (!empty($args['date_from'])) {
            $where[] = "last_hit >= %s";
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = "last_hit <= %s";
            $params[] = $args['date_to'];
        }

        // Build WHERE clause
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        // Order
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Pagination
        if ($args['per_page'] > 0) {
            $offset = ($args['page'] - 1) * $args['per_page'];
            $query .= " LIMIT %d, %d";
            $params[] = $offset;
            $params[] = $args['per_page'];
        }

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Count 404 logs
     *
     * @param array $args
     * @return int
     */
    public function count_404s($args = []) {
        global $wpdb;

        $defaults = [
            'search' => '',
            'date_from' => '',
            'date_to' => ''
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}seokar_404s";
        $where = [];
        $params = [];

        // Search
        if (!empty($args['search'])) {
            $where[] = "(url LIKE %s OR referer LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        // Date range
        if (!empty($args['date_from'])) {
            $where[] = "last_hit >= %s";
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = "last_hit <= %s";
            $params[] = $args['date_to'];
        }

        // Build WHERE clause
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Delete 404 log
     *
     * @param int $id
     * @return bool
     */
    public function delete_404($id) {
        global $wpdb;

        return $wpdb->delete(
            "{$wpdb->prefix}seokar_404s",
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Clear all 404 logs
     *
     * @return bool
     */
    public function clear_404s() {
        global $wpdb;

        return $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}seokar_404s");
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seo-settings',
            __('Redirects', 'seokar'),
            __('Redirects', 'seokar'),
            'manage_options',
            'seokar-redirects',
            [$this, 'render_redirects_page']
        );

        add_submenu_page(
            'seo-settings',
            __('404 Logs', 'seokar'),
            __('404 Logs', 'seokar'),
            'manage_options',
            'seokar-404s',
            [$this, 'render_404s_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('seokar_redirects_settings', 'seokar_options');

        add_settings_section(
            'seokar_redirects_section',
            __('Redirect Settings', 'seokar'),
            [$this, 'render_redirects_section'],
            'seokar-redirects'
        );

        add_settings_field(
            'log_404s',
            __('Log 404 Errors', 'seokar'),
            [$this, 'render_log_404s_field'],
            'seokar-redirects',
            'seokar_redirects_section'
        );

        add_settings_field(
            'redirect_404s',
            __('Auto Redirect 404s', 'seokar'),
            [$this, 'render_redirect_404s_field'],
            'seokar-redirects',
            'seokar_redirects_section'
        );
    }

    /**
     * Render redirects page
     */
    public function render_redirects_page() {
        $action = $_GET['action'] ?? 'list';
        $id = $_GET['id'] ?? 0;

        switch ($action) {
            case 'add':
                $this->render_add_redirect_page();
                break;
            case 'edit':
                $this->render_edit_redirect_page($id);
                break;
            case 'import':
                $this->render_import_page();
                break;
            case 'export':
                $this->render_export_page();
                break;
            default:
                $this->render_redirects_list();
        }
    }

    /**
     * Render 404s page
     */
    public function render_404s_page() {
        $this->render_404s_list();
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'seokar-redirects') === false && strpos($hook, 'seokar-404s') === false) {
            return;
        }

        wp_enqueue_style(
            'seokar-redirects',
            SEOKAR_URL . '/admin/assets/css/redirects.css',
            [],
            SEOKAR_VERSION
        );

        wp_enqueue_script(
            'seokar-redirects',
            SEOKAR_URL . '/admin/assets/js/redirects.js',
            ['jquery'],
            SEOKAR_VERSION,
            true
        );
    }

    /**
     * Handle add redirect form submission
     */
    public function handle_add_redirect() {
        // Verify nonce
        if (!isset($_POST['seokar_redirect_nonce']) || !wp_verify_nonce($_POST['seokar_redirect_nonce'], 'seokar_add_redirect')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        // Process form data
        $data = [
            'source' => $_POST['source'] ?? '',
            'target' => $_POST['target'] ?? '',
            'type' => (int) ($_POST['type'] ?? 301),
            'regex' => isset($_POST['regex']) ? 1 : 0,
            'keep_params' => isset($_POST['keep_params']) ? 1 : 0,
            'enabled' => isset($_POST['enabled']) ? 1 : 0
        ];

        $result = $this->add_redirect($data);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&added=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&error=1'));
        }

        exit;
    }

    /**
     * Handle edit redirect form submission
     */
    public function handle_edit_redirect() {
        // Verify nonce
        if (!isset($_POST['seokar_redirect_nonce']) || !wp_verify_nonce($_POST['seokar_redirect_nonce'], 'seokar_edit_redirect')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        $id = (int) ($_POST['id'] ?? 0);

        // Process form data
        $data = [
            'source' => $_POST['source'] ?? '',
            'target' => $_POST['target'] ?? '',
            'type' => (int) ($_POST['type'] ?? 301),
            'regex' => isset($_POST['regex']) ? 1 : 0,
            'keep_params' => isset($_POST['keep_params']) ? 1 : 0,
            'enabled' => isset($_POST['enabled']) ? 1 : 0
        ];

        $result = $this->update_redirect($id, $data);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&updated=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&error=1'));
        }

        exit;
    }

    /**
     * Handle delete redirect
     */
    public function handle_delete_redirect() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'seokar_delete_redirect')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        $id = (int) ($_GET['id'] ?? 0);

        $result = $this->delete_redirect($id);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&deleted=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&error=1'));
        }

        exit;
    }

    /**
     * Handle import redirects
     */
    public function handle_import_redirects() {
        // Verify nonce
        if (!isset($_POST['seokar_import_nonce']) || !wp_verify_nonce($_POST['seokar_import_nonce'], 'seokar_import_redirects')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        // Check file upload
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&action=import&error=1'));
            exit;
        }

        // Process file
        $file = $_FILES['import_file']['tmp_name'];
        $content = file_get_contents($file);
        $redirects = json_decode($content, true);

        if (empty($redirects) || !is_array($redirects)) {
            wp_redirect(admin_url('admin.php?page=seokar-redirects&action=import&error=2'));
            exit;
        }

        // Import redirects
        $imported = 0;
        foreach ($redirects as $redirect) {
            $data = [
                'source' => $redirect['source'] ?? '',
                'target' => $redirect['target'] ?? '',
                'type' => (int) ($redirect['type'] ?? 301),
                'regex' => (int) ($redirect['regex'] ?? 0),
                'keep_params' => (int) ($redirect['keep_params'] ?? 0),
                'enabled' => (int) ($redirect['enabled'] ?? 1)
            ];

            if ($this->add_redirect($data)) {
                $imported++;
            }
        }

        wp_redirect(admin_url('admin.php?page=seokar-redirects&imported=' . $imported));
        exit;
    }

    /**
     * Handle export redirects
     */
    public function handle_export_redirects() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'seokar_export_redirects')) {
            wp_die(__('Security check failed', 'seokar'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'seokar'));
        }

        // Get all redirects
        $redirects = $this->get_redirects(['per_page' => -1]);

        // Prepare data
        $data = [];
        foreach ($redirects as $redirect) {
            $data[] = [
                'source' => $redirect['source'],
                'target' => $redirect['target'],
                'type' => (int) $redirect['type'],
                'regex' => (int) $redirect['regex'],
                'keep_params' => (int) $redirect['keep_params'],
                'enabled' => (int) $redirect['enabled']
            ];
        }

        // Output JSON file
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=seokar-redirects-' . date('Y-m-d') . '.json');
        echo wp_json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    // Additional helper methods for rendering admin pages would follow...
    // These would include methods like render_redirects_list(), render_add_redirect_page(), etc.
    // They would handle the HTML output for the admin interface.
}
