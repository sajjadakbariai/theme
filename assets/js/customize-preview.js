jQuery(document).ready(function($) {
    // ارتباط با Customizer وردپرس
    wp.customize.bind('ready', function() {
        // پیگیری تغییرات و اعمال آنها در پیش‌نمایش
        $.each(seokarSettings, function(key, value) {
            wp.customize(key, function(setting) {
                setting.bind(function(newValue) {
                    // اعمال تغییرات در پیش‌نمایش
                    applySettingChange(key, newValue);
                });
            });
        });
    });
    
    // اعمال تغییرات در صفحه پیش‌نمایش
    function applySettingChange(key, value) {
        switch (key) {
            case 'primary_color':
            case 'secondary_color':
                updateColorVariables();
                break;
                
            case 'site_logo':
                updateSiteLogo(value);
                break;
                
            case 'custom_css':
                updateCustomCSS(value);
                break;
                
            // سایر موارد...
        }
    }
    
    // به‌روزرسانی متغیرهای رنگ
    function updateColorVariables() {
        var primaryColor = wp.customize('primary_color')();
        var secondaryColor = wp.customize('secondary_color')();
        
        document.documentElement.style.setProperty('--primary-color', primaryColor);
        document.documentElement.style.setProperty('--secondary-color', secondaryColor);
    }
    
    // به‌روزرسانی لوگو
    function updateSiteLogo(url) {
        var logo = document.querySelector('.site-logo img');
        if (logo) {
            logo.src = url;
        } else {
            var logoContainer = document.querySelector('.site-logo');
            if (logoContainer && url) {
                logoContainer.innerHTML = '<img src="' + url + '" alt="' + wp.customize('site_title')() + '">';
            }
        }
    }
    
    // به‌روزرسانی CSS سفارشی
    function updateCustomCSS(css) {
        var styleElement = document.getElementById('seokar-custom-css');
        if (styleElement) {
            styleElement.innerHTML = css;
        } else {
            var head = document.head || document.getElementsByTagName('head')[0];
            styleElement = document.createElement('style');
            styleElement.id = 'seokar-custom-css';
            styleElement.type = 'text/css';
            styleElement.appendChild(document.createTextNode(css));
            head.appendChild(styleElement);
        }
    }
});
