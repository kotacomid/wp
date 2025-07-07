# Kotacom AI Image Generator Guide

## Overview

The Kotacom AI Image Generator is a powerful feature that supports multiple FREE image providers with automatic fallback handling. Generate high-quality images for your content using various APIs and services.

## Supported Providers

### FREE Providers
- **Unsplash** - High-quality stock photography (50 requests/hour)
- **Pixabay** - Diverse image collection (5,000 requests/hour)
- **Pexels** - Professional stock photos (200 requests/hour)
- **Lorem Picsum** - Beautiful placeholder images (unlimited)
- **Placeholder.co** - Custom text placeholders (unlimited, fallback)

## Configuration

### 1. API Keys Setup

Go to **Kotacom AI → Settings → Image Generator Settings**:

1. **Unsplash**: Get free access key from [Unsplash Developers](https://unsplash.com/oauth/applications)
2. **Pixabay**: Get free API key from [Pixabay API](https://pixabay.com/api/docs/)
3. **Pexels**: Get free API key from [Pexels API](https://www.pexels.com/api/)

### 2. Default Settings

- **Default Image Provider**: Choose your preferred provider
- **Default Image Size**: Select from predefined sizes
- **Auto Generate Featured Images**: Enable automatic featured image generation

## Shortcode Usage

### Basic Shortcode
```
[ai_image prompt="sunset over mountains"]
```

### Advanced Shortcode
```
[ai_image prompt="modern office workspace" size="1200x800" provider="unsplash" featured="yes" class="hero-image" caption="Modern workspace design" align="center"]
```

### Shortcode Parameters

| Parameter | Description | Default | Options |
|-----------|-------------|---------|---------|
| `prompt` | Image description/keyword | Required | Any text |
| `size` | Image dimensions | `800x600` | `400x300`, `800x600`, `1200x800`, `1920x1080`, `800x800`, `600x800` |
| `provider` | Image provider | `unsplash` | `unsplash`, `pixabay`, `pexels`, `picsum`, `placeholder` |
| `featured` | Set as featured image | `no` | `yes`, `no` |
| `alt` | Custom alt text | Auto-generated | Any text |
| `class` | CSS class | `ai-generated-image` | Any CSS class |
| `caption` | Image caption | None | Any text |
| `align` | Image alignment | `none` | `left`, `center`, `right`, `none` |
| `fallback` | Enable fallback providers | `yes` | `yes`, `no` |

## Template Integration

### Template Editor Settings

In the Template Editor, configure image generation:

1. **Auto-generate images in content**: Enable automatic image insertion
2. **Default Image Provider**: Set provider for template
3. **Default Image Size**: Set size for template images
4. **Generate featured image for posts**: Auto-create featured images

### Template Variables

Use these variables in your templates:

- `{keyword}` - Current keyword/topic
- `{title}` - Post title
- `{current_content}` - Existing content

### Example Template Usage

```html
<h1>{keyword}</h1>

[ai_image prompt="{keyword}" size="1200x800" featured="yes" provider="unsplash"]

<p>Introduction about {keyword}...</p>

[ai_image prompt="benefits of {keyword}" size="800x600" caption="Key benefits visualization"]

<h2>How to Use {keyword}</h2>
<p>Step-by-step guide...</p>
```

## Advanced Features

### 1. Provider Fallback System

The system automatically tries alternative providers if the primary fails:

```
Primary (Unsplash) → Pixabay → Pexels → Lorem Picsum → Placeholder
```

### 2. AI-Generated Alt Text

All images automatically get SEO-friendly alt text using AI:

- Describes visual elements
- Includes keywords
- Optimized for accessibility
- Max 15 words for best SEO

### 3. Hero Image Generation

Generate featured images for posts:

1. Go to **Posts → All Posts**
2. Hover over any post
3. Click **"Generate Hero Image"**
4. Image is automatically set as featured

### 4. Automatic Featured Images

Enable in settings to automatically generate featured images for new posts using the post title as the prompt.

## API Limits & Recommendations

### Free Tier Limits

| Provider | Requests/Hour | Requests/Day | Monthly |
|----------|---------------|--------------|---------|
| Unsplash | 50 | 1,000 | 50,000 |
| Pixabay | 5,000 | 20,000 | 100,000 |
| Pexels | 200 | 3,000 | 50,000 |
| Lorem Picsum | Unlimited | Unlimited | Unlimited |

### Best Practices

1. **Use Descriptive Prompts**: "modern office workspace" vs "office"
2. **Enable Fallback**: Always keep fallback enabled for reliability
3. **Monitor Usage**: Check provider test results regularly
4. **Optimize Keywords**: Use specific, visual keywords for better results

## Troubleshooting

### Common Issues

1. **"API key not configured"**
   - Solution: Add API keys in Settings → Image Generator Settings

2. **"Provider did not return any images"**
   - Solution: Try different keywords or enable fallback

3. **"Rate limit exceeded"**
   - Solution: Wait or switch to different provider

4. **"Failed to set featured image"**
   - Solution: Check user permissions for file uploads

### Testing Providers

Use the provider test buttons in Settings to verify connections:

1. Go to **Kotacom AI → Settings**
2. Scroll to **"Test Image Providers"**
3. Click test buttons for each provider
4. Fix any connection issues

## Integration Examples

### Blog Post Template

```html
<h1>{keyword}: Complete Guide</h1>

[ai_image prompt="{keyword} overview" size="1200x800" featured="yes" provider="unsplash"]

<p>Everything you need to know about {keyword}...</p>

<h2>What is {keyword}?</h2>
<p>Detailed explanation...</p>

[ai_image prompt="examples of {keyword}" size="800x600" align="center" caption="Real-world examples"]

<h2>Benefits of {keyword}</h2>
<ul>
<li>Benefit 1</li>
<li>Benefit 2</li>
</ul>

[ai_image prompt="benefits of {keyword}" size="800x400" class="benefits-image"]
```

### Product Review Template

```html
<h1>{keyword} Review: Detailed Analysis</h1>

[ai_image prompt="{keyword} product review" size="1200x800" featured="yes"]

<h2>Product Overview</h2>
<p>In-depth look at {keyword}...</p>

[ai_image prompt="{keyword} features" size="800x600" caption="Key features highlighted"]

<h2>Pros and Cons</h2>
<h3>Pros:</h3>
<ul><li>Pro 1</li></ul>

<h3>Cons:</h3>
<ul><li>Con 1</li></ul>

[ai_image prompt="{keyword} comparison" size="800x400"]
```

## Performance Tips

1. **Choose Right Provider**: Unsplash for professional, Pixabay for variety
2. **Optimize Sizes**: Use appropriate dimensions for your theme
3. **Cache Images**: WordPress automatically caches downloaded images
4. **Use CDN**: Consider CDN for faster image delivery
5. **Compress Images**: Use image optimization plugins

## Support

For issues or questions:

1. Check provider test results in Settings
2. Review error logs in **Kotacom AI → Logs**
3. Verify API key configurations
4. Test with simple prompts first
5. Enable fallback for reliability

## Changelog

### Version 1.3.0
- Added multi-provider support
- Implemented shortcode system
- Added fallback handling
- Integrated template settings
- Added provider testing
- Enhanced logging
- Improved error handling