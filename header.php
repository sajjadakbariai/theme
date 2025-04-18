<?php
/**
 * Header template for SeoKar Theme
 * 
 * @package SeoKar
 * @version 2.0.0
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
    
    <?php // Preload critical resources ?>
    <link rel="preload" href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/fonts/vazir.woff2" as="font" type="font/woff2" crossorigin>
    
    <?php // DNS prefetch for external domains ?>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    
    <?php // Theme color meta tags ?>
    <meta name="theme-color" content="#3a7bd5">
    <meta name="msapplication-navbutton-color" content="#3a7bd5">
    <meta name="apple-mobile-web-app-status-bar-style" content="#3a7bd5">
    
    <?php // Structured data ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php bloginfo('name'); ?>",
        "url": "<?php echo esc_url(home_url()); ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo esc_url(home_url('/?s={search_term_string}')); ?>",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?> itemscope itemtype="https://schema.org/WebPage">
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('Skip to content', 'seokar'); ?></a>

<header id="masthead" class="site-header" role="banner" itemscope itemtype="https://schema.org/WPHeader">
    <div class="container">
        <div class="site-branding" itemscope itemtype="https://schema.org/Organization">
            <?php
            the_custom_logo();
            if (is_front_page() && is_home()) :
                ?>
                <h1 class="site-title" itemprop="name"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home" itemprop="url"><?php bloginfo('name'); ?></a></h1>
                <?php
            else :
                ?>
                <p class="site-title" itemprop="name"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home" itemprop="url"><?php bloginfo('name'); ?></a></p>
                <?php
            endif;
            $seokar_description = get_bloginfo('description', 'display');
            if ($seokar_description || is_customize_preview()) :
                ?>
                <p class="site-description" itemprop="description"><?php echo esc_html($seokar_description); ?></p>
            <?php endif; ?>
        </div>

        <nav id="site-navigation" class="main-navigation" role="navigation" itemscope itemtype="https://schema.org/SiteNavigationElement" aria-label="<?php esc_attr_e('Main Navigation', 'seokar'); ?>">
            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                <span class="screen-reader-text"><?php esc_html_e('Primary Menu', 'seokar'); ?></span>
                <span class="hamburger"></span>
            </button>
            
            <?php
            wp_nav_menu(array(
                'theme_location'  => 'primary',
                'menu_id'         => 'primary-menu',
                'container_class' => 'menu-container',
                'depth'           => 3,
                'walker'          => new SeoKar_Walker_Nav_Menu(),
                'fallback_cb'     => 'SeoKar_Walker_Nav_Menu::fallback',
                'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            ));
            ?>
            
            <?php if (class_exists('WooCommerce')) : ?>
                <div class="header-cart">
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cart-contents" title="<?php esc_attr_e('View your shopping cart', 'seokar'); ?>">
                        <span class="cart-icon"></span>
                        <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="header-search">
                <?php get_search_form(); ?>
            </div>
        </nav>
    </div>
</header>

<div id="content" class="site-content">
    <?php
    // Display breadcrumbs if enabled
    if (function_exists('seokar_breadcrumbs') && !is_front_page()) {
        seokar_breadcrumbs();
    }
    ?>
    
    <main id="main" class="site-main" role="main">
