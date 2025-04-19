<?php
/**
 * Template part for displaying page content in page.php
 *
 * @package SeoKar
 * @version 2.0.0
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('mb-5'); ?>>
    <header class="entry-header mb-4">
        <?php the_title('<h1 class="entry-title text-center mb-4">', '</h1>'); ?>
    </header><!-- .entry-header -->

    <div class="entry-content">
        <?php
        the_content();

        wp_link_pages(array(
            'before' => '<div class="page-links">' . esc_html__('صفحات:', 'seokar'),
            'after'  => '</div>',
        ));
        ?>
    </div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
