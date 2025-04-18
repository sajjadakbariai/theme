<?php
/**
 * The template for displaying all pages
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="container">
    <div class="row">
        <div id="primary" class="content-area <?php echo (is_active_sidebar('sidebar-page') ? 'has-sidebar' : 'no-sidebar'); ?>">
            <main id="main" class="site-main" role="main">

                <?php
                while (have_posts()) :
                    the_post();

                    // Include the page content template
                    get_template_part('template-parts/content', 'page');

                    // If comments are open or we have at least one comment, load up the comment template
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;

                endwhile; // End of the loop.
                ?>

            </main><!-- #main -->
        </div><!-- #primary -->

        <?php
        if (is_active_sidebar('sidebar-page')) {
            get_sidebar('page');
        }
        ?>
    </div><!-- .row -->
</div><!-- .container -->

<?php
get_footer();
