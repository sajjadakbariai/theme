<?php
/**
 * The template for displaying 404 pages (Not Found)
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="error-404-container">
    <div class="container">
        <div class="error-404-content" itemscope itemtype="https://schema.org/WebPage">
            <header class="page-header">
                <h1 class="page-title" itemprop="headline"><?php esc_html_e('Oops! That page can&rsquo;t be found.', 'seokar'); ?></h1>
            </header><!-- .page-header -->

            <div class="page-content" itemprop="mainContentOfPage">
                <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try a search or browse the links below.', 'seokar'); ?></p>

                <div class="error-search">
                    <?php get_search_form(); ?>
                </div>

                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary error-home-btn">
                    <?php esc_html_e('Back to Home', 'seokar'); ?>
                </a>

                <div class="error-widgets">
                    <div class="widget widget_categories">
                        <h2 class="widget-title"><?php esc_html_e('Popular Categories', 'seokar'); ?></h2>
                        <ul>
                            <?php
                            wp_list_categories(array(
                                'orderby'    => 'count',
                                'order'      => 'DESC',
                                'show_count' => 1,
                                'title_li'   => '',
                                'number'     => 5,
                            ));
                            ?>
                        </ul>
                    </div><!-- .widget -->

                    <div class="widget widget_recent_entries">
                        <h2 class="widget-title"><?php esc_html_e('Latest Posts', 'seokar'); ?></h2>
                        <ul>
                            <?php
                            $recent_posts = new WP_Query(array(
                                'posts_per_page' => 5,
                                'post_status'    => 'publish',
                            ));

                            if ($recent_posts->have_posts()) :
                                while ($recent_posts->have_posts()) : $recent_posts->the_post();
                                    ?>
                                    <li>
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </li>
                                    <?php
                                endwhile;
                                wp_reset_postdata();
                            endif;
                            ?>
                        </ul>
                    </div><!-- .widget -->

                    <div class="widget widget_tag_cloud">
                        <h2 class="widget-title"><?php esc_html_e('Tags Cloud', 'seokar'); ?></h2>
                        <?php
                        wp_tag_cloud(array(
                            'smallest' => 12,
                            'largest'  => 18,
                            'unit'     => 'px',
                            'number'   => 20,
                            'orderby'  => 'count',
                            'order'    => 'DESC',
                        ));
                        ?>
                    </div><!-- .widget -->
                </div><!-- .error-widgets -->
            </div><!-- .page-content -->
        </div><!-- .error-404-content -->
    </div><!-- .container -->
</div><!-- .error-404-container -->

<?php
get_footer();
?>
