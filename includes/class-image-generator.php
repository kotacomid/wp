<?php
/**
 * Image Generator class (OpenAI DALL·E 3 or OpenAI Images API)
 *
 * Usage:
 *   $image_gen = new KotacomAI_Image_Generator();
 *   $result = $image_gen->generate_image('A futuristic city skyline at sunset', '1024x1024');
 *   // $result = [ 'success' => true, 'url' => '...', 'alt' => '...' ]
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Image_Generator {

    /**
     * Generate an AI image and optional alt-text
     *
     * @param string $prompt  Image prompt
     * @param string $size    Size (e.g. 1024x1024, 512x512)
     * @param bool   $generate_alt  Whether to generate alt text via GPT
     * @return array [ success => bool, url => string, alt => string, error => string ]
     */
    public function generate_image($prompt, $size = '1024x1024', $generate_alt = true) {
        $api_key = trim(get_option('kotacom_ai_openai_api_key'));
        if (empty($api_key)) {
            return array('success' => false, 'error' => __('OpenAI API key not configured', 'kotacom-ai'));
        }

        // Create image via OpenAI Images API (DALL·E 3)
        $body = array(
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => $size,
            'model'  => 'dall-e-3' // Use dall-e-3; change if you want 2 or 3 etc.
        );

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['data'][0]['url'])) {
            $msg = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown image generation error', 'kotacom-ai');
            return array('success' => false, 'error' => $msg);
        }

        $image_url = esc_url_raw($data['data'][0]['url']);
        $alt_text  = '';

        // Optionally generate alt text using GPT (cheap)
        if ($generate_alt) {
            $alt_prompt  = sprintf('Generate a concise alt-text (max 20 words) for an image described as: "%s". Avoid starting with "An image of".', $prompt);
            $api_handler = new KotacomAI_API_Handler();
            $alt_result  = $api_handler->generate_content($alt_prompt, array('length' => '25'));
            if ($alt_result['success']) {
                $alt_text = trim(strip_tags($alt_result['content']));
            }
        }

        return array(
            'success' => true,
            'url'     => $image_url,
            'alt'     => $alt_text,
        );
    }
}