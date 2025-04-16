<?php
if (!defined('ABSPATH')) exit;

// اضافه کردن منو به پیشخوان
add_action('admin_menu', function () {
    add_menu_page(
        'تنظیمات قالب سئوکار',
        'تنظیمات سئوکار',
        'manage_options',
        'seokar-theme-options',
        'seokar_settings_page_render',
        'dashicons-admin-generic',
        61
    );
});

// ثبت تنظیمات برای تب‌ها
add_action('admin_init', function () {
    register_setting('seokar_general', 'seokar_general_options');
    register_setting('seokar_seo', 'seokar_seo_options');
    register_setting('seokar_ai', 'seokar_ai_options');
    register_setting('seokar_api', 'seokar_api_options');
});

// رندر کردن صفحه تنظیمات با تب‌ها
function seokar_settings_page_render() {
    $active_tab = $_GET['tab'] ?? 'general';
    ?>
    <div class="wrap">
        <h1>تنظیمات قالب سئوکار</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=seokar-theme-options&tab=general" class="nav-tab <?= $active_tab == 'general' ? 'nav-tab-active' : '' ?>">عمومی</a>
            <a href="?page=seokar-theme-options&tab=seo" class="nav-tab <?= $active_tab == 'seo' ? 'nav-tab-active' : '' ?>">تنظیمات سئو</a>
            <a href="?page=seokar-theme-options&tab=ai" class="nav-tab <?= $active_tab == 'ai' ? 'nav-tab-active' : '' ?>">هوش مصنوعی</a>
            <a href="?page=seokar-theme-options&tab=api" class="nav-tab <?= $active_tab == 'api' ? 'nav-tab-active' : '' ?>">اتصال API</a>
        </h2>

        <form method="post" action="options.php">
            <?php
            switch ($active_tab) {
                case 'seo':
                    settings_fields('seokar_seo');
                    do_settings_sections('seokar_seo');
                    break;
                case 'ai':
                    settings_fields('seokar_ai');
                    do_settings_sections('seokar_ai');
                    break;
                case 'api':
                    settings_fields('seokar_api');
                    do_settings_sections('seokar_api');
                    break;
                default:
                    settings_fields('seokar_general');
                    do_settings_sections('seokar_general');
                    break;
            }
            submit_button('ذخیره تغییرات');
            ?>
        </form>
    </div>
    <?php
}
