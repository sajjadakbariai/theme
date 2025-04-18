<?php
/**
 * The template for displaying tag archive pages
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="archive-header-container tag-header">
    <div class="container">
        <?php
        // Tag title
        echo '<h1 class="archive-title tag-title" itemprop="headline">';
        printf(esc_html__('Posts Tagged: %s', 'seokar'), '<span>' . single_tag_title('', false) . '</span>');
        echo '</h1>';

        // Tag description
        $tag_description = tag_description();
        if (!empty($tag_description)) {
            echo '<div class="archive-description tag-description" itemprop="description">' . $tag_description . '</div>';
        }

        // Breadcrumbs
        if (function_exists('seokar_breadcrumbs')) {
            seokar_breadcrumbs();
        }
        ?>
    </div>
</div>

<div class="container">
    <div class="row">
        <div id="primary" class="content-area <?php echo (is_active_sidebar('sidebar-1') ? 'has-sidebar' : 'no-sidebar'); ?>">
            <main id="main" class="site-main" role="main">

                <?php if (have_posts()) : ?>

                    <div class="tag-posts-grid" itemscope itemtype="https://schema.org/Blog">
                        <?php
                        // Start the Loop
                        while (have_posts()) :
                            the_post();

                            /*
                             * Include the Post-Type-specific template for the content.
                             * If you want to override this in a child theme, then include a file
                             * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                             */
                            get_template_part('template-parts/content', 'tag');

                        endwhile;
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

                else :

                    get_template_part('template-parts/content', 'none');

                endif;
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
