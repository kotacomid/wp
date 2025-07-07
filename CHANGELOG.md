## [1.3.0] - 2025-07-07
### Added
- Multi-source AI Image generator (OpenAI DALL·E 3, Unsplash Random, Replicate SDXL)
- `[ai_image]` shortcode & Gutenberg block with optional `featured="yes"`
- Hero-Image one–click row-action in Posts list (AJAX, sets featured image)
- Content Refresh admin page (bulk AI rewrite / update via `{current_content}` placeholder)
- Smart scheduling: `datetime-local` field in Generator → schedules future publication

### Changed
- `ajax_generate_image` accepts `provider` parameter
- Template-Manager registers new shortcodes and blocks

### Fixed / Improved
- UI busy indicators & alerts
- Settings page now accepts Unsplash Access Key