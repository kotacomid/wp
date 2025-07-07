<?php
/**
 * Database operations class - Updated with batch support
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Database {
    
    private $wpdb;
    private $keywords_table;
    private $prompts_table;
    private $queue_table;
    private $batches_table;
    private $templates_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->keywords_table = $wpdb->prefix . 'kotacom_keywords';
        $this->prompts_table = $wpdb->prefix . 'kotacom_prompts';
        $this->queue_table = $wpdb->prefix . 'kotacom_queue';
        $this->batches_table = $wpdb->prefix . 'kotacom_batches';
        $this->templates_table = $wpdb->prefix . 'kotacom_templates';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Keywords table
        $keywords_sql = "CREATE TABLE {$this->keywords_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            keyword VARCHAR(255) NOT NULL,
            tags TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY keyword (keyword),
            KEY tags_index (tags(100))
        ) $charset_collate;";
        
        // Prompts table
        $prompts_sql = "CREATE TABLE {$this->prompts_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            prompt_name VARCHAR(255) NOT NULL,
            prompt_template LONGTEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY prompt_name (prompt_name)
        ) $charset_collate;";
        
        // Queue table - Updated with batch support
        $queue_sql = "CREATE TABLE {$this->queue_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id VARCHAR(50),
            keyword VARCHAR(255) NOT NULL,
            prompt_template LONGTEXT NOT NULL,
            parameters TEXT,
            post_settings TEXT,
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            error_message TEXT,
            post_id BIGINT(20) UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY status_index (status),
            KEY batch_id_index (batch_id),
            KEY created_at_index (created_at)
        ) $charset_collate;";
        
        // Batches table for progress tracking
        $batches_sql = "CREATE TABLE {$this->batches_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id VARCHAR(50) NOT NULL,
            total_items INT NOT NULL DEFAULT 0,
            completed_items INT NOT NULL DEFAULT 0,
            failed_items INT NOT NULL DEFAULT 0,
            status ENUM('processing', 'completed', 'cancelled') DEFAULT 'processing',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY batch_id (batch_id),
            KEY status_index (status)
        ) $charset_collate;";
        
        // Templates table for template editor
        $templates_sql = "CREATE TABLE {$this->templates_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            content LONGTEXT NOT NULL,
            tags TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY template_name (name),
            KEY tags_index (tags(100))
        ) $charset_collate;";
        
        // Logs table (lightweight)
        $logs_table = $this->wpdb->prefix . 'kotacom_ai_logs';
        $logs_sql = "CREATE TABLE {$logs_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ts DATETIME NOT NULL,
            action VARCHAR(20) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT,
            post_id BIGINT(20) UNSIGNED NULL,
            PRIMARY KEY (id),
            KEY ts_index (ts),
            KEY action_index (action),
            KEY success_index (success)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        try {
            dbDelta($keywords_sql);
            dbDelta($prompts_sql);
            dbDelta($queue_sql);
            dbDelta($batches_sql);
            dbDelta($templates_sql);
            dbDelta($logs_sql);
            
            // Insert default prompts
            $this->insert_default_prompts();
            
        } catch (Exception $e) {
            if (defined('KOTACOM_AI_DEBUG') && KOTACOM_AI_DEBUG) {
                error_log('Kotacom AI Database Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Insert default prompt templates
     */
    private function insert_default_prompts() {
        $default_prompts = array(
            array(
                'prompt_name' => 'Blog Article',
                'prompt_template' => 'Write a comprehensive blog article about {keyword}. The article should be informative, engaging, and well-structured with proper headings and subheadings.',
                'description' => 'Template untuk membuat artikel blog yang komprehensif'
            ),
            array(
                'prompt_name' => 'Product Description',
                'prompt_template' => 'Create a compelling product description for {keyword}. Focus on benefits, features, and why customers should choose this product.',
                'description' => 'Template untuk deskripsi produk yang menarik'
            ),
            array(
                'prompt_name' => 'How-to Guide',
                'prompt_template' => 'Write a step-by-step how-to guide about {keyword}. Include clear instructions, tips, and best practices.',
                'description' => 'Template untuk panduan langkah demi langkah'
            )
        );
        
        foreach ($default_prompts as $prompt) {
            try {
                $existing = $this->wpdb->get_var(
                    $this->wpdb->prepare(
                        "SELECT id FROM {$this->prompts_table} WHERE prompt_name = %s",
                        $prompt['prompt_name']
                    )
                );
                
                if (!$existing) {
                    $this->add_prompt($prompt['prompt_name'], $prompt['prompt_template'], $prompt['description']);
                }
            } catch (Exception $e) {
                if (defined('KOTACOM_AI_DEBUG') && KOTACOM_AI_DEBUG) {
                    error_log('Kotacom AI Default Prompt Error: ' . $e->getMessage());
                }
            }
        }
    }
    
    // Keywords methods (unchanged)
    public function add_keyword($keyword, $tags = '') {
        return $this->wpdb->insert(
            $this->keywords_table,
            array(
                'keyword' => $keyword,
                'tags' => $tags
            ),
            array('%s', '%s')
        );
    }
    
    public function update_keyword($id, $keyword, $tags = '') {
        return $this->wpdb->update(
            $this->keywords_table,
            array(
                'keyword' => $keyword,
                'tags' => $tags
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    public function delete_keyword($id) {
        return $this->wpdb->delete(
            $this->keywords_table,
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_keywords($page = 1, $per_page = 20, $search = '', $tag_filter = '') {
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($search)) {
            $where_conditions[] = "keyword LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($search) . '%';
        }
        
        if (!empty($tag_filter)) {
            $where_conditions[] = "tags LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($tag_filter) . '%';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT * FROM {$this->keywords_table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $where_values),
            ARRAY_A
        );
    }
    
    public function get_keywords_count($search = '', $tag_filter = '') {
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($search)) {
            $where_conditions[] = "keyword LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($search) . '%';
        }
        
        if (!empty($tag_filter)) {
            $where_conditions[] = "tags LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($tag_filter) . '%';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->keywords_table} {$where_clause}";
        
        if (!empty($where_values)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $where_values));
        } else {
            return $this->wpdb->get_var($sql);
        }
    }
    
    public function get_all_tags() {
        $results = $this->wpdb->get_col("SELECT DISTINCT tags FROM {$this->keywords_table} WHERE tags != ''");
        
        $all_tags = array();
        foreach ($results as $tags_string) {
            $tags = array_map('trim', explode(',', $tags_string));
            $all_tags = array_merge($all_tags, $tags);
        }
        
        return array_unique(array_filter($all_tags));
    }
    
    public function get_keywords_by_tag($tag) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->keywords_table} WHERE tags LIKE %s ORDER BY keyword ASC",
                '%' . $this->wpdb->esc_like($tag) . '%'
            ),
            ARRAY_A
        );
    }
    
    // Prompts methods (unchanged)
    public function add_prompt($prompt_name, $prompt_template, $description = '') {
        return $this->wpdb->insert(
            $this->prompts_table,
            array(
                'prompt_name' => $prompt_name,
                'prompt_template' => $prompt_template,
                'description' => $description
            ),
            array('%s', '%s', '%s')
        );
    }
    
    public function update_prompt($id, $prompt_name, $prompt_template, $description = '') {
        return $this->wpdb->update(
            $this->prompts_table,
            array(
                'prompt_name' => $prompt_name,
                'prompt_template' => $prompt_template,
                'description' => $description
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    public function delete_prompt($id) {
        return $this->wpdb->delete(
            $this->prompts_table,
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_prompts() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->prompts_table} ORDER BY prompt_name ASC",
            ARRAY_A
        );
    }
    
    public function get_prompt_by_id($id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->prompts_table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
    }
    
    // Queue methods - Updated with batch support
    public function add_to_queue($keyword, $prompt_template, $parameters, $post_settings) {
        $result = $this->wpdb->insert(
            $this->queue_table,
            array(
                'keyword' => $keyword,
                'prompt_template' => $prompt_template,
                'parameters' => json_encode($parameters),
                'post_settings' => json_encode($post_settings),
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    public function update_queue_batch_id($queue_id, $batch_id) {
        return $this->wpdb->update(
            $this->queue_table,
            array('batch_id' => $batch_id),
            array('id' => $queue_id),
            array('%s'),
            array('%d')
        );
    }
    
    public function get_queue_item_by_id($id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->queue_table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
    }
    
    public function get_pending_queue_items($limit = 5) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->queue_table} WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
    
    public function update_queue_item_status($id, $status, $error_message = '') {
        $data = array(
            'status' => $status,
            'processed_at' => current_time('mysql')
        );
        
        if (!empty($error_message)) {
            $data['error_message'] = $error_message;
        }
        
        return $this->wpdb->update(
            $this->queue_table,
            $data,
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    public function update_queue_item_post_id($id, $post_id) {
        return $this->wpdb->update(
            $this->queue_table,
            array('post_id' => $post_id),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
    }
    
    public function get_queue_status() {
        $status = array();
        
        $status['pending'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'pending'"
        );
        
        $status['processing'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'processing'"
        );
        
        $status['completed'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'completed'"
        );
        
        $status['failed'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'failed'"
        );
        
        return $status;
    }
    
    public function get_failed_queue_items() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->queue_table} WHERE status = 'failed' ORDER BY created_at DESC",
            ARRAY_A
        );
    }
    
    public function retry_failed_items() {
        return $this->wpdb->update(
            $this->queue_table,
            array('status' => 'pending', 'error_message' => ''),
            array('status' => 'failed'),
            array('%s', '%s'),
            array('%s')
        );
    }
    
    public function clean_old_queue_items($days = 30) {
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->queue_table} WHERE status IN ('completed', 'failed') AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
    
    // Batch methods
    public function create_batch($batch_id, $total_items) {
        return $this->wpdb->insert(
            $this->batches_table,
            array(
                'batch_id' => $batch_id,
                'total_items' => $total_items,
                'completed_items' => 0,
                'failed_items' => 0,
                'status' => 'processing'
            ),
            array('%s', '%d', '%d', '%d', '%s')
        );
    }
    
    public function get_batch_status($batch_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->batches_table} WHERE batch_id = %s",
                $batch_id
            ),
            ARRAY_A
        );
    }
    
    public function update_batch_progress($batch_id) {
        // Get current batch stats from queue items
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                 FROM {$this->queue_table} 
                 WHERE batch_id = %s",
                $batch_id
            )
        );
        
        if ($stats) {
            $status = 'processing';
            if ($stats->completed + $stats->failed >= $stats->total) {
                $status = 'completed';
            }
            
            return $this->wpdb->update(
                $this->batches_table,
                array(
                    'completed_items' => $stats->completed,
                    'failed_items' => $stats->failed,
                    'status' => $status
                ),
                array('batch_id' => $batch_id),
                array('%d', '%d', '%s'),
                array('%s')
            );
        }
        
        return false;
    }
}
