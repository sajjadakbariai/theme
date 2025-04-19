<?php
/**
 * SEOKAR for WordPress Themes - SEO Analysis Module
 * 
 * @package    SeoKar
 * @subpackage Analysis
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Analysis implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Analysis tests
     *
     * @var array
     */
    private $tests = [];

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->setup_tests();
        $this->setup_hooks();
    }

    /**
     * Setup analysis tests
     */
    private function setup_tests() {
        $this->tests = [
            'title_length' => [
                'title' => __('Title Length', 'seokar'),
                'description' => __('SEO titles should be between 40-60 characters.', 'seokar'),
                'weight' => 10,
                'test' => 'test_title_length'
            ],
            'meta_description_length' => [
                'title' => __('Meta Description Length', 'seokar'),
                'description' => __('Meta descriptions should be between 120-160 characters.', 'seokar'),
                'weight' => 8,
                'test' => 'test_meta_description_length'
            ],
            'keyword_in_title' => [
                'title' => __('Keyword in Title', 'seokar'),
                'description' => __('The focus keyword should appear in the SEO title.', 'seokar'),
                'weight' => 7,
                'test' => 'test_keyword_in_title'
            ],
            'keyword_in_description' => [
                'title' => __('Keyword in Description', 'seokar'),
                'description' => __('The focus keyword should appear in the meta description.', 'seokar'),
                'weight' => 6,
                'test' => 'test_keyword_in_description'
            ],
            'keyword_in_content' => [
                'title' => __('Keyword in Content', 'seokar'),
                'description' => __('The focus keyword should appear in the content.', 'seokar'),
                'weight' => 5,
                'test' => 'test_keyword_in_content'
            ],
            'keyword_density' => [
                'title' => __('Keyword Density', 'seokar'),
                'description' => __('The focus keyword should make up 1-2% of the content.', 'seokar'),
                'weight' => 4,
                'test' => 'test_keyword_density'
            ],
            'heading_structure' => [
                'title' => __('Heading Structure', 'seokar'),
                'description' => __('Proper heading hierarchy (H1, H2, H3) improves readability.', 'seokar'),
                'weight' => 5,
                'test' => 'test_heading_structure'
            ],
            'image_alt_tags' => [
                'title' => __('Image Alt Tags', 'seokar'),
                'description' => __('Images should have alt attributes with keywords.', 'seokar'),
                'weight' => 4,
                'test' => 'test_image_alt_tags'
            ],
            'internal_links' => [
                'title' => __('Internal Links', 'seokar'),
                'description' => __('Content should contain internal links to related pages.', 'seokar'),
                'weight' => 3,
                'test' => 'test_internal_links'
            ],
            'external_links' => [
                'title' => __('External Links', 'seokar'),
                'description' => __('Content should contain relevant outbound links.', 'seokar'),
                'weight' => 2,
                'test' => 'test_external_links'
            ],
            'content_length' => [
                'title' => __('Content Length', 'seokar'),
                'description' => __('Longer content tends to rank better (minimum 300 words).', 'seokar'),
                'weight' => 6,
                'test' => 'test_content_length'
            ],
            'readability' => [
                'title' => __('Readability', 'seokar'),
                'description' => __('Content should be easy to read and understand.', 'seokar'),
                'weight' => 5,
                'test' => 'test_readability'
            ]
        ];
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('add_meta_boxes', [$this, 'add_analysis_meta_box']);
        add_action('wp_ajax_seokar_analyze_content', [$this, 'ajax_analyze_content']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Add analysis meta box
     */
    public function add_analysis_meta_box() {
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seokar_analysis_meta_box',
                __('SEO Analysis', 'seokar'),
                [$this, 'render_analysis_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render analysis meta box
     *
     * @param WP_Post $post
     */
    public function render_analysis_meta_box($post) {
        ?>
        <div class="seokar-analysis-container">
            <div class="seokar-analysis-header">
                <h3><?php _e('Content Analysis', 'seokar'); ?></h3>
                <button type="button" class="button button-primary seokar-run-analysis" 
                        data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php _e('Run Analysis', 'seokar'); ?>
                </button>
            </div>
            
            <div class="seokar-analysis-results">
                <div class="seokar-score-container">
                    <div class="seokar-score-circle" data-score="0">
                        <span>0</span>
                    </div>
                    <h4><?php _e('SEO Score', 'seokar'); ?></h4>
                </div>
                
                <div class="seokar-analysis-details">
                    <ul class="seokar-tests-list">
                        <?php foreach ($this->tests as $test_id => $test) : ?>
                            <li class="seokar-test-item" data-test="<?php echo esc_attr($test_id); ?>">
                                <span class="seokar-test-status"></span>
                                <strong><?php echo esc_html($test['title']); ?></strong>
                                <span class="seokar-test-description"><?php echo esc_html($test['description']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="seokar-keyword-section">
                <label for="seokar_focus_keyword"><?php _e('Focus Keyword:', 'seokar'); ?></label>
                <input type="text" id="seokar_focus_keyword" name="seokar_focus_keyword" 
                       value="<?php echo esc_attr(get_post_meta($post->ID, '_seokar_focus_keyword', true)); ?>" />
                <p class="description"><?php _e('Enter your focus keyword for this content', 'seokar'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Analyze content via AJAX
     */
    public function ajax_analyze_content() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seokar_analysis_nonce')) {
            wp_send_json_error(__('Security check failed', 'seokar'));
        }

        if (!isset($_POST['post_id'])) {
            wp_send_json_error(__('Invalid post ID', 'seokar'));
        }

        $post_id = (int) $_POST['post_id'];
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        
        // Save focus keyword
        if (!empty($keyword)) {
            update_post_meta($post_id, '_seokar_focus_keyword', $keyword);
        } else {
            delete_post_meta($post_id, '_seokar_focus_keyword');
        }

        $results = $this->analyze_post($post_id, $keyword);
        $score = $this->calculate_score($results);

        wp_send_json_success([
            'results' => $results,
            'score' => $score,
            'score_display' => $this->get_score_display($score)
        ]);
    }

    /**
     * Analyze post content
     *
     * @param int $post_id
     * @param string $keyword
     * @return array
     */
    public function analyze_post($post_id, $keyword = '') {
        $post = get_post($post_id);
        $results = [];
        $content = strip_tags($post->post_content);
        
        foreach ($this->tests as $test_id => $test) {
            if (method_exists($this, $test['test'])) {
                $results[$test_id] = call_user_func([$this, $test['test']], $post, $keyword, $content);
            }
        }

        return $results;
    }

    /**
     * Calculate overall score
     *
     * @param array $results
     * @return int
     */
    private function calculate_score($results) {
        $total_weight = 0;
        $total_score = 0;
        
        foreach ($this->tests as $test_id => $test) {
            if (isset($results[$test_id]['passed'])) {
                $total_weight += $test['weight'];
                $total_score += $results[$test_id]['passed'] ? $test['weight'] : 0;
            }
        }

        return $total_weight > 0 ? round(($total_score / $total_weight) * 100) : 0;
    }

    /**
     * Get score display class
     *
     * @param int $score
     * @return string
     */
    private function get_score_display($score) {
        if ($score >= 80) {
            return 'excellent';
        } elseif ($score >= 60) {
            return 'good';
        } elseif ($score >= 40) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Test title length
     *
     * @param WP_Post $post
     * @param string $keyword
     * @return array
     */
    private function test_title_length($post) {
        $title = $this->seokar->get_module('core')->generate_title($post->ID);
        $length = mb_strlen($title);
        
        return [
            'passed' => $length >= 40 && $length <= 60,
            'value' => sprintf(__('%d characters', 'seokar'), $length)
        ];
    }

    /**
     * Test meta description length
     *
     * @param WP_Post $post
     * @param string $keyword
     * @return array
     */
    private function test_meta_description_length($post) {
        $description = $this->seokar->get_module('core')->generate_description($post->ID);
        $length = mb_strlen($description);
        
        return [
            'passed' => $length >= 120 && $length <= 160,
            'value' => sprintf(__('%d characters', 'seokar'), $length)
        ];
    }

    /**
     * Test keyword in title
     *
     * @param WP_Post $post
     * @param string $keyword
     * @return array
     */
    private function test_keyword_in_title($post, $keyword) {
        if (empty($keyword)) {
            return [
                'passed' => false,
                'value' => __('No keyword set', 'seokar')
            ];
        }

        $title = $this->seokar->get_module('core')->generate_title($post->ID);
        $contains = stripos($title, $keyword) !== false;
        
        return [
            'passed' => $contains,
            'value' => $contains ? __('Found', 'seokar') : __('Not found', 'seokar')
        ];
    }

    /**
     * Test keyword in description
     *
     * @param WP_Post $post
     * @param string $keyword
     * @return array
     */
    private function test_keyword_in_description($post, $keyword) {
        if (empty($keyword)) {
            return [
                'passed' => false,
                'value' => __('No keyword set', 'seokar')
            ];
        }

        $description = $this->seokar->get_module('core')->generate_description($post->ID);
        $contains = stripos($description, $keyword) !== false;
        
        return [
            'passed' => $contains,
            'value' => $contains ? __('Found', 'seokar') : __('Not found', 'seokar')
        ];
    }

    /**
     * Test keyword in content
     *
     * @param WP_Post $post
     * @param string $keyword
     * @param string $content
     * @return array
     */
    private function test_keyword_in_content($post, $keyword, $content) {
        if (empty($keyword)) {
            return [
                'passed' => false,
                'value' => __('No keyword set', 'seokar')
            ];
        }

        $contains = stripos($content, $keyword) !== false;
        
        return [
            'passed' => $contains,
            'value' => $contains ? __('Found', 'seokar') : __('Not found', 'seokar')
        ];
    }

    /**
     * Test keyword density
     *
     * @param WP_Post $post
     * @param string $keyword
     * @param string $content
     * @return array
     */
    private function test_keyword_density($post, $keyword, $content) {
        if (empty($keyword)) {
            return [
                'passed' => false,
                'value' => __('No keyword set', 'seokar')
            ];
        }

        $word_count = str_word_count($content);
        if ($word_count === 0) {
            return [
                'passed' => false,
                'value' => __('No content', 'seokar')
            ];
        }

        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $density = ($keyword_count / $word_count) * 100;
        
        return [
            'passed' => $density >= 1 && $density <= 2,
            'value' => sprintf(__('%.2f%% (appears %d times)', 'seokar'), $density, $keyword_count)
        ];
    }

    /**
     * Test heading structure
     *
     * @param WP_Post $post
     * @return array
     */
    private function test_heading_structure($post) {
        $content = $post->post_content;
        $headings = [
            'h1' => 0,
            'h2' => 0,
            'h3' => 0,
            'h4' => 0,
            'h5' => 0,
            'h6' => 0
        ];

        // Count headings
        foreach ($headings as $tag => $count) {
            preg_match_all('/<' . $tag . '[^>]*>/i', $content, $matches);
            $headings[$tag] = count($matches[0]);
        }

        // Check for multiple H1s
        $multiple_h1 = $headings['h1'] > 1;
        $has_h2 = $headings['h2'] > 0;
        
        return [
            'passed' => !$multiple_h1 && $has_h2,
            'value' => sprintf(
                __('H1: %d, H2: %d, H3: %d', 'seokar'),
                $headings['h1'],
                $headings['h2'],
                $headings['h3']
            )
        ];
    }

    /**
     * Test image alt tags
     *
     * @param WP_Post $post
     * @param string $keyword
     * @return array
     */
    private function test_image_alt_tags($post, $keyword) {
        $content = $post->post_content;
        preg_match_all('/<img[^>]+>/i', $content, $img_tags);
        
        $total_images = 0;
        $images_with_alt = 0;
        $images_with_keyword = 0;

        foreach ($img_tags[0] as $img_tag) {
            preg_match('/alt="([^"]*)"/i', $img_tag, $alt);
            $alt = isset($alt[1]) ? $alt[1] : '';
            
            if (!empty($img_tag)) {
                $total_images++;
                
                if (!empty($alt)) {
                    $images_with_alt++;
                    
                    if (!empty($keyword) && stripos($alt, $keyword) !== false) {
                        $images_with_keyword++;
                    }
                }
            }
        }

        if ($total_images === 0) {
            return [
                'passed' => true,
                'value' => __('No images found', 'seokar')
            ];
        }
        
        $passed = ($images_with_alt / $total_images) >= 0.8;
        
        return [
            'passed' => $passed,
            'value' => sprintf(
                __('%d of %d images have alt text', 'seokar'),
                $images_with_alt,
                $total_images
            )
        ];
    }

    /**
     * Test internal links
     *
     * @param WP_Post $post
     * @return array
     */
    private function test_internal_links($post) {
        $content = $post->post_content;
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        $total_links = 0;
        $internal_links = 0;
        $home_url = home_url('/');

        foreach ($matches[1] as $url) {
            if (!empty($url)) {
                $total_links++;
                
                if (strpos($url, $home_url) === 0 || strpos($url, '/') === 0) {
                    $internal_links++;
                }
            }
        }

        if ($total_links === 0) {
            return [
                'passed' => false,
                'value' => __('No links found', 'seokar')
            ];
        }
        
        $ratio = $internal_links / $total_links;
        $passed = $ratio >= 0.3;
        
        return [
            'passed' => $passed,
            'value' => sprintf(
                __('%d of %d links are internal (%.1f%%)', 'seokar'),
                $internal_links,
                $total_links,
                $ratio * 100
            )
        ];
    }

    /**
     * Test external links
     *
     * @param WP_Post $post
     * @return array
     */
    private function test_external_links($post) {
        $content = $post->post_content;
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        $total_links = 0;
        $external_links = 0;
        $home_url = home_url('/');

        foreach ($matches[1] as $url) {
            if (!empty($url)) {
                $total_links++;
                
                if (strpos($url, 'http') === 0 && strpos($url, $home_url) !== 0) {
                    $external_links++;
                }
            }
        }

        if ($total_links === 0) {
            return [
                'passed' => false,
                'value' => __('No links found', 'seokar')
            ];
        }
        
        $passed = $external_links > 0;
        
        return [
            'passed' => $passed,
            'value' => sprintf(
                __('%d external links found', 'seokar'),
                $external_links
            )
        ];
    }

    /**
     * Test content length
     *
     * @param WP_Post $post
     * @param string $content
     * @return array
     */
    private function test_content_length($post, $keyword, $content) {
        $word_count = str_word_count($content);
        $passed = $word_count >= 300;
        
        return [
            'passed' => $passed,
            'value' => sprintf(
                __('%d words', 'seokar'),
                $word_count
            )
        ];
    }

    /**
     * Test readability
     *
     * @param WP_Post $post
     * @param string $content
     * @return array
     */
    private function test_readability($post, $keyword, $content) {
        // Simple readability check based on average words per sentence
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $word_count = str_word_count($content);
        
        if ($sentence_count === 0) {
            return [
                'passed' => false,
                'value' => __('Not enough content', 'seokar')
            ];
        }

        $avg_words = $word_count / $sentence_count;
        $passed = $avg_words <= 20;
        
        return [
            'passed' => $passed,
            'value' => sprintf(
                __('Average %.1f words per sentence', 'seokar'),
                $avg_words
            )
        ];
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        wp_enqueue_style(
            'seokar-analysis',
            SEOKAR_URL . '/admin/assets/css/analysis.css',
            [],
            SEOKAR_VERSION
        );

        wp_enqueue_script(
            'seokar-analysis',
            SEOKAR_URL . '/admin/assets/js/analysis.js',
            ['jquery'],
            SEOKAR_VERSION,
            true
        );

        wp_localize_script(
            'seokar-analysis',
            'seokar_analysis',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seokar_analysis_nonce'),
                'strings' => [
                    'analyzing' => __('Analyzing...', 'seokar'),
                    'error' => __('Error occurred', 'seokar')
                ]
            ]
        );
    }
}
