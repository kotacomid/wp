# WordPress AI Bulk Post Generation Guide

## Overview

This guide explains how to use the AI Content Generator for bulk post creation with the enhanced template-based system.

## Fixed Issues

### 1. JSON Parsing Error Resolution
- **Issue**: `"[object Object]" is not valid JSON` error in provider model loading
- **Fix**: Enhanced error handling in `loadProviderModels()` function with proper validation
- **Location**: `admin/views/generator-post-template.php`

### 2. 500 Server Error Resolution  
- **Issue**: AJAX handler `kotacom_generate_content_enhanced` causing server errors
- **Fix**: Proper initialization of database and background processor classes
- **Dependencies**: Ensure Action Scheduler plugin is installed

### 3. Template Integration
- **Issue**: Template selection workflow not properly connected
- **Fix**: Complete integration with `kotacom_template` custom post type

## Workflow Overview

### Single Keyword → Single Post
```
User selects AI model → Select 1 keyword → Choose template → Generate immediately
```

### Multiple Keywords → Bulk Posts
```
User selects AI model → Select multiple keywords → Choose template → Queue for background processing
```

## Best Practices for Bulk Generation

### 1. Recommended Approach: Action Scheduler

**Why Action Scheduler?**
- WordPress-native background processing
- Automatic retry on failures
- Built-in logging and monitoring
- Handles server timeouts gracefully
- Integrates with WooCommerce if available

**Installation:**
```bash
# Option 1: Install WooCommerce (includes Action Scheduler)
# Option 2: Install Action Scheduler as standalone plugin
composer require woocommerce/action-scheduler
```

### 2. Optimal Batch Configuration

**Recommended Settings:**
- **Batch Size**: 5-10 items per batch
- **Processing Interval**: 2 minutes between batches
- **Item Delay**: 10 seconds between individual items
- **Timeout**: 60 seconds per API call

**Performance Considerations:**
```php
// Optimal settings for different scenarios
$settings = [
    'light_load' => [
        'batch_size' => 10,
        'interval' => 120, // 2 minutes
        'delay_between_items' => 5
    ],
    'heavy_load' => [
        'batch_size' => 5,
        'interval' => 180, // 3 minutes  
        'delay_between_items' => 15
    ],
    'rate_limited_apis' => [
        'batch_size' => 3,
        'interval' => 300, // 5 minutes
        'delay_between_items' => 30
    ]
];
```

### 3. Template Best Practices

**Template Structure:**
```html
<h1>{keyword}</h1>

<p>Introduction about {keyword}...</p>

<h2>What is {keyword}?</h2>
<p>Detailed explanation of {keyword}...</p>

<h2>Benefits of {keyword}</h2>
<ul>
<li>Benefit 1 related to {keyword}</li>
<li>Benefit 2 related to {keyword}</li>
</ul>

<h2>Conclusion</h2>
<p>Summary about {keyword}...</p>
```

**Template Variables:**
- `{keyword}` - The primary keyword
- Use multiple `{keyword}` placements for better SEO
- Keep templates flexible and generic

### 4. AI Provider Selection

**Free Tier Providers (Best for Bulk):**
1. **Google AI (Gemini)** - Most generous free tier
2. **Groq** - Ultra-fast processing  
3. **Cohere** - Good for bulk operations
4. **Hugging Face** - Open source models

**Paid Providers (Premium Quality):**
1. **OpenAI** - Highest quality content
2. **Anthropic Claude** - Excellent reasoning
3. **OpenRouter** - Access to multiple models
4. **Perplexity** - Real-time information

### 5. Monitoring and Management

**Queue Monitoring:**
- Check queue status at: `/wp-admin/admin.php?page=kotacom-ai-queue`
- Monitor failed items and retry as needed
- Clean up old completed items regularly

**Error Handling:**
- Failed items are automatically retried up to 3 times
- Manual retry available for persistent failures
- Detailed error logs for troubleshooting

### 6. WordPress Configuration

**Post Settings:**
```php
$post_settings = [
    'post_type' => 'post',           // or custom post type
    'post_status' => 'draft',        // 'draft' or 'publish'
    'categories' => [1, 2, 3],       // Category IDs
    'tags' => 'ai, generated, bulk'  // Comma-separated tags
];
```

**Recommended Post Status:**
- Use `draft` for review before publishing
- Use `publish` only for trusted templates and providers

## Troubleshooting

### Common Issues

**1. Queue Not Processing**
```bash
# Check if Action Scheduler is running
wp action-scheduler status

# Manually trigger processing
wp action-scheduler run
```

**2. Memory Limits**
```php
// Add to wp-config.php
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
```

**3. API Rate Limits**
- Increase delays between items
- Use multiple API providers with fallback
- Monitor API usage quotas

### Performance Optimization

**Database Optimization:**
```sql
-- Clean old queue items (run monthly)
DELETE FROM wp_kotacom_queue 
WHERE status IN ('completed', 'failed') 
AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

**Caching Considerations:**
- Disable object caching during bulk generation
- Clear page cache after bulk publishing
- Monitor database performance

## Integration Examples

### Custom Post Type Integration
```php
// Register custom post type for generated content
register_post_type('ai_article', [
    'public' => true,
    'label' => 'AI Articles',
    'supports' => ['title', 'editor', 'excerpt']
]);
```

### Custom Taxonomies
```php
// Add AI-specific categories
wp_insert_term('AI Generated', 'category');
wp_insert_term('Bulk Content', 'category');
```

## Best Practices Summary

1. **Start Small**: Test with 5-10 keywords first
2. **Use Templates**: Create reusable content templates
3. **Monitor Progress**: Check queue status regularly
4. **Review Content**: Always review generated content before publishing
5. **Optimize Settings**: Adjust batch sizes based on server performance
6. **Use Free Tiers**: Start with free API providers for testing
7. **Set Realistic Expectations**: Bulk generation takes time for quality results

## API Usage Guidelines

### Free Tier Optimization
- Use shorter content lengths (300-500 words)
- Batch similar keywords together
- Monitor daily usage quotas
- Implement fallback providers

### Paid Provider Strategy
- Use for high-value content
- Longer, more detailed articles
- Premium model selection
- Cost monitoring and budgeting

## Maintenance

### Regular Tasks
- **Weekly**: Review generated content quality
- **Monthly**: Clean old queue items  
- **Quarterly**: Update templates based on performance
- **Annually**: Review API provider costs and performance

This enhanced bulk generation system provides a robust, scalable solution for AI-powered content creation while maintaining quality and performance standards.