<?php
/**
 * Custom Taxonomies for SEOKAR Theme
 * 
 * این فایل شامل تمامی تاکسونومی‌های سفارشی برای قالب SEOKAR می‌باشد.
 * 
 * @package    SEOKAR
 * @subpackage Core
 * @author     Your Name <your.email@example.com>
 * @license    GPL-3.0
 * @link       https://example.com
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register Custom Taxonomies
 *
 * این تابع تمامی تاکسونومی‌های سفارشی را ثبت می‌کند.
 */
function seokar_register_custom_taxonomies() {
    // تاکسونومی نمونه: دسته‌بندی پروژه‌ها
    register_taxonomy(
        'project_category',
        ['portfolio'],
        [
            'labels' => [
                'name'              => __('دسته‌بندی پروژه‌ها', 'seokar'),
                'singular_name'     => __('دسته‌بندی پروژه', 'seokar'),
                'search_items'      => __('جستجوی دسته‌بندی‌ها', 'seokar'),
                'all_items'         => __('همه دسته‌بندی‌ها', 'seokar'),
                'parent_item'       => __('دسته‌بندی مادر', 'seokar'),
                'parent_item_colon' => __('دسته‌بندی مادر:', 'seokar'),
                'edit_item'         => __('ویرایش دسته‌بندی', 'seokar'),
                'update_item'       => __('بروزرسانی دسته‌بندی', 'seokar'),
                'add_new_item'      => __('افزودن دسته‌بندی جدید', 'seokar'),
                'new_item_name'     => __('نام دسته‌بندی جدید', 'seokar'),
                'menu_name'         => __('دسته‌بندی پروژه‌ها', 'seokar'),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'project-category'],
            'show_in_rest'      => true,
            'capabilities'      => [
                'manage_terms'  => 'manage_categories',
                'edit_terms'    => 'manage_categories',
                'delete_terms'  => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ]
    );

    // تاکسونومی نمونه: تگ‌های محصولات
    register_taxonomy(
        'product_tag',
        ['product'],
        [
            'labels' => [
                'name'              => __('تگ‌های محصول', 'seokar'),
                'singular_name'     => __('تگ محصول', 'seokar'),
                'search_items'      => __('جستجوی تگ‌ها', 'seokar'),
                'all_items'         => __('همه تگ‌ها', 'seokar'),
                'edit_item'         => __('ویرایش تگ', 'seokar'),
                'update_item'       => __('بروزرسانی تگ', 'seokar'),
                'add_new_item'      => __('افزودن تگ جدید', 'seokar'),
                'new_item_name'     => __('نام تگ جدید', 'seokar'),
                'menu_name'         => __('تگ‌های محصول', 'seokar'),
            ],
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'product-tag'],
            'show_in_rest'      => true,
        ]
    );

    // تاکسونومی نمونه: برندها
    register_taxonomy(
        'brand',
        ['product'],
        [
            'labels' => [
                'name'              => __('برندها', 'seokar'),
                'singular_name'     => __('برند', 'seokar'),
                'search_items'      => __('جستجوی برندها', 'seokar'),
                'all_items'         => __('همه برندها', 'seokar'),
                'edit_item'         => __('ویرایش برند', 'seokar'),
                'update_item'       => __('بروزرسانی برند', 'seokar'),
                'add_new_item'      => __('افزودن برند جدید', 'seokar'),
                'new_item_name'     => __('نام برند جدید', 'seokar'),
                'menu_name'         => __('برندها', 'seokar'),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'brand'],
            'show_in_rest'      => true,
        ]
    );
}
add_action('init', 'seokar_register_custom_taxonomies', 0);

/**
 * Flush rewrite rules on theme activation
 *
 * این تابع قوانین بازنویسی را هنگام فعال‌سازی قالب بازنویسی می‌کند.
 */
function seokar_flush_rewrite_rules() {
    seokar_register_custom_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'seokar_flush_rewrite_rules');

/**
 * Add custom meta fields to taxonomies
 *
 * این تابع فیلدهای متا سفارشی را به تاکسونومی‌ها اضافه می‌کند.
 */
function seokar_add_taxonomy_meta_fields() {
    // افزودن فیلد تصویر برای دسته‌بندی‌ها
    if (!class_exists('Taxonomy_MetaData_Controller')) {
        require_once SEOKAR_DIR . '/inc/libs/taxonomy-metadata.php';
    }

    $meta_fields = [
        'project_category' => [
            'seokar_category_icon' => [
                'label' => __('آیکون دسته‌بندی', 'seokar'),
                'type'  => 'text',
                'desc'  => __('کد آیکون (مثلاً از فونت‌آیکون)', 'seokar'),
            ],
            'seokar_category_image' => [
                'label' => __('تصویر دسته‌بندی', 'seokar'),
                'type'  => 'image',
                'desc'  => __('تصویر نماینده برای این دسته‌بندی', 'seokar'),
            ],
        ],
        'brand' => [
            'seokar_brand_logo' => [
                'label' => __('لوگوی برند', 'seokar'),
                'type'  => 'image',
                'desc'  => __('لوگوی این برند را آپلود کنید', 'seokar'),
            ],
            'seokar_brand_url' => [
                'label' => __('آدرس سایت برند', 'seokar'),
                'type'  => 'url',
                'desc'  => __('آدرس رسمی وبسایت این برند', 'seokar'),
            ],
        ],
    ];

    foreach ($meta_fields as $taxonomy => $fields) {
        new Taxonomy_MetaData_Controller($taxonomy, $fields);
    }
}
add_action('admin_init', 'seokar_add_taxonomy_meta_fields');

/**
 * Add custom columns to taxonomy admin table
 *
 * این تابع ستون‌های سفارشی به جدول مدیریت تاکسونومی‌ها اضافه می‌کند.
 */
function seokar_add_taxonomy_admin_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'name' => $columns['name'],
        'icon' => __('آیکون', 'seokar'),
        'image' => __('تصویر', 'seokar'),
        'slug' => $columns['slug'],
        'posts' => $columns['posts'],
    ];
    return $new_columns;
}
add_filter('manage_edit-project_category_columns', 'seokar_add_taxonomy_admin_columns');

/**
 * Populate custom columns in taxonomy admin table
 *
 * این تابع محتوای ستون‌های سفارشی را در جدول مدیریت پر می‌کند.
 */
function seokar_populate_taxonomy_admin_columns($content, $column_name, $term_id) {
    switch ($column_name) {
        case 'icon':
            $icon = get_term_meta($term_id, 'seokar_category_icon', true);
            return $icon ? '<i class="' . esc_attr($icon) . '"></i>' : '—';
        case 'image':
            $image_id = get_term_meta($term_id, 'seokar_category_image_id', true);
            return $image_id ? wp_get_attachment_image($image_id, 'thumbnail') : '—';
        default:
            return $content;
    }
}
add_filter('manage_project_category_custom_column', 'seokar_populate_taxonomy_admin_columns', 10, 3);

/**
 * Register REST API fields for taxonomies
 *
 * این تابع فیلدهای متا را برای REST API ثبت می‌کند.
 */
function seokar_register_taxonomy_rest_fields() {
    register_rest_field(
        'project_category',
        'seokar_category_icon',
        [
            'get_callback' => function($term) {
                return get_term_meta($term['id'], 'seokar_category_icon', true);
            },
            'schema' => [
                'description' => __('آیکون دسته‌بندی', 'seokar'),
                'type' => 'string',
            ],
        ]
    );

    register_rest_field(
        'project_category',
        'seokar_category_image',
        [
            'get_callback' => function($term) {
                $image_id = get_term_meta($term['id'], 'seokar_category_image_id', true);
                return $image_id ? wp_get_attachment_url($image_id) : null;
            },
            'schema' => [
                'description' => __('تصویر دسته‌بندی', 'seokar'),
                'type' => 'string',
                'format' => 'uri',
            ],
        ]
    );
}
/**
 * Enqueue admin styles and scripts
 *
 * این تابع فایل‌های CSS و JS مورد نیاز برای بخش مدیریت را بارگذاری می‌کند.
 */
function seokar_enqueue_taxonomy_admin_assets($hook) {
    // فقط در صفحات مربوط به تاکسونومی‌ها بارگذاری شود
    if ($hook === 'edit-tags.php' || $hook === 'term.php') {
        // CSS
        wp_enqueue_style(
            'seokar-taxonomy-admin',
            SEOKAR_DIR_URI . '/inc/admin/css/admin-taxonomies.css',
            [],
            filemtime(SEOKAR_DIR . '/inc/admin/css/admin-taxonomies.css')
        );

        // JS
        wp_enqueue_media(); // برای استفاده از مدیا آپلودر وردپرس
        wp_enqueue_script(
            'seokar-taxonomy-admin',
            SEOKAR_DIR_URI . '/inc/admin/js/admin-taxonomies.js',
            ['jquery'],
            filemtime(SEOKAR_DIR . '/inc/admin/js/admin-taxonomies.js'),
            true
        );

        // انتقال داده‌ها به JS
        wp_localize_script('seokar-taxonomy-admin', 'seokarTaxonomyData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('seokar_taxonomy_nonce'),
        ]);
    }
}
add_action('admin_enqueue_scripts', 'seokar_enqueue_taxonomy_admin_assets');
add_action('rest_api_init', 'seokar_register_taxonomy_rest_fields');
