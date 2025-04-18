<?php
/**
 * The template for displaying search results pages
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="search-header-container">
    <div class="container">
        <h1 class="page-title" itemprop="headline">
            <?php
            /* translators: %s: search query. */
            printf(esc_html__('Search Results for: %s', 'seokar'), '<span>' . get_search_query() . '</span>');
            ?>
        </h1>
        
        <?php
        // Breadcrumbs
        if (function_exists('seokar_breadcrumbs')) {
            seokar_breadcrumbs();
        }
        ?>
        
        <div class="search-form-container">
            <?php get_search_form(); ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div id="primary" class="content-area <?php echo (is_active_sidebar('sidebar-1') ? 'has-sidebar' : 'no-sidebar'); ?>">
            <main id="main" class="site-main" role="main">

                <?php if (have_posts()) : ?>

                    <div class="search-results-grid">
                        <?php
                        // Start the Loop
                        while (have_posts()) :
                            the_post();

                            /**
                             * Run the loop for the search to output the results.
                             * If you want to overload this in a child theme then include a file
                             * called content-search.php and that will be used instead.
                             */
                            get_template_part('template-parts/content', 'search');

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
                    ?>
                    <div class="no-results-suggestions">
                        <h3><?php esc_html_e('Suggestions:', 'seokar'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Make sure all words are spelled correctly.', 'seokar'); ?></li>
                            <li><?php esc_html_e('Try different keywords.', 'seokar'); ?></li>
                            <li><?php esc_html_e('Try more general keywords.', 'seokar'); ?></li>
                            <li><?php esc_html_e('Try fewer keywords.', 'seokar'); ?></li>
                        </ul>
                    </div>

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
