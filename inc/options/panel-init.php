<?php
if (!defined('ABSPATH')) exit;

// ثبت منو پنل تنظیمات
add_action('admin_menu', function () {
    add_menu_page(
        'تنظیمات قالب سئوکار',
        'تنظیمات سئوکار',
        'manage_options',
        'seokar-theme-options',
        'seokar_render_options_page',
        'dashicons-admin-generic',
        61
    );
});

// ثبت استایل و اسکریپت در پنل تنظیمات
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_seokar-theme-options') {
        // استایل پنل تنظیمات
        wp_enqueue_style('seokar-admin-options', get_template_directory_uri() . '/assets/css/admin-options.css', [], '1.0');

        // جاوااسکریپت اختصاصی پنل تنظیمات
        wp_enqueue_script('seokar-admin-options-js', get_template_directory_uri() . '/assets/js/admin-options.js', ['jquery'], '1.0', true);

        // پاس دادن متغیرها به JS در صورت نیاز
        wp_localize_script('seokar-admin-options-js', 'seokarOptions', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('seokar_admin_nonce')
        ]);
    }
});

// رندر صفحه تنظیمات قالب
function seokar_render_options_page() {
    $active_tab = $_GET['tab'] ?? 'general';

    $tabs = [
        'general'    => 'عمومی',
        'seo'        => 'سئو',
        'ai'         => 'هوش مصنوعی',
        'api'        => 'اتصال API',
        'advanced'   => 'پیشرفته',
        'analytics'  => 'آمار سایت',
        'header'     => 'هدر و فوتر',
        'schema'     => 'اسکیما',
        'ads'        => 'تبلیغات و کد',
        'log'        => 'لاگ‌ها',
        'email'      => 'ایمیل و اعلان',
        'license'    => 'لایسنس',
        'support'    => 'پشتیبانی'
    ];

    echo '<div class="wrap seokar-panel">';
    echo '<h1 class="seokar-panel-title">تنظیمات قالب سئوکار</h1>';

    // تب‌ها
    echo '<nav class="nav-tab-wrapper">';
    foreach ($tabs as $key => $label) {
        $class = ($active_tab === $key) ? 'nav-tab nav-tab-active' : 'nav-tab';
        echo '<a class="' . esc_attr($class) . '" href="?page=seokar-theme-options&tab=' . esc_attr($key) . '">' . esc_html($label) . '</a>';
    }
    echo '</nav>';

    echo '<form method="post" action="options.php" class="seokar-options-form">';
    
    // بارگذاری فایل تنظیمات مربوط به تب فعال
    switch ($active_tab) {
        case 'seo':       require_once __DIR__ . '/seo-settings.php'; break;
        case 'ai':        require_once __DIR__ . '/ai-settings.php'; break;
        case 'api':       require_once __DIR__ . '/api-settings.php'; break;
        case 'advanced':  require_once __DIR__ . '/advanced-settings.php'; break;
        case 'analytics': require_once __DIR__ . '/analytics-settings.php'; break;
        case 'header':    require_once __DIR__ . '/header-footer-settings.php'; break;
        case 'schema':    require_once __DIR__ . '/schema-settings.php'; break;
        case 'ads':       require_once __DIR__ . '/ads-code-settings.php'; break;
        case 'log':       require_once __DIR__ . '/log-settings.php'; break;
        case 'email':     require_once __DIR__ . '/email-settings.php'; break;
        case 'license':   require_once __DIR__ . '/license-settings.php'; break;
        case 'support':   require_once __DIR__ . '/support-settings.php'; break;
        default:          require_once __DIR__ . '/general-settings.php'; break;
    }

    echo '</form>';
    echo '</div>';
}
