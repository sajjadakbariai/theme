<?php
if (!defined('ABSPATH')) exit;

// تنظیمات تب عمومی
add_action('admin_init', function () {
    add_settings_section('seokar_general_section', '', null, 'seokar_general');

    add_settings_field('footer_text', 'متن فوتر', function () {
        $options = get_option('seokar_general_options');
        echo '<input type="text" name="seokar_general_options[footer_text]" value="' . esc_attr($options['footer_text'] ?? '') . '" class="regular-text">';
    }, 'seokar_general', 'seokar_general_section');

    add_settings_field('site_logo', 'آدرس لوگو', function () {
        $options = get_option('seokar_general_options');
        echo '<input type="text" name="seokar_general_options[site_logo]" value="' . esc_attr($options['site_logo'] ?? '') . '" class="regular-text">';
    }, 'seokar_general', 'seokar_general_section');
});

// تنظیمات تب سئو
add_action('admin_init', function () {
    add_settings_section('seokar_seo_section', '', null, 'seokar_seo');

    add_settings_field('meta_title_length', 'حداکثر طول عنوان سئو', function () {
        $options = get_option('seokar_seo_options');
        echo '<input type="number" name="seokar_seo_options[meta_title_length]" value="' . esc_attr($options['meta_title_length'] ?? 60) . '" class="small-text"> کاراکتر';
    }, 'seokar_seo', 'seokar_seo_section');

    add_settings_field('enable_schema', 'فعال‌سازی اسکیما', function () {
        $options = get_option('seokar_seo_options');
        echo '<input type="checkbox" name="seokar_seo_options[enable_schema]" value="1"' . checked(1, $options['enable_schema'] ?? 0, false) . '> نمایش اسکیما در صفحات';
    }, 'seokar_seo', 'seokar_seo_section');
});

// تنظیمات تب هوش مصنوعی
add_action('admin_init', function () {
    add_settings_section('seokar_ai_section', '', null, 'seokar_ai');

    add_settings_field('ai_model', 'انتخاب مدل هوش مصنوعی', function () {
        $options = get_option('seokar_ai_options');
        $models = ['llama' => 'LLaMA', 'gpt' => 'GPT', 'custom' => 'مدل سفارشی'];
        echo '<select name="seokar_ai_options[ai_model]">';
        foreach ($models as $key => $label) {
            $selected = selected($options['ai_model'] ?? '', $key, false);
            echo "<option value='$key' $selected>$label</option>";
        }
        echo '</select>';
    }, 'seokar_ai', 'seokar_ai_section');

    add_settings_field('ai_tone', 'تنظیم لحن پاسخ', function () {
        $options = get_option('seokar_ai_options');
        echo '<input type="text" name="seokar_ai_options[ai_tone]" value="' . esc_attr($options['ai_tone'] ?? 'صمیمی، فارسی روان') . '" class="regular-text">';
    }, 'seokar_ai', 'seokar_ai_section');
});

// تنظیمات تب API
add_action('admin_init', function () {
    add_settings_section('seokar_api_section', '', null, 'seokar_api');

    add_settings_field('api_base_url', 'آدرس دامنه API', function () {
        $options = get_option('seokar_api_options');
        echo '<input type="url" name="seokar_api_options[api_base_url]" value="' . esc_attr($options['api_base_url'] ?? '') . '" class="regular-text">';
    }, 'seokar_api', 'seokar_api_section');

    add_settings_field('api_token', 'توکن اتصال', function () {
        $options = get_option('seokar_api_options');
        echo '<input type="text" name="seokar_api_options[api_token]" value="' . esc_attr($options['api_token'] ?? '') . '" class="regular-text">';
    }, 'seokar_api', 'seokar_api_section');
});
