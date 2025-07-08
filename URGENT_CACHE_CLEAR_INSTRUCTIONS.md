# 🚨 URGENT: Clear Caches to Fix Fatal Error

## The Issue
The fatal error you're seeing is due to **cached old code** still running. The current code is correct, but WordPress/PHP is still executing an old version.

## ✅ **IMMEDIATE FIX STEPS**

### **1. Clear All Caches (CRITICAL)**

#### **Option A: WordPress Admin (Easiest)**
1. Go to **Kotacom AI → Content Generator**
2. In the **Queue Debug** section, click **"Check Queue Status"** 
3. Then click **"Process Queue Now"** to test

#### **Option B: Manual Cache Clear**
Add this code to your `wp-config.php` file temporarily:
```php
// TEMPORARY: Clear opcode cache
if (function_exists('opcache_reset')) {
    opcache_reset();
}
```

#### **Option C: Server Level (Most Effective)**
If you have server access:
```bash
# Clear PHP OpCache
sudo systemctl reload php-fpm
# OR
sudo service nginx reload
sudo service apache2 reload
```

### **2. Clear Queue Items (Important)**
1. Go to **Kotacom AI → Content Generator**
2. Click **"Check Queue Status"** in the debug section
3. You should see all current queue items
4. **Clear old problematic items**:
   - If you see many failed items, they were created with the old method
   - You may need to clear the queue completely and start fresh

### **3. Test the Fix**
1. Try a **single keyword** generation first (not bulk)
2. Go to **Kotacom AI → Generator Post Template**
3. Select **ONE keyword only**
4. Configure template and click **Generate Content**
5. Check if it works without the fatal error

### **4. If Still Getting Errors**

#### **Check for Old Files**
Look for any backup or duplicate files:
- `class-queue-manager.php.bak`
- `class-queue-manager-old.php`
- Any cache folders that might have old versions

#### **Verify Current Code**
The fixed method should start with:
```php
/**
 * Process content generation - FIXED VERSION 2.0
 */
private function process_content_generation($data) {
    // Force clear any cached Content Generator calls
    $keyword = '';
    $prompt = '';
    $params = array();
```

## 🔧 **What Was Fixed**

### **Before (Causing Fatal Error):**
- Old code was calling: `$generator->generate_content($keyword, $prompt, $params)` 
- This requires 4+ parameters but only passed 3

### **After (Fixed):**
- New code calls: `$api_handler->generate_content($final_prompt, $params)`
- This is the correct API handler method with proper parameters

## 🚀 **Testing Process**

### **Step 1: Single Generation Test**
1. Use **Generator Post Template** page
2. Select **1 keyword only**
3. Choose a template
4. Click **Generate Content**
5. **Should work without fatal error**

### **Step 2: Bulk Generation Test**
1. Only after single generation works
2. Try **2-3 keywords** for bulk generation
3. Monitor using **Queue Debug** tools
4. Should create posts successfully

### **Step 3: Monitor Queue**
1. Use **"Check Queue Status"** regularly
2. Watch for any failed items
3. If items fail, check the error messages in debug info

## 📋 **If You Still See Fatal Errors**

### **Diagnostic Steps:**
1. **Check WordPress Error Log**: Look for the exact line causing the error
2. **Verify File Permissions**: Make sure the file can be read properly
3. **Check for Conflicting Plugins**: Temporarily deactivate other plugins
4. **Restart Web Server**: Sometimes requires a full restart

### **Emergency Fallback:**
If the issue persists, you can temporarily disable queue processing:
1. Go to WordPress Admin → **Plugins**
2. **Deactivate** Kotacom AI plugin
3. **Reactivate** it (this forces a fresh load)

## 💡 **Prevention**
- Always test single generation before bulk
- Use the debug tools to monitor queue status
- Clear caches after any code updates

---

**The fix is in place! The fatal error should stop once caches are cleared and you start with fresh queue items.**