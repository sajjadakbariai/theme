<?php
/**
 * The template for displaying comments
 *
 * @package SeoKar
 * @version 2.0.0
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area" itemscope itemtype="https://schema.org/Comment">

    <?php if (have_comments()) : ?>
        <h2 class="comments-title" itemprop="headline">
            <?php
            $seokar_comment_count = get_comments_number();
            if ('1' === $seokar_comment_count) {
                printf(
                    /* translators: 1: title. */
                    esc_html__('One thought on &ldquo;%1$s&rdquo;', 'seokar'),
                    '<span>' . wp_kses_post(get_the_title()) . '</span>'
                );
            } else {
                printf( 
                    /* translators: 1: comment count number, 2: title. */
                    esc_html(_nx('%1$s thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', $seokar_comment_count, 'comments title', 'seokar')),
                    number_format_i18n($seokar_comment_count), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    '<span>' . wp_kses_post(get_the_title()) . '</span>'
                );
            }
            ?>
        </h2><!-- .comments-title -->

        <?php the_comments_navigation(); ?>

        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style'      => 'ol',
                'short_ping' => true,
                'avatar_size' => 60,
                'walker'     => new SeoKar_Walker_Comment(),
                'callback'  => 'seokar_comment_template',
            ));
            ?>
        </ol><!-- .comment-list -->

        <?php
        the_comments_navigation();

        // If comments are closed and there are comments, let's leave a little note, shall we?
        if (!comments_open()) :
            ?>
            <p class="no-comments"><?php esc_html_e('Comments are closed.', 'seokar'); ?></p>
            <?php
        endif;

    endif; // Check for have_comments().

    // Custom comment form
    $commenter = wp_get_current_commenter();
    $req = get_option('require_name_email');
    $aria_req = ($req ? " aria-required='true'" : '');
    $required_text = sprintf(' ' . __('Required fields are marked %s', 'seokar'), '<span class="required">*</span>');
    
    $args = array(
        'fields' => apply_filters('comment_form_default_fields', array(
            'author' => '<div class="comment-form-author form-group">' .
                        '<label for="author">' . __('Name', 'seokar') . ($req ? ' <span class="required">*</span>' : '') . '</label>' .
                        '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' class="form-control" />' .
                        '</div>',
            'email'  => '<div class="comment-form-email form-group">' .
                        '<label for="email">' . __('Email', 'seokar') . ($req ? ' <span class="required">*</span>' : '') . '</label>' .
                        '<input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' class="form-control" />' .
                        '</div>',
            'url'    => '<div class="comment-form-url form-group">' .
                        '<label for="url">' . __('Website', 'seokar') . '</label>' .
                        '<input id="url" name="url" type="url" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" class="form-control" />' .
                        '</div>',
        )),
        'comment_field' => '<div class="comment-form-comment form-group">' .
                          '<label for="comment">' . _x('Comment', 'noun', 'seokar') . '</label>' .
                          '<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" class="form-control"></textarea>' .
                          '</div>',
        'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
        'title_reply_after' => '</h3>',
        'title_reply' => __('Leave a Reply', 'seokar'),
        'title_reply_to' => __('Leave a Reply to %s', 'seokar'),
        'cancel_reply_link' => __('Cancel reply', 'seokar'),
        'label_submit' => __('Post Comment', 'seokar'),
        'class_submit' => 'submit btn btn-primary',
        'comment_notes_before' => '<p class="comment-notes">' .
            __('Your email address will not be published.', 'seokar') . ($req ? $required_text : '') .
            '</p>',
        'comment_notes_after' => '',
        'format' => 'html5',
    );

    comment_form($args);
    ?>

</div><!-- #comments -->
