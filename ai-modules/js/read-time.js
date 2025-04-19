jQuery(document).ready(function($) {
    $('.art-read-time-container').each(function() {
        const container = $(this);
        const minutes = parseInt(container.data('readtime'));
        const progressFill = container.find('.art-progress-fill');
        
        // تنظیم عرض نوار پیشرفت بر اساس زمان مطالعه
        const progressWidth = Math.min((minutes / 10) * 100, 100);
        
        // انیمیشن نوار پیشرفت
        setTimeout(() => {
            progressFill.css('width', progressWidth + '%');
        }, 300);
        
        // اثر پارالاکس
        container.on('mousemove', function(e) {
            const x = e.pageX - $(this).offset().left;
            const y = e.pageY - $(this).offset().top;
            const centerX = $(this).width() / 2;
            const centerY = $(this).height() / 2;
            
            const rotateY = (x - centerX) / 50;
            const rotateX = (centerY - y) / 50;
            
            $(this).find('.art-read-time').css({
                'transform': `translateY(-3px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`
            });
        });
        
        container.on('mouseleave', function() {
            $(this).find('.art-read-time').css({
                'transform': 'translateY(-3px) rotateX(5deg)'
            });
        });
    });
});
