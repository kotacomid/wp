Berikut adalah semua AJAX handler (hook) yang terdaftar dalam plugin ini:

**Manajemen Keyword:**
- `kotacom_add_keyword`
- `kotacom_add_keywords_bulk`
- `kotacom_update_keyword`
- `kotacom_delete_keyword`
- `kotacom_get_keywords`

**Manajemen Prompt:**
- `kotacom_add_prompt`
- `kotacom_update_prompt`
- `kotacom_delete_prompt`
- `kotacom_get_prompts`

**Content Generation:**
- `kotacom_generate_content`
- `kotacom_get_queue_status`
- `kotacom_get_processing_status`
- `kotacom_retry_failed`
- `kotacom_test_api`

**Provider Management:**
- `kotacom_check_provider_status`
- `kotacom_test_provider_connection`

**Template Management:**
- `kotacom_save_template`
- `kotacom_preview_template`
- `kotacom_duplicate_template`
- `kotacom_get_template`
- `kotacom_get_templates`

**Lainnya:**
- `kotacom_generate_post`

Semua handler di atas menggunakan prefix `wp_ajax_` dan hanya bisa diakses oleh user yang sudah login (admin-side AJAX). Jika Anda ingin detail handler/fungsi masing-masing, silakan informasikan!
Berikut adalah informasi teknis utama terkait prefix, class, dan konstanta pada plugin ini:

Prefix
Semua fungsi, class, dan hook menggunakan prefix kotacom_ atau kotacom_ai_.
AJAX handler menggunakan prefix wp_ajax_kotacom_....
Konstanta
KOTACOM_AI_VERSION
KOTACOM_AI_PLUGIN_FILE
KOTACOM_AI_PLUGIN_DIR
KOTACOM_AI_PLUGIN_URL
KOTACOM_AI_PLUGIN_BASENAME
KOTACOM_AI_DEBUG
Class Utama
KotacomAI (main class)
KotacomAI_Admin
KotacomAI_Database
KotacomAI_API_Handler
KotacomAI_Background_Processor
KotacomAI_Content_Generator
KotacomAI_Template_Manager
KotacomAI_Queue_Processor
Fungsi Global
kotacom_ai_check_action_scheduler()
kotacom_ai()
Catatan
Semua file class menggunakan format nama file: class-nama-class.php dan class: KotacomAI_Nama_Class.
Namespace tidak digunakan, hanya prefix class.

Struktur File
kotacom-ai-content-generator.php (main plugin)
plugin.md (dokumentasi)
admin/
class-admin.php (admin logic)
css/: admin.css
js/: admin.js, template-editor.js
views/: generator-post-template.php, generator.php, keywords.php, prompts.php, queue.php, settings.php, template-editor.php
includes/
class-api-handler.php
class-background-processor.php
class-content-generator.php
class-database.php
class-queue-processor.php
class-template-editor.php
class-template-manager.php
Hook Penting
AJAX: wp_ajax_kotacom_* (lihat daftar di atas)
Action:
add_action('init', ...)
add_action('admin_init', ...)
add_action('admin_menu', ...)
add_action('admin_enqueue_scripts', ...)
add_action('kotacom_ai_process_batch', ...)
add_action('kotacom_ai_process_single_item', ...)
add_action('kotacom_ai_queue_item_processed', ...)
add_action('kotacom_ai_after_content_generation', ...)
add_action('kotacom_ai_before_content_generation', ...)
add_action('kotacom_ai_item_processed', ...)
Filter:
apply_filters('kotacom_ai_post_data', ...)
apply_filters('kotacom_ai_prompt_template', ...)
apply_filters('kotacom_ai_generated_content', ...)
Shortcode:
[ai_content]
[ai_section]
[ai_template]
[ai_conditional]
[ai_list]
Gutenberg Block:
kotacom-ai/content-block
kotacom-ai/list-block
kotacom-ai/template-structure
Custom Post Type:
kotacom_template
Method Penting
Semua class utama (KotacomAI, KotacomAI_Admin, dst) memiliki method public untuk operasi CRUD, AJAX, dan utilitas.
Banyak method AJAX: ajax_add_keyword, ajax_update_keyword, ajax_add_prompt, dst.
Method untuk shortcode: shortcode_ai_content, shortcode_ai_section, dst.
Method untuk block: render_ai_content_block, dst.
Method database: add_keyword, get_keywords, add_prompt, dst.
Catatan
Semua pengamanan AJAX menggunakan nonce: kotacom_ai_nonce.
Semua menu admin didaftarkan via add_menu_page dan add_submenu_page.
Semua pengaturan plugin didaftarkan via register_setting.
Tidak ada REST API endpoint, hanya AJAX dan shortcode/block.
Jika ingin detail method/class tertentu, silakan sebutkan nama class atau file!