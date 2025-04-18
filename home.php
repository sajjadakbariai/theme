<?php
/**
 * The template for displaying the blog posts index
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
                // Featured posts slider
                if (is_home() && !is_paged() && get_theme_mod('seokar_featured_posts', true)) {
                    get_template_part('template-parts/featured', 'slider');
                }
                ?>

                <div class="home-posts-grid" itemscope itemtype="https://schema.org/Blog">
                    <?php
                    if (have_posts()) :

                        // Start the Loop
                        while (have_posts()) :
                            the_post();

                            /*
                             * Include the Post-Type-specific template for the content.
                             * If you want to override this in a child theme, then include a file
                             * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                             */
                            get_template_part('template-parts/content', get_post_type());

                        endwhile;

                    else :

                        get_template_part('template-parts/content', 'none');

                    endif;
                    ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination(array(
                    'mid_size'  => 2,
                    'prev_text' => __('<span class="screen-reader-text">Previous</span>', 'seokar'),
                    'next_text' => __('<span class="screen-reader-text">Next</span>', 'seokar'),
                    'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Page', 'seokar') . ' </span>',
                ));
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
