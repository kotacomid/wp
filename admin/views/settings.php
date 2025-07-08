<?php
/**
 * Settings admin page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Kotacom AI Settings', 'kotacom-ai'); ?></h1>
    
    <!-- Quick Setup Guide -->
    <div class="info-card">
        <h3><span class="dashicons dashicons-admin-tools"></span> <?php _e('Quick Setup Guide', 'kotacom-ai'); ?></h3>
        <p><?php _e('Get started in 3 easy steps:', 'kotacom-ai'); ?></p>
        <ol style="margin-left: 20px;">
            <li><strong><?php _e('Choose an AI Provider:', 'kotacom-ai'); ?></strong> <?php _e('Select a provider below (recommend Google AI for free tier)', 'kotacom-ai'); ?></li>
            <li><strong><?php _e('Get API Key:', 'kotacom-ai'); ?></strong> <?php _e('Click the provider link to get your FREE API key', 'kotacom-ai'); ?></li>
            <li><strong><?php _e('Test Connection:', 'kotacom-ai'); ?></strong> <?php _e('Click "Test API Connection" to verify setup', 'kotacom-ai'); ?></li>
        </ol>
        <div class="highlight">
            <strong><?php _e('💡 Pro Tip:', 'kotacom-ai'); ?></strong> <?php _e('Most providers offer generous free tiers - no credit card required!', 'kotacom-ai'); ?>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('kotacom_ai_settings');
        do_settings_sections('kotacom_ai_settings');
        ?>
        
        <!-- API Settings -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('AI Provider Configuration', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('AI Provider', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_api_provider" id="api-provider">
                                <?php
                                $current_provider = get_option('kotacom_ai_api_provider', 'google_ai');
                                foreach ($providers as $key => $provider):
                                    $free_badge = $api_handler->is_free_tier($key) ? ' (FREE)' : '';
                                    $paid_badge = !$api_handler->is_free_tier($key) ? ' (PAID)' : '';
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_provider, $key); ?>>
                                    <?php echo esc_html($provider['name'] . $free_badge . $paid_badge); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Choose your preferred AI provider. Providers marked with (FREE) offer free tiers or trials.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <!-- Google AI Settings -->
                <div class="api-settings" id="google_ai-settings">
                    <h3><?php _e('Google AI (Gemini) Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_google_ai_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_google_ai_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://makersuite.google.com/app/apikey" target="_blank"><?php _e('Google AI Studio', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: 15 requests per minute, 1500 requests per day', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_google_ai_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_google_ai_model', 'gemini-1.5-flash');
                                    $google_models = $api_handler->get_provider_models('google_ai');
                                    foreach ($google_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Gemini 1.5 Flash is recommended for speed and efficiency.', 'kotacom-ai'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Groq Settings -->
                <div class="api-settings" id="groq-settings" style="display: none;">
                    <h3><?php _e('Groq Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_groq_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_groq_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://console.groq.com/keys" target="_blank"><?php _e('Groq Console', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: Ultra-fast inference with generous limits', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_groq_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_groq_model', 'llama-3.3-70b-versatile');
                                    $groq_models = $api_handler->get_provider_models('groq');
                                    foreach ($groq_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Hugging Face Settings -->
                <div class="api-settings" id="huggingface-settings" style="display: none;">
                    <h3><?php _e('Hugging Face Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_huggingface_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_huggingface_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://huggingface.co/settings/tokens" target="_blank"><?php _e('Hugging Face', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: 1000 requests per month', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_huggingface_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_huggingface_model', 'microsoft/DialoGPT-large');
                                    $hf_models = $api_handler->get_provider_models('huggingface');
                                    foreach ($hf_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Together AI Settings -->
                <div class="api-settings" id="together-settings" style="display: none;">
                    <h3><?php _e('Together AI Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_together_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_together_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://api.together.xyz/settings/api-keys" target="_blank"><?php _e('Together AI', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: $5 credit for new users', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_together_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_together_model', 'meta-llama/Llama-3.2-3B-Instruct-Turbo');
                                    $together_models = $api_handler->get_provider_models('together');
                                    foreach ($together_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Anthropic Settings -->
                <div class="api-settings" id="anthropic-settings" style="display: none;">
                    <h3><?php _e('Anthropic Claude Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_anthropic_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_anthropic_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://console.anthropic.com/" target="_blank"><?php _e('Anthropic Console', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: $5 credit for new users', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_anthropic_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_anthropic_model', 'claude-3-5-sonnet-20241022');
                                    $anthropic_models = $api_handler->get_provider_models('anthropic');
                                    foreach ($anthropic_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Cohere Settings -->
                <div class="api-settings" id="cohere-settings" style="display: none;">
                    <h3><?php _e('Cohere Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_cohere_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_cohere_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://dashboard.cohere.ai/api-keys" target="_blank"><?php _e('Cohere Dashboard', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: 1000 requests per month', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_cohere_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_cohere_model', 'command');
                                    $cohere_models = $api_handler->get_provider_models('cohere');
                                    foreach ($cohere_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Replicate Settings -->
                <div class="api-settings" id="replicate-settings" style="display: none;">
                    <h3><?php _e('Replicate Settings', 'kotacom-ai'); ?> <span class="free-badge"><?php _e('FREE TIER AVAILABLE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_replicate_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_replicate_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your FREE API key from', 'kotacom-ai'); ?> 
                                    <a href="https://replicate.com/account/api-tokens" target="_blank"><?php _e('Replicate', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Free tier: $10 credit for new users', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_replicate_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_replicate_model', 'meta/llama-2-7b-chat');
                                    $replicate_models = $api_handler->get_provider_models('replicate');
                                    foreach ($replicate_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Unsplash Settings -->
                <div class="api-settings" id="unsplash-settings" style="display: none;">
                    <h3><?php _e('Unsplash Settings', 'kotacom-ai'); ?> <span class="free-badge">FREE</span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Access Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="text" name="kotacom_ai_unsplash_access_key" value="<?php echo esc_attr(get_option('kotacom_ai_unsplash_access_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your access key from', 'kotacom-ai'); ?>
                                    <a href="https://unsplash.com/oauth/applications" target="_blank">Unsplash Developers</a>
                                    <br><strong><?php _e('Free: 50 requests per hour', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- OpenAI Settings -->
                <div class="api-settings" id="openai-settings" style="display: none;">
                    <h3><?php _e('OpenAI Settings', 'kotacom-ai'); ?> <span class="paid-badge"><?php _e('PAID SERVICE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_openai_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_openai_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your API key from', 'kotacom-ai'); ?> 
                                    <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('OpenAI Platform', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Note: This is a paid service with usage-based pricing', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_openai_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_openai_model', 'gpt-4o-mini');
                                    $openai_models = $api_handler->get_provider_models('openai');
                                    foreach ($openai_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- OpenRouter Settings -->
                <div class="api-settings" id="openrouter-settings" style="display: none;">
                    <h3><?php _e('OpenRouter Settings', 'kotacom-ai'); ?> <span class="paid-badge"><?php _e('PAID SERVICE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_openrouter_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_openrouter_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your API key from', 'kotacom-ai'); ?> 
                                    <a href="https://openrouter.ai/keys" target="_blank"><?php _e('OpenRouter Dashboard', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Note: OpenRouter aggregates various models, pricing varies by model.', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_openrouter_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_openrouter_model', 'mistralai/mistral-7b-instruct');
                                    $openrouter_models = $api_handler->get_provider_models('openrouter');
                                    foreach ($openrouter_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Perplexity AI Settings -->
                <div class="api-settings" id="perplexity-settings" style="display: none;">
                    <h3><?php _e('Perplexity AI Settings', 'kotacom-ai'); ?> <span class="paid-badge"><?php _e('PAID SERVICE', 'kotacom-ai'); ?></span></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="password" name="kotacom_ai_perplexity_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_perplexity_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your API key from', 'kotacom-ai'); ?> 
                                    <a href="https://docs.perplexity.ai/docs/getting-started" target="_blank"><?php _e('Perplexity AI Docs', 'kotacom-ai'); ?></a>
                                    <br><strong><?php _e('Note: This is a paid service with usage-based pricing.', 'kotacom-ai'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                            <td>
                                <select name="kotacom_ai_perplexity_model">
                                    <?php
                                    $current_model = get_option('kotacom_ai_perplexity_model', 'llama-3-sonar-small-32k-online');
                                    $perplexity_models = $api_handler->get_provider_models('perplexity');
                                    foreach ($perplexity_models as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_model, $key); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="api-test-section">
                    <button type="button" id="test-api" class="button button-secondary"><?php _e('Test API Connection', 'kotacom-ai'); ?></button>
                    <span class="spinner"></span>
                    <div id="api-test-result"></div>
                </div>
            </div>
        </div>
        
        <!-- Default Parameters -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Default Content Parameters', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Tone', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_default_tone">
                                <?php
                                $current_tone = get_option('kotacom_ai_default_tone', 'informative');
                                $tones = array(
                                    'informative' => __('Informative', 'kotacom-ai'),
                                    'formal' => __('Formal', 'kotacom-ai'),
                                    'casual' => __('Casual', 'kotacom-ai'),
                                    'persuasive' => __('Persuasive', 'kotacom-ai'),
                                    'creative' => __('Creative', 'kotacom-ai')
                                );
                                foreach ($tones as $key => $name):
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_tone, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Length', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_default_length">
                                <?php
                                $current_length = get_option('kotacom_ai_default_length', '500');
                                $lengths = array(
                                    '300' => __('Short (300 words)', 'kotacom-ai'),
                                    '500' => __('Medium (500 words)', 'kotacom-ai'),
                                    '800' => __('Long (800 words)', 'kotacom-ai'),
                                    '1200' => __('Very Long (1200 words)', 'kotacom-ai')
                                );
                                foreach ($lengths as $key => $name):
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_length, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Audience', 'kotacom-ai'); ?></th>
                        <td>
                            <input type="text" name="kotacom_ai_default_audience" value="<?php echo esc_attr(get_option('kotacom_ai_default_audience', 'general')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- WordPress Settings -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Default WordPress Settings', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Post Type', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_default_post_type">
                                <?php
                                $current_post_type = get_option('kotacom_ai_default_post_type', 'post');
                                $post_types = get_post_types(array('public' => true), 'objects');
                                foreach ($post_types as $post_type):
                                ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($current_post_type, $post_type->name); ?>>
                                    <?php echo esc_html($post_type->label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Post Status', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_default_post_status">
                                <?php
                                $current_status = get_option('kotacom_ai_default_post_status', 'draft');
                                $statuses = array(
                                    'draft' => __('Draft', 'kotacom-ai'),
                                    'publish' => __('Publish', 'kotacom-ai')
                                );
                                foreach ($statuses as $key => $name):
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_status, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Image Generator Settings -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Image Generator Settings', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <!-- Image Settings Help -->
                <div class="info-card">
                    <h3><span class="dashicons dashicons-format-image"></span> <?php _e('Image Generation Guide', 'kotacom-ai'); ?></h3>
                    <p><?php _e('Configure automatic image generation for your content. Multiple providers ensure reliability through fallback system.', 'kotacom-ai'); ?></p>
                    <div class="highlight">
                        <strong><?php _e('🆓 FREE Providers Available:', 'kotacom-ai'); ?></strong><br>
                        • <strong>Lorem Picsum:</strong> <?php _e('No API key needed - always works!', 'kotacom-ai'); ?><br>
                        • <strong>Placeholder.co:</strong> <?php _e('No API key needed - solid color backgrounds', 'kotacom-ai'); ?><br>
                        • <strong>Unsplash:</strong> <?php _e('Beautiful photos - 50 requests/hour free', 'kotacom-ai'); ?><br>
                        • <strong>Pixabay:</strong> <?php _e('Stock photos - 5000 requests/hour free', 'kotacom-ai'); ?><br>
                        • <strong>Pexels:</strong> <?php _e('Quality photos - 200 requests/hour free', 'kotacom-ai'); ?>
                    </div>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Image Provider', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_default_image_provider">
                                <?php
                                $current_provider = get_option('kotacom_ai_default_image_provider', 'unsplash');
                                $image_generator = new KotacomAI_Image_Generator();
                                $image_providers = $image_generator->get_providers();
                                foreach ($image_providers as $key => $name):
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_provider, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Choose your preferred image provider for auto-generated images.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Image Size', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_default_image_size">
                                <?php
                                $current_size = get_option('kotacom_ai_default_image_size', '1200x800');
                                $sizes = array(
                                    '400x300' => __('Small (400x300)', 'kotacom-ai'),
                                    '800x600' => __('Medium (800x600)', 'kotacom-ai'),
                                    '1200x800' => __('Large (1200x800)', 'kotacom-ai'),
                                    '1920x1080' => __('Extra Large (1920x1080)', 'kotacom-ai'),
                                    '800x800' => __('Square (800x800)', 'kotacom-ai'),
                                    '600x800' => __('Portrait (600x800)', 'kotacom-ai')
                                );
                                foreach ($sizes as $key => $name):
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_size, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Auto Generate Featured Images', 'kotacom-ai'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="kotacom_ai_auto_featured_image" value="1" <?php checked(get_option('kotacom_ai_auto_featured_image')); ?>>
                                <?php _e('Automatically generate featured images for new posts', 'kotacom-ai'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <!-- Image Provider API Keys -->
                <h3><?php _e('Image Provider API Keys', 'kotacom-ai'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Unsplash Access Key', 'kotacom-ai'); ?> <span class="free-badge">FREE</span></th>
                        <td>
                            <input type="text" name="kotacom_ai_unsplash_access_key" value="<?php echo esc_attr(get_option('kotacom_ai_unsplash_access_key')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Get your access key from', 'kotacom-ai'); ?>
                                <a href="https://unsplash.com/oauth/applications" target="_blank">Unsplash Developers</a>
                                <br><strong><?php _e('Free: 50 requests per hour', 'kotacom-ai'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Pixabay API Key', 'kotacom-ai'); ?> <span class="free-badge">FREE</span></th>
                        <td>
                            <input type="text" name="kotacom_ai_pixabay_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_pixabay_api_key')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Get your API key from', 'kotacom-ai'); ?>
                                <a href="https://pixabay.com/api/docs/" target="_blank">Pixabay API</a>
                                <br><strong><?php _e('Free: 5000 requests per hour', 'kotacom-ai'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Pexels API Key', 'kotacom-ai'); ?> <span class="free-badge">FREE</span></th>
                        <td>
                            <input type="text" name="kotacom_ai_pexels_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_pexels_api_key')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Get your API key from', 'kotacom-ai'); ?>
                                <a href="https://www.pexels.com/api/" target="_blank">Pexels API</a>
                                <br><strong><?php _e('Free: 200 requests per hour', 'kotacom-ai'); ?></strong>
                            </p>
                        </td>
                    </tr>
                                 </table>
                 
                 <!-- Test Image Providers -->
                 <div class="image-provider-test">
                     <h3><?php _e('Test Image Providers', 'kotacom-ai'); ?></h3>
                     <p class="description"><?php _e('Test your image provider connections to ensure they are working correctly.', 'kotacom-ai'); ?></p>
                     <div class="provider-test-buttons">
                         <button type="button" class="button test-image-provider" data-provider="unsplash"><?php _e('Test Unsplash', 'kotacom-ai'); ?></button>
                         <button type="button" class="button test-image-provider" data-provider="pixabay"><?php _e('Test Pixabay', 'kotacom-ai'); ?></button>
                         <button type="button" class="button test-image-provider" data-provider="pexels"><?php _e('Test Pexels', 'kotacom-ai'); ?></button>
                         <button type="button" class="button test-image-provider" data-provider="picsum"><?php _e('Test Lorem Picsum', 'kotacom-ai'); ?></button>
                         <button type="button" class="button test-image-provider" data-provider="placeholder"><?php _e('Test Placeholder', 'kotacom-ai'); ?></button>
                     </div>
                     <div id="image-provider-test-result"></div>
                 </div>
             </div>
         </div>

        <!-- Queue Settings -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Queue Settings', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Batch Size', 'kotacom-ai'); ?></th>
                        <td>
                            <input type="number" name="kotacom_ai_queue_batch_size" value="<?php echo esc_attr(get_option('kotacom_ai_queue_batch_size', 5)); ?>" min="1" max="20" class="small-text">
                            <p class="description"><?php _e('Number of items to process in each batch. Lower values reduce server load.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Processing Interval', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_queue_processing_interval">
                                <?php
                                $current_interval = get_option('kotacom_ai_queue_processing_interval', 'every_minute');
                                $intervals = array(
                                    'every_minute' => __('Every Minute', 'kotacom-ai'),
                                    'every_five_minutes' => __('Every 5 Minutes', 'kotacom-ai')
                                );
                                foreach ($intervals as $key => $name):
                                ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_interval, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('How often the queue should be processed automatically.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Internal Linking Settings -->
        <div class="postbox">
            <h2 class="hndle">🔗 <?php _e('Internal Linking', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Auto Internal Links', 'kotacom-ai'); ?></th>
                        <td>
                            <input type="checkbox" name="kotacom_ai_internal_link_enable" value="1" <?php checked(get_option('kotacom_ai_internal_link_enable')); ?> />
                            <p class="description"><?php _e('Automatically insert contextual links to related posts when a post is published.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Max Links per Post', 'kotacom-ai'); ?></th>
                        <td>
                            <input type="number" name="kotacom_ai_internal_link_max" value="<?php echo esc_attr(get_option('kotacom_ai_internal_link_max', 5)); ?>" min="1" max="10" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Link Targets', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_internal_link_rule">
                                <option value="tags" <?php selected(get_option('kotacom_ai_internal_link_rule', 'tags'), 'tags'); ?>><?php _e('Same Tags', 'kotacom-ai'); ?></option>
                                <option value="category" <?php selected(get_option('kotacom_ai_internal_link_rule', 'tags'), 'category'); ?>><?php _e('Same Category', 'kotacom-ai'); ?></option>
                                <option value="both" <?php selected(get_option('kotacom_ai_internal_link_rule', 'tags'), 'both'); ?>><?php _e('Tags OR Category', 'kotacom-ai'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Anchor Style', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="kotacom_ai_internal_link_anchor_style">
                                <option value="full" <?php selected(get_option('kotacom_ai_internal_link_anchor_style', 'full'), 'full'); ?>><?php _e('Full Title', 'kotacom-ai'); ?></option>
                                <option value="truncate" <?php selected(get_option('kotacom_ai_internal_link_anchor_style', 'full'), 'truncate'); ?>><?php _e('First 5 Words', 'kotacom-ai'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Keyword Dictionary (keyword,url or keyword,post_id)', 'kotacom-ai'); ?></th>
                        <td>
                            <textarea name="kotacom_ai_internal_link_dict" rows="6" cols="60" placeholder="seo audit,123\nwordpress,https://example.com/wordpress-guide"><?php echo esc_textarea(get_option('kotacom_ai_internal_link_dict', '')); ?></textarea>
                            <p class="description"><?php _e('One per line: keyword,URL-or-PostID. These links override tag/category matching.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<style>
.free-badge {
    background: #46b450;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 10px;
}

.paid-badge {
    background: #dc3232;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 10px;
}

.api-settings {
    margin-top: 20px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.image-provider-test {
    margin-top: 20px;
    padding: 15px;
    background: #f0f8ff;
    border: 1px solid #007cba;
    border-radius: 4px;
}

.provider-test-buttons {
    margin: 15px 0;
}

.provider-test-buttons .button {
    margin-right: 10px;
    margin-bottom: 5px;
}

.test-image-provider.testing {
    opacity: 0.6;
}

#image-provider-test-result {
    margin-top: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Show/hide API settings based on selected provider
    $('#api-provider').on('change', function() {
        var provider = $(this).val();
        $('.api-settings').hide();
        $('#' + provider + '-settings').show();
    });
    
    // Initialize with current provider
    $('#api-provider').trigger('change');
    
    // Test API connection
    $('#test-api').on('click', function() {
        var $btn = $(this);
        var $spinner = $('.api-test-section .spinner');
        var $result = $('#api-test-result');
        
        var provider = $('#api-provider').val();
        var apiKey = $('input[name="kotacom_ai_' + provider + '_api_key"]').val();
        
        if (!apiKey) {
            $result.html('<div class="notice notice-error inline"><p><?php _e('Please enter an API key first.', 'kotacom-ai'); ?></p></div>');
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.empty();
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_test_api',
                nonce: kotacomAI.nonce,
                provider: provider,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p><?php _e('API connection successful!', 'kotacom-ai'); ?></p></div>');
                } else {
                    $result.html('<div class="notice notice-error inline"><p><?php _e('API connection failed:', 'kotacom-ai'); ?> ' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p><?php _e('Connection test failed. Please try again.', 'kotacom-ai'); ?></p></div>');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Test image providers
    $('.test-image-provider').on('click', function() {
        var $btn = $(this);
        var provider = $btn.data('provider');
        var $result = $('#image-provider-test-result');
        
        if ($btn.hasClass('testing')) return;
        
        $btn.addClass('testing').prop('disabled', true).text('Testing...');
        $result.empty();
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_test_image_provider',
                nonce: kotacomAI.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p><strong>' + provider.toUpperCase() + ':</strong> ' + response.data.message + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error inline"><p><strong>' + provider.toUpperCase() + ':</strong> ' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p><strong>' + provider.toUpperCase() + ':</strong> Connection test failed. Please try again.</p></div>');
            },
            complete: function() {
                $btn.removeClass('testing').prop('disabled', false).text('Test ' + provider.charAt(0).toUpperCase() + provider.slice(1));
            }
        });
    });
});
</script>
