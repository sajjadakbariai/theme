<?php
/**
 * The template for displaying category archive pages
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="archive-header-container category-header">
    <div class="container">
        <?php
        // Category title
        echo '<h1 class="archive-title category-title" itemprop="headline">';
        single_cat_title();
        echo '</h1>';

        // Category description
        $category_description = category_description();
        if (!empty($category_description)) {
            echo '<div class="archive-description category-description" itemprop="description">' . $category_description . '</div>';
        }

        // Breadcrumbs
        if (function_exists('seokar_breadcrumbs')) {
            seokar_breadcrumbs();
        }

        // Subcategories list
        $current_cat = get_queried_object();
        if ($current_cat && $current_cat->term_id) {
            $subcategories = get_categories(array(
                'child_of' => $current_cat->term_id,
                'hide_empty' => 0
            ));

            if ($subcategories) {
                echo '<div class="subcategories-list">';
                echo '<h3>' . esc_html__('Subcategories:', 'seokar') . '</h3>';
                echo '<ul>';
                foreach ($subcategories as $subcategory) {
                    echo '<li><a href="' . esc_url(get_category_link($subcategory->term_id)) . '">' . esc_html($subcategory->name) . '</a></li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        }
        ?>
    </div>
</div>

<div class="container">
    <div class="row">
        <div id="primary" class="content-area <?php echo (is_active_sidebar('sidebar-1') ? 'has-sidebar' : 'no-sidebar'); ?>">
            <main id="main" class="site-main" role="main">

                <?php if (have_posts()) : ?>

                    <div class="category-posts-grid">
                        <?php
                        // Start the Loop
                        while (have_posts()) :
                            the_post();

                            /*
                             * Include the Post-Type-specific template for the content.
                             * If you want to override this in a child theme, then include a file
                             * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                             */
                            get_template_part('template-parts/content', 'category');

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
