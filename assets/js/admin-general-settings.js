jQuery(document).ready(function($) {
    // رنگ‌پیکر
    $('.seokar-color-picker').wpColorPicker();
    
    // آپلودگر مدیا
    $('.seokar-media-upload').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var mimeTypes = button.data('mime-types') || ['image/*'];
        var frame = wp.media({
            title: button.data('uploader-title'),
            library: { type: mimeTypes },
            button: { text: button.data('uploader-button-text') },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            button.siblings('.seokar-media-url').val(attachment.url);
            button.parent().find('.seokar-media-preview').html(
                '<img src="' + attachment.url + '" style="max-height: 80px; margin-top: 10px;">'
            );
        });
        
        frame.open();
    });
    
    // حذف تصویر
    $('.seokar-media-remove').on('click', function() {
        $(this).siblings('.seokar-media-url').val('');
        $(this).parent().find('.seokar-media-preview').html('');
    });
    
    // ویرایشگر کد
    $('.seokar-code-editor').each(function() {
        var editor = wp.codeEditor.initialize($(this), {
            codemirror: {
                mode: $(this).data('language'),
                lineNumbers: true,
                indentUnit: 4,
                tabSize: 4,
                theme: 'default'
            }
        });
    });
});
