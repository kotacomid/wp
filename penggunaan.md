# Cara Penggunaan Kotacom AI Content Generator

## 1. Instalasi Plugin
1. Upload folder plugin ke direktori `wp-content/plugins/` di WordPress Anda.
2. Aktifkan plugin melalui menu **Plugins** di dashboard admin WordPress.

## 2. Pengaturan Awal
1. Masuk ke menu **Kotacom AI** di sidebar admin WordPress.
2. Buka halaman **Settings**.
3. Masukkan API Key dan pilih provider AI yang diinginkan (Google AI, OpenAI, Groq, Cohere, dll).
4. Simpan pengaturan.

## 3. Manajemen Keyword
- Buka menu **Keywords**.
- Tambahkan, edit, atau hapus keyword yang akan digunakan untuk generate konten.

## 4. Manajemen Prompt
- Buka menu **Prompts**.
- Tambahkan, edit, atau hapus prompt/template AI sesuai kebutuhan konten.

## 5. Template Editor
- Buka menu **Template Editor**.
- Buat atau edit template struktur konten yang akan digunakan untuk generate post.

## 6. Generate Konten
- Buka menu **Generator**.
- Pilih keyword, prompt, dan template yang ingin digunakan.
- Klik tombol **Generate** untuk memulai proses pembuatan konten otomatis.
- Cek status antrian di menu **Queue**.

## 7. Shortcode & Block
- Gunakan shortcode berikut di post/page:
  - `[ai_content]`, `[ai_section]`, `[ai_template]`, `[ai_conditional]`, `[ai_list]`
- Atau gunakan Gutenberg block: **AI Content Block**, **AI List Block**, **AI Template Structure**.

## 8. Fitur Lain
- Cek status provider API di menu **Settings**.
- Lakukan tes koneksi API jika diperlukan.
- Semua aksi penting diamankan dengan nonce dan hak akses admin.

---

Untuk detail teknis lebih lanjut, silakan lihat file `readme.md` atau `plugin.md`.
