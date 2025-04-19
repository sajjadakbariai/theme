<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @package SeoKar
 * @version 2.0.0
 */

?>

<section class="no-results not-found text-center py-5">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('متاسفیم، چیزی برای نمایش وجود ندارد.', 'seokar'); ?></h1>
    </header><!-- .page-header -->

    <div class="page-content mt-4">
        <p><?php esc_html_e('به نظر می‌رسد چیزی که دنبالش بودید پیدا نشد. شاید جستجو کمک کند.', 'seokar'); ?></p>

        <?php get_search_form(); ?>
    </div><!-- .page-content -->
</section><!-- .no-results -->
