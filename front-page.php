<?php
/**
 * The template for displaying the front page
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="front-page-content">
    <?php
    // Hero section
    get_template_part('template-parts/hero', 'section');

    // Features section
    if (get_theme_mod('seokar_show_features', true)) {
        get_template_part('template-parts/features', 'section');
    }

    // Latest posts section
    if (get_theme_mod('seokar_show_latest_posts', true)) {
        get_template_part('template-parts/latest-posts', 'section');
    }

    // Testimonials section
    if (get_theme_mod('seokar_show_testimonials', false)) {
        get_template_part('template-parts/testimonials', 'section');
    }

    // Call to action section
    if (get_theme_mod('seokar_show_cta', true)) {
        get_template_part('template-parts/cta', 'section');
    }
    ?>
</div>

<?php
get_footer();
