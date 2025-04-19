<?php
/**
 * Seokar WebP Support - Advanced WebP Conversion and Optimization for WordPress
 * 
 * @package     Seokar
 * @author      SEO Expert
 * @version     2.0.0
 * @license     GPL-3.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Seokar_WebP_Support {

    /**
     * Minimum quality for WebP conversion
     */
    const WEBP_QUALITY = 80;

    /**
     * Supported image mime types
     */
    const SUPPORTED_TYPES = ['image/jpeg', 'image/png'];

    /**
     * Initialize WebP support features
     */
    public static function init() {
        add_filter('the_content', [__CLASS__, 'replace_images_with_webp'], 99);
        add_filter('wp_get_attachment_image_src', [__CLASS__, 'replace_attachment_with_webp'], 10, 4);
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'convert_image_to_webp'], 20, 2);
        add_action('delete_attachment', [__CLASS__, 'delete_webp_versions']);
        add_filter('wp_check_filetype_and_ext', [__CLASS__, 'webp_filetype_check'], 10, 4);
        add_filter('upload_mimes', [__CLASS__, 'add_webp_mime_type']);
    }

    /**
     * Check if browser supports WebP
     * 
     * @return bool
     */
    public static function is_webp_supported() {
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false 
                || strpos($_SERVER['HTTP_ACCEPT'], 'image/avif') !== false;
        }
        return false;
    }

    /**
     * Replace image URLs with WebP versions in content
     * 
     * @param string $content
     * @return string
     */
    public static function replace_images_with_webp($content) {
        if (!self::is_webp_supported() || is_feed()) {
            return $content;
        }

        // Use DOMDocument for more precise replacement
        if (class_exists('DOMDocument') && function_exists('libxml_use_internal_errors')) {
            libxml_use_internal_errors(true);
            
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            
            $images = $dom->getElementsByTagName('img');
            
            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                $srcset = $img->getAttribute('srcset');
                
                if ($src) {
                    $webp_src = self::get_webp_version($src);
                    if ($webp_src) {
                        $img->setAttribute('src', $webp_src);
                        $img->setAttribute('data-original-src', $src); // Keep original for fallback
                    }
                }
                
                if ($srcset) {
                    $webp_srcset = preg_replace_callback(
                        '/(\.(jpg|jpeg|png)(?:\s+\d+w)?)/i', 
                        function($matches) {
                            $webp = self::get_webp_version($matches[0]);
                            return $webp ? $webp : $matches[0];
                        }, 
                        $srcset
                    );
                    $img->setAttribute('srcset', $webp_srcset);
                }
            }
            
            $content = $dom->saveHTML();
            libxml_clear_errors();
        } else {
            // Fallback regex replacement
            $content = preg_replace_callback(
                '/(<img[^>]*src=["\'])([^"\']+\.(jpg|jpeg|png))([^>]*>)/i',
                function($matches) {
                    $webp = self::get_webp_version($matches[2]);
                    return $webp 
                        ? $matches[1] . $webp . '" data-original-src="' . $matches[2] . $matches[4]
                        : $matches[0];
                },
                $content
            );
        }
        
        return $content;
    }

    /**
     * Replace attachment URLs with WebP versions
     * 
     * @param array|false $image
     * @param int $attachment_id
     * @param string|array $size
     * @param bool $icon
     * @return array|false
     */
    public static function replace_attachment_with_webp($image, $attachment_id, $size, $icon) {
        if (!self::is_webp_supported() || !$image) {
            return $image;
        }

        $webp_url = self::get_webp_version($image[0]);
        if ($webp_url) {
            $image[0] = $webp_url;
        }
        
        return $image;
    }

    /**
     * Convert uploaded images to WebP format
     * 
     * @param array $metadata
     * @param int $attachment_id
     * @return array
     */
    public static function convert_image_to_webp($metadata, $attachment_id) {
        if (!function_exists('imagewebp') || wp_is_stream($metadata['file'])) {
            return $metadata;
        }

        $file_path = get_attached_file($attachment_id);
        $mime_type = get_post_mime_type($attachment_id);
        
        if (!in_array($mime_type, self::SUPPORTED_TYPES) || !file_exists($file_path)) {
            return $metadata;
        }

        $webp_path = self::get_webp_path($file_path);
        
        if (file_exists($webp_path)) {
            return $metadata;
        }

        $image = null;
        $quality = apply_filters('seokar_webp_quality', self::WEBP_QUALITY);
        
        try {
            switch ($mime_type) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($file_path);
                    break;
                    
                case 'image/png':
                    $image = imagecreatefrompng($file_path);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
            }
            
            if ($image && imagewebp($image, $webp_path, $quality)) {
                // Generate WebP versions for all image sizes
                if (!empty($metadata['sizes'])) {
                    foreach ($metadata['sizes'] as $size) {
                        $size_path = path_join(dirname($file_path), $size['file']);
                        $size_webp = self::get_webp_path($size_path);
                        
                        if (file_exists($size_path) && !file_exists($size_webp)) {
                            self::create_webp_version($size_path, $size_webp, $quality);
                        }
                    }
                }
                
                // Optimize the WebP file
                if (apply_filters('seokar_optimize_webp', true)) {
                    self::optimize_webp($webp_path);
                }
            }
            
            if ($image) {
                imagedestroy($image);
            }
        } catch (Exception $e) {
            error_log('Seokar WebP Conversion Error: ' . $e->getMessage());
        }
        
        return $metadata;
    }

    /**
     * Delete WebP versions when attachment is deleted
     * 
     * @param int $attachment_id
     */
    public static function delete_webp_versions($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $webp_path = self::get_webp_path($file_path);
        
        if (file_exists($webp_path)) {
            wp_delete_file($webp_path);
        }
        
        // Delete WebP versions for all sizes
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size) {
                $size_webp = self::get_webp_path(path_join(dirname($file_path), $size['file']));
                if (file_exists($size_webp)) {
                    wp_delete_file($size_webp);
                }
            }
        }
    }

    /**
     * Add WebP mime type support
     * 
     * @param array $mimes
     * @return array
     */
    public static function add_webp_mime_type($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    /**
     * Fix filetype check for WebP files
     * 
     * @param array $data
     * @param string $file
     * @param string $filename
     * @param array $mimes
     * @return array
     */
    public static function webp_filetype_check($data, $file, $filename, $mimes) {
        if (empty($data['type']) && empty($data['ext'])) {
            $filetype = wp_check_filetype($filename, $mimes);
            if ('image/webp' === $filetype['type']) {
                $data['ext'] = 'webp';
                $data['type'] = 'image/webp';
            }
        }
        return $data;
    }

    /**
     * Get WebP version of an image path
     * 
     * @param string $path
     * @return string|false
     */
    private static function get_webp_path($path) {
        if (!preg_match('/\.(jpg|jpeg|png)$/i', $path)) {
            return false;
        }
        return preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
    }

    /**
     * Get WebP version URL
     * 
     * @param string $url
     * @return string|false
     */
    private static function get_webp_version($url) {
        if (!preg_match('/\.(jpg|jpeg|png)(?:\?.*)?$/i', $url)) {
            return false;
        }
        
        $webp_url = preg_replace('/\.(jpg|jpeg|png)(?:\?.*)?$/i', '.webp', $url);
        
        // Check if WebP file exists on server
        $path = self::url_to_path($webp_url);
        return file_exists($path) ? $webp_url : false;
    }

    /**
     * Convert URL to server path
     * 
     * @param string $url
     * @return string
     */
    private static function url_to_path($url) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_path = $upload_dir['basedir'];
        
        if (0 === strpos($url, $base_url)) {
            return str_replace($base_url, $base_path, $url);
        }
        
        return ABSPATH . str_replace(site_url('/'), '', $url);
    }

    /**
     * Create WebP version of an image
     * 
     * @param string $source
     * @param string $destination
     * @param int $quality
     * @return bool
     */
    private static function create_webp_version($source, $destination, $quality) {
        if (!file_exists($source)) {
            return false;
        }
        
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $image = null;
        
        try {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($source);
                    break;
                    
                case 'png':
                    $image = imagecreatefrompng($source);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
                    
                default:
                    return false;
            }
            
            if ($image) {
                $result = imagewebp($image, $destination, $quality);
                imagedestroy($image);
                return $result;
            }
        } catch (Exception $e) {
            error_log('Seokar WebP Creation Error: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Optimize WebP file
     * 
     * @param string $file_path
     */
    private static function optimize_webp($file_path) {
        if (!file_exists($file_path)) {
            return;
        }
        
        // Try cwebp if available
        if (function_exists('exec') && exec('which cwebp')) {
            exec('cwebp -q 80 -m 6 -pass 10 -mt -quiet "' . $file_path . '" -o "' . $file_path . '"');
        }
        
        // Alternative optimization methods can be added here
    }
}

// Initialize the class
Seokar_WebP_Support::init();
