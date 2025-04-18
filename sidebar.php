<?php
/**
 * Sidebar template for SeoKar Theme
 * 
 * @package SeoKar
 * @version 2.0.0
 */

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside id="secondary" class="widget-area" role="complementary" itemscope itemtype="https://schema.org/WPSideBar">
    <div class="sidebar-inner">
        <?php // Sticky sidebar wrapper ?>
        <div class="sidebar-sticky">
            <?php
            // Before widgets hook
            do_action('seokar_before_sidebar_widgets');
            
            // Main sidebar widgets
            dynamic_sidebar('sidebar-1');
            
            // After widgets hook
            do_action('seokar_after_sidebar_widgets');
            ?>
            
            <?php // Optional promotional widget area ?>
            <?php if (is_active_sidebar('sidebar-promo') && is_single()) : ?>
                <div class="sidebar-promo">
                    <?php dynamic_sidebar('sidebar-promo'); ?>
                </div>
            <?php endif; ?>
            
            <?php // Author box in single posts ?>
            <?php if (is_single() && get_theme_mod('seokar_author_box', true)) : ?>
                <div class="widget author-widget" itemscope itemtype="https://schema.org/Person">
                    <h3 class="widget-title"><?php esc_html_e('About The Author', 'seokar'); ?></h3>
                    <div class="author-content">
                        <div class="author-avatar">
                            <?php echo get_avatar(get_the_author_meta('ID'), 100, '', get_the_author_meta('display_name')); ?>
                        </div>
                        <div class="author-info">
                            <h4 class="author-name" itemprop="name"><?php the_author(); ?></h4>
                            <?php if (get_the_author_meta('description')) : ?>
                                <p class="author-bio" itemprop="description"><?php echo esc_html(get_the_author_meta('description')); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="author-link">
                                <?php esc_html_e('View all posts', 'seokar'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php // Related posts in single view ?>
            <?php if (is_single() && get_theme_mod('seokar_related_posts', true)) : ?>
                <div class="widget related-posts-widget">
                    <h3 class="widget-title"><?php esc_html_e('Related Posts', 'seokar'); ?></h3>
                    <?php
                    $related_posts = seokar_get_related_posts(get_the_ID(), 3);
                    if ($related_posts->have_posts()) :
                        echo '<ul class="related-posts-list">';
                        while ($related_posts->have_posts()) : $related_posts->the_post();
                            echo '<li itemscope itemtype="https://schema.org/Article">';
                            if (has_post_thumbnail()) :
                                echo '<a href="' . esc_url(get_permalink()) . '" class="related-post-thumbnail" aria-hidden="true" tabindex="-1">';
                                the_post_thumbnail('thumbnail', array(
                                    'alt' => the_title_attribute(array('echo' => false)),
                                    'itemprop' => 'image'
                                ));
                                echo '</a>';
                            endif;
                            echo '<div class="related-post-content">';
                            echo '<h4 class="related-post-title" itemprop="headline"><a href="' . esc_url(get_permalink()) . '" itemprop="url">' . get_the_title() . '</a></h4>';
                            echo '<time class="related-post-date" datetime="' . esc_attr(get_the_date('c')) . '" itemprop="datePublished">' . esc_html(get_the_date()) . '</time>';
                            echo '</div>';
                            echo '</li>';
                        endwhile;
                        echo '</ul>';
                        wp_reset_postdata();
                    else :
                        echo '<p>' . esc_html__('No related posts found.', 'seokar') . '</p>';
                    endif;
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</aside><!-- #secondary -->
