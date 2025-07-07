# Kotacom AI Complete Guide

## 🚀 Plugin Overview

Kotacom AI Content Generator is a comprehensive WordPress plugin that provides AI-powered content generation with multiple providers, advanced image generation, and Gutenberg blocks integration.

## 📋 Features Summary

### ✅ Content Generation
- **Multiple AI Providers**: Google AI, OpenAI, Groq, Anthropic, Cohere, Hugging Face, Together AI, Replicate
- **Smart Fallback System**: Automatic provider switching if primary fails
- **Bulk Processing**: Queue system for multiple posts
- **Template System**: Reusable content templates
- **Smart Scheduling**: Schedule posts for future publication

### ✅ Image Generation
- **5 FREE Providers**: Unsplash, Pixabay, Pexels, Lorem Picsum, Placeholder.co
- **Provider Fallback**: Automatic failover between providers
- **AI Alt Text**: SEO-friendly alt text generation
- **Featured Images**: Auto-set featured images
- **Multiple Sizes**: Various dimensions available

### ✅ Gutenberg Blocks
- **AI Content Block**: Generate content directly in editor
- **AI Image Block**: Generate images with live preview
- **Template Integration**: Use existing templates in blocks

### ✅ Advanced Features
- **Content Refresh**: Update existing posts with AI
- **Logging System**: Track all operations with statistics
- **Template Editor**: Visual template builder
- **Queue Management**: Background processing for bulk operations

---

## 🎯 Shortcodes Reference

### 1. AI Content Shortcode

Generate AI-powered content using keywords and templates.

#### Basic Usage
```
[ai_content keyword="WordPress SEO"]
```

#### Advanced Usage
```
[ai_content keyword="WordPress SEO" prompt="Blog Article" tone="informative" length="800" audience="beginners"]
```

#### Parameters
| Parameter | Description | Default | Options |
|-----------|-------------|---------|---------|
| `keyword` | Main topic/keyword | Required | Any text |
| `prompt` | Template name | Default | Template names |
| `tone` | Writing tone | `informative` | `informative`, `formal`, `casual`, `persuasive`, `creative` |
| `length` | Word count | `500` | `300`, `500`, `800`, `1200` |
| `audience` | Target audience | `general` | Any text |

#### Examples
```
[ai_content keyword="Digital Marketing" tone="professional" length="1200"]
[ai_content keyword="Cooking Tips" prompt="How-to Guide" audience="beginners"]
[ai_content keyword="Investment" tone="formal" length="800" audience="professionals"]
```

### 2. AI Image Shortcode

Generate images using multiple FREE providers with fallback support.

#### Basic Usage
```
[ai_image prompt="sunset over mountains"]
```

#### Advanced Usage
```
[ai_image prompt="modern office workspace" size="1200x800" provider="unsplash" featured="yes" class="hero-image" caption="Modern workspace design" align="center"]
```

#### Parameters
| Parameter | Description | Default | Options |
|-----------|-------------|---------|---------|
| `prompt` | Image description | Required | Any text |
| `size` | Image dimensions | `800x600` | `400x300`, `800x600`, `1200x800`, `1920x1080`, `800x800`, `600x800` |
| `provider` | Image provider | `unsplash` | `unsplash`, `pixabay`, `pexels`, `picsum`, `placeholder` |
| `featured` | Set as featured | `no` | `yes`, `no` |
| `alt` | Custom alt text | Auto-generated | Any text |
| `class` | CSS class | `ai-generated-image` | Any CSS class |
| `caption` | Image caption | None | Any text |
| `align` | Image alignment | `none` | `left`, `center`, `right`, `none` |
| `fallback` | Enable fallback | `yes` | `yes`, `no` |

#### Examples
```
[ai_image prompt="coffee shop interior" size="800x600" provider="unsplash"]
[ai_image prompt="business meeting" featured="yes" caption="Team collaboration"]
[ai_image prompt="technology concept" size="1200x800" class="tech-image" align="center"]
```

### 3. AI Section Shortcode

Create structured content sections with AI generation.

#### Usage
```
[ai_section title="Introduction" keyword="WordPress" prompt="Blog Article"]
Content will be generated here
[/ai_section]
```

#### Parameters
| Parameter | Description | Default |
|-----------|-------------|---------|
| `title` | Section title | None |
| `keyword` | Topic keyword | Required |
| `prompt` | Template to use | Default |
| `tone` | Writing tone | `informative` |
| `length` | Section length | `200` |

#### Examples
```
[ai_section title="What is SEO?" keyword="SEO" length="300"]
[ai_section title="Benefits" keyword="WordPress" tone="persuasive"]
```

### 4. AI List Shortcode

Generate AI-powered lists and bullet points.

#### Usage
```
[ai_list prompt="5 benefits of WordPress" type="ul" length="5"]
```

#### Parameters
| Parameter | Description | Default |
|-----------|-------------|---------|
| `prompt` | List description | Required |
| `type` | List type | `ul` |
| `length` | Number of items | `5` |
| `keyword` | Related keyword | None |

#### Examples
```
[ai_list prompt="Top 10 SEO tips" type="ol" length="10"]
[ai_list prompt="WordPress features" type="ul" keyword="WordPress"]
```

### 5. AI Conditional Shortcode

Display content based on conditions.

#### Usage
```
[ai_conditional if="post_type" equals="product"]
This content shows only for products
[/ai_conditional]
```

#### Parameters
| Parameter | Description | Options |
|-----------|-------------|---------|
| `if` | Condition type | `post_type`, `category`, `tag` |
| `equals` | Value to match | Any value |

---

## 🔧 Admin Interface

### Main Menu: "Kotacom AI"

#### 1. **Generator** (`/wp-admin/admin.php?page=kotacom-ai`)
- Generate single or bulk content
- Select keywords, templates, and parameters
- Real-time generation or queue processing
- Smart scheduling with date picker

#### 2. **Keywords** (`/wp-admin/admin.php?page=kotacom-ai-keywords`)
- Manage content keywords
- Add/edit/delete keywords
- Tag organization
- Bulk import from CSV

#### 3. **Templates** (`/wp-admin/admin.php?page=kotacom-ai-templates`)
- Visual template builder
- Drag & drop components
- Template preview
- Variable system

#### 4. **Queue** (`/wp-admin/admin.php?page=kotacom-ai-queue`)
- Monitor background processing
- View pending/completed items
- Retry failed operations
- Batch management

#### 5. **Refresh** (`/wp-admin/admin.php?page=kotacom-ai-refresh`)
- Update existing posts with AI
- Filter by date ranges
- Template-based refresh
- Bulk processing with progress

#### 6. **Logs** (`/wp-admin/admin.php?page=kotacom-ai-logs`)
- Operation history
- Success/failure tracking
- Filter by action type
- Statistics dashboard

#### 7. **Settings** (`/wp-admin/admin.php?page=kotacom-ai-settings`)
- AI provider configuration
- Image provider settings
- Default parameters
- API key management

---

## 🎨 Gutenberg Blocks

### AI Content Block

Generate content directly in the WordPress editor.

#### Features:
- Real-time content generation
- Template selection
- Parameter configuration
- Auto-save on generation

#### Usage:
1. Add "AI Content" block
2. Enter keyword in sidebar
3. Configure settings
4. Click "Generate Content"

### AI Image Block

Generate images with live preview in editor.

#### Features:
- Multiple provider support
- Size selection
- Featured image option
- Caption support

#### Usage:
1. Add "AI Image" block
2. Enter image prompt
3. Select provider and size
4. Click "Generate Image"

---

## ⚙️ Configuration

### AI Providers Setup

#### Free Providers (Recommended):
1. **Google AI (Gemini)** - 15 requests/minute, 1500/day
2. **Groq** - Ultra-fast inference
3. **Hugging Face** - 1000 requests/month
4. **Together AI** - $5 free credit
5. **Anthropic** - $5 free credit

#### Paid Providers:
1. **OpenAI** - Usage-based pricing
2. **OpenRouter** - Varies by model
3. **Perplexity** - Usage-based pricing

### Image Providers Setup

#### All FREE Providers:
1. **Unsplash** - 50 requests/hour
2. **Pixabay** - 5000 requests/hour
3. **Pexels** - 200 requests/hour
4. **Lorem Picsum** - Unlimited
5. **Placeholder.co** - Unlimited (fallback)

---

## 📝 Template System

### Template Variables

Use these variables in your templates:

| Variable | Description | Example |
|----------|-------------|---------|
| `{keyword}` | Main keyword | WordPress |
| `{title}` | Post title | How to Use WordPress |
| `{current_content}` | Existing content | (for refresh) |
| `{published_date}` | Publication date | January 1, 2024 |

### Template Examples

#### Blog Article Template
```html
<h1>{keyword}: Complete Guide</h1>

[ai_image prompt="{keyword} overview" size="1200x800" featured="yes"]

<p>Everything you need to know about {keyword}...</p>

<h2>What is {keyword}?</h2>
[ai_content keyword="{keyword}" length="300" tone="informative"]

<h2>Benefits of {keyword}</h2>
[ai_list prompt="benefits of {keyword}" type="ul" length="5"]

<h2>How to Use {keyword}</h2>
[ai_content keyword="{keyword}" length="400" tone="instructional"]

[ai_image prompt="{keyword} tutorial" size="800x600" caption="Step-by-step guide"]

<h2>Conclusion</h2>
[ai_content keyword="{keyword}" length="200" tone="conclusive"]
```

#### Product Review Template
```html
<h1>{keyword} Review: Detailed Analysis</h1>

[ai_image prompt="{keyword} product review" size="1200x800" featured="yes"]

<h2>Product Overview</h2>
[ai_content keyword="{keyword}" length="300" tone="objective"]

<h2>Key Features</h2>
[ai_list prompt="features of {keyword}" type="ul" length="6"]

[ai_image prompt="{keyword} features" size="800x600" caption="Key features highlighted"]

<h2>Pros and Cons</h2>
<h3>Pros:</h3>
[ai_list prompt="advantages of {keyword}" type="ul" length="4"]

<h3>Cons:</h3>
[ai_list prompt="disadvantages of {keyword}" type="ul" length="3"]

<h2>Final Verdict</h2>
[ai_content keyword="{keyword}" length="250" tone="conclusive"]
```

---

## 🔄 Workflow Examples

### Single Post Generation
1. Go to **Kotacom AI → Generator**
2. Enter keyword
3. Select template
4. Configure parameters
5. Click "Generate Post"

### Bulk Content Creation
1. Go to **Kotacom AI → Generator**
2. Enter multiple keywords (one per line)
3. Select template
4. Choose "Bulk Generation"
5. Monitor in **Queue** page

### Content Refresh
1. Go to **Kotacom AI → Refresh**
2. Filter posts by date/category
3. Select posts to update
4. Choose refresh template
5. Click "Run Refresh"

### Hero Image Generation
1. Go to **Posts → All Posts**
2. Hover over any post
3. Click "Generate Hero Image"
4. Image auto-set as featured

---

## 🎛️ API Limits & Recommendations

### Free Tier Limits

| Provider | Requests/Hour | Requests/Day | Monthly |
|----------|---------------|--------------|---------|
| Google AI | 15/min | 1,500 | 45,000 |
| Groq | High | Very High | Very High |
| Hugging Face | Variable | Variable | 1,000 |
| Unsplash | 50 | 1,000 | 50,000 |
| Pixabay | 5,000 | 20,000 | 100,000 |
| Pexels | 200 | 3,000 | 50,000 |

### Best Practices
1. **Start with Free Providers**: Google AI + Groq for content, Unsplash + Pixabay for images
2. **Enable Fallbacks**: Always keep fallback enabled for reliability
3. **Monitor Usage**: Check logs regularly
4. **Use Templates**: Reuse successful templates
5. **Optimize Keywords**: Use specific, descriptive keywords

---

## 🐛 Troubleshooting

### Common Issues

#### "API key not configured"
- **Solution**: Add API keys in Settings → AI Provider Settings

#### "Provider did not return content"
- **Solution**: Try different keywords or enable fallback

#### "Rate limit exceeded"
- **Solution**: Wait or switch to different provider

#### "Failed to generate image"
- **Solution**: Check provider API keys or try different provider

#### Gutenberg blocks not showing
- **Solution**: Clear cache, check WordPress version (5.0+)

### Debug Mode
Enable debug logging by adding to `wp-config.php`:
```php
define('KOTACOM_AI_DEBUG', true);
```

Check logs in **Kotacom AI → Logs** for detailed error information.

---

## 🚀 Quick Start

### 1. Initial Setup (5 minutes)
1. Install and activate plugin
2. Go to **Settings** → add Google AI API key
3. Add Unsplash access key for images
4. Test connections

### 2. Create First Content (2 minutes)
1. Go to **Generator**
2. Enter keyword "WordPress Tips"
3. Click "Generate Post"
4. Review and publish

### 3. Try Gutenberg Blocks (3 minutes)
1. Create new post
2. Add "AI Content" block
3. Enter keyword and generate
4. Add "AI Image" block
5. Generate matching image

### 4. Bulk Generation (5 minutes)
1. Go to **Templates** → create template
2. Go to **Generator**
3. Enter multiple keywords
4. Select template
5. Run bulk generation

---

## 📊 Performance Tips

1. **Choose Right Provider**: Google AI for quality, Groq for speed
2. **Optimize Batch Size**: Keep under 10 items per batch
3. **Use Caching**: Enable WordPress caching
4. **Monitor Logs**: Check success rates regularly
5. **Clean Old Data**: Logs auto-clean after 1000 entries

---

## 🔗 Support Resources

- **Settings Page**: Test all provider connections
- **Logs Page**: Monitor all operations
- **Queue Page**: Track background processing
- **Template Editor**: Build reusable templates
- **Documentation**: This guide covers all features

---

## 📈 Version History

### v1.3.0 (Current)
- Enhanced image generator with 5 FREE providers
- Advanced shortcodes with full customization
- Gutenberg blocks integration
- Enhanced logging system
- Content refresh system
- Template-based generation

### Previous Versions
- v1.2.0: Template system, queue processing
- v1.1.0: Multi-provider support
- v1.0.0: Initial release

---

## 🎉 Conclusion

Kotacom AI provides everything needed for automated content creation:

✅ **Content Generation** with 10+ AI providers  
✅ **Image Generation** with 5 FREE providers  
✅ **Gutenberg Integration** for modern editing  
✅ **Template System** for consistent content  
✅ **Bulk Processing** for efficiency  
✅ **Advanced Logging** for monitoring  
✅ **Fallback Systems** for reliability  

Start with free providers and scale as needed. The plugin handles fallbacks automatically, ensuring content generation never fails.

**Happy Content Creating! 🚀**