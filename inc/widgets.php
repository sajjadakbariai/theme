<?php
/**
 * Widgets for SEOKar WordPress Theme
 * 
 * @package SEOKar
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register widget areas and custom widgets for SEOKar theme.
 */
class SEOKar_Widgets {

    /**
     * Constructor - Initialize widgets.
     */
    public function __construct() {
        add_action('widgets_init', array($this, 'register_widget_areas'));
        add_action('widgets_init', array($this, 'register_custom_widgets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_widget_assets'));
    }

    /**
     * Register widget areas.
     */
    public function register_widget_areas() {
        // Main Sidebar
        register_sidebar(array(
            'name'          => esc_html__('Main Sidebar', 'seokar'),
            'id'            => 'main-sidebar',
            'description'   => esc_html__('Widgets in this area will be shown on all posts and pages.', 'seokar'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));

        // Footer Widget Area 1
        register_sidebar(array(
            'name'          => esc_html__('Footer Widget Area 1', 'seokar'),
            'id'            => 'footer-1',
            'description'   => esc_html__('Widgets in this area will be shown in the first footer column.', 'seokar'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ));

        // Footer Widget Area 2
        register_sidebar(array(
            'name'          => esc_html__('Footer Widget Area 2', 'seokar'),
            'id'            => 'footer-2',
            'description'   => esc_html__('Widgets in this area will be shown in the second footer column.', 'seokar'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ));

        // Footer Widget Area 3
        register_sidebar(array(
            'name'          => esc_html__('Footer Widget Area 3', 'seokar'),
            'id'            => 'footer-3',
            'description'   => esc_html__('Widgets in this area will be shown in the third footer column.', 'seokar'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ));

        // Header Banner Area
        register_sidebar(array(
            'name'          => esc_html__('Header Banner Area', 'seokar'),
            'id'            => 'header-banner',
            'description'   => esc_html__('Widgets in this area will be shown in the header banner section.', 'seokar'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }

    /**
     * Register custom widgets.
     */
    public function register_custom_widgets() {
        register_widget('SEOKar_Popular_Posts_Widget');
        register_widget('SEOKar_Social_Media_Widget');
        register_widget('SEOKar_Newsletter_Widget');
        register_widget('SEOKar_Author_Info_Widget');
    }

    /**
     * Enqueue widget-specific assets.
     */
    public function enqueue_widget_assets() {
        // Widgets CSS
        wp_enqueue_style(
            'seokar-widgets',
            get_template_directory_uri() . 'inc/admin/css/widgets.css',
            array(),
            '1.0.0'
        );

        // Widgets JS (only if needed)
        if (is_active_widget(false, false, 'seokar_popular_posts') || 
            is_active_widget(false, false, 'seokar_social_media')) {
            wp_enqueue_script(
                'seokar-widgets',
                get_template_directory_uri() . 'inc/admin/js/widgets.js',
                array('jquery'),
                '1.0.0',
                true
            );
        }
    }
}

// Initialize widgets
new SEOKar_Widgets();

/**
 * Popular Posts Widget
 */
class SEOKar_Popular_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'seokar_popular_posts',
            esc_html__('SEOKar - Popular Posts', 'seokar'),
            array(
                'description' => esc_html__('Displays popular posts with thumbnails.', 'seokar'),
            )
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_date = isset($instance['show_date']) ? $instance['show_date'] : false;

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $popular_posts = new WP_Query(array(
            'posts_per_page' => $number,
            'meta_key' => 'post_views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'ignore_sticky_posts' => true
        ));

        if ($popular_posts->have_posts()) {
            echo '<ul class="seokar-popular-posts">';
            while ($popular_posts->have_posts()) {
                $popular_posts->the_post();
                echo '<li>';
                if (has_post_thumbnail()) {
                    echo '<a href="' . esc_url(get_permalink()) . '" class="popular-post-thumbnail">';
                    the_post_thumbnail('thumbnail');
                    echo '</a>';
                }
                echo '<div class="popular-post-content">';
                echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
                if ($show_date) {
                    echo '<span class="post-date">' . get_the_date() . '</span>';
                }
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__('No popular posts found.', 'seokar') . '</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : esc_html__('Popular Posts', 'seokar');
        $number = isset($instance['number']) ? absint($instance['number']) : 5;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>"><?php esc_html_e('Number of posts to show:', 'seokar'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" />
        </p>
        <p>
            <input class="checkbox" type="checkbox"<?php checked($show_date); ?> id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" />
            <label for="<?php echo $this->get_field_id('show_date'); ?>"><?php esc_html_e('Display post date?', 'seokar'); ?></label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['number'] = (int) $new_instance['number'];
        $instance['show_date'] = isset($new_instance['show_date']) ? (bool) $new_instance['show_date'] : false;
        return $instance;
    }
}

/**
 * Social Media Widget
 */
class SEOKar_Social_Media_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'seokar_social_media',
            esc_html__('SEOKar - Social Media', 'seokar'),
            array(
                'description' => esc_html__('Displays social media links with icons.', 'seokar'),
            )
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $social_links = array(
            'facebook'  => isset($instance['facebook']) ? esc_url($instance['facebook']) : '',
            'twitter'   => isset($instance['twitter']) ? esc_url($instance['twitter']) : '',
            'instagram' => isset($instance['instagram']) ? esc_url($instance['instagram']) : '',
            'linkedin'  => isset($instance['linkedin']) ? esc_url($instance['linkedin']) : '',
            'youtube'   => isset($instance['youtube']) ? esc_url($instance['youtube']) : '',
            'pinterest' => isset($instance['pinterest']) ? esc_url($instance['pinterest']) : '',
        );

        echo '<div class="seokar-social-media">';
        foreach ($social_links as $network => $url) {
            if (!empty($url)) {
                echo '<a href="' . $url . '" class="social-icon ' . esc_attr($network) . '" target="_blank" rel="noopener noreferrer">';
                echo '<i class="fab fa-' . esc_attr($network) . '"></i>';
                echo '</a>';
            }
        }
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : esc_html__('Follow Us', 'seokar');
        $facebook = isset($instance['facebook']) ? esc_url($instance['facebook']) : '';
        $twitter = isset($instance['twitter']) ? esc_url($instance['twitter']) : '';
        $instagram = isset($instance['instagram']) ? esc_url($instance['instagram']) : '';
        $linkedin = isset($instance['linkedin']) ? esc_url($instance['linkedin']) : '';
        $youtube = isset($instance['youtube']) ? esc_url($instance['youtube']) : '';
        $pinterest = isset($instance['pinterest']) ? esc_url($instance['pinterest']) : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('facebook'); ?>"><?php esc_html_e('Facebook URL:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('facebook'); ?>" name="<?php echo $this->get_field_name('facebook'); ?>" type="text" value="<?php echo $facebook; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('twitter'); ?>"><?php esc_html_e('Twitter URL:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('twitter'); ?>" name="<?php echo $this->get_field_name('twitter'); ?>" type="text" value="<?php echo $twitter; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('instagram'); ?>"><?php esc_html_e('Instagram URL:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('instagram'); ?>" name="<?php echo $this->get_field_name('instagram'); ?>" type="text" value="<?php echo $instagram; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('linkedin'); ?>"><?php esc_html_e('LinkedIn URL:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('linkedin'); ?>" name="<?php echo $this->get_field_name('linkedin'); ?>" type="text" value="<?php echo $linkedin; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('youtube'); ?>"><?php esc_html_e('YouTube URL:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('youtube'); ?>" name="<?php echo $this->get_field_name('youtube'); ?>" type="text" value="<?php echo $youtube; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('pinterest'); ?>"><?php esc_html_e('Pinterest URL:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('pinterest'); ?>" name="<?php echo $this->get_field_name('pinterest'); ?>" type="text" value="<?php echo $pinterest; ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['facebook'] = esc_url_raw($new_instance['facebook']);
        $instance['twitter'] = esc_url_raw($new_instance['twitter']);
        $instance['instagram'] = esc_url_raw($new_instance['instagram']);
        $instance['linkedin'] = esc_url_raw($new_instance['linkedin']);
        $instance['youtube'] = esc_url_raw($new_instance['youtube']);
        $instance['pinterest'] = esc_url_raw($new_instance['pinterest']);
        return $instance;
    }
}

/**
 * Newsletter Widget
 */
class SEOKar_Newsletter_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'seokar_newsletter',
            esc_html__('SEOKar - Newsletter', 'seokar'),
            array(
                'description' => esc_html__('A newsletter subscription form.', 'seokar'),
            )
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $description = isset($instance['description']) ? $instance['description'] : '';
        $form_action = isset($instance['form_action']) ? $instance['form_action'] : '#';

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        if ($description) {
            echo '<div class="newsletter-description">' . wp_kses_post($description) . '</div>';
        }

        echo '<form action="' . esc_url($form_action) . '" method="post" class="seokar-newsletter-form">';
        echo '<input type="email" name="EMAIL" placeholder="' . esc_attr__('Your email address', 'seokar') . '" required />';
        echo '<button type="submit" class="newsletter-submit">' . esc_html__('Subscribe', 'seokar') . '</button>';
        echo '</form>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : esc_html__('Newsletter', 'seokar');
        $description = isset($instance['description']) ? esc_textarea($instance['description']) : '';
        $form_action = isset($instance['form_action']) ? esc_url($instance['form_action']) : '#';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('description'); ?>"><?php esc_html_e('Description:', 'seokar'); ?></label>
            <textarea class="widefat" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>" rows="3"><?php echo $description; ?></textarea>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('form_action'); ?>"><?php esc_html_e('Form Action URL (Mailchimp etc):', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('form_action'); ?>" name="<?php echo $this->get_field_name('form_action'); ?>" type="text" value="<?php echo $form_action; ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['description'] = wp_kses_post($new_instance['description']);
        $instance['form_action'] = esc_url_raw($new_instance['form_action']);
        return $instance;
    }
}

/**
 * Author Info Widget
 */
class SEOKar_Author_Info_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'seokar_author_info',
            esc_html__('SEOKar - Author Info', 'seokar'),
            array(
                'description' => esc_html__('Displays author information with avatar.', 'seokar'),
            )
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $author_id = isset($instance['author_id']) ? absint($instance['author_id']) : 1;
        $show_social = isset($instance['show_social']) ? $instance['show_social'] : false;

        $author = get_user_by('ID', $author_id);

        if (!$author) {
            return;
        }

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        echo '<div class="seokar-author-widget">';
        echo '<div class="author-avatar">' . get_avatar($author_id, 150) . '</div>';
        echo '<div class="author-info">';
        echo '<h4 class="author-name">' . esc_html($author->display_name) . '</h4>';
        
        if ($author->description) {
            echo '<div class="author-bio">' . wp_kses_post($author->description) . '</div>';
        }

        if ($show_social) {
            echo '<div class="author-social-links">';
            $social_fields = array(
                'facebook'  => get_the_author_meta('facebook', $author_id),
                'twitter'   => get_the_author_meta('twitter', $author_id),
                'instagram' => get_the_author_meta('instagram', $author_id),
                'linkedin'  => get_the_author_meta('linkedin', $author_id),
                'youtube'   => get_the_author_meta('youtube', $author_id),
            );

            foreach ($social_fields as $network => $url) {
                if ($url) {
                    echo '<a href="' . esc_url($url) . '" class="author-social-link ' . esc_attr($network) . '" target="_blank" rel="noopener noreferrer">';
                    echo '<i class="fab fa-' . esc_attr($network) . '"></i>';
                    echo '</a>';
                }
            }
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : esc_html__('About Author', 'seokar');
        $author_id = isset($instance['author_id']) ? absint($instance['author_id']) : 1;
        $show_social = isset($instance['show_social']) ? (bool) $instance['show_social'] : false;
        
        $users = get_users(array(
            'orderby' => 'display_name',
            'fields' => array('ID', 'display_name')
        ));
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'seokar'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('author_id'); ?>"><?php esc_html_e('Select Author:', 'seokar'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('author_id'); ?>" name="<?php echo $this->get_field_name('author_id'); ?>">
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($author_id, $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <input class="checkbox" type="checkbox"<?php checked($show_social); ?> id="<?php echo $this->get_field_id('show_social'); ?>" name="<?php echo $this->get_field_name('show_social'); ?>" />
            <label for="<?php echo $this->get_field_id('show_social'); ?>"><?php esc_html_e('Display social links?', 'seokar'); ?></label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['author_id'] = absint($new_instance['author_id']);
        $instance['show_social'] = isset($new_instance['show_social']) ? (bool) $new_instance['show_social'] : false;
        return $instance;
    }
}
/**
 * AJAX handler for newsletter subscription
 */
function seokar_newsletter_subscribe() {
    check_ajax_referer('seokar_widgets_nonce', 'security');
    
    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(__('Please enter a valid email address.', 'seokar'));
    }
    
    $email = sanitize_email($_POST['email']);
    $option_name = 'seokar_newsletter_subscribers';
    
    // Get current subscribers
    $subscribers = get_option($option_name, array());
    
    // Check if email already exists
    if (in_array($email, $subscribers)) {
        wp_send_json_error(__('This email is already subscribed.', 'seokar'));
    }
    
    // Add new subscriber
    $subscribers[] = $email;
    update_option($option_name, $subscribers);
    
    // Send confirmation email (optional)
    $subject = __('Thank you for subscribing!', 'seokar');
    $message = __('Thank you for subscribing to our newsletter.', 'seokar');
    wp_mail($email, $subject, $message);
    
    wp_send_json_success(array(
        'message' => __('Thank you for subscribing!', 'seokar')
    ));
}
add_action('wp_ajax_seokar_newsletter_subscribe', 'seokar_newsletter_subscribe');
add_action('wp_ajax_nopriv_seokar_newsletter_subscribe', 'seokar_newsletter_subscribe');

/**
 * Add custom user contact methods for social links
 */
function seokar_add_user_contact_methods($methods) {
    $methods['facebook'] = __('Facebook URL', 'seokar');
    $methods['twitter'] = __('Twitter URL', 'seokar');
    $methods['instagram'] = __('Instagram URL', 'seokar');
    $methods['linkedin'] = __('LinkedIn URL', 'seokar');
    $methods['youtube'] = __('YouTube URL', 'seokar');
    $methods['pinterest'] = __('Pinterest URL', 'seokar');
    
    return $methods;
}
add_filter('user_contactmethods', 'seokar_add_user_contact_methods');

/**
 * Track post views
 */
function seokar_track_post_views($post_id) {
    if (!is_single()) return;
    if (empty($post_id)) {
        global $post;
        $post_id = $post->ID;
    }
    seokar_set_post_views($post_id);
}
add_action('wp_head', 'seokar_track_post_views');

function seokar_set_post_views($post_id) {
    $count_key = 'post_views_count';
    $count = get_post_meta($post_id, $count_key, true);
    
    if ($count == '') {
        delete_post_meta($post_id, $count_key);
        add_post_meta($post_id, $count_key, '0');
    } else {
        $count++;
        update_post_meta($post_id, $count_key, $count);
    }
}

/**
 * Shortcode for displaying popular posts
 */
function seokar_popular_posts_shortcode($atts) {
    $atts = shortcode_atts(array(
        'number' => 5,
        'show_date' => false
    ), $atts, 'seokar_popular_posts');
    
    ob_start();
    
    $popular_posts = new WP_Query(array(
        'posts_per_page' => absint($atts['number']),
        'meta_key' => 'post_views_count',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'ignore_sticky_posts' => true
    ));
    
    if ($popular_posts->have_posts()) {
        echo '<ul class="seokar-popular-posts-shortcode">';
        while ($popular_posts->have_posts()) {
            $popular_posts->the_post();
            echo '<li>';
            if (has_post_thumbnail()) {
                echo '<a href="' . esc_url(get_permalink()) . '" class="popular-post-thumbnail">';
                the_post_thumbnail('thumbnail');
                echo '</a>';
            }
            echo '<div class="popular-post-content">';
            echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
            if ($atts['show_date']) {
                echo '<span class="post-date">' . get_the_date() . '</span>';
            }
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__('No popular posts found.', 'seokar') . '</p>';
    }
    
    return ob_get_clean();
}
add_shortcode('seokar_popular_posts', 'seokar_popular_posts_shortcode');

/**
 * Dashboard widget for newsletter subscribers
 */
function seokar_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'seokar_newsletter_widget',
        __('Newsletter Subscribers', 'seokar'),
        'seokar_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'seokar_add_dashboard_widget');

function seokar_dashboard_widget_content() {
    $subscribers = get_option('seokar_newsletter_subscribers', array());
    $count = count($subscribers);
    
    echo '<div class="seokar-dashboard-widget">';
    echo '<h3>' . sprintf(__('Total Subscribers: %d', 'seokar'), $count) . '</h3>';
    
    if ($count > 0) {
        echo '<div class="subscriber-list-container">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . __('Email', 'seokar') . '</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($subscribers as $email) {
            echo '<tr><td>' . esc_html($email) . '</td></tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        // Export button
        echo '<p><a href="' . admin_url('admin-ajax.php?action=seokar_export_subscribers&nonce=' . wp_create_nonce('export_subscribers')) . '" class="button button-primary">' . __('Export Subscribers', 'seokar') . '</a></p>';
    } else {
        echo '<p>' . __('No subscribers yet.', 'seokar') . '</p>';
    }
    
    echo '</div>';
}

/**
 * Export subscribers functionality
 */
function seokar_export_subscribers() {
    check_ajax_referer('export_subscribers', 'nonce');
    
    $subscribers = get_option('seokar_newsletter_subscribers', array());
    
    if (empty($subscribers)) {
        wp_die(__('No subscribers to export.', 'seokar'));
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=seokar-subscribers-' . date('Y-m-d') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Email'));
    
    foreach ($subscribers as $email) {
        fputcsv($output, array($email));
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_seokar_export_subscribers', 'seokar_export_subscribers');
