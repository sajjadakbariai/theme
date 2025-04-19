<?php
/**
 * نمایش متا تگ‌های سئو در هدر سایت
 * 
 * @package    SeoKar
 * @subpackage Templates
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// دریافت تنظیمات سئو
$seo_title = apply_filters('seokar_meta_title', '');
$seo_description = apply_filters('seokar_meta_description', '');
$seo_keywords = apply_filters('seokar_meta_keywords', '');
$seo_canonical = apply_filters('seokar_canonical_url', '');
$seo_robots = apply_filters('seokar_meta_robots', '');

// نمایش متا تگ‌ها
if (!empty($seo_title)) : ?>
<title><?php echo esc_html($seo_title); ?></title>
<?php endif;

if (!empty($seo_description)) : ?>
<meta name="description" content="<?php echo esc_attr($seo_description); ?>">
<?php endif;

if (!empty($seo_keywords)) : ?>
<meta name="keywords" content="<?php echo esc_attr($seo_keywords); ?>">
<?php endif;

if (!empty($seo_canonical)) : ?>
<link rel="canonical" href="<?php echo esc_url($seo_canonical); ?>">
<?php endif;

if (!empty($seo_robots)) : ?>
<meta name="robots" content="<?php echo esc_attr($seo_robots); ?>">
<?php endif;

// نمایش متا تگ‌های OpenGraph
if (apply_filters('seokar_enable_og_tags', true)) :
    $og_title = !empty($seo_title) ? $seo_title : get_the_title();
    $og_description = !empty($seo_description) ? $seo_description : get_the_excerpt();
    $og_url = !empty($seo_canonical) ? $seo_canonical : get_permalink();
    $og_image = apply_filters('seokar_og_image', ''); ?>
<meta property="og:locale" content="<?php echo esc_attr(get_locale()); ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
<meta property="og:description" content="<?php echo esc_attr($og_description); ?>">
<meta property="og:url" content="<?php echo esc_url($og_url); ?>">
<?php if (!empty($og_image)) : ?>
<meta property="og:image" content="<?php echo esc_url($og_image); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<?php endif;
endif;

// نمایش متا تگ‌های Twitter Card
if (apply_filters('seokar_enable_twitter_tags', true)) :
    $twitter_card = apply_filters('seokar_twitter_card_type', 'summary_large_image');
    $twitter_title = !empty($seo_title) ? $seo_title : get_the_title();
    $twitter_description = !empty($seo_description) ? $seo_description : get_the_excerpt();
    $twitter_image = apply_filters('seokar_twitter_image', ''); ?>
<meta name="twitter:card" content="<?php echo esc_attr($twitter_card); ?>">
<meta name="twitter:title" content="<?php echo esc_attr($twitter_title); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($twitter_description); ?>">
<?php if (!empty($twitter_image)) : ?>
<meta name="twitter:image" content="<?php echo esc_url($twitter_image); ?>">
<?php endif;
endif;

// نمایش متا تگ‌های اضافی
do_action('seokar_after_meta_tags');
