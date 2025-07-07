<?php
/**
 * AI API Handler class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_API_Handler {
    
    private $api_providers = array();
    
    public function __construct() {
        $this->init_providers();
    }
    
    /**
     * Initialize API providers
     */
    private function init_providers() {
        $this->api_providers = array(
            'google_ai' => array(
                'name' => 'Google AI (Gemini)',
                'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => true,
                'models' => array(
                    'gemini-2.5-pro' => 'Gemini 2.5 Pro',
                    'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                    'gemini-2.5-flash-lite-preview-06-17' => 'Gemini 2.5 Flash-Lite Preview 06-17',
                    'gemini-2.5-flash-preview-tts' => 'Gemini 2.5 Flash Preview TTS',
                    'gemini-2.5-pro-preview-tts' => 'Gemini 2.5 Pro Preview TTS',
                    'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                    'gemini-2.0-flash-preview-image-generation' => 'Gemini 2.0 Flash Preview Image Generation',
                    'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash-Lite',
                    'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fastest)',
                    'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B (Ultra Fast)',
                    'gemini-1.5-pro' => 'Gemini 1.5 Pro (Most Capable)',
                    'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)',
                    'gemini-exp-1206' => 'Gemini Experimental 1206'
                )
            ),
            'groq' => array(
                'name' => 'Groq (Fast Inference)',
                'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => true,
                'models' => array(
                    'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile',
                    'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
                    'mixtral-8x7b-32768' => 'Mixtral 8x7B',
                    'gemma2-9b-it' => 'Gemma 2 9B IT',
                    'qwen-qwq-32b' => 'Qwen QwQ 32B'
                )
            ),
            'openai' => array(
                'name' => 'OpenAI',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => false,
                'models' => array(
                    'gpt-4o' => 'GPT-4o',
                    'gpt-4o-mini' => 'GPT-4o Mini',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                )
            ),
            'anthropic' => array(
                'name' => 'Anthropic Claude',
                'endpoint' => 'https://api.anthropic.com/v1/messages',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'anthropic-version' => '2023-06-01'
                ),
                'free_tier' => true,
                'models' => array(
                    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                    'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
                    'claude-3-opus-20240229' => 'Claude 3 Opus'
                )
            ),
            'cohere' => array(
                'name' => 'Cohere',
                'endpoint' => 'https://api.cohere.ai/v1/generate',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => true,
                'models' => array(
                    'command' => 'Command',
                    'command-light' => 'Command Light',
                    'command-nightly' => 'Command Nightly'
                )
            ),
            'huggingface' => array(
                'name' => 'Hugging Face',
                'endpoint' => 'https://api-inference.huggingface.co/models/{model}',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => true,
                'models' => array(
                    'microsoft/DialoGPT-large' => 'DialoGPT Large',
                    'facebook/blenderbot-400M-distill' => 'BlenderBot 400M',
                    'microsoft/DialoGPT-medium' => 'DialoGPT Medium',
                    'gpt2' => 'GPT-2'
                )
            ),
            'together' => array(
                'name' => 'Together AI',
                'endpoint' => 'https://api.together.xyz/v1/chat/completions',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => true,
                'models' => array(
                    'meta-llama/Llama-3.2-3B-Instruct-Turbo' => 'Llama 3.2 3B Instruct Turbo',
                    'meta-llama/Llama-3.2-1B-Instruct-Turbo' => 'Llama 3.2 1B Instruct Turbo',
                    'mistralai/Mistral-7B-Instruct-v0.1' => 'Mistral 7B Instruct',
                    'togethercomputer/RedPajama-INCITE-Chat-3B-v1' => 'RedPajama Chat 3B'
                )
            ),
            'replicate' => array(
                'name' => 'Replicate',
                'endpoint' => 'https://api.replicate.com/v1/predictions',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => true,
                'models' => array(
                    'meta/llama-2-7b-chat' => 'Llama 2 7B Chat',
                    'meta/llama-2-13b-chat' => 'Llama 2 13B Chat',
                    'mistralai/mistral-7b-instruct-v0.1' => 'Mistral 7B Instruct'
                )
            ),
            'openrouter' => array(
                'name' => 'OpenRouter',
                'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => get_site_url(), // Required for some OpenRouter models
                    'X-Title' => get_bloginfo('name') // Optional, for identifying your app
                ),
                'free_tier' => false, // OpenRouter is a paid service, but offers free credits
                'models' => array(
                    'mistralai/mistral-7b-instruct' => 'Mistral 7B Instruct',
                    'nousresearch/nous-hermes-2-mixtral-8x7b-s' => 'Nous Hermes 2 Mixtral 8x7B',
                    'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo (via OpenRouter)',
                    'google/gemini-pro' => 'Gemini Pro (via OpenRouter)',
                    'anthropic/claude-3-haiku' => 'Claude 3 Haiku (via OpenRouter)'
                )
            ),
            'perplexity' => array(
                'name' => 'Perplexity AI',
                'endpoint' => 'https://api.perplexity.ai/chat/completions',
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'free_tier' => false, // Perplexity AI is a paid service, but offers free credits
                'models' => array(
                    'llama-3-sonar-small-32k-online' => 'Sonar Small (Online)',
                    'llama-3-sonar-large-32k-online' => 'Sonar Large (Online)',
                    'llama-3-sonar-small-33k-chat' => 'Sonar Small (Chat)',
                    'llama-3-sonar-large-33k-chat' => 'Sonar Large (Chat)'
                )
            )
        );
    }
    
    /**
     * Generate content using AI API
     */
    public function generate_content($prompt, $parameters = array()) {
        $provider = get_option('kotacom_ai_api_provider', 'google_ai');
        $api_key = get_option('kotacom_ai_' . $provider . '_api_key');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'error' => __('API key not configured', 'kotacom-ai')
            );
        }
        
        // Build the final prompt with parameters
        $final_prompt = $this->build_prompt($prompt, $parameters);
        
        // Call the appropriate API
        switch ($provider) {
            case 'google_ai':
                return $this->call_google_ai($final_prompt, $api_key, $parameters);
            case 'openai':
                return $this->call_openai($final_prompt, $api_key, $parameters);
            case 'groq':
                return $this->call_groq($final_prompt, $api_key, $parameters);
            case 'cohere':
                return $this->call_cohere($final_prompt, $api_key, $parameters);
            case 'huggingface':
                return $this->call_huggingface($final_prompt, $api_key, $parameters);
            case 'together':
                return $this->call_together($final_prompt, $api_key, $parameters);
            case 'anthropic':
                return $this->call_anthropic($final_prompt, $api_key, $parameters);
            case 'replicate':
                return $this->call_replicate($final_prompt, $api_key, $parameters);
            case 'openrouter':
                return $this->call_openrouter($final_prompt, $api_key, $parameters);
            case 'perplexity':
                return $this->call_perplexity($final_prompt, $api_key, $parameters);
            default:
                return array(
                    'success' => false,
                    'error' => __('Unsupported API provider', 'kotacom-ai')
                );
        }
    }
    
    /**
     * Build final prompt with parameters
     */
    private function build_prompt($prompt, $parameters) {
        $final_prompt = $prompt;
        
        // Add parameters to prompt
        if (!empty($parameters['tone'])) {
            $final_prompt .= "\n\nTone: " . $parameters['tone'];
        }
        
        if (!empty($parameters['length'])) {
            $final_prompt .= "\nTarget length: " . $parameters['length'] . " words";
        }
        
        if (!empty($parameters['audience'])) {
            $final_prompt .= "\nTarget audience: " . $parameters['audience'];
        }
        
        if (!empty($parameters['niche'])) {
            $final_prompt .= "\nIndustry/Niche: " . $parameters['niche'];
        }
        
        return $final_prompt;
    }
    
    /**
     * Call Google AI (Gemini) API with model selection
     */
    private function call_google_ai($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_google_ai_model', 'gemini-1.5-flash');
        $endpoint = str_replace('{model}', $model, $this->api_providers['google_ai']['endpoint']) . '?key=' . $api_key;
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 8192,
                'topP' => 0.8,
                'topK' => 10
            ),
            'safetySettings' => array(
                array(
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                )
            )
        );
        
        $response = wp_remote_post($endpoint, array(
            'headers' => $this->api_providers['google_ai']['headers'],
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['candidates'][0]['content']['parts'][0]['text']
            );
        } else {
            $error_message = 'Unknown API error';
            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            } elseif (isset($data['error']['details'][0]['reason'])) {
                $error_message = $data['error']['details'][0]['reason'];
            }
        
            return array(
                'success' => false,
                'error' => $error_message
            );
        }
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_openai_model', 'gpt-3.5-turbo');
        
        $headers = $this->api_providers['openai']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 2048
        );
        
        $response = wp_remote_post($this->api_providers['openai']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }
    
    /**
     * Call Groq API
     */
    private function call_groq($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_groq_model', 'mixtral-8x7b-32768');
        
        $headers = $this->api_providers['groq']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 2048
        );
        
        $response = wp_remote_post($this->api_providers['groq']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }
    
    /**
     * Call Cohere API
     */
    private function call_cohere($prompt, $api_key, $parameters) {
        $headers = $this->api_providers['cohere']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'prompt' => $prompt,
            'model' => 'command',
            'temperature' => 0.7,
            'max_tokens' => 2048
        );
        
        $response = wp_remote_post($this->api_providers['cohere']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['generations'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['generations'][0]['text']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['message']) ? $data['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }

    /**
     * Call Hugging Face API
     */
    private function call_huggingface($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_huggingface_model', 'microsoft/DialoGPT-large');
        $endpoint = str_replace('{model}', $model, $this->api_providers['huggingface']['endpoint']);
        
        $headers = $this->api_providers['huggingface']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'inputs' => $prompt,
            'parameters' => array(
                'max_length' => 2048,
                'temperature' => 0.7,
                'do_sample' => true
            )
        );
        
        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data[0]['generated_text'])) {
            return array(
                'success' => true,
                'content' => $data[0]['generated_text']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']) ? $data['error'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }

    /**
     * Call Together AI API
     */
    private function call_together($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_together_model', 'meta-llama/Llama-3.2-3B-Instruct-Turbo');
        
        $headers = $this->api_providers['together']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 2048
        );
        
        $response = wp_remote_post($this->api_providers['together']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }

    /**
     * Call Anthropic Claude API
     */
    private function call_anthropic($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_anthropic_model', 'claude-3-5-sonnet-20241022');
        
        $headers = $this->api_providers['anthropic']['headers'];
        $headers['x-api-key'] = $api_key;
        
        $body = array(
            'model' => $model,
            'max_tokens' => 2048,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );
        
        $response = wp_remote_post($this->api_providers['anthropic']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['content'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['content'][0]['text']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }

    /**
     * Call Replicate API
     */
    private function call_replicate($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_replicate_model', 'meta/llama-2-7b-chat');
        
        $headers = $this->api_providers['replicate']['headers'];
        $headers['Authorization'] = 'Token ' . $api_key;
        
        $body = array(
            'version' => $model,
            'input' => array(
                'prompt' => $prompt,
                'max_length' => 2048,
                'temperature' => 0.7
            )
        );
        
        $response = wp_remote_post($this->api_providers['replicate']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['output'])) {
            $content = is_array($data['output']) ? implode('', $data['output']) : $data['output'];
            return array(
                'success' => true,
                'content' => $content
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['detail']) ? $data['detail'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }

    /**
     * Call OpenRouter API
     */
    private function call_openrouter($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_openrouter_model', 'mistralai/mistral-7b-instruct');
        
        $headers = $this->api_providers['openrouter']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 2048
        );
        
        $response = wp_remote_post($this->api_providers['openrouter']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }

    /**
     * Call Perplexity AI API
     */
    private function call_perplexity($prompt, $api_key, $parameters) {
        $model = get_option('kotacom_ai_perplexity_model', 'llama-3-sonar-small-32k-online');
        
        $headers = $this->api_providers['perplexity']['headers'];
        $headers['Authorization'] = 'Bearer ' . $api_key;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 2048
        );
        
        $response = wp_remote_post($this->api_providers['perplexity']['endpoint'], array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content']
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'kotacom-ai')
            );
        }
    }
   
    /**
     * Test API connection
     */
    public function test_api_connection($provider, $api_key) {
        $test_prompt = "Hello, this is a test message. Please respond with 'API connection successful'.";
        
        // Temporarily set the API key for testing
        $original_provider = get_option('kotacom_ai_api_provider');
        $original_key = get_option('kotacom_ai_' . $provider . '_api_key');
        
        update_option('kotacom_ai_api_provider', $provider);
        update_option('kotacom_ai_' . $provider . '_api_key', $api_key);
        
        $result = $this->generate_content($test_prompt);
        
        // Restore original settings
        update_option('kotacom_ai_api_provider', $original_provider);
        update_option('kotacom_ai_' . $provider . '_api_key', $original_key);
        
        return $result;
    }
    
    /**
     * Get available providers
     */
    public function get_providers() {
        return $this->api_providers;
    }
    
    /**
     * Get provider models
     */
    public function get_provider_models($provider) {
        if (isset($this->api_providers[$provider]['models'])) {
            return $this->api_providers[$provider]['models'];
        }
        return array();
    }

    /**
     * Check if provider has free tier
     */
    public function is_free_tier($provider) {
        return isset($this->api_providers[$provider]['free_tier']) && $this->api_providers[$provider]['free_tier'];
    }
}
