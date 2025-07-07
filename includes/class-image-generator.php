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
     * Generate an AI/open-source image and optional alt-text
     *
     * @param string $prompt        Prompt / keyword
     * @param string $size          e.g. 1024x1024
     * @param bool   $generate_alt  Generate alt text via GPT
     * @param string $provider      openai|unsplash|replicate  (default openai)
     * @return array
     */
    public function generate_image($prompt, $size = '1024x1024', $generate_alt = true, $provider = 'openai') {
        switch ($provider) {
            case 'unsplash':
                return $this->get_unsplash_image($prompt, $size, $generate_alt);
            case 'replicate':
                return $this->get_replicate_sd_image($prompt, $size, $generate_alt);
            case 'openai':
            default:
                return $this->get_openai_image($prompt, $size, $generate_alt);
        }
    }

    private function get_unsplash_image($keyword, $size, $generate_alt) {
        $access_key = trim(get_option('kotacom_ai_unsplash_access_key'));
        if (empty($access_key)) {
            return array('success' => false, 'error' => __('Unsplash access key not configured', 'kotacom-ai'));
        }

        $url = add_query_arg(array(
            'query' => urlencode($keyword),
            'orientation' => 'landscape',
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
        $alt     = sanitize_text_field($data['alt_description'] ?? $keyword);

        // optionally compress size param mapping – Unsplash auto scales.

        return array('success' => true, 'url' => $img_url, 'alt' => $alt);
    }

    private function get_replicate_sd_image($prompt, $size, $generate_alt) {
        $api_key = trim(get_option('kotacom_ai_replicate_api_key'));
        if (empty($api_key)) {
            return array('success' => false, 'error' => __('Replicate API key not configured', 'kotacom-ai'));
        }

        // Call Replicate Stable-Diffusion endpoint
        $body = array(
            'version' => 'stability-ai/sdxl', // adjust to model you prefer
            'input'   => array(
                'prompt' => $prompt,
                'width'  => intval(explode('x', $size)[0]),
                'height' => intval(explode('x', $size)[1]),
            )
        );

        $response = wp_remote_post('https://api.replicate.com/v1/predictions', array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Token ' . $api_key,
            ),
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['urls']['get'])) {
            return array('success' => false, 'error' => __('Replicate did not return prediction URL', 'kotacom-ai'));
        }

        // Poll for completion (simple loop – could be optimized)
        $poll_url = $data['urls']['get'];
        $img_url  = '';
        for ($i = 0; $i < 10; $i++) {
            $check = wp_remote_get($poll_url, array('headers' => array('Authorization' => 'Token ' . $api_key), 'timeout' => 20));
            $check_data = is_wp_error($check) ? null : json_decode(wp_remote_retrieve_body($check), true);
            if ($check_data && $check_data['status'] === 'succeeded') {
                $img_url = $check_data['output'][0] ?? '';
                break;
            }
            sleep(2);
        }

        if (empty($img_url)) {
            return array('success' => false, 'error' => __('Replicate image generation timed out', 'kotacom-ai'));
        }

        $alt = '';
        if ($generate_alt) {
            $api_handler = new KotacomAI_API_Handler();
            $alt_prompt  = sprintf('Generate a concise alt-text (max 20 words) for an image described as: "%s".', $prompt);
            $alt_result  = $api_handler->generate_content($alt_prompt, array('length' => '25'));
            if ($alt_result['success']) {
                $alt = trim(strip_tags($alt_result['content']));
            }
        }

        return array('success' => true, 'url' => esc_url_raw($img_url), 'alt' => $alt);
    }

    private function get_openai_image($prompt, $size, $generate_alt) {
        $api_key = trim(get_option('kotacom_ai_openai_api_key'));
        if (empty($api_key)) {
            return array('success' => false, 'error' => __('OpenAI API key not configured', 'kotacom-ai'));
        }

        $body = array('prompt' => $prompt, 'n' => 1, 'size' => $size, 'model' => 'dall-e-3');
        $response = wp_remote_post('https://api.openai.com/v1/images/generations', array(
            'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key),
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ));
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['data'][0]['url'])) {
            return array('success' => false, 'error' => __('OpenAI image generation failed', 'kotacom-ai'));
        }
        $img_url = esc_url_raw($data['data'][0]['url']);
        $alt     = '';
        if ($generate_alt) {
            $api_handler = new KotacomAI_API_Handler();
            $alt_prompt  = sprintf('Generate a concise alt-text (max 20 words) for an image described as: "%s".', $prompt);
            $alt_result  = $api_handler->generate_content($alt_prompt, array('length' => '25'));
            if ($alt_result['success']) {
                $alt = trim(strip_tags($alt_result['content']));
            }
        }
        return array('success' => true, 'url' => $img_url, 'alt' => $alt);
    }
}