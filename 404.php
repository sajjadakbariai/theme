<?php
/**
 * The template for displaying 404 pages (not found)
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
                <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'seokar'); ?></p>

                <div class="error-search">
                    <?php get_search_form(); ?>
                </div>

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
                                'number'     => 10,
                            ));
                            ?>
                        </ul>
                    </div><!-- .widget -->

                    <div class="widget widget_recent_entries">
                        <h2 class="widget-title"><?php esc_html_e('Latest Posts', 'seokar'); ?></h2>
                        <ul>
                            <?php
                            $recent_posts = wp_get_recent_posts(array(
                                'numberposts' => 5,
                                'post_status' => 'publish',
                            ));
                            foreach ($recent_posts as $post) :
                                ?>
                                <li>
                                    <a href="<?php echo esc_url(get_permalink($post['ID'])); ?>"><?php echo esc_html($post['post_title']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div><!-- .widget -->

                    <div class="widget widget_tag_cloud">
                        <h2 class="widget-title"><?php esc_html_e('Tags Cloud', 'seokar'); ?></h2>
                        <?php
                        wp_tag_cloud(array(
                            'smallest' => 12,
                            'largest'  => 12,
                            'unit'     => 'px',
                            'number'   => 50,
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
