<?php
/**
 * The template for displaying all pages
 *
 * @package SeoKar
 * @version 2.2.0
 */

get_header(); ?>

<div class="container">
    <div class="row">
        <?php
        $sidebar_active = is_active_sidebar('sidebar-page');
        $content_classes = $sidebar_active ? 'col-lg-8 has-sidebar' : 'col-12 no-sidebar';
        ?>

        <div id="primary" class="content-area <?php echo esc_attr($content_classes); ?>">
            <main id="main" class="site-main" role="main">

                <?php
                if (have_posts()) :
                    while (have_posts()) :
                        the_post();

                        // Include the page content template
                        get_template_part('template-parts/content', 'page');

                        // Load comments if open and not password protected
                        if (!post_password_required() && (comments_open() || get_comments_number())) :
                            comments_template();
                        endif;

                    endwhile;
                else :
                    // If no content, display fallback template
                    get_template_part('template-parts/content', 'none');
                endif;
                ?>

            </main><!-- #main -->
        </div><!-- #primary -->

        <?php if ($sidebar_active) : ?>
            <aside id="secondary" class="widget-area col-lg-4">
                <?php get_sidebar('page'); ?>
            </aside>
        <?php endif; ?>
    </div><!-- .row -->
</div><!-- .container -->

<?php
get_footer();
?>
