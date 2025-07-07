const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { 
    PanelBody, 
    TextControl, 
    SelectControl, 
    TextareaControl, 
    Button, 
    ToggleControl,
    Spinner,
    Notice
} = wp.components;
const { InspectorControls, RichText } = wp.blockEditor;
const { Component } = wp.element;
const { select } = wp.data;

class AIContentBlock extends Component {
    constructor(props) {
        super(props);
        this.state = {
            isGenerating: false,
            error: null,
            prompts: []
        };
    }

    componentDidMount() {
        this.loadPrompts();
    }

    loadPrompts = () => {
        wp.apiFetch({
            path: '/wp/v2/kotacom_template',
            method: 'GET'
        }).then(prompts => {
            this.setState({ prompts });
        }).catch(error => {
            console.error('Failed to load prompts:', error);
        });
    }

    generateContent = () => {
        const { attributes, setAttributes } = this.props;
        const { keyword, prompt, tone, length, audience } = attributes;

        if (!keyword.trim()) {
            this.setState({ error: __('Please enter a keyword', 'kotacom-ai') });
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
                action: 'kotacom_generate_content_block',
                nonce: kotacomAI.nonce,
                keyword: keyword,
                prompt: prompt,
                tone: tone,
                length: length,
                audience: audience
            })
        }).then(response => {
            if (response.success) {
                setAttributes({ generated_content: response.data.content });
            } else {
                this.setState({ error: response.data.message || __('Generation failed', 'kotacom-ai') });
            }
        }).catch(error => {
            this.setState({ error: __('Network error occurred', 'kotacom-ai') });
        }).finally(() => {
            this.setState({ isGenerating: false });
        });
    }

    render() {
        const { attributes, setAttributes } = this.props;
        const { keyword, prompt, tone, length, audience, generated_content, auto_generate } = attributes;
        const { isGenerating, error, prompts } = this.state;

        const toneOptions = [
            { label: __('Informative', 'kotacom-ai'), value: 'informative' },
            { label: __('Formal', 'kotacom-ai'), value: 'formal' },
            { label: __('Casual', 'kotacom-ai'), value: 'casual' },
            { label: __('Persuasive', 'kotacom-ai'), value: 'persuasive' },
            { label: __('Creative', 'kotacom-ai'), value: 'creative' }
        ];

        const lengthOptions = [
            { label: __('Short (300 words)', 'kotacom-ai'), value: '300' },
            { label: __('Medium (500 words)', 'kotacom-ai'), value: '500' },
            { label: __('Long (800 words)', 'kotacom-ai'), value: '800' },
            { label: __('Very Long (1200 words)', 'kotacom-ai'), value: '1200' }
        ];

        const promptOptions = [
            { label: __('Custom Prompt', 'kotacom-ai'), value: '' },
            ...prompts.map(p => ({ label: p.title.rendered, value: p.id }))
        ];

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('AI Content Settings', 'kotacom-ai')} initialOpen={true}>
                        <TextControl
                            label={__('Keyword', 'kotacom-ai')}
                            value={keyword}
                            onChange={(value) => setAttributes({ keyword: value })}
                            placeholder={__('Enter your keyword...', 'kotacom-ai')}
                        />

                        <SelectControl
                            label={__('Template/Prompt', 'kotacom-ai')}
                            value={prompt}
                            options={promptOptions}
                            onChange={(value) => setAttributes({ prompt: value })}
                        />

                        <SelectControl
                            label={__('Tone', 'kotacom-ai')}
                            value={tone}
                            options={toneOptions}
                            onChange={(value) => setAttributes({ tone: value })}
                        />

                        <SelectControl
                            label={__('Length', 'kotacom-ai')}
                            value={length}
                            options={lengthOptions}
                            onChange={(value) => setAttributes({ length: value })}
                        />

                        <TextControl
                            label={__('Target Audience', 'kotacom-ai')}
                            value={audience}
                            onChange={(value) => setAttributes({ audience: value })}
                            placeholder={__('e.g., beginners, professionals', 'kotacom-ai')}
                        />

                        <ToggleControl
                            label={__('Auto-generate on save', 'kotacom-ai')}
                            checked={auto_generate}
                            onChange={(value) => setAttributes({ auto_generate: value })}
                        />

                        <Button
                            variant="primary"
                            onClick={this.generateContent}
                            disabled={isGenerating || !keyword.trim()}
                            style={{ marginTop: '10px' }}
                        >
                            {isGenerating && <Spinner />}
                            {isGenerating ? __('Generating...', 'kotacom-ai') : __('Generate Content', 'kotacom-ai')}
                        </Button>
                    </PanelBody>
                </InspectorControls>

                <div className="kotacom-ai-content-block">
                    {error && (
                        <Notice status="error" isDismissible={false}>
                            {error}
                        </Notice>
                    )}

                    {!generated_content && !isGenerating && (
                        <div className="block-placeholder">
                            <div className="block-placeholder-icon">🤖</div>
                            <h3>{__('AI Content Block', 'kotacom-ai')}</h3>
                            <p>{__('Configure settings in the sidebar and click "Generate Content" to create AI-powered content.', 'kotacom-ai')}</p>
                            {keyword && (
                                <p><strong>{__('Keyword:', 'kotacom-ai')}</strong> {keyword}</p>
                            )}
                        </div>
                    )}

                    {isGenerating && (
                        <div className="block-placeholder">
                            <Spinner />
                            <p>{__('Generating AI content...', 'kotacom-ai')}</p>
                        </div>
                    )}

                    {generated_content && (
                        <RichText
                            tagName="div"
                            value={generated_content}
                            onChange={(value) => setAttributes({ generated_content: value })}
                            placeholder={__('Generated content will appear here...', 'kotacom-ai')}
                            className="ai-generated-content"
                        />
                    )}
                </div>
            </>
        );
    }
}

registerBlockType('kotacom-ai/ai-content', {
    edit: AIContentBlock,
    save: ({ attributes }) => {
        const { generated_content } = attributes;
        return (
            <div className="kotacom-ai-content-block">
                <RichText.Content value={generated_content} />
            </div>
        );
    }
});