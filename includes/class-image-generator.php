<?php
/**
 * Enhanced Image Generator class with multiple FREE providers
 * 
 * Supports: Unsplash, Pixabay, Pexels, Lorem Picsum, Placeholder.co fallback
 * Features: Shortcode support, auto alt-text, fallback handling
 *
 * Usage:
 *   $image_gen = new KotacomAI_Image_Generator();
 *   $result = $image_gen->generate_image('A futuristic city skyline at sunset', '1024x1024');
 *   
 * Shortcode:
 *   [ai_image prompt="sunset city" size="800x600" featured="yes" provider="unsplash"]
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Image_Generator {

    private $providers = array(
        'unsplash' => 'Unsplash (FREE)',
        'pixabay' => 'Pixabay (FREE)',
        'pexels' => 'Pexels (FREE)',
        'picsum' => 'Lorem Picsum (FREE)',
        'placeholder' => 'Placeholder.co (Fallback)'
    );

    public function __construct() {
        add_action('init', array($this, 'register_shortcode'));
    }

    /**
     * Register the image shortcode
     */
    public function register_shortcode() {
        add_shortcode('ai_image', array($this, 'image_shortcode'));
    }

    /**
     * Handle [ai_image] shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function image_shortcode($atts) {
        $atts = shortcode_atts(array(
            'prompt' => '',
            'size' => '800x600',
            'alt' => '',
            'featured' => 'no',
            'provider' => get_option('kotacom_ai_default_image_provider', 'unsplash'),
            'class' => 'ai-generated-image',
            'caption' => '',
            'align' => 'none',
            'fallback' => 'yes'
        ), $atts);

        if (empty($atts['prompt'])) {
            return '<p class="ai-error">' . __('Error: Image prompt is required', 'kotacom-ai') . '</p>';
        }

        $result = $this->generate_image($atts['prompt'], $atts['size'], true, $atts['provider'], $atts['fallback'] === 'yes');
        
        if (!$result['success']) {
            if ($atts['fallback'] === 'yes') {
                // Try placeholder fallback
                $fallback_result = $this->get_placeholder_image($atts['prompt'], $atts['size']);
                if ($fallback_result['success']) {
                    $result = $fallback_result;
                } else {
                    return '<p class="ai-error">' . __('Error: Unable to generate image', 'kotacom-ai') . '</p>';
                }
            } else {
                return '<p class="ai-error">' . sprintf(__('Error: %s', 'kotacom-ai'), $result['error']) . '</p>';
            }
        }

        // Set as featured image if requested
        if ($atts['featured'] === 'yes' && is_singular()) {
            $this->set_featured_image(get_the_ID(), $result['url'], $atts['prompt']);
        }

        // Build image HTML
        $alt_text = !empty($atts['alt']) ? $atts['alt'] : $result['alt'];
        $class = 'ai-generated-image ' . $atts['class'];
        if ($atts['align'] !== 'none') {
            $class .= ' align' . $atts['align'];
        }

        $html = sprintf(
            '<img src="%s" alt="%s" class="%s" data-provider="%s" data-prompt="%s">',
            esc_url($result['url']),
            esc_attr($alt_text),
            esc_attr($class),
            esc_attr($atts['provider']),
            esc_attr($atts['prompt'])
        );

        // Add caption if provided
        if (!empty($atts['caption'])) {
            $html = sprintf(
                '<figure class="wp-caption %s"><div class="wp-caption-img">%s</div><figcaption class="wp-caption-text">%s</figcaption></figure>',
                esc_attr('align' . $atts['align']),
                $html,
                esc_html($atts['caption'])
            );
        }

        return $html;
    }

    /**
     * Generate an image using specified provider with fallback support
     *
     * @param string $prompt        Prompt / keyword
     * @param string $size          e.g. 1024x1024
     * @param bool   $generate_alt  Generate alt text via AI
     * @param string $provider      Provider to use
     * @param bool   $enable_fallback Enable fallback to placeholder
     * @return array
     */
    public function generate_image($prompt, $size = '1024x1024', $generate_alt = true, $provider = 'unsplash', $enable_fallback = true) {
        // Try primary provider
        $result = $this->call_provider($provider, $prompt, $size, $generate_alt);
        
        // If failed and fallback enabled, try other providers
        if (!$result['success'] && $enable_fallback) {
            $provider_order = $this->get_fallback_order($provider);
            foreach ($provider_order as $fallback_provider) {
                $result = $this->call_provider($fallback_provider, $prompt, $size, $generate_alt);
                if ($result['success']) {
                    break;
                }
            }
        }

        // Log the result
        if (class_exists('KotacomAI_Logger')) {
            KotacomAI_Logger::add(
                'image_generation', 
                $result['success'], 
                0, 
                sprintf('Provider: %s, Prompt: %s, Result: %s', 
                    $provider, 
                    $prompt, 
                    $result['success'] ? 'Success' : $result['error']
                )
            );
        }

        return $result;
    }

    /**
     * Call specific provider
     */
    private function call_provider($provider, $prompt, $size, $generate_alt) {
        switch ($provider) {
            case 'unsplash':
                return $this->get_unsplash_image($prompt, $size, $generate_alt);
            case 'pixabay':
                return $this->get_pixabay_image($prompt, $size, $generate_alt);
            case 'pexels':
                return $this->get_pexels_image($prompt, $size, $generate_alt);
            case 'picsum':
                return $this->get_picsum_image($prompt, $size, $generate_alt);
            case 'placeholder':
                return $this->get_placeholder_image($prompt, $size);
            default:
                return array('success' => false, 'error' => __('Unknown provider', 'kotacom-ai'));
        }
    }

    /**
     * Get fallback provider order
     */
    private function get_fallback_order($primary_provider) {
        $all_providers = array_keys($this->providers);
        $fallback_order = array_diff($all_providers, array($primary_provider));
        // Always try placeholder last
        $fallback_order = array_diff($fallback_order, array('placeholder'));
        $fallback_order[] = 'placeholder';
        return $fallback_order;
    }

    /**
     * Unsplash API integration
     */
    private function get_unsplash_image($keyword, $size, $generate_alt) {
        $access_key = trim(get_option('kotacom_ai_unsplash_access_key'));
        if (empty($access_key)) {
            return array('success' => false, 'error' => __('Unsplash access key not configured', 'kotacom-ai'));
        }

        $url = add_query_arg(array(
            'query' => urlencode($keyword),
            'orientation' => $this->get_orientation_from_size($size),
            'content_filter' => 'high',
        ), 'https://api.unsplash.com/photos/random');

        $response = wp_remote_get($url, array(
            'headers' => array('Authorization' => 'Client-ID ' . $access_key),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['urls']['regular'])) {
            return array('success' => false, 'error' => __('Unsplash did not return an image', 'kotacom-ai'));
        }

        $img_url = esc_url_raw($data['urls']['regular']);
        $alt = $this->generate_alt_text($keyword, $data['alt_description'] ?? '', $generate_alt);

        return array('success' => true, 'url' => $img_url, 'alt' => $alt, 'provider' => 'unsplash');
    }

    /**
     * Pixabay API integration
     */
    private function get_pixabay_image($keyword, $size, $generate_alt) {
        $api_key = trim(get_option('kotacom_ai_pixabay_api_key'));
        if (empty($api_key)) {
            return array('success' => false, 'error' => __('Pixabay API key not configured', 'kotacom-ai'));
        }

        $url = add_query_arg(array(
            'key' => $api_key,
            'q' => urlencode($keyword),
            'image_type' => 'photo',
            'orientation' => $this->get_orientation_from_size($size),
            'safesearch' => 'true',
            'per_page' => 3,
        ), 'https://pixabay.com/api/');

        $response = wp_remote_get($url, array('timeout' => 20));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['hits']) || empty($data['hits'])) {
            return array('success' => false, 'error' => __('Pixabay did not return any images', 'kotacom-ai'));
        }

        $image = $data['hits'][0];
        $img_url = esc_url_raw($image['largeImageURL']);
        $alt = $this->generate_alt_text($keyword, $image['tags'] ?? '', $generate_alt);

        return array('success' => true, 'url' => $img_url, 'alt' => $alt, 'provider' => 'pixabay');
    }

    /**
     * Pexels API integration
     */
    private function get_pexels_image($keyword, $size, $generate_alt) {
        $api_key = trim(get_option('kotacom_ai_pexels_api_key'));
        if (empty($api_key)) {
            return array('success' => false, 'error' => __('Pexels API key not configured', 'kotacom-ai'));
        }

        $url = add_query_arg(array(
            'query' => urlencode($keyword),
            'per_page' => 1,
        ), 'https://api.pexels.com/v1/search');

        $response = wp_remote_get($url, array(
            'headers' => array('Authorization' => $api_key),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['photos']) || empty($data['photos'])) {
            return array('success' => false, 'error' => __('Pexels did not return any images', 'kotacom-ai'));
        }

        $image = $data['photos'][0];
        $img_url = esc_url_raw($image['src']['large']);
        $alt = $this->generate_alt_text($keyword, $image['alt'] ?? '', $generate_alt);

        return array('success' => true, 'url' => $img_url, 'alt' => $alt, 'provider' => 'pexels');
    }

    /**
     * Lorem Picsum integration (random images with size)
     */
    private function get_picsum_image($keyword, $size, $generate_alt) {
        list($width, $height) = explode('x', $size);
        $width = intval($width);
        $height = intval($height);

        // Lorem Picsum doesn't support keywords, so we generate a seed from the keyword
        $seed = crc32($keyword);
        $img_url = sprintf('https://picsum.photos/seed/%s/%d/%d', $seed, $width, $height);

        $alt = $this->generate_alt_text($keyword, '', $generate_alt);

        return array('success' => true, 'url' => $img_url, 'alt' => $alt, 'provider' => 'picsum');
    }

    /**
     * Placeholder.co fallback with text
     */
    private function get_placeholder_image($keyword, $size) {
        list($width, $height) = explode('x', $size);
        $width = intval($width);
        $height = intval($height);

        // Create placeholder with relevant text
        $placeholder_text = urlencode(ucwords(str_replace(array('-', '_'), ' ', $keyword)));
        $img_url = sprintf('https://placehold.co/%dx%d/cccccc/333333?text=%s', $width, $height, $placeholder_text);

        $alt = sprintf(__('Placeholder image for %s', 'kotacom-ai'), $keyword);

        return array('success' => true, 'url' => $img_url, 'alt' => $alt, 'provider' => 'placeholder');
    }

    /**
     * Generate AI alt text
     */
    private function generate_alt_text($keyword, $existing_description = '', $generate_alt = true) {
        if (!$generate_alt) {
            return !empty($existing_description) ? $existing_description : $keyword;
        }

        if (!empty($existing_description) && strlen($existing_description) > 10) {
            return $existing_description;
        }

        // Try to generate AI alt text
        if (class_exists('KotacomAI_API_Handler')) {
            $api_handler = new KotacomAI_API_Handler();
            $alt_prompt = sprintf('Generate a concise, SEO-friendly alt-text (max 15 words) for an image about: "%s". Focus on visual elements and accessibility.', $keyword);
            $alt_result = $api_handler->generate_content($alt_prompt, array('length' => '25'));
            
            if ($alt_result['success']) {
                $alt_text = trim(strip_tags($alt_result['content']));
                // Remove quotes if present
                $alt_text = trim($alt_text, '"\'');
                return $alt_text;
            }
        }

        // Fallback to keyword
        return ucwords(str_replace(array('-', '_'), ' ', $keyword));
    }

    /**
     * Get orientation from size
     */
    private function get_orientation_from_size($size) {
        list($width, $height) = explode('x', $size);
        $width = intval($width);
        $height = intval($height);

        if ($width > $height) {
            return 'landscape';
        } elseif ($height > $width) {
            return 'portrait';
        } else {
            return 'squarish';
        }
    }

    /**
     * Set featured image from URL
     */
    public function set_featured_image($post_id, $image_url, $description = '') {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Download image
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return false;
        }

        // Set up the file array
        $file_array = array(
            'name' => basename($image_url) . '.jpg',
            'tmp_name' => $tmp
        );

        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id, $description);

        // Clean up temp file
        if (file_exists($tmp)) {
            unlink($tmp);
        }

        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        return $attachment_id;
    }

    /**
     * Get available providers
     */
    public function get_providers() {
        return $this->providers;
    }

    /**
     * Test provider connection
     */
    public function test_provider($provider) {
        return $this->call_provider($provider, 'test', '400x300', false);
    }

    /**
     * Generate hero image for post
     */
    public function generate_hero_image($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'error' => __('Post not found', 'kotacom-ai'));
        }

        $prompt = $post->post_title;
        $provider = get_option('kotacom_ai_default_image_provider', 'unsplash');
        $size = get_option('kotacom_ai_default_image_size', '1200x800');

        $result = $this->generate_image($prompt, $size, true, $provider);
        
        if ($result['success']) {
            $attachment_id = $this->set_featured_image($post_id, $result['url'], $prompt);
            if ($attachment_id) {
                $result['attachment_id'] = $attachment_id;
            }
        }

        return $result;
    }
}