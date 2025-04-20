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
jQuery(document).ready(function($) {
    // مدیریت آپلود رسانه
    $('.seokar-media-upload').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var mimeTypes = button.data('mime-types') || ['image'];
        var uploaderTitle = button.data('uploader-title') || 'Select Media';
        var uploaderButtonText = button.data('uploader-button-text') || 'Use This';
        var container = button.closest('.seokar-media-uploader');
        var input = container.find('.seokar-media-url');
        var preview = container.find('.seokar-media-preview');
        
        var frame = wp.media({
            title: uploaderTitle,
            button: {
                text: uploaderButtonText
            },
            library: {
                type: mimeTypes
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.url).trigger('change');
            
            if (attachment.type === 'image') {
                preview.html('<img src="' + attachment.url + '" style="max-height: 80px; margin-top: 10px;">');
                container.find('.seokar-media-remove').show();
            }
        });
        
        frame.open();
    });
    
    // حذف رسانه
    $('.seokar-media-remove').on('click', function(e) {
        e.preventDefault();
        
        var container = $(this).closest('.seokar-media-uploader');
        container.find('.seokar-media-url').val('').trigger('change');
        container.find('.seokar-media-preview').empty();
        $(this).hide();
    });
    
    // انتخاب رنگ
    $('.seokar-color-picker').wpColorPicker();
    
    // ویرایشگر کد
    $('.seokar-code-editor').each(function() {
        var language = $(this).data('language') || 'html';
        var editorSettings = {
            codemirror: {
                mode: language,
                lineNumbers: true,
                indentUnit: 4,
                tabSize: 4,
                theme: 'default'
            }
        };
        
        wp.codeEditor.initialize($(this), editorSettings);
    });
    
    // مدیریت فیلدهای شرطی
    function checkFieldConditions() {
        $('[data-condition-field]').each(function() {
            var field = $(this);
            var conditionField = field.data('condition-field');
            var conditionValue = field.data('condition-value');
            var conditionOperator = field.data('condition-operator') || '==';
            var dependentField = $('[name="' + conditionField + '"]');
            
            function evaluateCondition() {
                var dependentValue;
                
                if (dependentField.attr('type') === 'checkbox') {
                    dependentValue = dependentField.is(':checked') ? '1' : '0';
                } else {
                    dependentValue = dependentField.val();
                }
                
                var isVisible = false;
                
                if (conditionOperator === '==') {
                    isVisible = (dependentValue == conditionValue);
                } else if (conditionOperator === '!=') {
                    isVisible = (dependentValue != conditionValue);
                }
                
                if (isVisible) {
                    field.closest('tr').show();
                } else {
                    field.closest('tr').hide();
                }
            }
            
            // بررسی اولیه
            evaluateCondition();
            
            // بررسی هنگام تغییر مقدار
            dependentField.on('change', evaluateCondition);
        });
    }
    
    // اعتبارسنجی فرم
    $('form.seokar-settings-form').on('submit', function(e) {
        var isValid = true;
        var errorMessages = [];
        
        $('[data-validate]').each(function() {
            var field = $(this);
            var value = field.val();
            var validationType = field.data('validate');
            
            if (validationType === 'email' && value && !isValidEmail(value)) {
                isValid = false;
                errorMessages.push('آدرس ایمیل وارد شده معتبر نیست.');
            }
            
            if (validationType === 'url' && value && !isValidUrl(value)) {
                isValid = false;
                errorMessages.push('آدرس URL وارد شده معتبر نیست.');
            }
            
            if (field.data('max-length') && value.length > field.data('max-length')) {
                isValid = false;
                errorMessages.push('حداکثر طول مجاز برای فیلد ' + field.attr('name') + ' رعایت نشده است.');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('خطا در فرم:\n\n' + errorMessages.join('\n'));
        }
    });
    
    // بازنشانی بخش
    $('.seokar-reset-section').on('click', function() {
        if (confirm('آیا از بازنشانی تنظیمات این بخش اطمینان دارید؟ تمام تغییرات ذخیره نشده از بین خواهند رفت.')) {
            var form = $(this).closest('form');
            var section = form.find('h2').first().text();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'seokar_reset_section',
                    section: section,
                    nonce: seokarSettingsData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('خطا در بازنشانی بخش: ' + response.data);
                    }
                }
            });
        }
    });
    
    // توابع کمکی
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // بررسی اولیه شرایط فیلدها
    checkFieldConditions();
}); 
