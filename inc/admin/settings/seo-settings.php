<?php
/**
 * Advanced Theme SEO Settings - SEOKar Pro
 * 
 * Comprehensive SEO solution for WordPress themes with schema markup, meta tags,
 * social integration, and performance optimization.
 *
 * @package    SeoKar
 * @subpackage Admin
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    4.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Theme_SEO_Pro {

    private $options;
    private $schema_markup;
    private $current_post;

    public function __construct() {
        // Initialize properties
        $this->options = get_option('theme_seo_pro_options');
        $this->current_post = null;

        // Core SEO hooks
        add_action('after_setup_theme', array($this, 'setup_seo_features'));
        add_action('customize_register', array($this, 'register_seo_customizer_settings'));
        add_action('wp_head', array($this, 'output_seo_meta_tags'), 1);
        add_action('wp_footer', array($this, 'output_schema_markup'));
        
        // Title and meta modifications
        add_filter('pre_get_document_title', array($this, 'generate_document_title'), 20);
        add_filter('wpseo_title', array($this, 'generate_document_title'));
        add_filter('wpseo_metadesc', array($this, 'generate_meta_description'));
        
        // Content optimizations
        add_filter('the_content', array($this, 'optimize_content_markup'));
        add_filter('post_thumbnail_html', array($this, 'optimize_image_markup'), 10, 5);
        
        // Admin interface
        add_action('admin_init', array($this, 'register_seo_settings_page'));
        add_action('add_meta_boxes', array($this, 'add_seo_meta_boxes'));
        add_action('save_post', array($this, 'save_seo_meta_data'));
        
        // Performance optimizations
        add_action('template_redirect', array($this, 'redirect_attachment_pages'));
        add_filter('wp_resource_hints', array($this, 'add_resource_hints'), 10, 2);
        
        // XML Sitemap functionality
        add_action('init', array($this, 'register_xml_sitemap'));
        add_filter('robots_txt', array($this, 'modify_robots_txt'), 10, 2);
    }

    /**
     * Setup theme SEO features
     */
    public function setup_seo_features() {
        // Enable title tag support
        add_theme_support('title-tag');
        
        // Enable HTML5 markup
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ));
        
        // Enable post thumbnails
        add_theme_support('post-thumbnails');
        
        // Add custom image sizes for social sharing
        add_image_size('seokar-social-share', 1200, 630, true);
        add_image_size('seokar-twitter-card', 800, 418, true);
        
        // Register custom breadcrumbs
        register_nav_menus(array(
            'breadcrumb' => __('Breadcrumb Navigation', 'seokar')
        ));
    }

    /**
     * Register SEO settings in WordPress Customizer
     */
    public function register_seo_customizer_settings($wp_customize) {
        // Add SEO Panel
        $wp_customize->add_panel('theme_seo_pro_panel', array(
            'title' => __('Advanced SEO Settings', 'seokar'),
            'priority' => 200,
            'capability' => 'manage_options',
        ));

        // General SEO Settings Section
        $this->add_general_seo_section($wp_customize);
        
        // Social Media Section
        $this->add_social_media_section($wp_customize);
        
        // Indexing Control Section
        $this->add_indexing_control_section($wp_customize);
        
        // Schema Markup Section
        $this->add_schema_markup_section($wp_customize);
        
        // Breadcrumbs Section
        $this->add_breadcrumbs_section($wp_customize);
        
        // Analytics Section
        $this->add_analytics_section($wp_customize);
        
        // Performance Section
        $this->add_performance_section($wp_customize);
        
        // Advanced Section
        $this->add_advanced_section($wp_customize);
    }
    
    private function add_general_seo_section($wp_customize) {
        $wp_customize->add_section('general_seo_section', array(
            'title' => __('General SEO', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // Title Format
        $wp_customize->add_setting('seo_title_format', array(
            'default' => '%page_title% | %site_title%',
            'sanitize_callback' => 'sanitize_text_field',
            'transport' => 'postMessage'
        ));

        $wp_customize->add_control('seo_title_format', array(
            'label' => __('Title Format', 'seokar'),
            'section' => 'general_seo_section',
            'type' => 'text',
            'description' => __('Available variables: %page_title%, %site_title%, %category%, %current_date%, %current_year%', 'seokar'),
        ));

        // Meta Description Length
        $wp_customize->add_setting('seo_meta_description_length', array(
            'default' => 160,
            'sanitize_callback' => 'absint',
        ));

        $wp_customize->add_control('seo_meta_description_length', array(
            'label' => __('Max Meta Description Length', 'seokar'),
            'section' => 'general_seo_section',
            'type' => 'number',
            'input_attrs' => array(
                'min' => 70,
                'max' => 320,
                'step' => 1,
            ),
        ));

        // Default Meta Description
        $wp_customize->add_setting('default_meta_description', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ));

        $wp_customize->add_control('default_meta_description', array(
            'label' => __('Default Meta Description', 'seokar'),
            'section' => 'general_seo_section',
            'type' => 'textarea',
            'description' => __('Used when no specific description is available.', 'seokar'),
        ));

        // Keywords Support
        $wp_customize->add_setting('enable_meta_keywords', array(
            'default' => false,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_meta_keywords', array(
            'label' => __('Enable Meta Keywords', 'seokar'),
            'section' => 'general_seo_section',
            'type' => 'checkbox',
            'description' => __('Note: Most search engines no longer use meta keywords for ranking.', 'seokar'),
        ));
    }
    
    private function add_social_media_section($wp_customize) {
        $wp_customize->add_section('social_media_section', array(
            'title' => __('Social Media', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // OpenGraph
        $wp_customize->add_setting('enable_opengraph', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_opengraph', array(
            'label' => __('Enable OpenGraph Tags', 'seokar'),
            'section' => 'social_media_section',
            'type' => 'checkbox',
        ));

        // Twitter Cards
        $wp_customize->add_setting('enable_twitter_cards', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_twitter_cards', array(
            'label' => __('Enable Twitter Cards', 'seokar'),
            'section' => 'social_media_section',
            'type' => 'checkbox',
        ));

        // Default Social Image
        $wp_customize->add_setting('default_og_image', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));

        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'default_og_image', array(
            'label' => __('Default Social Image', 'seokar'),
            'section' => 'social_media_section',
            'settings' => 'default_og_image',
            'description' => __('Recommended size: 1200x630 pixels', 'seokar'),
        ));

        // Facebook App ID
        $wp_customize->add_setting('facebook_app_id', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('facebook_app_id', array(
            'label' => __('Facebook App ID', 'seokar'),
            'section' => 'social_media_section',
            'type' => 'text',
        ));

        // Twitter Username
        $wp_customize->add_setting('twitter_username', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('twitter_username', array(
            'label' => __('Twitter @username', 'seokar'),
            'section' => 'social_media_section',
            'type' => 'text',
            'description' => __('Without the @ symbol', 'seokar'),
        ));
    }
    
    private function add_indexing_control_section($wp_customize) {
        $wp_customize->add_section('indexing_section', array(
            'title' => __('Indexing Control', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // Noindex Search Pages
        $wp_customize->add_setting('noindex_search_pages', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('noindex_search_pages', array(
            'label' => __('Noindex Search Pages', 'seokar'),
            'section' => 'indexing_section',
            'type' => 'checkbox',
        ));

        // Noindex Archive Pages
        $wp_customize->add_setting('noindex_archive_pages', array(
            'default' => false,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('noindex_archive_pages', array(
            'label' => __('Noindex Archive Pages', 'seokar'),
            'section' => 'indexing_section',
            'type' => 'checkbox',
            'description' => __('Category, tag, author and date archives', 'seokar'),
        ));

        // Noindex Older Content
        $wp_customize->add_setting('noindex_older_than', array(
            'default' => 0,
            'sanitize_callback' => 'absint',
        ));

        $wp_customize->add_control('noindex_older_than', array(
            'label' => __('Noindex Content Older Than (years)', 'seokar'),
            'section' => 'indexing_section',
            'type' => 'number',
            'description' => __('Set to 0 to disable', 'seokar'),
        ));

        // Noindex Paginated Pages
        $wp_customize->add_setting('noindex_paginated_pages', array(
            'default' => false,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('noindex_paginated_pages', array(
            'label' => __('Noindex Paginated Pages', 'seokar'),
            'section' => 'indexing_section',
            'type' => 'checkbox',
            'description' => __('Pages beyond the first page in archives', 'seokar'),
        ));

        // Noindex Attachment Pages
        $wp_customize->add_setting('noindex_attachment_pages', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('noindex_attachment_pages', array(
            'label' => __('Noindex Media Attachment Pages', 'seokar'),
            'section' => 'indexing_section',
            'type' => 'checkbox',
        ));
    }
    
    private function add_schema_markup_section($wp_customize) {
        $wp_customize->add_section('schema_section', array(
            'title' => __('Schema Markup', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // Enable Schema
        $wp_customize->add_setting('enable_schema', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_schema', array(
            'label' => __('Enable Schema Markup', 'seokar'),
            'section' => 'schema_section',
            'type' => 'checkbox',
        ));

        // Organization Name
        $wp_customize->add_setting('organization_name', array(
            'default' => get_bloginfo('name'),
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('organization_name', array(
            'label' => __('Organization Name', 'seokar'),
            'section' => 'schema_section',
            'type' => 'text',
        ));

        // Organization Logo
        $wp_customize->add_setting('organization_logo', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));

        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'organization_logo', array(
            'label' => __('Organization Logo', 'seokar'),
            'section' => 'schema_section',
            'settings' => 'organization_logo',
            'description' => __('Recommended size: 600x60 pixels', 'seokar'),
        )));

        // Organization Type
        $wp_customize->add_setting('organization_type', array(
            'default' => 'Organization',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('organization_type', array(
            'label' => __('Organization Type', 'seokar'),
            'section' => 'schema_section',
            'type' => 'select',
            'choices' => array(
                'Organization' => __('General Organization', 'seokar'),
                'Corporation' => __('Corporation', 'seokar'),
                'EducationalOrganization' => __('Educational Organization', 'seokar'),
                'GovernmentOrganization' => __('Government Organization', 'seokar'),
                'LocalBusiness' => __('Local Business', 'seokar'),
                'NGO' => __('NGO', 'seokar'),
                'PerformingGroup' => __('Performing Group', 'seokar'),
                'SportsOrganization' => __('Sports Organization', 'seokar'),
            ),
        ));

        // Enable Breadcrumb Schema
        $wp_customize->add_setting('enable_breadcrumb_schema', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_breadcrumb_schema', array(
            'label' => __('Enable Breadcrumb Schema', 'seokar'),
            'section' => 'schema_section',
            'type' => 'checkbox',
        ));

        // Enable Article Schema
        $wp_customize->add_setting('enable_article_schema', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_article_schema', array(
            'label' => __('Enable Article Schema', 'seokar'),
            'section' => 'schema_section',
            'type' => 'checkbox',
        ));
    }
    
    private function add_breadcrumbs_section($wp_customize) {
        $wp_customize->add_section('breadcrumbs_section', array(
            'title' => __('Breadcrumbs', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // Enable Breadcrumbs
        $wp_customize->add_setting('enable_breadcrumbs', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_breadcrumbs', array(
            'label' => __('Enable Breadcrumbs', 'seokar'),
            'section' => 'breadcrumbs_section',
            'type' => 'checkbox',
        ));

        // Breadcrumbs Separator
        $wp_customize->add_setting('breadcrumbs_separator', array(
            'default' => '»',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('breadcrumbs_separator', array(
            'label' => __('Breadcrumbs Separator', 'seokar'),
            'section' => 'breadcrumbs_section',
            'type' => 'text',
        ));

        // Show Home Link
        $wp_customize->add_setting('breadcrumbs_show_home', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('breadcrumbs_show_home', array(
            'label' => __('Show Home Link', 'seokar'),
            'section' => 'breadcrumbs_section',
            'type' => 'checkbox',
        ));

        // Home Link Text
        $wp_customize->add_setting('breadcrumbs_home_text', array(
            'default' => __('Home', 'seokar'),
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('breadcrumbs_home_text', array(
            'label' => __('Home Link Text', 'seokar'),
            'section' => 'breadcrumbs_section',
            'type' => 'text',
        ));

        // Show Current Page
        $wp_customize->add_setting('breadcrumbs_show_current', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('breadcrumbs_show_current', array(
            'label' => __('Show Current Page', 'seokar'),
            'section' => 'breadcrumbs_section',
            'type' => 'checkbox',
        ));
    }
    
    private function add_analytics_section($wp_customize) {
        $wp_customize->add_section('analytics_section', array(
            'title' => __('Analytics & Tracking', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // Google Analytics
        $wp_customize->add_setting('ga_tracking_id', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('ga_tracking_id', array(
            'label' => __('Google Analytics Tracking ID', 'seokar'),
            'section' => 'analytics_section',
            'type' => 'text',
            'description' => __('Example: UA-XXXXX-Y or G-XXXXXXXX', 'seokar'),
        ));

        // Google Tag Manager
        $wp_customize->add_setting('gtm_container_id', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('gtm_container_id', array(
            'label' => __('Google Tag Manager Container ID', 'seokar'),
            'section' => 'analytics_section',
            'type' => 'text',
            'description' => __('Example: GTM-XXXXXX', 'seokar'),
        ));

        // Facebook Pixel
        $wp_customize->add_setting('facebook_pixel_id', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('facebook_pixel_id', array(
            'label' => __('Facebook Pixel ID', 'seokar'),
            'section' => 'analytics_section',
            'type' => 'text',
        ));

        // Tracking Code Position
        $wp_customize->add_setting('tracking_code_position', array(
            'default' => 'head',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('tracking_code_position', array(
            'label' => __('Tracking Code Position', 'seokar'),
            'section' => 'analytics_section',
            'type' => 'select',
            'choices' => array(
                'head' => __('Head (Recommended for most analytics)', 'seokar'),
                'footer' => __('Footer (Faster page loading)', 'seokar'),
            ),
        ));

        // Anonymize IP
        $wp_customize->add_setting('ga_anonymize_ip', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('ga_anonymize_ip', array(
            'label' => __('Anonymize IP Addresses (GDPR)', 'seokar'),
            'section' => 'analytics_section',
            'type' => 'checkbox',
        ));
    }
    
    private function add_performance_section($wp_customize) {
        $wp_customize->add_section('performance_section', array(
            'title' => __('Performance', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // Lazy Loading
        $wp_customize->add_setting('enable_lazy_loading', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_lazy_loading', array(
            'label' => __('Enable Lazy Loading', 'seokar'),
            'section' => 'performance_section',
            'type' => 'checkbox',
            'description' => __('Improve loading speed by lazy loading images', 'seokar'),
        ));

        // Defer JavaScript
        $wp_customize->add_setting('defer_javascript', array(
            'default' => false,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('defer_javascript', array(
            'label' => __('Defer JavaScript Loading', 'seokar'),
            'section' => 'performance_section',
            'type' => 'checkbox',
            'description' => __('May break some plugins - test carefully', 'seokar'),
        ));

        // Preload Critical Assets
        $wp_customize->add_setting('preload_critical_assets', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('preload_critical_assets', array(
            'label' => __('Preload Critical Assets', 'seokar'),
            'section' => 'performance_section',
            'type' => 'checkbox',
        ));

        // DNS Prefetch
        $wp_customize->add_setting('dns_prefetch_domains', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control('dns_prefetch_domains', array(
            'label' => __('DNS Prefetch Domains', 'seokar'),
            'section' => 'performance_section',
            'type' => 'text',
            'description' => __('Comma separated list of domains (e.g., fonts.googleapis.com,fonts.gstatic.com)', 'seokar'),
        ));
    }
    
    private function add_advanced_section($wp_customize) {
        $wp_customize->add_section('advanced_section', array(
            'title' => __('Advanced', 'seokar'),
            'panel' => 'theme_seo_pro_panel',
        ));

        // XML Sitemap
        $wp_customize->add_setting('enable_xml_sitemap', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enable_xml_sitemap', array(
            'label' => __('Enable XML Sitemap', 'seokar'),
            'section' => 'advanced_section',
            'type' => 'checkbox',
        ));

        // RSS Feed Enhancements
        $wp_customize->add_setting('enhance_rss_feeds', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('enhance_rss_feeds', array(
            'label' => __('Enhance RSS Feeds', 'seokar'),
            'section' => 'advanced_section',
            'type' => 'checkbox',
            'description' => __('Add featured images and better metadata to feeds', 'seokar'),
        ));

        // Remove Generator Tag
        $wp_customize->add_setting('remove_generator_tag', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('remove_generator_tag', array(
            'label' => __('Remove WordPress Generator Tag', 'seokar'),
            'section' => 'advanced_section',
            'type' => 'checkbox',
        ));

        // Disable Emojis
        $wp_customize->add_setting('disable_emojis', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('disable_emojis', array(
            'label' => __('Disable WordPress Emojis', 'seokar'),
            'section' => 'advanced_section',
            'type' => 'checkbox',
            'description' => __('Reduces HTTP requests and improves performance', 'seokar'),
        ));

        // Disable Embeds
        $wp_customize->add_setting('disable_embeds', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));

        $wp_customize->add_control('disable_embeds', array(
            'label' => __('Disable WordPress Embeds', 'seokar'),
            'section' => 'advanced_section',
            'type' => 'checkbox',
            'description' => __('Reduces HTTP requests and improves security', 'seokar'),
        ));
    }

    /**
     * Output SEO meta tags in head
     */
    public function output_seo_meta_tags() {
        // Basic meta tags
        $this->output_basic_meta_tags();
        
        // Viewport and compatibility
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">' . "\n";
        echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
        
        // Content Language
        echo '<meta http-equiv="Content-Language" content="' . esc_attr(get_bloginfo('language')) . '">' . "\n";
        
        // OpenGraph Tags
        if (get_theme_mod('enable_opengraph', true)) {
            $this->output_opengraph_tags();
        }
        
        // Twitter Card Tags
        if (get_theme_mod('enable_twitter_cards', true)) {
            $this->output_twitter_card_tags();
        }
        
        // Canonical URL
        $this->output_canonical_url();
        
        // Robots Meta
        $this->output_robots_meta();
        
        // Meta Keywords (if enabled)
        if (get_theme_mod('enable_meta_keywords', false)) {
            $this->output_meta_keywords();
        }
        
        // Preload and prefetch resources
        $this->output_preload_resources();
    }
    
    private function output_basic_meta_tags() {
        // Charset
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">' . "\n";
        
        // Generator removal
        if (get_theme_mod('remove_generator_tag', true)) {
            remove_action('wp_head', 'wp_generator');
        }
        
        // Shortlink
        echo '<link rel="shortlink" href="' . esc_url(wp_get_shortlink()) . '">' . "\n";
        
        // Pingback
        echo '<link rel="pingback" href="' . esc_url(get_bloginfo('pingback_url')) . '">' . "\n";
    }
    
    private function output_opengraph_tags() {
        global $post;
        
        $og_title = $this->generate_document_title();
        $og_description = $this->generate_meta_description();
        $og_url = $this->get_canonical_url();
        $og_site_name = get_bloginfo('name');
        $og_locale = get_locale();
        
        // Default image
        $og_image = get_theme_mod('default_og_image', '');
        $og_image_width = 1200;
        $og_image_height = 630;
        
        // Try to get featured image
        if (is_singular() && has_post_thumbnail($post->ID)) {
            $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'seokar-social-share');
            if ($thumbnail_src) {
                $og_image = $thumbnail_src[0];
                $og_image_width = $thumbnail_src[1];
                $og_image_height = $thumbnail_src[2];
            }
        }
        
        // Basic OG tags
        echo '<meta property="og:locale" content="' . esc_attr($og_locale) . '">' . "\n";
        echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($og_site_name) . '">' . "\n";
        
        // Image tags
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
            echo '<meta property="og:image:width" content="' . esc_attr($og_image_width) . '">' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr($og_image_height) . '">' . "\n";
            echo '<meta property="og:image:alt" content="' . esc_attr($og_title) . '">' . "\n";
        }
        
        // Article specific tags
        if (is_singular('post')) {
            $published_time = get_the_date('c');
            $modified_time = get_the_modified_date('c');
            $author = get_the_author_meta('display_name', $post->post_author);
            
            echo '<meta property="article:published_time" content="' . esc_attr($published_time) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr($modified_time) . '">' . "\n";
            echo '<meta property="article:author" content="' . esc_attr($author) . '">' . "\n";
            
            // Article sections (categories)
            $categories = get_the_category();
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    echo '<meta property="article:section" content="' . esc_attr($category->name) . '">' . "\n";
                }
            }
            
            // Article tags
            $tags = get_the_tags();
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '">' . "\n";
                }
            }
        }
        
        // Facebook App ID
        $fb_app_id = get_theme_mod('facebook_app_id', '');
        if ($fb_app_id) {
            echo '<meta property="fb:app_id" content="' . esc_attr($fb_app_id) . '">' . "\n";
        }
    }
    
    private function output_twitter_card_tags() {
        global $post;
        
        $twitter_title = $this->generate_document_title();
        $twitter_description = $this->generate_meta_description();
        $twitter_username = get_theme_mod('twitter_username', '');
        
        // Default image
        $twitter_image = get_theme_mod('default_og_image', '');
        
        // Try to get featured image
        if (is_singular() && has_post_thumbnail($post->ID)) {
            $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'seokar-twitter-card');
            if ($thumbnail_src) {
                $twitter_image = $thumbnail_src[0];
            }
        }
        
        // Card type
        $card_type = ($twitter_image) ? 'summary_large_image' : 'summary';
        
        echo '<meta name="twitter:card" content="' . esc_attr($card_type) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '">' . "\n";
        
        // Twitter username
        if ($twitter_username) {
            echo '<meta name="twitter:site" content="@' . esc_attr($twitter_username) . '">' . "\n";
            echo '<meta name="twitter:creator" content="@' . esc_attr($twitter_username) . '">' . "\n";
        }
        
        // Twitter image
        if ($twitter_image) {
            echo '<meta name="twitter:image" content="' . esc_url($twitter_image) . '">' . "\n";
            echo '<meta name="twitter:image:alt" content="' . esc_attr($twitter_title) . '">' . "\n";
        }
    }
    
    private function output_canonical_url() {
        $canonical_url = $this->get_canonical_url();
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    }
    
    private function get_canonical_url() {
        global $wp;
        
        if (is_singular()) {
            $canonical_url = get_permalink();
        } elseif (is_front_page()) {
            $canonical_url = home_url('/');
        } elseif (is_home()) {
            $canonical_url = get_permalink(get_option('page_for_posts'));
        } else {
            $canonical_url = home_url($wp->request);
        }
        
        // Remove query parameters
        $canonical_url = strtok($canonical_url, '?');
        
        // For paginated content
        if (is_paged()) {
            $canonical_url = get_pagenum_link(1);
        }
        
        return apply_filters('theme_seo_canonical_url', $canonical_url);
    }
    
    private function output_robots_meta() {
        $robots = array();
        
        // Noindex control
        if ($this->should_noindex()) {
            $robots[] = 'noindex';
        } else {
            $robots[] = 'index';
        }
        
        // Nofollow control
        $robots[] = 'follow';
        
        // Archive control
        if (is_singular()) {
            $robots[] = 'noarchive';
        }
        
        // Output robots meta tag
        echo '<meta name="robots" content="' . esc_attr(implode(',', $robots)) . '">' . "\n";
    }
    
    private function should_noindex() {
        // Noindex search pages
        if (is_search() && get_theme_mod('noindex_search_pages', true)) {
            return true;
        }
        
        // Noindex archive pages
        if ((is_category() || is_tag() || is_author() || is_date()) && get_theme_mod('noindex_archive_pages', false)) {
            return true;
        }
        
        // Noindex paginated pages
        if (is_paged() && get_theme_mod('noindex_paginated_pages', false)) {
            return true;
        }
        
        // Noindex attachment pages
        if (is_attachment() && get_theme_mod('noindex_attachment_pages', true)) {
            return true;
        }
        
        // Noindex older content
        if (is_singular() && get_theme_mod('noindex_older_than', 0) > 0) {
            $post_date = get_the_date('Y-m-d');
            $years_diff = (time() - strtotime($post_date)) / (365 * 24 * 60 * 60);
            
            if ($years_diff > get_theme_mod('noindex_older_than')) {
                return true;
            }
        }
        
        return false;
    }
    
    private function output_meta_keywords() {
        if (!is_singular()) {
            return;
        }
        
        $keywords = $this->generate_meta_keywords();
        if (!empty($keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
    }
    
    private function generate_meta_keywords() {
        global $post;
        
        $keywords = array();
        
        // Add categories as keywords
        $categories = get_the_category();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $keywords[] = $category->name;
            }
        }
        
        // Add tags as keywords
        $tags = get_the_tags();
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $keywords[] = $tag->name;
            }
        }
        
        // Add post-specific keywords if set
        $post_keywords = get_post_meta($post->ID, '_seo_keywords', true);
        if (!empty($post_keywords)) {
            $post_keywords = explode(',', $post_keywords);
            $keywords = array_merge($keywords, $post_keywords);
        }
        
        // Clean and unique keywords
        $keywords = array_map('trim', $keywords);
        $keywords = array_unique($keywords);
        $keywords = array_filter($keywords);
        
        return implode(', ', $keywords);
    }
    
    private function output_preload_resources() {
        // Preload critical assets
        if (get_theme_mod('preload_critical_assets', true)) {
            echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/css/critical.css" as="style">' . "\n";
            echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/js/main.js" as="script">' . "\n";
        }
        
        // DNS prefetch
        $dns_prefetch = get_theme_mod('dns_prefetch_domains', '');
        if (!empty($dns_prefetch)) {
            $domains = explode(',', $dns_prefetch);
            foreach ($domains as $domain) {
                $domain = trim($domain);
                if (!empty($domain)) {
                    echo '<link rel="dns-prefetch" href="//' . esc_attr($domain) . '">' . "\n";
                }
            }
        }
    }
    
    /**
     * Generate document title based on settings
     */
    public function generate_document_title($title = '') {
        if (!empty($title)) {
            return $title;
        }
        
        $title_format = get_theme_mod('seo_title_format', '%page_title% | %site_title%');
        
        if (empty($title_format)) {
            return wp_get_document_title();
        }
        
        // Get context variables
        $page_title = single_post_title('', false);
        $site_title = get_bloginfo('name');
        $current_date = date_i18n(get_option('date_format'));
        $current_year = date('Y');
        
        // Handle category for posts
        $category = '';
        if (is_single() && has_category()) {
            $categories = get_the_category();
            $category = $categories[0]->name;
        } elseif (is_category()) {
            $category = single_cat_title('', false);
        }
        
        // Replace variables
        $new_title = str_replace(
            array('%page_title%', '%site_title%', '%current_date%', '%current_year%', '%category%'),
            array($page_title, $site_title, $current_date, $current_year, $category),
            $title_format
        );
        
        // Clean up title
        $new_title = wp_strip_all_tags($new_title);
        $new_title = trim($new_title);
        
        return apply_filters('theme_seo_document_title', $new_title);
    }
    
    /**
     * Generate meta description
     */
    public function generate_meta_description($desc = '') {
        if (!empty($desc)) {
            return $desc;
        }
        
        global $post;
        
        $description = '';
        $max_length = get_theme_mod('seo_meta_description_length', 160);
        
        // Check for custom meta description first
        if (is_singular() && $custom_desc = get_post_meta($post->ID, '_seo_description', true)) {
            $description = $custom_desc;
        } elseif (is_singular() && $post->post_excerpt) {
            $description = $post->post_excerpt;
        } elseif (is_singular()) {
            $description = $post->post_content;
        } elseif (is_category() || is_tag()) {
            $description = term_description();
        } elseif (is_author()) {
            $description = get_the_author_meta('description');
        } elseif (is_front_page()) {
            $description = get_bloginfo('description');
        }
        
        // Fallback to default description
        if (empty($description)) {
            $description = get_theme_mod('default_meta_description', '');
        }
        
        // Clean up the description
        $description = wp_strip_all_tags($description);
        $description = str_replace(array("\r", "\n"), ' ', $description);
        $description = trim($description);
        
        // Truncate to max length
        if (mb_strlen($description) > $max_length) {
            $description = mb_substr($description, 0, $max_length - 3) . '...';
        }
        
        return apply_filters('theme_seo_meta_description', $description);
    }
    
    /**
     * Output schema markup in footer
     */
    public function output_schema_markup() {
        if (!get_theme_mod('enable_schema', true)) {
            return;
        }
        
        $this->schema_markup = array();
        
        // Organization schema
        $this->generate_organization_schema();
        
        // Website schema
        $this->generate_website_schema();
        
        // Breadcrumb schema
        if (get_theme_mod('enable_breadcrumb_schema', true)) {
            $this->generate_breadcrumb_schema();
        }
        
        // Article schema
        if (is_singular('post') && get_theme_mod('enable_article_schema', true)) {
            $this->generate_article_schema();
        }
        
        // Output all schema
        if (!empty($this->schema_markup)) {
            foreach ($this->schema_markup as $schema) {
                echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
            }
        }
    }
    
    private function generate_organization_schema() {
        $organization_name = get_theme_mod('organization_name', get_bloginfo('name'));
        $organization_logo = get_theme_mod('organization_logo', '');
        $organization_type = get_theme_mod('organization_type', 'Organization');
        
        if ($organization_logo) {
            $organization_logo = wp_get_attachment_image_src(attachment_url_to_postid($organization_logo), 'full');
            $organization_logo = $organization_logo[0];
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $organization_type,
            'name' => $organization_name,
            'url' => home_url(),
        );
        
        if ($organization_logo) {
            $schema['logo'] = $organization_logo;
        }
        
        // Social profiles
        $social_profiles = $this->get_social_profiles();
        if (!empty($social_profiles)) {
            $schema['sameAs'] = $social_profiles;
        }
        
        $this->schema_markup[] = $schema;
    }
    
    private function generate_website_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ),
        );
        
        $this->schema_markup[] = $schema;
    }
    
    private function generate_breadcrumb_schema() {
        if (!function_exists('theme_breadcrumbs')) {
            return;
        }
        
        $breadcrumbs = theme_breadcrumbs(true);
        if (empty($breadcrumbs)) {
            return;
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(),
        );
        
        $position = 1;
        foreach ($breadcrumbs as $crumb) {
            $schema['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            );
            $position++;
        }
        
        $this->schema_markup[] = $schema;
    }
    
    private function generate_article_schema() {
        global $post;
        
        $organization_name = get_theme_mod('organization_name', get_bloginfo('name'));
        $organization_logo = get_theme_mod('organization_logo', '');
        
        if ($organization_logo) {
            $organization_logo = wp_get_attachment_image_src(attachment_url_to_postid($organization_logo), 'full');
            $organization_logo = $organization_logo[0];
        }
        
        $publisher = array(
            '@type' => 'Organization',
            'name' => $organization_name,
        );
        
        if ($organization_logo) {
            $publisher['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $organization_logo,
            );
        }
        
        $author = get_the_author_meta('display_name', $post->post_author);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => $this->generate_meta_description(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => $author,
            ),
            'publisher' => $publisher,
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink(),
            ),
        );
        
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2],
            );
        }
        
        $this->schema_markup[] = $schema;
    }
    
    private function get_social_profiles() {
        $profiles = array();
        
        $social_links = array(
            'facebook' => get_theme_mod('facebook_url', ''),
            'twitter' => get_theme_mod('twitter_url', ''),
            'instagram' => get_theme_mod('instagram_url', ''),
            'linkedin' => get_theme_mod('linkedin_url', ''),
            'youtube' => get_theme_mod('youtube_url', ''),
            'pinterest' => get_theme_mod('pinterest_url', ''),
        );
        
        foreach ($social_links as $network => $url) {
            if (!empty($url)) {
                $profiles[] = $url;
            }
        }
        
        return $profiles;
    }
    
    /**
     * Register SEO settings page in admin
     */
    public function register_seo_settings_page() {
        add_menu_page(
            __('SEO Settings', 'seokar'),
            __('SEO Settings', 'seokar'),
            'manage_options',
            'theme-seo-settings',
            array($this, 'render_seo_settings_page'),
            'dashicons-search',
            80
        );
        
        // Register settings
        register_setting('theme_seo_pro_options', 'theme_seo_pro_options', array($this, 'validate_options'));
        
        // Add sections
        add_settings_section(
            'general_settings',
            __('General SEO Settings', 'seokar'),
            array($this, 'render_general_settings_section'),
            'theme-seo-settings'
        );
        
        // Add fields
        add_settings_field(
            'seo_title_format',
            __('Title Format', 'seokar'),
            array($this, 'render_title_format_field'),
            'theme-seo-settings',
            'general_settings'
        );
        
        // Add more fields as needed...
    }
    
    public function render_seo_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('theme_seo_pro_options');
                do_settings_sections('theme-seo-settings');
                submit_button(__('Save Settings', 'seokar'));
                ?>
            </form>
        </div>
        <?php
    }
    
    public function render_general_settings_section() {
        echo '<p>' . __('Configure general SEO settings for your website.', 'seokar') . '</p>';
    }
    
    public function render_title_format_field() {
        $options = get_option('theme_seo_pro_options');
        $value = isset($options['seo_title_format']) ? $options['seo_title_format'] : '%page_title% | %site_title%';
        
        echo '<input type="text" id="seo_title_format" name="theme_seo_pro_options[seo_title_format]" ';
        echo 'value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Available variables: %page_title%, %site_title%, %category%, %current_date%', 'seokar') . '</p>';
    }
    
    public function validate_options($input) {
        $output = array();
        
        // Validate title format
        if (isset($input['seo_title_format'])) {
            $output['seo_title_format'] = sanitize_text_field($input['seo_title_format']);
        }
        
        // Add validation for other fields...
        
        return $output;
    }
    
    /**
     * Add SEO meta boxes to post edit screens
     */
    public function add_seo_meta_boxes() {
        $post_types = get_post_types(array('public' => true));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'theme_seo_meta_box',
                __('SEO Settings', 'seokar'),
                array($this, 'render_seo_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    public function render_seo_meta_box($post) {
        wp_nonce_field('theme_seo_meta_box', 'theme_seo_meta_box_nonce');
        
        $meta_title = get_post_meta($post->ID, '_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_seo_description', true);
        $meta_keywords = get_post_meta($post->ID, '_seo_keywords', true);
        $meta_noindex = get_post_meta($post->ID, '_seo_noindex', true);
        
        ?>
        <div class="theme-seo-meta-box">
            <div class="theme-seo-field">
                <label for="seo_title"><?php _e('Meta Title', 'seokar'); ?></label>
                <input type="text" id="seo_title" name="seo_title" value="<?php echo esc_attr($meta_title); ?>" class="widefat" />
                <p class="description"><?php _e('Custom title for this page. Leave blank to use default format.', 'seokar'); ?></p>
            </div>
            
            <div class="theme-seo-field">
                <label for="seo_description"><?php _e('Meta Description', 'seokar'); ?></label>
                <textarea id="seo_description" name="seo_description" rows="3" class="widefat"><?php echo esc_textarea($meta_description); ?></textarea>
                <p class="description"><?php _e('Custom meta description for this page.', 'seokar'); ?></p>
            </div>
            
            <?php if (get_theme_mod('enable_meta_keywords', false)) : ?>
            <div class="theme-seo-field">
                <label for="seo_keywords"><?php _e('Meta Keywords', 'seokar'); ?></label>
                <input type="text" id="seo_keywords" name="seo_keywords" value="<?php echo esc_attr($meta_keywords); ?>" class="widefat" />
                <p class="description"><?php _e('Comma-separated list of keywords (e.g., word1, word2, word3)', 'seokar'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="theme-seo-field">
                <label>
                    <input type="checkbox" id="seo_noindex" name="seo_noindex" value="1" <?php checked($meta_noindex, '1'); ?> />
                    <?php _e('Noindex this page', 'seokar'); ?>
                </label>
                <p class="description"><?php _e('Prevent search engines from indexing this page.', 'seokar'); ?></p>
            </div>
            
            <div class="theme-seo-preview">
                <h3><?php _e('Search Engine Preview', 'seokar'); ?></h3>
                <div class="preview-container">
                    <div class="preview-title"><?php echo esc_html($this->generate_document_title()); ?></div>
                    <div class="preview-url"><?php echo esc_url(get_permalink()); ?></div>
                    <div class="preview-description"><?php echo esc_html($this->generate_meta_description()); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function save_seo_meta_data($post_id) {
        if (!isset($_POST['theme_seo_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['theme_seo_meta_box_nonce'], 'theme_seo_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta title
        if (isset($_POST['seo_title'])) {
            update_post_meta($post_id, '_seo_title', sanitize_text_field($_POST['seo_title']));
        } else {
            delete_post_meta($post_id, '_seo_title');
        }
        
        // Save meta description
        if (isset($_POST['seo_description'])) {
            update_post_meta($post_id, '_seo_description', sanitize_textarea_field($_POST['seo_description']));
        } else {
            delete_post_meta($post_id, '_seo_description');
        }
        
        // Save meta keywords
        if (isset($_POST['seo_keywords'])) {
            update_post_meta($post_id, '_seo_keywords', sanitize_text_field($_POST['seo_keywords']));
        } else {
            delete_post_meta($post_id, '_seo_keywords');
        }
        
        // Save noindex setting
        if (isset($_POST['seo_noindex'])) {
            update_post_meta($post_id, '_seo_noindex', '1');
        } else {
            delete_post_meta($post_id, '_seo_noindex');
        }
    }
    
    /**
     * Register XML sitemap functionality
     */
    public function register_xml_sitemap() {
        if (!get_theme_mod('enable_xml_sitemap', true)) {
            return;
        }
        
        add_rewrite_rule('^sitemap\.xml$', 'index.php?theme_seo_sitemap=1', 'top');
        add_filter('query_vars', array($this, 'add_sitemap_query_var'));
        add_action('template_redirect', array($this, 'generate_xml_sitemap'), 1);
    }
    
    public function add_sitemap_query_var($vars) {
        $vars[] = 'theme_seo_sitemap';
        return $vars;
    }
    
    public function generate_xml_sitemap() {
        if (!get_query_var('theme_seo_sitemap')) {
            return;
        }
        
        // Prevent 404
        global $wp_query;
        $wp_query->is_404 = false;
        
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        echo 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        echo 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 ';
        echo 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
        
        // Homepage
        $this->output_sitemap_url(home_url('/'), time(), 'daily', '1.0');
        
        // Posts
        $posts = get_posts(array(
            'numberposts' => -1,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'has_password' => false,
            'meta_query' => array(
                array(
                    'key' => '_seo_noindex',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ));
        
        foreach ($posts as $post) {
            $this->output_sitemap_url(
                get_permalink($post->ID),
                get_the_modified_date('U', $post->ID),
                'weekly',
                '0.8'
            );
        }
        
        // Pages
        $pages = get_pages(array(
            'meta_key' => '_seo_noindex',
            'meta_compare' => 'NOT EXISTS',
        ));
        
        foreach ($pages as $page) {
            $this->output_sitemap_url(
                get_permalink($page->ID),
                get_the_modified_date('U', $page->ID),
                'monthly',
                '0.7'
            );
        }
        
        // Custom post types
        $post_types = get_post_types(array('public' => true, '_builtin' => false));
        foreach ($post_types as $post_type) {
            $items = get_posts(array(
                'numberposts' => -1,
                'post_type'   => $post_type,
                'post_status' => 'publish',
                'has_password' => false,
                'meta_query' => array(
                    array(
                        'key' => '_seo_noindex',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ));
            
            foreach ($items as $item) {
                $this->output_sitemap_url(
                    get_permalink($item->ID),
                    get_the_modified_date('U', $item->ID),
                    'weekly',
                    '0.6'
                );
            }
        }
        
        // Taxonomies
        $taxonomies = get_taxonomies(array('public' => true));
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
            ));
            
            foreach ($terms as $term) {
                $this->output_sitemap_url(
                    get_term_link($term),
                    time(),
                    'weekly',
                    '0.5'
                );
            }
        }
        
        echo '</urlset>';
        exit;
    }
    
    private function output_sitemap_url($url, $lastmod, $changefreq, $priority) {
        echo "\t<url>\n";
        echo "\t\t<loc>" . esc_url($url) . "</loc>\n";
        echo "\t\t<lastmod>" . date('Y-m-d\TH:i:s+00:00', $lastmod) . "</lastmod>\n";
        echo "\t\t<changefreq>" . esc_html($changefreq) . "</changefreq>\n";
        echo "\t\t<priority>" . esc_html($priority) . "</priority>\n";
        echo "\t</url>\n";
    }
    
    /**
     * Modify robots.txt content
     */
    public function modify_robots_txt($output, $public) {
        if (!$public) {
            return $output;
        }
        
        $output = "User-agent: *\n";
        $output .= "Disallow: /wp-admin/\n";
        $output .= "Allow: /wp-admin/admin-ajax.php\n";
        
        // Add sitemap reference
        if (get_theme_mod('enable_xml_sitemap', true)) {
            $output .= "\nSitemap: " . home_url('/sitemap.xml') . "\n";
        }
        
        return $output;
    }
    
    /**
     * Redirect attachment pages to their parent or file
     */
    public function redirect_attachment_pages() {
        if (is_attachment() && get_theme_mod('noindex_attachment_pages', true)) {
            $url = wp_get_attachment_url(get_queried_object_id());
            if ($url) {
                wp_redirect($url, 301);
                exit;
            }
        }
    }
    
    /**
     * Add resource hints for performance
     */
    public function add_resource_hints($hints, $relation_type) {
        if ('dns-prefetch' === $relation_type) {
            $domains = get_theme_mod('dns_prefetch_domains', '');
            if (!empty($domains)) {
                $domains = explode(',', $domains);
                foreach ($domains as $domain) {
                    $domain = trim($domain);
                    if (!empty($domain)) {
                        $hints[] = '//' . $domain;
                    }
                }
            }
        }
        
        return $hints;
    }
    
    /**
     * Optimize content markup for SEO
     */
    public function optimize_content_markup($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        // Add nofollow to external links
        if (get_theme_mod('nofollow_external_links', true)) {
            $content = $this->add_nofollow_to_external_links($content);
        }
        
        // Add title attributes to images
        if (get_theme_mod('optimize_image_markup', true)) {
            $content = $this->add_title_to_images($content);
        }
        
        return $content;
    }
    
    private function add_nofollow_to_external_links($content) {
        $site_url = home_url();
        
        return preg_replace_callback('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', function($matches) use ($site_url) {
            $link = $matches[0];
            $url = $matches[1];
            
            // Skip if already has rel attribute
            if (strpos($link, 'rel=') !== false) {
                return $link;
            }
            
            // Skip if internal link
            if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                return $link;
            }
            
            // Skip if mailto or tel link
            if (strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) {
                return $link;
            }
            
            // Add nofollow
            return str_replace('<a ', '<a rel="nofollow" ', $link);
        }, $content);
    }
    
    private function add_title_to_images($content) {
        return preg_replace_callback('/<img([^>]*)>/i', function($matches) {
            $img_tag = $matches[0];
            $img_attrs = $matches[1];
            
            // Skip if already has title attribute
            if (strpos($img_attrs, 'title=') !== false) {
                return $img_tag;
            }
            
            // Get alt text if available
            $alt = '';
            if (preg_match('/alt=["\']([^"\']*)["\']/i', $img_attrs, $alt_matches)) {
                $alt = $alt_matches[1];
            }
            
            if (!empty($alt)) {
                return str_replace('<img', '<img title="' . esc_attr($alt) . '"', $img_tag);
            }
            
            return $img_tag;
        }, $content);
    }
    
    /**
     * Optimize image markup
     */
    public function optimize_image_markup($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (empty($html)) {
            return $html;
        }
        
        // Add missing alt attribute
        if (strpos($html, 'alt=') === false) {
            $alt = get_post_meta($post_thumbnail_id, '_wp_attachment_image_alt', true);
            if (empty($alt)) {
                $alt = get_the_title($post_id);
            }
            $html = str_replace('<img ', '<img alt="' . esc_attr($alt) . '" ', $html);
        }
        
        // Add loading="lazy" for lazy loading
        if (get_theme_mod('enable_lazy_loading', true) && strpos($html, 'loading=') === false) {
            $html = str_replace('<img ', '<img loading="lazy" ', $html);
        }
        
        return $html;
    }
}

// Initialize the SEO class
new Theme_SEO_Pro();

/**
 * Breadcrumbs function for theme
 */
function theme_breadcrumbs($return_array = false) {
    if (!get_theme_mod('enable_breadcrumbs', true)) {
        return $return_array ? array() : '';
    }
    
    $separator = get_theme_mod('breadcrumbs_separator', '»');
    $show_home = get_theme_mod('breadcrumbs_show_home', true);
    $home_text = get_theme_mod('breadcrumbs_home_text', __('Home', 'seokar'));
    $show_current = get_theme_mod('breadcrumbs_show_current', true);
    
    global $post;
    $breadcrumbs = array();
    
    // Home page
    if ($show_home) {
        $breadcrumbs[] = array(
            'name' => $home_text,
            'url' => home_url('/'),
        );
    }
    
    if (is_category() || is_single()) {
        // Category
        $category = get_the_category();
        if (!empty($category)) {
            $breadcrumbs[] = array(
                'name' => $category[0]->cat_name,
                'url' => get_category_link($category[0]->term_id),
            );
        }
        
        // Single post
        if (is_single() && $show_current) {
            $breadcrumbs[] = array(
                'name' => get_the_title(),
                'url' => get_permalink(),
            );
        }
    } elseif (is_page()) {
        // Page hierarchy
        if ($post->post_parent) {
            $ancestors = get_post_ancestors($post->ID);
            $ancestors = array_reverse($ancestors);
            
            foreach ($ancestors as $ancestor) {
                $breadcrumbs[] = array(
                    'name' => get_the_title($ancestor),
                    'url' => get_permalink($ancestor),
                );
            }
        }
        
        // Current page
        if ($show_current) {
            $breadcrumbs[] = array(
                'name' => get_the_title(),
                'url' => get_permalink(),
            );
        }
    } elseif (is_search()) {
        // Search results
        if ($show_current) {
            $breadcrumbs[] = array(
                'name' => __('Search Results', 'seokar'),
                'url' => get_search_link(),
            );
        }
    } elseif (is_404()) {
        // 404 page
        $breadcrumbs[] = array(
            'name' => __('404 Not Found', 'seokar'),
            'url' => '',
        );
    } elseif (is_tag()) {
        // Tag archive
        $breadcrumbs[] = array(
            'name' => single_tag_title('', false),
            'url' => get_tag_link(get_queried_object_id()),
        );
    } elseif (is_author()) {
        // Author archive
        $breadcrumbs[] = array(
            'name' => get_the_author_meta('display_name', get_queried_object_id()),
            'url' => get_author_posts_url(get_queried_object_id()),
        );
    } elseif (is_day()) {
        // Daily archive
        $breadcrumbs[] = array(
            'name' => get_the_date(),
            'url' => get_day_link(get_the_time('Y'), get_the_time('m'), get_the_time('d')),
        );
    } elseif (is_month()) {
        // Monthly archive
        $breadcrumbs[] = array(
            'name' => get_the_date('F Y'),
            'url' => get_month_link(get_the_time('Y'), get_the_time('m')),
        );
    } elseif (is_year()) {
        // Yearly archive
        $breadcrumbs[] = array(
            'name' => get_the_date('Y'),
            'url' => get_year_link(get_the_time('Y')),
        );
    }

    if ($return_array) {
        return $breadcrumbs;
    }

    // Generate HTML output
    $output = '<nav class="breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'seokar') . '">';
    $output .= '<ol itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    $position = 1;
    foreach ($breadcrumbs as $crumb) {
        $output .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if (!empty($crumb['url'])) {
            $output .= '<a itemprop="item" href="' . esc_url($crumb['url']) . '">';
            $output .= '<span itemprop="name">' . esc_html($crumb['name']) . '</span>';
            $output .= '</a>';
        } else {
            $output .= '<span itemprop="name">' . esc_html($crumb['name']) . '</span>';
        }
        
        $output .= '<meta itemprop="position" content="' . $position . '" />';
        $output .= '</li>';
        
        if ($crumb !== end($breadcrumbs)) {
            $output .= '<li class="separator">' . esc_html($separator) . '</li>';
        }
        
        $position++;
    }
    
    $output .= '</ol>';
    $output .= '</nav>';
    
    return $output;
}

/**
 * Output analytics code
 */
function theme_analytics_code() {
    $ga_tracking_id = get_theme_mod('ga_tracking_id', '');
    $gtm_container_id = get_theme_mod('gtm_container_id', '');
    $facebook_pixel_id = get_theme_mod('facebook_pixel_id', '');
    $tracking_position = get_theme_mod('tracking_code_position', 'head');
    
    if ($tracking_position !== 'head') {
        return;
    }
    
    // Google Analytics
    if ($ga_tracking_id) {
        echo "<!-- Google Analytics -->\n";
        echo "<script async src='https://www.googletagmanager.com/gtag/js?id=" . esc_js($ga_tracking_id) . "'></script>\n";
        echo "<script>\n";
        echo "  window.dataLayer = window.dataLayer || [];\n";
        echo "  function gtag(){dataLayer.push(arguments);}\n";
        echo "  gtag('js', new Date());\n";
        
        // GDPR compliance
        if (get_theme_mod('ga_anonymize_ip', true)) {
            echo "  gtag('config', '" . esc_js($ga_tracking_id) . "', { 'anonymize_ip': true });\n";
        } else {
            echo "  gtag('config', '" . esc_js($ga_tracking_id) . "');\n";
        }
        
        echo "</script>\n";
    }
    
    // Google Tag Manager
    if ($gtm_container_id) {
        echo "<!-- Google Tag Manager -->\n";
        echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n";
        echo "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n";
        echo "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n";
        echo "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n";
        echo "})(window,document,'script','dataLayer','" . esc_js($gtm_container_id) . "');</script>\n";
        echo "<!-- End Google Tag Manager -->\n";
    }
    
    // Facebook Pixel
    if ($facebook_pixel_id) {
        echo "<!-- Facebook Pixel Code -->\n";
        echo "<script>\n";
        echo "!function(f,b,e,v,n,t,s)\n";
        echo "{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\n";
        echo "n.callMethod.apply(n,arguments):n.queue.push(arguments)};\n";
        echo "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\n";
        echo "n.queue=[];t=b.createElement(e);t.async=!0;\n";
        echo "t.src=v;s=b.getElementsByTagName(e)[0];\n";
        echo "s.parentNode.insertBefore(t,s)}(window, document,'script',\n";
        echo "'https://connect.facebook.net/en_US/fbevents.js');\n";
        echo "fbq('init', '" . esc_js($facebook_pixel_id) . "');\n";
        echo "fbq('track', 'PageView');\n";
        echo "</script>\n";
        echo "<noscript><img height='1' width='1' style='display:none'\n";
        echo "src='https://www.facebook.com/tr?id=" . esc_js($facebook_pixel_id) . "&ev=PageView&noscript=1'\n";
        echo "/></noscript>\n";
        echo "<!-- End Facebook Pixel Code -->\n";
    }
}
add_action('wp_head', 'theme_analytics_code', 20);

/**
 * Output footer analytics code
 */
function theme_footer_analytics_code() {
    $tracking_position = get_theme_mod('tracking_code_position', 'head');
    
    if ($tracking_position !== 'footer') {
        return;
    }
    
    $ga_tracking_id = get_theme_mod('ga_tracking_id', '');
    
    if ($ga_tracking_id) {
        echo "<!-- Google Analytics -->\n";
        echo "<script>\n";
        echo "  window.dataLayer = window.dataLayer || [];\n";
        echo "  function gtag(){dataLayer.push(arguments);}\n";
        echo "  gtag('js', new Date());\n";
        
        // GDPR compliance
        if (get_theme_mod('ga_anonymize_ip', true)) {
            echo "  gtag('config', '" . esc_js($ga_tracking_id) . "', { 'anonymize_ip': true });\n";
        } else {
            echo "  gtag('config', '" . esc_js($ga_tracking_id) . "');\n";
        }
        
        echo "</script>\n";
    }
}
add_action('wp_footer', 'theme_footer_analytics_code', 20);

/**
 * Output GTM noscript code
 */
function theme_gtm_noscript() {
    $gtm_container_id = get_theme_mod('gtm_container_id', '');
    
    if ($gtm_container_id) {
        echo '<!-- Google Tag Manager (noscript) -->';
        echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr($gtm_container_id) . '"';
        echo ' height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
        echo '<!-- End Google Tag Manager (noscript) -->';
    }
}
add_action('wp_body_open', 'theme_gtm_noscript');

/**
 * Enhance RSS feeds
 */
function theme_enhance_rss_feeds() {
    if (!get_theme_mod('enhance_rss_feeds', true)) {
        return;
    }
    
    // Add featured image to RSS feed
    add_filter('the_excerpt_rss', 'theme_add_featured_image_to_rss');
    add_filter('the_content_feed', 'theme_add_featured_image_to_rss');
    
    // Add better metadata to RSS feed
    add_action('rss2_item', 'theme_add_rss_item_metadata');
}
add_action('init', 'theme_enhance_rss_feeds');

function theme_add_featured_image_to_rss($content) {
    global $post;
    
    if (has_post_thumbnail($post->ID)) {
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium');
        if ($thumbnail) {
            $content = '<p><img src="' . esc_url($thumbnail[0]) . '" width="' . esc_attr($thumbnail[1]) . '" height="' . esc_attr($thumbnail[2]) . '" /></p>' . $content;
        }
    }
    
    return $content;
}

function theme_add_rss_item_metadata() {
    global $post;
    
    // Add categories
    $categories = get_the_category($post->ID);
    if (!empty($categories)) {
        foreach ($categories as $category) {
            echo '<category><![CDATA[' . esc_html($category->name) . ']]></category>' . "\n";
        }
    }
    
    // Add publish and modified dates
    echo '<pubDate>' . esc_html(mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false)) . '</pubDate>' . "\n";
    echo '<dc:creator>' . esc_html(get_the_author_meta('display_name', $post->post_author)) . '</dc:creator>' . "\n";
    echo '<guid isPermaLink="false">' . esc_html(get_the_guid($post->ID)) . '</guid>' . "\n";
}

/**
 * Disable emojis if setting is enabled
 */
function theme_disable_emojis() {
    if (!get_theme_mod('disable_emojis', true)) {
        return;
    }
    
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    
    add_filter('tiny_mce_plugins', 'theme_disable_emojis_tinymce');
    add_filter('wp_resource_hints', 'theme_disable_emojis_remove_dns_prefetch', 10, 2);
}
add_action('init', 'theme_disable_emojis');

function theme_disable_emojis_tinymce($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    }
    return array();
}

function theme_disable_emojis_remove_dns_prefetch($urls, $relation_type) {
    if ('dns-prefetch' === $relation_type) {
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}

/**
 * Disable embeds if setting is enabled
 */
function theme_disable_embeds() {
    if (!get_theme_mod('disable_embeds', true)) {
        return;
    }
    
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    
    add_filter('embed_oembed_discover', '__return_false');
    add_filter('tiny_mce_plugins', 'theme_disable_embeds_tinymce_plugin');
    add_filter('rewrite_rules_array', 'theme_disable_embeds_rewrites');
}
add_action('init', 'theme_disable_embeds', 9999);

function theme_disable_embeds_tinymce_plugin($plugins) {
    return array_diff($plugins, array('wpembed'));
}

function theme_disable_embeds_rewrites($rules) {
    foreach ($rules as $rule => $rewrite) {
        if (false !== strpos($rewrite, 'embed=true')) {
            unset($rules[$rule]);
        }
    }
    return $rules;
}

/**
 * Add SEO admin bar menu
 */
function theme_seo_admin_bar_menu($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $wp_admin_bar->add_node(array(
        'id'    => 'theme-seo',
        'title' => __('SEO', 'seokar'),
        'href'  => admin_url('admin.php?page=theme-seo-settings'),
    ));
    
    // Add quick links
    $wp_admin_bar->add_node(array(
        'id'     => 'theme-seo-settings',
        'parent' => 'theme-seo',
        'title'  => __('SEO Settings', 'seokar'),
        'href'   => admin_url('admin.php?page=theme-seo-settings'),
    ));
    
    $wp_admin_bar->add_node(array(
        'id'     => 'theme-seo-analysis',
        'parent' => 'theme-seo',
        'title'  => __('SEO Analysis', 'seokar'),
        'href'   => admin_url('admin.php?page=theme-seo-analysis'),
    ));
    
    // Add current page SEO score
    if (is_singular()) {
        global $post;
        $score = get_post_meta($post->ID, '_seo_score', true);
        $color = '#46b450'; // Green
        
        if ($score < 50) {
            $color = '#dc3232'; // Red
        } elseif ($score < 70) {
            $color = '#ffb900'; // Yellow
        }
        
        $wp_admin_bar->add_node(array(
            'id'     => 'theme-seo-score',
            'parent' => 'theme-seo',
            'title'  => sprintf(__('Current Page Score: %s%%', 'seokar'), $score),
            'meta'   => array(
                'html'  => '<style>#wpadminbar #wp-admin-bar-theme-seo-score .ab-item { color: ' . $color . ' !important; }</style>',
            ),
        ));
    }
}
add_action('admin_bar_menu', 'theme_seo_admin_bar_menu', 100);
