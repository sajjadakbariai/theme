jQuery(document).ready(function($) {
    // Popular Posts Widget - Add view count on click
    $('.seokar-popular-posts a').on('click', function() {
        const postId = $(this).closest('li').attr('id').replace('post-', '');
        if (postId) {
            $.ajax({
                url: seokarWidgets.ajaxurl,
                type: 'POST',
                data: {
                    action: 'seokar_update_post_views',
                    post_id: postId,
                    security: seokarWidgets.nonce
                }
            });
        }
    });

    // Newsletter Widget - AJAX submission
    $('.seokar-newsletter-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $email = $form.find('input[type="email"]');
        const $submit = $form.find('button[type="submit"]');
        const formAction = $form.attr('action');
        
        // Basic validation
        if (!$email.val() || !$email.val().includes('@')) {
            $email.addClass('error').focus();
            return false;
        }
        
        // If using Mailchimp or other external service
        if (formAction && formAction !== '#') {
            $form.get(0).submit();
            return true;
        }
        
        // AJAX submission
        $submit.prop('disabled', true).text(seokarWidgets.submittingText);
        
        $.ajax({
            url: seokarWidgets.ajaxurl,
            type: 'POST',
            data: {
                action: 'seokar_newsletter_subscribe',
                email: $email.val(),
                security: seokarWidgets.nonce
            },
            success: function(response) {
                if (response.success) {
                    $form.html('<div class="newsletter-success">' + response.data.message + '</div>');
                } else {
                    $form.prepend('<div class="newsletter-error">' + response.data + '</div>');
                    $submit.prop('disabled', false).text(seokarWidgets.subscribeText);
                }
            },
            error: function() {
                $form.prepend('<div class="newsletter-error">' + seokarWidgets.errorText + '</div>');
                $submit.prop('disabled', false).text(seokarWidgets.subscribeText);
            }
        });
    });
});
// Smooth scroll for widget anchors
$('.widget a[href*="#"]').on('click', function(e) {
    e.preventDefault();
    
    const target = $(this.hash);
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top - 100
        }, 800);
    }
});

// Lazy load widget images
if ('IntersectionObserver' in window) {
    const widgetImageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('.widget img[data-src]').forEach(img => {
        widgetImageObserver.observe(img);
    });
}

// Widget tabs functionality
$('.widget-tabs').each(function() {
    const $widget = $(this);
    const $tabs = $widget.find('.widget-tab');
    const $contents = $widget.find('.widget-tab-content');
    
    $tabs.on('click', function(e) {
        e.preventDefault();
        
        const tabId = $(this).data('tab');
        
        // Update active tab
        $tabs.removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding content
        $contents.removeClass('active');
        $widget.find('.widget-tab-content[data-tab="' + tabId + '"]').addClass('active');
    });
});

// Widget accordion functionality
$('.widget-accordion-title').on('click', function() {
    $(this).toggleClass('active')
        .next('.widget-accordion-content')
        .slideToggle()
        .toggleClass('active');
});
