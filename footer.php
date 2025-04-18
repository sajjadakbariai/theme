<?php
/**
 * Footer template for SeoKar Theme
 * 
 * @package SeoKar
 * @version 2.0.0
 */

?>
        </main><!-- #main -->
    </div><!-- #content -->

    <footer id="colophon" class="site-footer" role="contentinfo" itemscope itemtype="https://schema.org/WPFooter">
        <div class="footer-top">
            <div class="container">
                <div class="footer-widgets">
                    <?php if (is_active_sidebar('footer-1')) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar('footer-1'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (is_active_sidebar('footer-2')) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar('footer-2'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (is_active_sidebar('footer-3')) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar('footer-3'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (is_active_sidebar('footer-4')) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar('footer-4'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <div class="site-info">
                    <div class="copyright">
                        &copy; <?php echo date('Y'); ?> <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>.
                        <?php esc_html_e('All rights reserved.', 'seokar'); ?>
                    </div>

                    <div class="footer-links">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'footer',
                            'depth' => 1,
                            'container' => false,
                            'menu_class' => 'footer-menu',
                            'fallback_cb' => false
                        ));
                        ?>
                    </div>

                    <div class="social-links">
                        <?php
                        $social_profiles = array(
                            'facebook' => __('Facebook', 'seokar'),
                            'twitter' => __('Twitter', 'seokar'),
                            'instagram' => __('Instagram', 'seokar'),
                            'linkedin' => __('LinkedIn', 'seokar'),
                            'youtube' => __('YouTube', 'seokar')
                        );

                        foreach ($social_profiles as $key => $label) {
                            $url = get_theme_mod('seokar_' . $key . '_url');
                            if ($url) {
                                echo '<a href="' . esc_url($url) . '" class="social-link ' . esc_attr($key) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($label) . '">';
                                echo '<span class="screen-reader-text">' . esc_html($label) . '</span>';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div><!-- .site-info -->

                <?php if (has_nav_menu('footer-legal')) : ?>
                    <nav class="footer-legal-navigation" aria-label="<?php esc_attr_e('Legal Navigation', 'seokar'); ?>">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'footer-legal',
                            'depth' => 1,
                            'container' => false,
                            'menu_class' => 'legal-menu',
                            'fallback_cb' => false
                        ));
                        ?>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </footer><!-- #colophon -->

    <?php // Back to top button ?>
    <a href="#page" class="back-to-top" aria-label="<?php esc_attr_e('Back to top', 'seokar'); ?>" hidden>
        <span class="screen-reader-text"><?php esc_html_e('Back to top', 'seokar'); ?></span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 15l-6-6-6 6"/>
        </svg>
    </a>

    <?php // Mobile menu overlay ?>
    <div class="mobile-menu-overlay" aria-hidden="true"></div>

    <?php // Theme modals ?>
    <div id="search-modal" class="modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Search Modal', 'seokar'); ?>" hidden>
        <div class="modal-content">
            <button class="modal-close" aria-label="<?php esc_attr_e('Close modal', 'seokar'); ?>">
                <span class="screen-reader-text"><?php esc_html_e('Close', 'seokar'); ?></span>
                <span aria-hidden="true">&times;</span>
            </button>
            <?php get_search_form(); ?>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
