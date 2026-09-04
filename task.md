# Master Implementation Plan (SIAP-AI)

Checklist ini adalah panduan langkah demi langkah (terurut berdasarkan dependensi) untuk membangun Sistem Informasi Arsip Puskesmas Terintegrasi AI dari nol menggunakan Laravel 13 dan pola arsitektur Service-Repository.

### Fase 1: Setup Lingkungan & Inisialisasi Proyek (Laravel 13)
- `[ ]` Instalasi proyek baru Laravel 13.
- `[ ]` Konfigurasi `.env` untuk koneksi database lokal.
- `[ ]` Install Laravel AI SDK (`composer require laravel/ai`) untuk integrasi Gemini/OpenAI.
- `[ ]` Konfigurasi API Key di `.env` (`AI_PROVIDER_KEY`).
- `[ ]` Siapkan folder direktori `storage/app/private/archives` untuk penyimpanan file rahasia yang aman.

### Fase 2: Skema Database & Migrasi (Sesuai ERD)
- `[ ]` Update tabel `users` bawaan Laravel (tambahkan kolom enum `role`).
- `[ ]` Buat Model & Migration untuk `Category` (beserta fitur Soft Deletes).
- `[ ]` Buat Model & Migration untuk `Classification`.
- `[ ]` Buat Model & Migration untuk `Archive` (Lengkap dengan FK, kolom metadata AI, dan Soft Deletes).
- `[ ]` Buat Model & Migration untuk `ArchiveLog` (Untuk Audit Trail).
- `[ ]` Buat `DatabaseSeeder` untuk *generate* akun Admin default, daftar kategori dasar, dan daftar klasifikasi keamanan.
- `[ ]` Jalankan migrasi dan *seeding* database.

### Fase 3: Arsitektur Backend (Service-Repository Pattern)
- `[ ]` Buat struktur folder: `app/Interfaces`, `app/Repositories`, `app/Services`.
- `[ ]` Buat `ArchiveRepositoryInterface` (Mendefinisikan kontrak operasi database).
- `[ ]` Buat `ArchiveRepository` (Implementasi logika Eloquent untuk *Create, Read, Update, Delete, Filter by Date/Category*).
- `[ ]` Buat `AiClassificationService` (Fungsi khusus berinteraksi dengan API AI untuk membedah teks OCR dan mengembalikan saran Kategori & Ringkasan).
- `[ ]` Buat `ArchiveService` (Menyatukan logika upload file, pemanggilan AI, dan penyimpanan data melalui Repository).
- `[ ]` (Opsional tapi direkomendasikan) Implementasi *Laravel Observers* pada model `Archive` untuk otomatis mencatat aktivitas (Audit Trail) ke tabel `ArchiveLog`.

### Fase 4: Controllers & Routing
- `[ ]` Buat `AuthController` (Logika Login/Logout).
- `[ ]` Buat `DashboardController` (Menarik agregasi data / grafik statistik dari Repository).
- `[ ]` Buat `ArchiveController` (Menangani *request* web: form upload, tabel data, proses unduh dokumen).
- `[ ]` Amankan route (`routes/web.php`) menggunakan *Middleware Auth* agar hanya Admin/Pimpinan yang bisa mengakses data arsip.

### Fase 5: Frontend & Antarmuka (Blade / Tailwind CSS)
- `[ ]` Setup struktur layout utama (Sidebar, Header navigasi, Footer).
- `[ ]` Bangun Halaman Login.
- `[ ]` Bangun Halaman Dashboard (Tampilkan grafik batang/pie chart "Total Arsip berdasarkan Kategori & Waktu").
- `[ ]` Bangun Halaman Manajemen Arsip (Tabel *DataTables* dengan fitur pencarian dan filter *dropdown* kategori).
- `[ ]` Bangun Halaman Upload Arsip Terintegrasi AI (Ketika file diupload, muncul status *Loading AI*, lalu *form* judul, kategori, ringkasan terisi secara otomatis).
- `[ ]` Bangun Halaman Audit Trail / Log (Hanya bisa dilihat oleh akun berwenang untuk memantau aktivitas unduhan).
- `[ ]` Bangun Halaman Ekspor Laporan (Fungsi cetak rekapitulasi data).

### Fase 6: Testing (Pengujian & Finalisasi)
- `[ ]` Siapkan 3-5 dokumen uji coba (PDF/JPG dummy bertema kesehatan/Puskesmas).
- `[ ]` Uji alur Upload -> AI membaca file -> AI menebak kategori -> Form terisi otomatis.
- `[ ]` Uji keamanan unduhan (pastikan file *private* tidak bisa diakses tanpa login).
- `[ ]` Uji fitur Soft Deletes (hapus arsip dan pastikan tidak terhapus permanen di DB).
- `[ ]` Uji *Audit Trail* (pastikan saat Anda mengunduh file, terekam di log).
