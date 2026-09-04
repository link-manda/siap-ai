# Product Requirements Document (PRD)
**Sistem Informasi Arsip Puskesmas Terintegrasi AI (SIAP-AI)**

**Versi:** 1.0.0
**Status:** Draf / Review
**Tech Stack:** PHP (Laravel), MySQL, Laravel AI SDK (Google Gemini / OpenAI)

---

## 1. Visi dan Tujuan Produk
**Visi:**
Menciptakan sistem manajemen arsip Puskesmas yang modern, otomatis, dan minim *human error* melalui pemanfaatan kecerdasan buatan (AI).

**Tujuan:**
1. Mengurangi waktu yang dibutuhkan pegawai Puskesmas dalam mengurutkan dan mengkategorikan dokumen secara manual.
2. Memastikan pengelompokan arsip yang konsisten dan sesuai dengan standar tata naskah dinas/kesehatan.
3. Mempercepat proses pencarian dokumen (Retrieval) melalui penyaringan berdasarkan waktu, kategori, dan hasil analisis AI.
4. Menjadi sistem yang layak saing, berstandar industri (*Clean Architecture*), dan bernilai akademis tinggi sebagai luaran Tugas Akhir/Skripsi.

---

## 2. Target Pengguna (User Personas)
Sistem ini dirancang untuk 2 peran (role) utama:

1. **Petugas Arsip / Administrasi (Admin):**
   * **Kebutuhan:** Mengunggah ribuan surat/dokumen tiap bulan tanpa repot membaca satu per satu untuk menamai dan mengkategorikannya.
   * **Tugas:** Upload file, memeriksa hasil deteksi AI, mencetak laporan.
2. **Kepala Puskesmas (Pimpinan):**
   * **Kebutuhan:** Memantau volume administrasi Puskesmas dan mencari dokumen lama secara instan tanpa harus pergi ke gudang fisik.
   * **Tugas:** Melihat Dashboard (Total Arsip), melihat laporan berkala.

---

## 3. Ruang Lingkup Fitur (Scope)

Sistem akan berfokus pada digitalisasi dokumen fisik (yang sudah di-scan) atau dokumen digital murni (PDF) untuk diproses, disimpan, dan dilaporkan.

### 3.1. Fitur di Luar Lingkup (Out of Scope)
* Sistem tidak menyertakan alat *scanning* fisik secara langsung (terhubung ke mesin scanner/printer). File harus sudah berupa PDF/JPG/PNG.
* Sistem bukan merupakan E-Rekam Medis (EMR) yang berfokus pada pemeriksaan klinis pasien, melainkan manajemen arsip dokumen (surat, SK, berkas rekam medis pasif, laporan).

---

## 4. Kebutuhan Fungsional (Functional Requirements)

Berdasarkan *needs* awal yang ditetapkan, berikut adalah rincian fitur utama:

### FR-01: Autentikasi & Otorisasi
* Pengguna dapat melakukan Login dan Logout.
* Sistem memiliki manajemen peran (*Role-Based Access Control*) minimal untuk Admin dan Pimpinan.

### FR-02: Dashboard & Visualisasi Data
* Menampilkan **Total Arsip** secara keseluruhan.
* Menampilkan grafik batang/garis tren jumlah **Arsip berdasarkan Waktu Penyimpanan** (harian, mingguan, bulanan, tahunan).
* Menampilkan grafik *pie/donut* persentase jumlah **Arsip berdasarkan Kategori** (Misal: 30% Surat Masuk, 20% SK, 50% Rekam Medis).

### FR-03: Manajemen Arsip (Upload & Download)
* Form untuk mengunggah dokumen (format yang diizinkan: PDF, JPG, PNG). Batas maksimal 10MB per file.
* Daftar arsip berupa tabel lengkap dengan fitur *sorting*, *pagination*, dan pencarian.
* Pengguna dapat mengunduh (*download*) dokumen asli yang telah diunggah sebelumnya.

### FR-04: Integrasi AI - Klasifikasi & Ekstraksi (Fitur Inti)
* Saat proses upload, sistem akan membaca teks pada dokumen (menggunakan library OCR sederhana jika formatnya gambar, atau langsung *parse text* jika PDF).
* **Klasifikasi Arsip Otomatis**: Teks dikirim ke AI API. AI akan secara otomatis menentukan:
  1. **Kategori Arsip** (Contoh: Rekam Medis, Surat Dinas, Laporan).
  2. **Tingkat Klasifikasi/Keamanan** (Contoh: Biasa, Penting, Rahasia).
  3. **Ringkasan (Summary)**: AI mengekstrak 1-2 kalimat ringkasan tentang isi surat/dokumen.
* Sistem akan memunculkan *form pre-filled* (sudah terisi oleh AI) sehingga petugas hanya perlu meninjau (Review) dan menekan "Simpan".

### FR-05: Laporan
* Sistem dapat menghasilkan laporan rekapitulasi arsip dalam rentang waktu tertentu.
* Laporan dapat difilter berdasarkan Kategori tertentu atau keseluruhan.
* Mendukung fungsi cetak (*Print/Export*) dokumen dalam format PDF atau Excel.

### FR-06: Manajemen Master Data
* Fitur untuk menambah, mengubah, atau menghapus (CRUD) daftar **Kategori Arsip**.
* Fitur untuk mengelola **Klasifikasi Keamanan**.

---

## 5. Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **Arsitektur Kode:** Harus mengimplementasikan **Service-Repository Pattern** pada kerangka kerja Laravel untuk memastikan skalabilitas dan *Clean Code*.
2. **Kinerja (Performance):**
   * Halaman harus memuat dalam waktu maksimal 2 detik.
   * Proses klasifikasi AI mungkin memakan waktu (bergantung pada *latency API*). Sistem harus memberikan indikator pemuatan (*loading state / spinner*) saat memanggil AI. Opsional: Pemrosesan AI dilakukan di *background job/queue* jika dokumen sangat besar.
3. **Keamanan (Security):**
   * Perlindungan dari serangan standar (CSRF, XSS, SQL Injection) ditangani secara bawaan oleh Laravel.
   * *Data Privacy*: Jika menggunakan API pihak ketiga (OpenAI/Gemini), dalam konteks pengembangan/Tugas Akhir, wajib didemokan dengan data tiruan (dummy) agar data sensitif medis tidak terkirim ke server eksternal.
4. **Desain Antarmuka (UI/UX):**
   * Responsif (dapat diakses nyaman melalui Tablet/Laptop/PC).
   * Menggunakan framework CSS yang rapi (seperti Tailwind CSS atau Bootstrap 5).

---

## 6. Desain Entitas Data (Database Schema / ERD)

Struktur tabel relasional inti yang dibutuhkan:

1. **users**
   * id, name, email, password, role_id, created_at
2. **categories** (Master Data Kategori)
   * id, name (contoh: "Surat Keputusan"), description, created_at
3. **classifications** (Master Data Klasifikasi)
   * id, name (contoh: "Rahasia", "Biasa"), level, created_at
4. **archives** (Tabel Utama)
   * id
   * title (Judul / Nama Dokumen)
   * file_path (Lokasi penyimpanan file di server lokal)
   * file_type (MIME type: pdf, image, dll)
   * category_id (Foreign Key ke categories, disarankan oleh AI)
   * classification_id (Foreign Key ke classifications, disarankan oleh AI)
   * summary (Teks ringkasan dari AI)
   * ai_confidence_score (Menyimpan tingkat keyakinan AI atas tebakannya: 0-100%)
   * uploaded_by (Foreign Key ke users)
   * document_date (Tanggal dokumen fisik)
   * created_at (Waktu penyimpanan di sistem)

---

## 7. User Flow (Alur Pengguna) - Proses Upload & Klasifikasi

1. **Admin** menekan tombol "Upload Arsip Baru".
2. **Admin** memilih file PDF atau gambar (JPG/PNG).
3. **Sistem (Frontend)** menampilkan indikator proses: *"Sedang membaca dokumen..."*.
4. **Sistem (Backend)** membaca dokumen (ekstrak teks) dan mengirimkannya ke `AiClassificationService`.
5. **AI Service** mengembalikan respons JSON berisi rekomendasi: `{kategori_id: 2, klasifikasi_id: 1, ringkasan: "Surat permohonan alkes..."}`.
6. **Sistem (Frontend)** memuat form dengan bidang yang sudah terisi otomatis dari data AI.
7. **Admin** meninjau (*Review*). Jika AI salah, Admin dapat memilih kategori yang benar (koreksi).
8. **Admin** menekan "Simpan".
9. Data tersimpan melalui `ArchiveRepository`.

---

## 8. Kriteria Penerimaan (Acceptance Criteria / Pengujian)

Proyek (Skripsi) dianggap **BERHASIL** jika:
1. Sistem berhasil diinstal dan dijalankan (*localhost/server*).
2. Data grafik pada dashboard berbanding lurus dan akurat sesuai jumlah data di tabel arsip.
3. Saat sebuah dokumen surat dinas/medis tiruan diunggah, AI dapat menebak Kategori dan memberikan Ringkasan isi dengan bahasa yang masuk akal tanpa input manual terlebih dahulu.
4. Fitur filter pada halaman tabel arsip dan Laporan berfungsi sempurna berdasarkan waktu dan kategori.
5. Struktur *source code* terbukti memisahkan *logic* HTTP (Controller) dan logika database/AI (Service-Repository).
