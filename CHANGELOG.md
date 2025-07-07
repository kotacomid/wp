## [1.3.0] - 2025-07-07

### Added - Enhanced Image Generator
- **Multi-Provider Support**: 5 FREE image providers (Unsplash, Pixabay, Pexels, Lorem Picsum, Placeholder.co)
- **Advanced Shortcode**: `[ai_image prompt="..." size="1200x800" provider="unsplash" featured="yes" class="hero-image" caption="..." align="center" fallback="yes"]`
- **Provider Fallback System**: Automatic failover between providers for reliability
- **AI-Generated Alt Text**: SEO-friendly alt text generation using AI for accessibility
- **Image Provider Settings**: Dedicated settings section with API key management and testing
- **Provider Connection Testing**: Built-in test buttons for all image providers
- **Template Integration**: Image generation settings integrated into Template Editor
- **Auto Featured Images**: Option to automatically generate featured images for new posts
- **Enhanced Image Logging**: Detailed logging for all image generation operations
- **Placeholder Fallback**: Custom text placeholders using Placeholder.co as final fallback

### Added - Other Features  
- Hero-Image one–click row-action in Posts list (AJAX, sets featured image)
- Content Refresh admin page (bulk AI rewrite / update via `{current_content}` placeholder)
- Smart scheduling: `datetime-local` field in Generator → schedules future publication
- Multi-source image generation with comprehensive error handling

### Changed
- `ajax_generate_image` enhanced with provider parameter and fallback support
- Template-Manager registers new shortcodes and blocks
- Image generation now uses configurable default provider and size settings

### Fixed / Improved
- UI busy indicators & alerts
- Settings page enhanced with image provider configuration
- Comprehensive error handling for image generation
- Provider test functionality for troubleshooting
- Enhanced logging system for image operations