/**
 * اسکریپت مدیریت تاکسونومی‌های سفارشی
 */
jQuery(document).ready(function($) {
    // مدیریت آپلود تصویر
    $('.seokar-upload-image').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var custom_uploader = wp.media({
            title: 'انتخاب تصویر',
            library: {
                type: 'image'
            },
            button: {
                text: 'استفاده از این تصویر'
            },
            multiple: false
        });

        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            button.siblings('.image-id').val(attachment.id);
            button.siblings('.image-url').val(attachment.url);
            button.siblings('.image-preview').attr('src', attachment.url).show();
        });

        custom_uploader.open();
    });

    // حذف تصویر
    $('.seokar-remove-image').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        button.siblings('.image-id').val('');
        button.siblings('.image-url').val('');
        button.siblings('.image-preview').attr('src', '').hide();
    });

    // اعتبارسنجی فیلدها
    $('.seokar-taxonomy-meta-form').on('submit', function(e) {
        var isValid = true;
        
        $(this).find('[data-required="true"]').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                $(this).addClass('error');
                $(this).after('<span class="error-message">این فیلد الزامی است</span>');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('لطفاً تمام فیلدهای الزامی را پر کنید');
        }
    });
});
