const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { 
    PanelBody, 
    TextControl, 
    SelectControl, 
    Button, 
    ToggleControl,
    Spinner,
    Notice
} = wp.components;
const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { Component } = wp.element;

class AIImageBlock extends Component {
    constructor(props) {
        super(props);
        this.state = {
            isGenerating: false,
            error: null
        };
    }

    generateImage = () => {
        const { attributes, setAttributes } = this.props;
        const { prompt, size, provider, fallback } = attributes;

        if (!prompt.trim()) {
            this.setState({ error: __('Please enter an image prompt', 'kotacom-ai') });
            return;
        }

        this.setState({ isGenerating: true, error: null });

        wp.apiFetch({
            path: '/wp-admin/admin-ajax.php',
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'kotacom_generate_image',
                nonce: kotacomAI.nonce,
                prompt: prompt,
                size: size,
                provider: provider,
                fallback: fallback ? 'yes' : 'no'
            })
        }).then(response => {
            if (response.success) {
                setAttributes({ 
                    imageUrl: response.data.url,
                    imageId: response.data.attachment_id || 0,
                    alt: response.data.alt
                });
            } else {
                this.setState({ error: response.data.message || __('Image generation failed', 'kotacom-ai') });
            }
        }).catch(error => {
            this.setState({ error: __('Network error occurred', 'kotacom-ai') });
        }).finally(() => {
            this.setState({ isGenerating: false });
        });
    }

    render() {
        const { attributes, setAttributes } = this.props;
        const { prompt, size, provider, alt, caption, featured, fallback, imageUrl } = attributes;
        const { isGenerating, error } = this.state;

        const sizeOptions = [
            { label: __('Small (400x300)', 'kotacom-ai'), value: '400x300' },
            { label: __('Medium (800x600)', 'kotacom-ai'), value: '800x600' },
            { label: __('Large (1200x800)', 'kotacom-ai'), value: '1200x800' },
            { label: __('Extra Large (1920x1080)', 'kotacom-ai'), value: '1920x1080' },
            { label: __('Square (800x800)', 'kotacom-ai'), value: '800x800' },
            { label: __('Portrait (600x800)', 'kotacom-ai'), value: '600x800' }
        ];

        const providerOptions = [
            { label: __('Unsplash (FREE)', 'kotacom-ai'), value: 'unsplash' },
            { label: __('Pixabay (FREE)', 'kotacom-ai'), value: 'pixabay' },
            { label: __('Pexels (FREE)', 'kotacom-ai'), value: 'pexels' },
            { label: __('Lorem Picsum (FREE)', 'kotacom-ai'), value: 'picsum' },
            { label: __('Placeholder (Fallback)', 'kotacom-ai'), value: 'placeholder' }
        ];

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('AI Image Settings', 'kotacom-ai')} initialOpen={true}>
                        <TextControl
                            label={__('Image Prompt', 'kotacom-ai')}
                            value={prompt}
                            onChange={(value) => setAttributes({ prompt: value })}
                            placeholder={__('Describe the image you want...', 'kotacom-ai')}
                            help={__('Be descriptive for better results', 'kotacom-ai')}
                        />

                        <SelectControl
                            label={__('Image Size', 'kotacom-ai')}
                            value={size}
                            options={sizeOptions}
                            onChange={(value) => setAttributes({ size: value })}
                        />

                        <SelectControl
                            label={__('Provider', 'kotacom-ai')}
                            value={provider}
                            options={providerOptions}
                            onChange={(value) => setAttributes({ provider: value })}
                            help={__('All providers are FREE with generous limits', 'kotacom-ai')}
                        />

                        <TextControl
                            label={__('Custom Alt Text', 'kotacom-ai')}
                            value={alt}
                            onChange={(value) => setAttributes({ alt: value })}
                            placeholder={__('Auto-generated if empty', 'kotacom-ai')}
                            help={__('Leave empty for AI-generated alt text', 'kotacom-ai')}
                        />

                        <TextControl
                            label={__('Caption', 'kotacom-ai')}
                            value={caption}
                            onChange={(value) => setAttributes({ caption: value })}
                            placeholder={__('Optional image caption', 'kotacom-ai')}
                        />

                        <ToggleControl
                            label={__('Set as Featured Image', 'kotacom-ai')}
                            checked={featured}
                            onChange={(value) => setAttributes({ featured: value })}
                            help={__('Automatically set as post featured image', 'kotacom-ai')}
                        />

                        <ToggleControl
                            label={__('Enable Provider Fallback', 'kotacom-ai')}
                            checked={fallback}
                            onChange={(value) => setAttributes({ fallback: value })}
                            help={__('Try other providers if primary fails', 'kotacom-ai')}
                        />

                        <Button
                            variant="primary"
                            onClick={this.generateImage}
                            disabled={isGenerating || !prompt.trim()}
                            style={{ marginTop: '10px' }}
                        >
                            {isGenerating && <Spinner />}
                            {isGenerating ? __('Generating...', 'kotacom-ai') : __('Generate Image', 'kotacom-ai')}
                        </Button>
                    </PanelBody>
                </InspectorControls>

                <div className="kotacom-ai-image-block">
                    {error && (
                        <Notice status="error" isDismissible={false}>
                            {error}
                        </Notice>
                    )}

                    {!imageUrl && !isGenerating && (
                        <div className="block-placeholder">
                            <div className="block-placeholder-icon">🖼️</div>
                            <h3>{__('AI Image Block', 'kotacom-ai')}</h3>
                            <p>{__('Configure settings in the sidebar and click "Generate Image" to create AI-powered images.', 'kotacom-ai')}</p>
                            {prompt && (
                                <p><strong>{__('Prompt:', 'kotacom-ai')}</strong> {prompt}</p>
                            )}
                        </div>
                    )}

                    {isGenerating && (
                        <div className="block-placeholder">
                            <Spinner />
                            <p>{__('Generating AI image...', 'kotacom-ai')}</p>
                        </div>
                    )}

                    {imageUrl && (
                        <figure className="wp-block-image">
                            <img
                                src={imageUrl}
                                alt={alt || prompt}
                                className="ai-generated-image"
                            />
                            {caption && (
                                <figcaption className="wp-element-caption">
                                    {caption}
                                </figcaption>
                            )}
                        </figure>
                    )}
                </div>
            </>
        );
    }
}

registerBlockType('kotacom-ai/ai-image', {
    edit: AIImageBlock,
    save: ({ attributes }) => {
        const { imageUrl, alt, caption, prompt } = attributes;
        
        if (!imageUrl) {
            return null;
        }

        return (
            <figure className="wp-block-image kotacom-ai-image-block">
                <img
                    src={imageUrl}
                    alt={alt || prompt}
                    className="ai-generated-image"
                />
                {caption && (
                    <figcaption className="wp-element-caption">
                        {caption}
                    </figcaption>
                )}
            </figure>
        );
    }
});