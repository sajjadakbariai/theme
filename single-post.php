<?php
/**
 * The template for displaying single blog posts
 * This is a more specific version of single.php for blog posts only
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
                    get_template_part('template-parts/content', 'single-post');

                    // Post meta (custom implementation for posts)
                    if ('post' === get_post_type()) :
                        echo '<div class="entry-meta-wrapper">';
                        seokar_entry_meta();
                        echo '</div>';
                    endif;

                    // Post navigation
                    if (get_theme_mod('seokar_post_navigation', true)) :
                        the_post_navigation(array(
                            'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous Post:', 'seokar') . '</span> <span class="nav-title">%title</span>',
                            'next_text' => '<span class="nav-subtitle">' . esc_html__('Next Post:', 'seokar') . '</span> <span class="nav-title">%title</span>',
                        ));
                    endif;

                    // Comments
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
