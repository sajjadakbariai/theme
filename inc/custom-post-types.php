<?php
/**
 * فایل تعریف پست تایپ‌های سفارشی برای قالب SEOKar
 * 
 * @package SEOKar
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // دسترسی مستقیم ممنوع
}

class SEOKar_Custom_Post_Types {

    /**
     * Constructor - ثبت هوک‌های وردپرس
     */
    public function __construct() {
        add_action('init', array($this, 'register_custom_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('post_updated_messages', array($this, 'custom_post_type_messages'));
    }

    /**
     * ثبت تمام پست تایپ‌های سفارشی
     */
    public function register_custom_post_types() {
        // پست تایپ خدمات
        $this->register_service_post_type();
        
        // پست تایپ پروژه‌ها
        $this->register_project_post_type();
        
        // پست تایپ تیم
        $this->register_team_post_type();
        
        // پست تایپ testimonials (نظرات مشتریان)
        $this->register_testimonial_post_type();
        
        // پست تایپ FAQ (سوالات متداول)
        $this->register_faq_post_type();
    }

    /**
     * ثبت تمام تاکسونومی‌های سفارشی
     */
    public function register_taxonomies() {
        // تاکسونومی دسته‌بندی خدمات
        $this->register_service_category_taxonomy();
        
        // تاکسونومی تگ‌های پروژه
        $this->register_project_tags_taxonomy();
        
        // تاکسونومی بخش‌های تیم
        $this->register_team_department_taxonomy();
    }

    /**
     * ثبت پست تایپ خدمات
     */
    private function register_service_post_type() {
        $labels = array(
            'name'                  => _x('خدمات', 'Post type general name', 'seokar'),
            'singular_name'         => _x('خدمت', 'Post type singular name', 'seokar'),
            'menu_name'             => _x('خدمات', 'Admin Menu text', 'seokar'),
            'name_admin_bar'        => _x('خدمت', 'Add New on Toolbar', 'seokar'),
            'add_new'               => __('افزودن جدید', 'seokar'),
            'add_new_item'          => __('افزودن خدمت جدید', 'seokar'),
            'new_item'              => __('خدمت جدید', 'seokar'),
            'edit_item'             => __('ویرایش خدمت', 'seokar'),
            'view_item'             => __('مشاهده خدمت', 'seokar'),
            'all_items'             => __('همه خدمات', 'seokar'),
            'search_items'          => __('جستجوی خدمات', 'seokar'),
            'parent_item_colon'     => __('خدمات مادر:', 'seokar'),
            'not_found'             => __('خدمتی یافت نشد.', 'seokar'),
            'not_found_in_trash'    => __('هیچ خدمتی در زباله‌دان یافت نشد.', 'seokar'),
            'featured_image'        => _x('تصویر شاخص خدمت', 'Overrides the "Featured Image" phrase for this post type.', 'seokar'),
            'set_featured_image'    => _x('تنظیم تصویر شاخص', 'Overrides the "Set featured image" phrase for this post type.', 'seokar'),
            'remove_featured_image' => _x('حذف تصویر شاخص', 'Overrides the "Remove featured image" phrase for this post type.', 'seokar'),
            'use_featured_image'    => _x('استفاده به عنوان تصویر شاخص', 'Overrides the "Use as featured image" phrase for this post type.', 'seokar'),
            'archives'              => _x('آرشیو خدمات', 'The post type archive label used in nav menus.', 'seokar'),
            'insert_into_item'      => _x('درج در خدمت', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post).', 'seokar'),
            'uploaded_to_this_item' => _x('بارگذاری شده در این خدمت', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase (used when viewing media attached to a post).', 'seokar'),
            'filter_items_list'     => _x('فیلتر لیست خدمات', 'Screen reader text for the filter links heading on the post type listing screen.', 'seokar'),
            'items_list_navigation' => _x('مسیریابی لیست خدمات', 'Screen reader text for the pagination heading on the post type listing screen.', 'seokar'),
            'items_list'            => _x('لیست خدمات', 'Screen reader text for the items list heading on the post type listing screen.', 'seokar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'services', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'      => false,
            'menu_position'     => 5,
            'menu_icon'         => 'dashicons-admin-tools',
            'supports'          => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'comments'),
            'show_in_rest'      => true,
            'rest_base'         => 'services',
        );

        register_post_type('service', $args);
    }

    /**
     * ثبت پست تایپ پروژه‌ها
     */
    private function register_project_post_type() {
        $labels = array(
            'name'               => _x('پروژه‌ها', 'Post type general name', 'seokar'),
            'singular_name'      => _x('پروژه', 'Post type singular name', 'seokar'),
            'menu_name'          => _x('پروژه‌ها', 'Admin Menu text', 'seokar'),
            'name_admin_bar'     => _x('پروژه', 'Add New on Toolbar', 'seokar'),
            'add_new'            => __('افزودن جدید', 'seokar'),
            'add_new_item'       => __('افزودن پروژه جدید', 'seokar'),
            'new_item'           => __('پروژه جدید', 'seokar'),
            'edit_item'          => __('ویرایش پروژه', 'seokar'),
            'view_item'          => __('مشاهده پروژه', 'seokar'),
            'all_items'          => __('همه پروژه‌ها', 'seokar'),
            'search_items'       => __('جستجوی پروژه‌ها', 'seokar'),
            'parent_item_colon'  => __('پروژه‌های مادر:', 'seokar'),
            'not_found'          => __('پروژه‌ای یافت نشد.', 'seokar'),
            'not_found_in_trash' => __('هیچ پروژه‌ای در زباله‌دان یافت نشد.', 'seokar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'           => array('slug' => 'portfolio', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'       => true,
            'hierarchical'      => false,
            'menu_position'     => 6,
            'menu_icon'        => 'dashicons-portfolio',
            'supports'          => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'show_in_rest'     => true,
            'rest_base'        => 'projects',
        );

        register_post_type('project', $args);
    }

    /**
     * ثبت پست تایپ تیم
     */
    private function register_team_post_type() {
        $labels = array(
            'name'               => _x('اعضای تیم', 'Post type general name', 'seokar'),
            'singular_name'      => _x('عضو تیم', 'Post type singular name', 'seokar'),
            'menu_name'          => _x('تیم', 'Admin Menu text', 'seokar'),
            'name_admin_bar'     => _x('عضو تیم', 'Add New on Toolbar', 'seokar'),
            'add_new'            => __('افزودن عضو جدید', 'seokar'),
            'add_new_item'       => __('افزودن عضو جدید تیم', 'seokar'),
            'new_item'           => __('عضو جدید', 'seokar'),
            'edit_item'          => __('ویرایش عضو تیم', 'seokar'),
            'view_item'          => __('مشاهده عضو تیم', 'seokar'),
            'all_items'          => __('همه اعضا', 'seokar'),
            'search_items'       => __('جستجوی اعضا', 'seokar'),
            'parent_item_colon'  => __('اعضای مادر:', 'seokar'),
            'not_found'          => __('عضو تیمی یافت نشد.', 'seokar'),
            'not_found_in_trash' => __('هیچ عضوی در زباله‌دان یافت نشد.', 'seokar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'           => array('slug' => 'team', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'       => false,
            'hierarchical'      => false,
            'menu_position'     => 7,
            'menu_icon'        => 'dashicons-groups',
            'supports'          => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest'      => true,
        );

        register_post_type('team', $args);
    }

    /**
     * ثبت پست تایپ نظرات مشتریان
     */
    private function register_testimonial_post_type() {
        $labels = array(
            'name'               => _x('نظرات', 'Post type general name', 'seokar'),
            'singular_name'      => _x('نظر', 'Post type singular name', 'seokar'),
            'menu_name'          => _x('نظرات', 'Admin Menu text', 'seokar'),
            'name_admin_bar'     => _x('نظر', 'Add New on Toolbar', 'seokar'),
            'add_new'            => __('افزودن نظر جدید', 'seokar'),
            'add_new_item'       => __('افزودن نظر جدید', 'seokar'),
            'new_item'           => __('نظر جدید', 'seokar'),
            'edit_item'          => __('ویرایش نظر', 'seokar'),
            'view_item'          => __('مشاهده نظر', 'seokar'),
            'all_items'          => __('همه نظرات', 'seokar'),
            'search_items'       => __('جستجوی نظرات', 'seokar'),
            'parent_item_colon'  => __('نظرات مادر:', 'seokar'),
            'not_found'          => __('نظری یافت نشد.', 'seokar'),
            'not_found_in_trash' => __('هیچ نظری در زباله‌دان یافت نشد.', 'seokar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'           => array('slug' => 'testimonials', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'       => false,
            'hierarchical'      => false,
            'menu_position'     => 8,
            'menu_icon'        => 'dashicons-testimonial',
            'supports'          => array('title', 'editor', 'thumbnail'),
            'show_in_rest'      => true,
        );

        register_post_type('testimonial', $args);
    }

    /**
     * ثبت پست تایپ سوالات متداول
     */
    private function register_faq_post_type() {
        $labels = array(
            'name'               => _x('سوالات متداول', 'Post type general name', 'seokar'),
            'singular_name'      => _x('سوال متداول', 'Post type singular name', 'seokar'),
            'menu_name'          => _x('سوالات متداول', 'Admin Menu text', 'seokar'),
            'name_admin_bar'     => _x('سوال متداول', 'Add New on Toolbar', 'seokar'),
            'add_new'            => __('افزودن سوال جدید', 'seokar'),
            'add_new_item'       => __('افزودن سوال جدید', 'seokar'),
            'new_item'           => __('سوال جدید', 'seokar'),
            'edit_item'          => __('ویرایش سوال', 'seokar'),
            'view_item'          => __('مشاهده سوال', 'seokar'),
            'all_items'          => __('همه سوالات', 'seokar'),
            'search_items'       => __('جستجوی سوالات', 'seokar'),
            'parent_item_colon'  => __('سوالات مادر:', 'seokar'),
            'not_found'          => __('سوالی یافت نشد.', 'seokar'),
            'not_found_in_trash' => __('هیچ سوالی در زباله‌دان یافت نشد.', 'seokar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'           => array('slug' => 'faq', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'       => true,
            'hierarchical'      => false,
            'menu_position'     => 9,
            'menu_icon'        => 'dashicons-editor-help',
            'supports'          => array('title', 'editor', 'page-attributes'),
            'show_in_rest'      => true,
        );

        register_post_type('faq', $args);
    }

    /**
     * ثبت تاکسونومی دسته‌بندی خدمات
     */
    private function register_service_category_taxonomy() {
        $labels = array(
            'name'              => _x('دسته‌بندی خدمات', 'taxonomy general name', 'seokar'),
            'singular_name'     => _x('دسته‌بندی خدمت', 'taxonomy singular name', 'seokar'),
            'search_items'      => __('جستجوی دسته‌بندی‌ها', 'seokar'),
            'all_items'         => __('همه دسته‌بندی‌ها', 'seokar'),
            'parent_item'       => __('دسته‌بندی مادر', 'seokar'),
            'parent_item_colon' => __('دسته‌بندی مادر:', 'seokar'),
            'edit_item'         => __('ویرایش دسته‌بندی', 'seokar'),
            'update_item'       => __('بروزرسانی دسته‌بندی', 'seokar'),
            'add_new_item'      => __('افزودن دسته‌بندی جدید', 'seokar'),
            'new_item_name'     => __('نام دسته‌بندی جدید', 'seokar'),
            'menu_name'         => __('دسته‌بندی‌ها', 'seokar'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'service-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('service_category', 'service', $args);
    }

    /**
     * ثبت تاکسونومی تگ‌های پروژه
     */
    private function register_project_tags_taxonomy() {
        $labels = array(
            'name'              => _x('تگ‌های پروژه', 'taxonomy general name', 'seokar'),
            'singular_name'     => _x('تگ پروژه', 'taxonomy singular name', 'seokar'),
            'search_items'      => __('جستجوی تگ‌ها', 'seokar'),
            'all_items'         => __('همه تگ‌ها', 'seokar'),
            'parent_item'       => __('تگ مادر', 'seokar'),
            'parent_item_colon' => __('تگ مادر:', 'seokar'),
            'edit_item'         => __('ویرایش تگ', 'seokar'),
            'update_item'       => __('بروزرسانی تگ', 'seokar'),
            'add_new_item'      => __('افزودن تگ جدید', 'seokar'),
            'new_item_name'     => __('نام تگ جدید', 'seokar'),
            'menu_name'         => __('تگ‌ها', 'seokar'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'project-tag'),
            'show_in_rest'      => true,
        );

        register_taxonomy('project_tag', 'project', $args);
    }

    /**
     * ثبت تاکسونومی بخش‌های تیم
     */
    private function register_team_department_taxonomy() {
        $labels = array(
            'name'              => _x('بخش‌ها', 'taxonomy general name', 'seokar'),
            'singular_name'     => _x('بخش', 'taxonomy singular name', 'seokar'),
            'search_items'      => __('جستجوی بخش‌ها', 'seokar'),
            'all_items'         => __('همه بخش‌ها', 'seokar'),
            'parent_item'       => __('بخش مادر', 'seokar'),
            'parent_item_colon' => __('بخش مادر:', 'seokar'),
            'edit_item'         => __('ویرایش بخش', 'seokar'),
            'update_item'       => __('بروزرسانی بخش', 'seokar'),
            'add_new_item'      => __('افزودن بخش جدید', 'seokar'),
            'new_item_name'     => __('نام بخش جدید', 'seokar'),
            'menu_name'         => __('بخش‌ها', 'seokar'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'department'),
            'show_in_rest'      => true,
        );

        register_taxonomy('department', 'team', $args);
    }

    /**
     * سفارشی‌سازی پیام‌های پست تایپ‌ها
     */
    public function custom_post_type_messages($messages) {
        global $post, $post_ID;

        $messages['service'] = array(
            0 => '',
            1 => sprintf(__('خدمت بروزرسانی شد. <a href="%s">مشاهده خدمت</a>', 'seokar'), esc_url(get_permalink($post_ID))),
            2 => __('فیلد سفارشی بروزرسانی شد.', 'seokar'),
            3 => __('فیلد سفارشی حذف شد.', 'seokar'),
            4 => __('خدمت بروزرسانی شد.', 'seokar'),
            5 => isset($_GET['revision']) ? sprintf(__('خدمت به نسخه %s بازگردانی شد.', 'seokar'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
            6 => sprintf(__('خدمت منتشر شد. <a href="%s">مشاهده خدمت</a>', 'seokar'), esc_url(get_permalink($post_ID))),
            7 => __('خدمت ذخیره شد.', 'seokar'),
            8 => sprintf(__('خدمت ارسال شد. <a target="_blank" href="%s">پیش‌نمایش خدمت</a>', 'seokar'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
            9 => sprintf(__('خدمت برای تاریخ <strong>%1$s</strong> برنامه‌ریزی شد. <a target="_blank" href="%2$s">پیش‌نمایش خدمت</a>', 'seokar'),
                date_i18n(__('M j, Y @ G:i', 'seokar'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
            10 => sprintf(__('پیش‌نویس خدمت بروزرسانی شد. <a target="_blank" href="%s">پیش‌نمایش خدمت</a>', 'seokar'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
        );

        $messages['project'] = array(
            0 => '',
            1 => sprintf(__('پروژه بروزرسانی شد. <a href="%s">مشاهده پروژه</a>', 'seokar'), esc_url(get_permalink($post_ID))),
            2 => __('فیلد سفارشی بروزرسانی شد.', 'seokar'),
            3 => __('فیلد سفارشی حذف شد.', 'seokar'),
            4 => __('پروژه بروزرسانی شد.', 'seokar'),
            5 => isset($_GET['revision']) ? sprintf(__('پروژه به نسخه %s بازگردانی شد.', 'seokar'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
            6 => sprintf(__('پروژه منتشر شد. <a href="%s">مشاهده پروژه</a>', 'seokar'), esc_url(get_permalink($post_ID))),
            7 => __('پروژه ذخیره شد.', 'seokar'),
            8 => sprintf(__('پروژه ارسال شد. <a target="_blank" href="%s">پیش‌نمایش پروژه</a>', 'seokar'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
            9 => sprintf(__('پروژه برای تاریخ <strong>%1$s</strong> برنامه‌ریزی شد. <a target="_blank" href="%2$s">پیش‌نمایش پروژه</a>', 'seokar'),
                date_i18n(__('M j, Y @ G:i', 'seokar'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
            10 => sprintf(__('پیش‌نویس پروژه بروزرسانی شد. <a target="_blank" href="%s">پیش‌نمایش پروژه</a>', 'seokar'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
        );

        return $messages;
    }
}

// راه‌اندازی کلاس
new SEOKar_Custom_Post_Types();
