<?php
/**
 * The template for displaying author archive pages
 *
 * @package SeoKar
 * @version 2.0.0
 */

get_header(); ?>

<div class="archive-header-container author-header">
    <div class="container">
        <?php
        // Author information
        $author = get_queried_object();
        if ($author) :
            ?>
            <div class="author-bio" itemscope itemtype="https://schema.org/Person">
                <div class="author-avatar">
                    <?php echo get_avatar($author->ID, 120, '', $author->display_name, array('itemprop' => 'image')); ?>
                </div>
                <div class="author-info">
                    <h1 class="archive-title author-title" itemprop="name"><?php echo esc_html($author->display_name); ?></h1>
                    
                    <?php if ($author->description) : ?>
                        <div class="author-description" itemprop="description"><?php echo wp_kses_post($author->description); ?></div>
                    <?php endif; ?>
                    
                    <div class="author-social-links">
                        <?php
                        $social_links = array(
                            'twitter'   => get_the_author_meta('twitter', $author->ID),
                            'facebook'  => get_the_author_meta('facebook', $author->ID),
                            'instagram' => get_the_author_meta('instagram', $author->ID),
                            'linkedin'  => get_the_author_meta('linkedin', $author->ID),
                            'website'   => get_the_author_meta('url', $author->ID),
                        );
                        
                        foreach ($social_links as $network => $url) :
                            if (!empty($url)) :
                                $icon_class = ($network === 'website') ? 'globe' : $network;
                                ?>
                                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" class="author-social-link <?php echo esc_attr($network); ?>" aria-label="<?php echo esc_attr(ucfirst($network)); ?>">
                                    <i class="fab fa-<?php echo esc_attr($icon_class); ?>"></i>
                                </a>
                                <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
            <?php
        endif;

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

                    <div class="author-posts-grid" itemscope itemtype="https://schema.org/Blog">
                        <?php
                        // Start the Loop
                        while (have_posts()) :
                            the_post();

                            /*
                             * Include the Post-Type-specific template for the content.
                             * If you want to override this in a child theme, then include a file
                             * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                             */
                            get_template_part('template-parts/content', 'author');

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
