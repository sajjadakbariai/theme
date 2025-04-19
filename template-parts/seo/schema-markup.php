<?php
/**
 * تولید داده‌های ساختاریافته (Schema Markup) برای سئو
 * 
 * @package    SeoKar
 * @subpackage Templates
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('seokar_schema_markup')) :
function seokar_schema_markup() {
    // اسکیما پایه
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => []
    ];
    
    // اسکیما سازمان
    $organization_schema = [
        '@type' => 'Organization',
        '@id' => home_url('/#organization'),
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
        'sameAs' => [
            'https://www.facebook.com/example',
            'https://twitter.com/example',
            'https://www.instagram.com/example'
        ],
        'logo' => [
            '@type' => 'ImageObject',
            '@id' => home_url('/#logo'),
            'url' => get_custom_logo() ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
            'width' => 600,
            'height' => 60
        ]
    ];
    
    // اسکیما وبسایت
    $website_schema = [
        '@type' => 'WebSite',
        '@id' => home_url('/#website'),
        'url' => home_url('/'),
        'name' => get_bloginfo('name'),
        'description' => get_bloginfo('description'),
        'publisher' => [
            '@id' => home_url('/#organization')
        ],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => home_url('/?s={search_term_string}'),
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    $schema['@graph'][] = $organization_schema;
    $schema['@graph'][] = $website_schema;
    
    // اسکیما صفحه فعلی
    if (is_singular('post')) {
        global $post;
        $author = get_userdata($post->post_author);
        
        $article_schema = [
            '@type' => 'Article',
            '@id' => get_permalink() . '#article',
            'headline' => get_the_title(),
            'description' => wp_strip_all_tags(get_the_excerpt()),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => [
                '@type' => 'Person',
                '@id' => home_url('/#/schema/person/' . $author->user_nicename),
                'name' => $author->display_name
            ],
            'publisher' => [
                '@id' => home_url('/#organization')
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink()
            ]
        ];
        
        if (has_post_thumbnail()) {
            $article_schema['image'] = [
                '@type' => 'ImageObject',
                '@id' => get_permalink() . '#primaryimage',
                'url' => get_the_post_thumbnail_url(null, 'full'),
                'width' => 1200,
                'height' => 800
            ];
        }
        
        $schema['@graph'][] = $article_schema;
    }
    elseif (is_page()) {
        $page_schema = [
            '@type' => 'WebPage',
            '@id' => get_permalink() . '#webpage',
            'headline' => get_the_title(),
            'description' => wp_strip_all_tags(get_the_excerpt()),
            'url' => get_permalink(),
            'publisher' => [
                '@id' => home_url('/#organization')
            ]
        ];
        
        $schema['@graph'][] = $page_schema;
    }
    
    // اسکیما Breadcrumb
    if (function_exists('seokar_breadcrumbs')) {
        $breadcrumbs = [];
        $position = 1;
        
        // آیتم خانه
        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('خانه', 'seokar'),
            'item' => home_url('/')
        ];
        
        // سایر آیتم‌ها بر اساس نوع صفحه
        if (is_singular()) {
            $categories = get_the_category();
            if ($categories) {
                $main_category = $categories[0];
                $breadcrumbs[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $main_category->name,
                    'item' => get_category_link($main_category->term_id)
                ];
            }
            
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title(),
                'item' => get_permalink()
            ];
        }
        elseif (is_category()) {
            $current_cat = get_category(get_query_var('cat'));
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $current_cat->name,
                'item' => get_category_link($current_cat->term_id)
            ];
        }
        
        if (!empty($breadcrumbs)) {
            $schema['@graph'][] = [
                '@type' => 'BreadcrumbList',
                '@id' => get_permalink() . '#breadcrumb',
                'itemListElement' => $breadcrumbs
            ];
        }
    }
    
    // فیلتر برای تغییر اسکیما
    $schema = apply_filters('seokar_schema_markup', $schema);
    
    // خروجی نهایی
    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
endif;
