<?php
/**
 * Seokar Optimization - Advanced WordPress Performance Optimization Class
 * 
 * @package     Seokar
 * @author      SEO Expert
 * @version     3.0.0
 * @license     GPL-3.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Seokar_Optimization {

    /**
     * Optimization settings
     */
    private $settings = [
        'disable_emojis'         => true,
        'enable_gzip'            => true,
        'limit_revisions'        => 5,
        'lazy_load_images'       => true,
        'defer_js'              => true,
        'disable_heartbeat'      => true,
        'optimize_db_tables'     => ['posts', 'postmeta', 'comments', 'commentmeta', 'options'],
        'optimize_db_schedule'  => 'weekly',
        'remove_dns_prefetch'    => ['//s.w.org'],
        'remove_block_css'       => true,
        'remove_query_strings'   => true,
        'preload_critical_css'   => true
    ];

    /**
     * Initialize optimization features
     */
    public function __construct() {
        $this->settings = apply_filters('seokar_optimization_settings', $this->settings);

        // Frontend optimizations
        add_action('wp_enqueue_scripts', [$this, 'optimize_assets'], PHP_INT_MAX);
        add_action('wp_head', [$this, 'preload_critical_assets'], 1);
        add_filter('style_loader_tag', [$this, 'style_loader_tag'], 10, 4);
        
        // General optimizations
        add_action('init', [$this, 'apply_basic_optimizations']);
        add_filter('wp_resource_hints', [$this, 'resource_hints_optimization'], 10, 2);
        
        // Database optimizations
        add_action('wp_loaded', [$this, 'init_database_optimization']);
        
        // Content optimizations
        add_filter('the_content', [$this, 'content_optimization'], 99);
        add_action('wp_footer', [$this, 'footer_optimization'], PHP_INT_MAX);
        
        // Heartbeat API control
        add_action('admin_init', [$this, 'admin_heartbeat_control']);
        
        // Cleanup hooks
        add_action('wp', [$this, 'cleanup_headers']);
    }

    /**
     * Apply basic WordPress optimizations
     */
    public function apply_basic_optimizations() {
        // Emoji removal
        if ($this->settings['disable_emojis']) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            add_filter('emoji_svg_url', '__return_false');
        }

        // Gzip compression
        if ($this->settings['enable_gzip'] && !ob_start('ob_gzhandler')) {
            ob_start();
        }

        // Limit post revisions
        if (!defined('WP_POST_REVISIONS') && $this->settings['limit_revisions']) {
            define('WP_POST_REVISIONS', $this->settings['limit_revisions']);
        }
    }

    /**
     * Optimize frontend assets
     */
    public function optimize_assets() {
        // Remove block library CSS
        if ($this->settings['remove_block_css'] && !is_admin()) {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('global-styles');
        }

        // Remove query strings from static resources
        if ($this->settings['remove_query_strings']) {
            add_filter('script_loader_src', [$this, 'remove_query_strings'], 15);
            add_filter('style_loader_src', [$this, 'remove_query_strings'], 15);
        }
    }

    /**
     * Remove query strings from static resources
     */
    public function remove_query_strings($src) {
        if (strpos($src, '?ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    /**
     * Initialize database optimization schedule
     */
    public function init_database_optimization() {
        if (!wp_next_scheduled('seokar_db_optimization_event')) {
            wp_schedule_event(
                time(), 
                $this->settings['optimize_db_schedule'], 
                'seokar_db_optimization_event'
            );
        }
        add_action('seokar_db_optimization_event', [$this, 'optimize_database']);
    }

    /**
     * Optimize WordPress database tables
     */
    public function optimize_database() {
        global $wpdb;
        
        $tables = apply_filters('seokar_optimize_db_tables', $this->settings['optimize_db_tables']);
        $optimized = [];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                $result = $wpdb->query("OPTIMIZE TABLE $table_name");
                if ($result) {
                    $optimized[] = $table_name;
                }
            }
        }
        
        if (!empty($optimized)) {
            error_log('Seokar: Optimized tables - ' . implode(', ', $optimized));
        }
        
        // Cleanup transients
        $this->cleanup_transients();
    }

    /**
     * Cleanup expired transients
     */
    private function cleanup_transients() {
        global $wpdb;
        
        $time = time();
        $expired = $wpdb->get_col("
            SELECT option_name 
            FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout%' 
            AND option_value < $time
        ");
        
        foreach ($expired as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient);
            delete_transient($key);
        }
        
        // Optimize options table
        $wpdb->query("OPTIMIZE TABLE {$wpdb->options}");
    }

    /**
     * Optimize content with lazy loading and other improvements
     */
    public function content_optimization($content) {
        if (is_admin() || !$this->settings['lazy_load_images']) {
            return $content;
        }

        // Use DOMDocument for more precise lazy loading
        if (class_exists('DOMDocument') && function_exists('libxml_use_internal_errors')) {
            libxml_use_internal_errors(true);
            
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            
            $images = $dom->getElementsByTagName('img');
            
            foreach ($images as $img) {
                if (!$img->hasAttribute('loading')) {
                    $img->setAttribute('loading', 'lazy');
                }
                
                // Add width and height if missing to prevent layout shifts
                if (!$img->hasAttribute('width') && $img->hasAttribute('src')) {
                    $src = $img->getAttribute('src');
                    if (strpos($src, 'data:image') === false) {
                        $img->setAttribute('width', '100%');
                        $img->setAttribute('height', 'auto');
                    }
                }
            }
            
            $content = $dom->saveHTML();
            libxml_clear_errors();
        } else {
            // Fallback regex lazy loading
            $content = preg_replace_callback(
                '/<img((?:(?!loading=)[^>])+)src=/i',
                function($matches) {
                    return '<img' . $matches[1] . 'loading="lazy" src=';
                },
                $content
            );
        }
        
        return $content;
    }

    /**
     * Footer optimizations including JS deferral
     */
    public function footer_optimization() {
        if ($this->settings['defer_js']) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var scripts = document.querySelectorAll('script:not([type=\"application/ld+json\"]):not([defer]):not([async])');
                    scripts.forEach(function(script) {
                        if (!script.src.includes('jquery')) {
                            script.setAttribute('defer', 'defer');
                        }
                    });
                });
            </script>";
        }
    }

    /**
     * Control Heartbeat API
     */
    public function disable_heartbeat() {
        if (!$this->settings['disable_heartbeat']) {
            return;
        }
        
        wp_deregister_script('heartbeat');
        
        // Allow heartbeat only in post editing screen
        if (!is_admin() || (isset($_GET['action']) && $_GET['action'] === 'edit') {
            wp_enqueue_script('heartbeat');
        }
    }

    /**
     * Admin-specific heartbeat control
     */
    public function admin_heartbeat_control() {
        if ($this->settings['disable_heartbeat']) {
            add_filter('heartbeat_settings', function($settings) {
                $settings['interval'] = 120; // Slow down heartbeat to 2 minutes
                return $settings;
            });
        }
    }

    /**
     * Optimize resource hints
     */
    public function resource_hints_optimization($hints, $relation_type) {
        if ('dns-prefetch' === $relation_type) {
            $remove_hints = apply_filters('seokar_remove_dns_prefetch', $this->settings['remove_dns_prefetch']);
            return array_diff($hints, $remove_hints);
        }
        return $hints;
    }

    /**
     * Preload critical assets
     */
    public function preload_critical_assets() {
        if (!$this->settings['preload_critical_css']) {
            return;
        }
        
        // Preload main stylesheet
        $stylesheet_uri = get_stylesheet_uri();
        echo '<link rel="preload" href="' . esc_url($stylesheet_uri) . '" as="style">';
        
        // Preload fonts
        echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/fonts/roboto.woff2" as="font" type="font/woff2" crossorigin>';
    }

    /**
     * Optimize style loader tag
     */
    public function style_loader_tag($html, $handle, $href, $media) {
        if ($this->settings['preload_critical_css']) {
            $preload_handles = apply_filters('seokar_preload_styles', ['main-css', 'theme-styles']);
            
            if (in_array($handle, $preload_handles)) {
                return '<link rel="preload" href="' . esc_url($href) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">
                        <noscript><link rel="stylesheet" href="' . esc_url($href) . '"></noscript>';
            }
        }
        return $html;
    }

    /**
     * Cleanup unnecessary HTTP headers
     */
    public function cleanup_headers() {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            header_remove('Server');
            header_remove('X-Pingback');
        }
    }
}

// Initialize with high priority
add_action('after_setup_theme', function() {
    new Seokar_Optimization();
}, 1);
