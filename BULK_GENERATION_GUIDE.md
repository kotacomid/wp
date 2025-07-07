# Bulk Post Generation Guide - Kotacom AI Content Generator

## 🚀 **Fixed Issues**

### ✅ **JSON Parsing Error Fixed**
The JavaScript error `"[object Object]" is not valid JSON` has been resolved by properly handling the data-models attribute parsing.

### ✅ **Missing Variables Fixed**
All undefined variables (`$existing_templates`, `$tags`, `$categories`, `$post_types`) have been added to the admin class.

### ✅ **Enhanced AJAX Handler**
New `kotacom_generate_content_enhanced` AJAX handler supports both single and bulk generation with proper template integration.

### ✅ **Custom Post Type Registration**
The `kotacom_template` custom post type is now properly registered and accessible at `/wp-admin/edit.php?post_type=kotacom_template`.

## 📋 **How to Use Bulk Generation**

### **Single Post Generation** (1 keyword)
1. Go to **Kotacom AI > Generator Post Template**
2. Select AI model from dropdown
3. Choose **one keyword** from database or enter manually
4. Select a template from the dropdown
5. Configure content parameters (tone, length, audience)
6. Set WordPress post settings (status, categories, tags)
7. Click **Generate Content**
8. ✅ Post is created immediately

### **Bulk Post Generation** (Multiple keywords)
1. Go to **Kotacom AI > Generator Post Template**
2. Select AI model from dropdown
3. Choose **multiple keywords** from database or enter manually (one per line)
4. Select a template from the dropdown
5. Configure content parameters
6. Set WordPress post settings
7. Click **Generate Content**
8. ✅ All posts are queued for background processing

## 🔧 **Workflow Architecture**

```
User selects keywords → Plugin detects count
    ↓
Single Keyword (1)        Multiple Keywords (2+)
    ↓                           ↓
Direct Generation          Background Queue
    ↓                           ↓
Immediate Result          Batch Processing
    ↓                           ↓
Post Created              Monitor in Queue page
```

## 📊 **Template System**

### **How Templates Work**
1. **Custom Post Type**: Templates are stored as `kotacom_template` custom posts
2. **Placeholder System**: Use `{keyword}` in templates - gets replaced with actual keywords
3. **AI Integration**: Templates guide AI generation structure
4. **Reusable**: One template can generate unlimited posts with different keywords

### **Default Templates Included**
- **Blog Article Template**: Comprehensive article structure
- **Product Review Template**: Review format with pros/cons
- **How-to Guide Template**: Step-by-step instructions format

### **Creating Custom Templates**
1. Go to **Kotacom AI > AI Templates** or `/wp-admin/edit.php?post_type=kotacom_template`
2. Click **Add New AI Template**
3. Create your structure using `{keyword}` placeholders
4. Save and use in generator

## ⚡ **Action Scheduler Setup (Recommended for Bulk)**

### **What is Action Scheduler?**
Action Scheduler is a background job processing system that:
- Processes tasks without blocking the user interface
- Handles failures gracefully with automatic retries
- Prevents server timeouts on large operations
- Provides detailed logging and monitoring

### **Installation Options**

#### **Option 1: Install WooCommerce (Recommended)**
```bash
# WooCommerce includes Action Scheduler
# Go to Plugins > Add New > Search "WooCommerce" > Install
```

#### **Option 2: Standalone Action Scheduler**
```bash
# Download from: https://github.com/woocommerce/action-scheduler
# Upload to /wp-content/plugins/ and activate
```

### **Performance Benefits**
- ✅ **No timeouts** - Each post processed separately
- ✅ **Rate limiting** - 10 seconds between AI API calls
- ✅ **Error handling** - Failed items can be retried
- ✅ **Progress tracking** - Real-time status monitoring
- ✅ **Server friendly** - Doesn't overload hosting

## 📈 **Monitoring Bulk Generation**

### **Real-time Monitoring**
1. **During Generation**: Check status with "Check Status" button
2. **Queue Page**: Go to **Kotacom AI > Queue** for detailed view
3. **Batch Details**: Each bulk operation gets unique Batch ID

### **Status Types**
- 🟡 **Pending**: Waiting in queue
- 🔵 **Processing**: Currently being generated
- ✅ **Completed**: Post created successfully
- ❌ **Failed**: Error occurred (can be retried)
- ⏹️ **Cancelled**: Manually stopped

### **Progress Tracking**
```
Batch Status:
Total: 50
Completed: 45
Failed: 2
Pending: 3
Progress: 94%
```

## 🛠️ **Best Practices for Bulk Generation**

### **Keyword Management**
1. **Organize with Tags**: Use tag system for grouping keywords
2. **Quality over Quantity**: Focus on relevant, high-quality keywords
3. **Batch Size**: Recommend 10-50 keywords per batch for optimal performance

### **Template Optimization**
1. **Clear Structure**: Use headings and logical flow
2. **Placeholder Usage**: Strategic placement of `{keyword}` for natural integration
3. **Content Guidelines**: Include tone and style instructions in template

### **API Provider Selection**
1. **Free Tiers**: Start with Google AI or Groq for testing
2. **Rate Limits**: Respect API provider limits
3. **Quality vs Speed**: Choose based on your priority

### **Server Configuration**
1. **Memory Limit**: Ensure adequate PHP memory (256MB+ recommended)
2. **Execution Time**: Not critical with Action Scheduler
3. **Cron Jobs**: Ensure WordPress cron is working

## 🔍 **Troubleshooting Common Issues**

### **Action Scheduler Not Working**
```bash
# Check if Action Scheduler is active
# Go to Tools > Action Scheduler (if available)
# Install WooCommerce or standalone Action Scheduler plugin
```

### **Generation Stuck in "Pending"**
1. Check WordPress cron: `/wp-admin/admin.php?page=action-scheduler`
2. Trigger manual processing: Visit any admin page
3. Check error logs for API issues

### **API Errors**
1. **Invalid API Key**: Configure in Settings page
2. **Rate Limiting**: Reduce batch size or increase delays
3. **Provider Issues**: Switch to alternative provider

### **Template Not Found Error**
1. Ensure template is published (not draft)
2. Check custom post type is registered
3. Create default templates if none exist

## 📋 **API Provider Comparison**

| Provider | Free Tier | Speed | Quality | Best For |
|----------|-----------|-------|---------|----------|
| Google AI (Gemini) | ✅ Yes | ⚡⚡⚡⚡ | ⭐⭐⭐⭐⭐ | General use |
| Groq | ✅ Yes | ⚡⚡⚡⚡⚡ | ⭐⭐⭐⭐ | Fast bulk generation |
| OpenAI | ❌ Paid | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | Premium quality |
| Anthropic | ✅ Credits | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | Complex content |
| Cohere | ✅ Yes | ⚡⚡⚡ | ⭐⭐⭐⭐ | Business content |

## 🎯 **Optimization Tips**

### **For Single Posts**
- Use for immediate content needs
- Perfect for testing templates
- Direct feedback and editing

### **For Bulk Posts**
- Schedule during low-traffic hours
- Monitor progress regularly
- Use consistent templates for uniformity

### **Performance Tuning**
```php
// Adjust batch processing interval (default: 2 minutes)
update_option('kotacom_ai_queue_processing_interval', 120);

// Adjust items per batch (default: 5)
update_option('kotacom_ai_queue_batch_size', 10);
```

## 📞 **Support & Maintenance**

### **Regular Maintenance**
1. **Clean old queue items**: Automatic cleanup after 30 days
2. **Monitor API usage**: Track provider quotas
3. **Template updates**: Refine templates based on results

### **Getting Help**
1. Check error logs: `/wp-content/debug.log`
2. Enable debug mode: `define('KOTACOM_AI_DEBUG', true);`
3. Review queue status page for detailed information

---

## 🎉 **Summary**

Your WordPress plugin now supports:
- ✅ **Single post generation** (immediate)
- ✅ **Bulk post generation** (background queue)
- ✅ **Custom template system** 
- ✅ **Multiple AI providers**
- ✅ **Progress monitoring**
- ✅ **Error handling & retries**
- ✅ **Action Scheduler integration**

**The plugin is ready for production use!** 🚀