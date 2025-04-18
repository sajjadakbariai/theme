<?php
/**
 * The template for displaying single posts
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="container">
    <div class="row">
        <div id="primary" class="content-area <?php echo (is_active_sidebar('sidebar-1') ? 'has-sidebar' : 'no-sidebar'); ?>">
            <main id="main" class="site-main" role="main">

                <?php
                while (have_posts()) :
                    the_post();

                    // Include the single post content template
                    get_template_part('template-parts/content', 'single');

                    // Post navigation
                    if (get_theme_mod('seokar_post_navigation', true)) :
                        the_post_navigation(array(
                            'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous:', 'seokar') . '</span> <span class="nav-title">%title</span>',
                            'next_text' => '<span class="nav-subtitle">' . esc_html__('Next:', 'seokar') . '</span> <span class="nav-title">%title</span>',
                        ));
                    endif;

                    // Author box (moved to sidebar in this theme)
                    
                    // Related posts (moved to sidebar in this theme)

                    // If comments are open or we have at least one comment, load up the comment template
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;

                endwhile; // End of the loop.
                ?>

            </main><!-- #main -->
        </div><!-- #primary -->

        <?php
        if (is_active_sidebar('sidebar-1')) {
            get_sidebar();
        }
        ?>
    </div><!-- .row -->
</div><!-- .container -->

<?php
get_footer();
