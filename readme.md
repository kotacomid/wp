# Kotacom AI Content Generator

## Struktur Direktori

- `kotacom-ai-content-generator.php` (main plugin)
- `plugin.md` (dokumentasi)
- **admin/**
  - `class-admin.php` (admin logic)
  - **css/**: `admin.css`
  - **js/**: `admin.js`, `template-editor.js`
  - **views/**: `generator-post-template.php`, `generator.php`, `keywords.php`, `prompts.php`, `queue.php`, `settings.php`, `template-editor.php`
- **includes/**
  - `class-api-handler.php`
  - `class-background-processor.php`
  - `class-content-generator.php`
  - `class-database.php`
  - `class-queue-processor.php`
  - `class-template-editor.php`
  - `class-template-manager.php`

## Prefix & Konstanta
- Semua fungsi, class, dan hook menggunakan prefix `kotacom_` atau `kotacom_ai_`.
- AJAX handler: prefix `wp_ajax_kotacom_...`
- Konstanta utama:
  - `KOTACOM_AI_VERSION`
  - `KOTACOM_AI_PLUGIN_FILE`
  - `KOTACOM_AI_PLUGIN_DIR`
  - `KOTACOM_AI_PLUGIN_URL`
  - `KOTACOM_AI_PLUGIN_BASENAME`
  - `KOTACOM_AI_DEBUG`

## Class Utama
- `KotacomAI` (main class)
- `KotacomAI_Admin`
- `KotacomAI_Database`
- `KotacomAI_API_Handler`
- `KotacomAI_Background_Processor`
- `KotacomAI_Content_Generator`
- `KotacomAI_Template_Manager`
- `KotacomAI_Queue_Processor`

## Struktur Database

Plugin ini menggunakan beberapa tabel kustom di database WordPress untuk menyimpan data terkait keyword, prompt, antrian proses, dan batch. Semua nama tabel diawali dengan prefix database WordPress (`wpdb->prefix`).

### Tabel: `kotacom_keywords`

Menyimpan daftar keyword yang digunakan untuk generate konten.

| Kolom          | Tipe Data             | Deskripsi                               |
| :------------- | :-------------------- | :-------------------------------------- |
| `id`           | BIGINT(20) UNSIGNED   | Primary Key, Auto Increment             |
| `keyword`      | VARCHAR(255)          | Keyword unik                            |
| `tags`         | TEXT                  | Tag terkait keyword (dipisahkan koma)   |
| `created_at`   | DATETIME              | Waktu pembuatan keyword                 |
| `updated_at`   | DATETIME              | Waktu terakhir update keyword           |

### Tabel: `kotacom_prompts`

Menyimpan template prompt yang digunakan untuk generate konten.

| Kolom             | Tipe Data           | Deskripsi                                   |
| :---------------- | :------------------ | :------------------------------------------ |
| `id`              | BIGINT(20) UNSIGNED | Primary Key, Auto Increment                 |
| `prompt_name`     | VARCHAR(255)        | Nama unik untuk template prompt             |
| `prompt_template` | LONGTEXT            | Isi template prompt (mendukung placeholder) |
| `description`     | TEXT                | Deskripsi singkat template prompt           |
| `created_at`      | DATETIME            | Waktu pembuatan template prompt             |
| `updated_at`      | DATETIME            | Waktu terakhir update template prompt       |

### Tabel: `kotacom_queue`

Menyimpan antrian item yang menunggu atau sedang diproses untuk generate konten.

| Kolom             | Tipe Data             | Deskripsi                                       |
| :---------------- | :-------------------- | :---------------------------------------------- |
| `id`              | BIGINT(20) UNSIGNED   | Primary Key, Auto Increment                     |
| `batch_id`        | VARCHAR(50)           | ID Batch terkait (untuk pengelompokan proses)   |
| `keyword`         | VARCHAR(255)          | Keyword yang digunakan untuk item ini           |
| `prompt_template` | LONGTEXT              | Template prompt spesifik untuk item ini         |
| `parameters`      | TEXT                  | Parameter tambahan (JSON)                       |
| `post_settings`   | TEXT                  | Pengaturan post (JSON)                          |
| `status`          | ENUM                  | Status item: 'pending', 'processing', 'completed', 'failed', 'cancelled' |
| `error_message`   | TEXT                  | Pesan error jika proses gagal                   |
| `post_id`         | BIGINT(20) UNSIGNED   | ID Post WordPress yang dibuat (jika berhasil)   |
| `created_at`      | DATETIME              | Waktu item ditambahkan ke antrian               |
| `processed_at`    | DATETIME              | Waktu item selesai diproses                     |

### Tabel: `kotacom_batches`

Menyimpan informasi tentang batch proses generate konten.

| Kolom           | Tipe Data           | Deskripsi                                   |
| :-------------- | :------------------ | :------------------------------------------ |
| `id`            | BIGINT(20) UNSIGNED | Primary Key, Auto Increment                 |
| `batch_id`      | VARCHAR(50)         | ID unik untuk batch                         |
| `total_items`   | INT                 | Jumlah total item dalam batch               |
| `completed_items`| INT                 | Jumlah item yang berhasil diproses          |
| `failed_items`  | INT                 | Jumlah item yang gagal diproses             |
| `status`        | ENUM                | Status batch: 'processing', 'completed', 'cancelled' |
| `created_at`    | DATETIME            | Waktu batch dibuat                          |
| `updated_at`    | DATETIME            | Waktu terakhir update status batch          |

## Method Penting Class `KotacomAI_Database`

Class `KotacomAI_Database` menyediakan method untuk berinteraksi dengan tabel-tabel di atas. Berikut adalah beberapa method penting:

### Method Keywords

*   `add_keyword($keyword, $tags = '')`: Menambahkan keyword baru.
*   `update_keyword($id, $keyword, $tags = '')`: Mengupdate keyword berdasarkan ID.
*   `delete_keyword($id)`: Menghapus keyword berdasarkan ID.
*   `get_keywords($page = 1, $per_page = 20, $search = '', $tag_filter = '')`: Mengambil daftar keyword dengan opsi pagination, pencarian, dan filter tag.
*   `get_keywords_count($search = '', $tag_filter = '')`: Menghitung jumlah total keyword dengan opsi pencarian dan filter tag.
*   `get_all_tags()`: Mengambil semua tag unik yang ada.
*   `get_keywords_by_tag($tag)`: Mengambil keyword berdasarkan tag tertentu.

### Method Prompts

*   `add_prompt($prompt_name, $prompt_template, $description = '')`: Menambahkan template prompt baru.
*   `update_prompt($id, $prompt_name, $prompt_template, $description = '')`: Mengupdate template prompt berdasarkan ID.
*   `delete_prompt($id)`: Menghapus template prompt berdasarkan ID.
*   `get_prompts()`: Mengambil semua template prompt.
*   `get_prompt_by_id($id)`: Mengambil template prompt berdasarkan ID.

### Method Queue

*   `add_to_queue($keyword, $prompt_template, $parameters, $post_settings)`: Menambahkan item ke antrian proses.
*   `update_queue_batch_id($queue_id, $batch_id)`: Mengupdate ID batch untuk item antrian.
*   `get_queue_item_by_id($id)`: Mengambil item antrian berdasarkan ID.
*   `get_pending_queue_items($limit = 5)`: Mengambil item antrian dengan status 'pending'.
*   `update_queue_item_status($id, $status, $error_message = '')`: Mengupdate status item antrian.
*   `update_queue_item_post_id($id, $post_id)`: Mengupdate ID post WordPress yang dibuat untuk item antrian.
*   `get_queue_status()`: Mengambil jumlah item dalam setiap status antrian.
*   `get_failed_queue_items()`: Mengambil item antrian dengan status 'failed'.
*   `retry_failed_items()`: Mengubah status item antrian yang 'failed' menjadi 'pending'.
*   `clean_old_queue_items($days = 30)`: Menghapus item antrian lama yang sudah selesai atau gagal.

## Fungsi Global
- `kotacom_ai_check_action_scheduler()`
- `kotacom_ai()`

## AJAX Handler
Lihat daftar lengkap di `plugin.md` (semua diawali `wp_ajax_kotacom_...`).

## Hook Penting
- **Action**:
  - `init`, `admin_init`, `admin_menu`, `admin_enqueue_scripts`
  - `kotacom_ai_process_batch`, `kotacom_ai_process_single_item`, `kotacom_ai_queue_item_processed`, `kotacom_ai_after_content_generation`, `kotacom_ai_before_content_generation`, `kotacom_ai_item_processed`
- **Filter**:
  - `kotacom_ai_post_data`, `kotacom_ai_prompt_template`, `kotacom_ai_generated_content`
- **Shortcode**:
  - `[ai_content]`, `[ai_section]`, `[ai_template]`, `[ai_conditional]`, `[ai_list]`
- **Gutenberg Block**:
  - `kotacom-ai/content-block`, `kotacom-ai/list-block`, `kotacom-ai/template-structure`
- **Custom Post Type**:
  - `kotacom_template`

## Daftar Fungsi Shortcode

Plugin ini menyediakan beberapa shortcode utama yang dapat digunakan di editor WordPress:

### `[ai_content]`
Menampilkan konten AI berdasarkan keyword dan prompt tertentu.
- **Atribut:**
  - `keyword` (string, wajib): Keyword yang digunakan untuk generate konten.
  - `prompt` (string, opsional): Nama template prompt yang digunakan.
- **Contoh:**
  ```
  [ai_content keyword="sepeda listrik" prompt="Blog Article"]
  ```
- **Fungsi Handler:**
  - `KotacomAI_Content_Generator::shortcode_ai_content()`

### `[ai_section]`
Menampilkan bagian/section tertentu dari konten AI.
- **Atribut:**
  - `keyword` (string, wajib)
  - `prompt` (string, opsional)
  - `section` (string, opsional): Nama section yang ingin ditampilkan.
- **Contoh:**
  ```
  [ai_section keyword="sepeda listrik" prompt="How-to Guide" section="Langkah 1"]
  ```
- **Fungsi Handler:**
  - `KotacomAI_Content_Generator::shortcode_ai_section()`

### `[ai_template]`
Menampilkan hasil generate berdasarkan template tertentu.
- **Atribut:**
  - `template` (string, wajib): Nama template yang digunakan.
  - `keyword` (string, opsional)
- **Contoh:**
  ```
  [ai_template template="Product Description" keyword="sepeda listrik"]
  ```
- **Fungsi Handler:**
  - `KotacomAI_Content_Generator::shortcode_ai_template()`

### `[ai_conditional]`
Menampilkan konten AI secara kondisional berdasarkan parameter tertentu.
- **Atribut:**
  - `if` (string, wajib): Kondisi yang harus dipenuhi.
  - `keyword` (string, opsional)
- **Contoh:**
  ```
  [ai_conditional if="is_single" keyword="sepeda listrik"]
  ```
- **Fungsi Handler:**
  - `KotacomAI_Content_Generator::shortcode_ai_conditional()`

### `[ai_list]`
Menampilkan daftar konten AI berdasarkan list keyword atau prompt.
- **Atribut:**
  - `keywords` (string, dipisah koma): Daftar keyword.
  - `prompt` (string, opsional)
- **Contoh:**
  ```
  [ai_list keywords="sepeda listrik, motor listrik" prompt="Blog Article"]
  ```
- **Fungsi Handler:**
  - `KotacomAI_Content_Generator::shortcode_ai_list()`

## Fitur Utama
- Generate konten otomatis berbasis AI (OpenAI/LLM) untuk post WordPress
- Manajemen keyword, prompt/template, dan batch proses
- Antrian proses background (queue & batch)
- AJAX handler untuk semua operasi CRUD (keyword, prompt, queue)
- Shortcode dan Gutenberg block untuk menampilkan konten AI
- Custom post type untuk template AI
- Menu admin dan pengaturan plugin
- Keamanan: nonce, hak akses, validasi input
- Logging error (jika `KOTACOM_AI_DEBUG` aktif)

## Alur Kerja Utama
1. **Tambah Keyword & Prompt**: Admin menambah keyword dan template prompt.
2. **Generate Konten**: Admin memilih keyword & prompt, lalu submit ke antrian (queue).
3. **Proses Antrian**: Sistem memproses queue secara background (batch), membuat post WordPress.
4. **Monitoring**: Status antrian dan batch dapat dipantau di halaman admin.
5. **Retry & Clean**: Item gagal bisa di-retry, item lama otomatis dibersihkan.

## Contoh Penggunaan Shortcode
```
[ai_content keyword="sepeda listrik" prompt="Blog Article"]
[ai_section keyword="sepeda listrik" prompt="How-to Guide"]
```

## Contoh Penggunaan Gutenberg Block
- Tambahkan block "AI Content" di editor Gutenberg, pilih keyword & prompt.

## Instalasi & Aktivasi
1. Upload folder plugin ke `wp-content/plugins/`
2. Aktifkan plugin via menu Plugins di WordPress
3. Plugin otomatis membuat tabel database saat aktivasi

## Troubleshooting
- Jika tabel database tidak terbentuk, nonaktifkan lalu aktifkan ulang plugin
- Aktifkan `KOTACOM_AI_DEBUG` untuk log error detail
- Cek file `plugin.md` untuk daftar AJAX handler dan detail API internal

## Catatan Pengembangan
- Semua class dan fungsi menggunakan prefix `kotacom_` atau `kotacom_ai_`
- Tidak menggunakan namespace, hanya prefix class
- Semua operasi AJAX diamankan dengan nonce dan hak akses
- Tidak ada REST API endpoint, hanya AJAX dan shortcode/block
- Struktur file class: `class-nama-class.php`, class: `KotacomAI_Nama_Class`
- Untuk pengembangan lebih lanjut, cek file sumber terkait di folder `admin/` dan `includes/`

## API & Fitur WordPress yang Digunakan

Plugin ini memanfaatkan berbagai API dan fitur WordPress inti, antara lain:

### 1. Database API (`$wpdb`)
Digunakan untuk operasi CRUD pada tabel kustom (`kotacom_keywords`, `kotacom_prompts`, `kotacom_queue`, `kotacom_batches`).
- Fungsi: `$wpdb->insert()`, `$wpdb->update()`, `$wpdb->delete()`, `$wpdb->get_results()`, `$wpdb->get_var()`, `$wpdb->prepare()`, dsb.
- Pembuatan tabel: `dbDelta()`

### 2. AJAX API
- Handler AJAX: `add_action('wp_ajax_kotacom_...')`
- Validasi nonce: `check_ajax_referer('kotacom_ai_nonce')`
- Hak akses: `current_user_can()`
- Output JSON: `wp_send_json_success()`, `wp_send_json_error()`

### 3. Action & Filter Hook
- `add_action()`, `add_filter()` untuk menghubungkan logic plugin ke lifecycle WordPress.
- Custom action/filter: `kotacom_ai_process_batch`, `kotacom_ai_post_data`, dsb.

### 4. Shortcode API
- Registrasi: `add_shortcode()`
- Handler: Fungsi public di class `KotacomAI_Content_Generator`

### 5. Gutenberg Block API
- Registrasi block: `register_block_type()`
- Script/style enqueue: `wp_enqueue_script()`, `wp_enqueue_style()`

### 6. Custom Post Type API
- Registrasi: `register_post_type('kotacom_template', ...)`

### 7. Settings API
- Registrasi pengaturan: `register_setting()`, `add_settings_section()`, `add_settings_field()`

### 8. Menu & Page Admin
- Registrasi menu: `add_menu_page()`, `add_submenu_page()`
- Pengaturan tampilan: penggunaan file di `admin/views/`

### 9. User Capability & Security
- Cek hak akses: `current_user_can()`
- Nonce: `wp_create_nonce()`, `check_admin_referer()`, `check_ajax_referer()`

### 10. Utility & Helper
- Waktu: `current_time('mysql')`
- Logging: `error_log()` (jika debug aktif)
- JSON: `json_encode()`, `json_decode()`

### 11. File & Asset Management
- Path & URL: `plugin_dir_path(__FILE__)`, `plugin_dir_url(__FILE__)`
- Enqueue asset: `admin_enqueue_scripts`, `wp_enqueue_script`, `wp_enqueue_style`

### 12. Activation/Deactivation Hook
- `register_activation_hook()`, `register_deactivation_hook()`
- Untuk pembuatan tabel database dan pembersihan data

### 13. WP Cron/Background Process
- (Jika ada) Menggunakan Action Scheduler atau WP Cron untuk proses batch/antrian

---

Untuk dokumentasi lebih lengkap, cek file `plugin.md` dan komentar di setiap file sumber.
