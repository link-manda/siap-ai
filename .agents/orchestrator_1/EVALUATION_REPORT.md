# Laporan Audit & Evaluasi Komprehensif Implementasi SIAP-AI

**Tanggal Evaluasi**: 2026-09-05  
**Auditor / Tim Penilai**: Project Orchestrator, Tim Explorer, Test Worker, & Forensic Security Auditor  
**Target Basis Kode**: `/Users/brahmanda/Sites/siap-ai` (Laravel 12 / PHP 8.5)  
**Dokumen Acuan**: `PRD.md`, `ORIGINAL_REQUEST.md`, `AGENTS.md`, `.ai/rules`

---

## 1. Executive Summary

Audit komprehensif ini dilakukan untuk memverifikasi kesesuaian implementasi basis kode **SIAP-AI** terhadap **6 Kebutuhan Utama PRD** serta validasi keandalan **Test Suite** dan batas keamanan **Role-Based Access Control (RBAC)**.

### Ringkasan Status 6 Kebutuhan Utama PRD
1. **Klasifikasi Arsip (termasuk filter level kerahasiaan)**: **Sebagian Terpenuhi**
2. **Total Arsip (statistik perhitungan di dasbor/laporan)**: **Sebagian Terpenuhi**
3. **Arsip berdasarkan Waktu Penyimpanan (tren bulanan & retensi)**: **Sebagian Terpenuhi**
4. **Arsip berdasarkan Kategori (statistik)**: **Sebagian Terpenuhi**
5. **Laporan (Dasbor Charts & Ekspor PDF/Excel)**: **Sebagian Terpenuhi**
6. **Menu Upload / Download Arsip (Watermark & Validasi File)**: **Terpenuhi**

### Ringkasan Validasi Test Suite & Integritas Kode
- **Hasil Test Suite**: **14 tests passed (33 assertions)**, 0 failed, 0 skipped, durasi 0.24s.
- **Integritas Kode (Forensic Audit)**: **CLEAN** (Tidak ditemukan kode palsu, hardcoded test expectation, atau facade untuk mengelabui pengujian).
- **Celah Keamanan Ditemukan**: Ditemukan **3 kerentanan HIGH**, **3 kerentanan MEDIUM**, dan **4 isu LOW**, termasuk kebocoran judul dokumen rahasia pada halaman audit trail dan risiko path traversal pada form simpan arsip.
- **Kepatuhan Arsitektur (AGENTS.md)**: **Tidak Patuh** pada pemisahan Service-Repository di layer Controller.

---

## 2. Matriks Evaluasi Rinci 6 Kebutuhan Utama

| No | Kebutuhan PRD | Status | Bukti Implementasi Konkret | Analisis Kesenjangan & Temuan Defek |
|:--:|:---|:---:|:---|:---|
| **1** | **Klasifikasi Arsip**<br>*(Filter level kerahasiaan: Biasa, Terbatas, Rahasia, Sangat Rahasia, AI/OCR, Search/Filter)* | **Sebagian Terpenuhi** | • `app/Models/Classification.php:10-13`<br>• `database/migrations/2026_09_04_151606_create_classifications_table.php:14`<br>• `app/Ai/Agents/ArchiveClassifier.php:13-34`<br>• `app/Services/AiClassificationService.php:14-37`<br>• `app/Policies/ArchivePolicy.php:22-30` | 1. **Nama Level Kerahasiaan di Seeder Salah**: Seeder (`DatabaseSeeder.php:34`) membuat level 2 bernama `'Penting'`, bukan `'Terbatas'` sebagaimana diwajibkan PRD.<br>2. **OCR Fisik Disimulasikan**: `ArchiveService.php:30` menggunakan fallback string `"Simulasi isi dokumen: " . $fileName` tanpa library OCR fisik.<br>3. **Ketiadaan Filter & Search**: Controller (`ArchiveController.php:29`), Repository (`ArchiveRepository.php:11`), dan View (`basic-tables.blade.php`) tidak memiliki fitur pencarian teks maupun filter dropdown level klasifikasi. |
| **2** | **Total Arsip**<br>*(Statistik perhitungan di dasbor & laporan)* | **Sebagian Terpenuhi** | • `app/Repositories/ArchiveRepository.php:62-65` (`countTotal()`)<br>• `app/Http/Controllers/DashboardController.php:16`<br>• `resources/views/pages/dashboard/ecommerce.blade.php:11-13` | 1. **Tidak Ada Ringkasan Total di Laporan Ekspor**: Ekspor Excel (`ReportExportService.php:12-28`) dan PDF (`ReportExportService.php:40-80`) hanya menampilkan baris tabel tanpa baris total/rekapitulasi.<br>2. **Pelanggaran Arsitektur**: `DashboardController` menginjeksi repository secara langsung tanpa melalui service layer.<br>3. **Ketiadaan Test**: Tidak ada pengujian otomatis untuk perhitungan total arsip maupun scoping hak akses Admin vs Pimpinan pada metrik ini. |
| **3** | **Arsip Berdasarkan Waktu Penyimpanan**<br>*(Tren bulanan & retensi)* | **Sebagian Terpenuhi** | • `app/Repositories/ArchiveRepository.php:95-115` (`getMonthlyStats()`)<br>• `app/Http/Controllers/DashboardController.php:19`<br>• `resources/views/pages/dashboard/ecommerce.blade.php:22-25, 48-58` | 1. **Fitur Retensi Arsip 0% (Belum Diimplementasikan)**: Tidak ada kolom retensi (`retention_period`, `retention_date`), tidak ada service retensi, dan tidak ada visualisasi peringatan retensi di UI.<br>2. **Inkompatibilitas Dialek Database**: Query tren bulanan menggunakan fungsi MySQL `MONTH(archives.created_at)` yang menyebabkan fatal query exception pada SQLite in-memory runner.<br>3. **Ketiadaan Filter Timeframe**: Opsi rentang waktu (harian, mingguan, tahunan) di PRD FR-02 belum diakomodasi. |
| **4** | **Arsip Berdasarkan Kategori**<br>*(Statistik & distribusi per kategori)* | **Sebagian Terpenuhi** | • `app/Repositories/ArchiveRepository.php:67-79` (`getCategoryStats()`)<br>• `app/Http/Controllers/DashboardController.php:17`<br>• `resources/views/pages/dashboard/ecommerce.blade.php:60-71` | 1. **Ketiadaan Filter Kategori pada UI Tabel Arsip**: Pengguna tidak dapat memfilter daftar arsip per kategori di `basic-tables.blade.php`.<br>2. **Kebocoran Soft Delete**: Kueri `getCategoryStats()` melakukan join langsung ke tabel `categories` tanpa klausa `whereNull('categories.deleted_at')`.<br>3. **Kueri Langsung di Controller**: `ArchiveController.php:36` memanggil `Category::all()` secara langsung. |
| **5** | **Laporan**<br>*(Dasbor Charts & Ekspor PDF/Excel)* | **Sebagian Terpenuhi** | • Dashboard Charts (Bar, Donut, Pie, Card Total) pada `ecommerce.blade.php`<br>• Ekspor CSV via SimpleExcelWriter: `ReportExportService.php:12-28`<br>• Ekspor PDF via DomPDF: `ReportExportService.php:30-80` | 1. **Tidak Ada Halaman/Menu Laporan Mandiri**: Aplikasi tidak memiliki menu atau rute `GET /reports`. Fitur ekspor hanya berupa tombol di header tabel arsip.<br>2. **Bug Kolom Skema `file_size`**: `ReportExportService.php:22` mengambil `$archive->file_size`, padahal kolom tersebut tidak ada di skema migrasi tabel `archives`. Semua baris ekspor menampilkan `0` KB.<br>3. **Cacat Filter Tanggal**: Penggunaan `whereBetween` tanpa jam (`23:59:59`) memotong arsip pada hari terakhir rentang tanggal.<br>4. **Ketiadaan Audit Trail Ekspor**: Tindakan ekspor massal data arsip tidak dicatat dalam tabel `archive_logs`. |
| **6** | **Menu Upload / Download Arsip**<br>*(Watermark dinamis & validasi file)* | **Terpenuhi** | • Validasi File: `ArchiveController.php:59-62`<br>• Form Upload Asinkron AI: `form-elements.blade.php:100-167`<br>• Otorisasi RBAC Download: `ArchivePolicy.php:22-30`<br>• Dynamic Watermarking (PDF & Gambar): `WatermarkService.php:14-93`<br>• Temporary File Cleanup: `ArchiveController.php:129-132` | 1. **Status Implementasi Baik**: Validasi file membatasi mimes (`pdf,jpg,jpeg,png`) dan ukuran maksimal 10MB. Admin diblokir (403) mengunduh Level 4, sementara Pimpinan diizinkan. Watermark menyematkan Nama User, IP, dan Timestamp.<br>2. **Catatan Keamanan**: Ditemukan celah kebocoran judul Level 4 pada Audit Trail, potensi manipulasi `file_path` pada form `store()`, dan silent fallback tanpa peringatan jika FPDI gagal memproses PDF versi 1.5+. |

---

## 3. Validasi & Analisis Cakupan Test Suite

### 3.1. Hasil Eksekusi Pengujian
Pengujian dijalankan menggunakan Pest PHP 5.1.0 (PHPUnit 13.3.1, PHP 8.5.4) dengan konfigurasi SQLite in-memory:
- **Total Test Files**: 4 berkas (seluruhnya di `tests/Feature/`, 0 berkas di `tests/Unit/`)
- **Total Test Cases**: 14 tests
- **Total Assertions**: 33 assertions
- **Status**: **14 Passed (100%)**, 0 Failed, 0 Skipped
- **Waktu Eksekusi**: 0.24 detik

### 3.2. Rincian Per Berkas Pengujian
1. `tests/Feature/ArchiveRbacTest.php` (5 tests, 10 assertions)
   - Memastikan dokumen "Sangat Rahasia" disembunyikan dari datatable Admin.
   - Memastikan dokumen "Sangat Rahasia" ditampilkan kepada Pimpinan.
   - Memastikan Admin menerima HTTP 403 Forbidden saat mengunduh Level 4.
   - Memastikan Pimpinan berhasil mengunduh dokumen Level 4.
   - Memastikan Admin dilarang menghapus dokumen Level 4.
2. `tests/Feature/ArchiveTest.php` (4 tests, 13 assertions)
   - Memastikan pemrosesan dokumen upload via AI mengembalikan output terstruktur JSON.
   - Memastikan tamu (*guest*) dilarang mengunduh arsip (redirect ke `/login`).
   - Memastikan pengguna terautentikasi dapat mengunduh arsip dan aksinya tercatat di `archive_logs`.
   - Memastikan fitur *soft delete* arsip berfungsi.
3. `tests/Feature/ArchiveWatermarkTest.php` (2 tests, 5 assertions)
   - Memastikan dokumen PDF Level 4 diproses melalui `WatermarkService`.
   - Memastikan format non-PDF (DOCX) mengunduh berkas asli dengan flash message warning.
4. `tests/Feature/ReportTest.php` (3 tests, 5 assertions)
   - Memastikan Pimpinan dapat mengekspor seluruh arsip ke Excel (HTTP 200).
   - Memastikan Pimpinan dapat mengekspor seluruh arsip ke PDF (HTTP 200).
   - Memastikan ekspor Admin mengecualikan dokumen Level 4.

### 3.3. Evaluasi Celah Cakupan Pengujian (Test Coverage Gaps)
Meskipun 14 pengujian yang ada berstatus 100% lulus, audit menemukan celah cakupan yang signifikan:
1. **0% Cakupan untuk Dashboard (Req 2, 3, 4)**: Tidak ada pengujian untuk `DashboardController::index()`, `countTotal()`, `getCategoryStats()`, maupun `getMonthlyStats()`.
2. **Bug Tersembunyi pada `getMonthlyStats()`**: Kueri menggunakan klausa MySQL `MONTH(archives.created_at)`. Jika dieksekusi di SQLite test runner, kueri langsung gagal dengan error `no such function: MONTH`. Bug ini tidak terdeteksi karena method tersebut tidak pernah dipanggil dalam test suite.
3. **Ketiadaan Uji Validasi Form Upload**: Tidak ada uji negatif untuk berkas di atas 10MB, ekstensi terlarang (misal `.exe`), atau dokumen kosong.
4. **Rute `POST /archives` (store) Belum Diuji**: Endpoint penyimpanan akhir dari form upload belum memiliki feature test.
5. **Ketiadaan Uji Boundary Tamu (*Guest*)**: Dari 9 rute terproteksi, hanya rute unduh arsip yang diuji terhadap akses tamu; rute `/dashboard`, `/archives`, `/archives/create`, `/archives/export/*`, dan `/audit-trail` belum memiliki asersi pencegahan tamu.

---

## 4. Temuan Audit Forensik Keamanan & RBAC

Berdasarkan audit forensik yang dipimpin oleh `auditor_security`, integritas kode berstatus **CLEAN** (tidak ada manipulasi pengujian). Namun, ditemukan 10 temuan kerentanan dan defek keamanan:

```
+-------------------------------------------------------------------------------+
|                      MATRIKS TEMUAN KERENTANAN & DEFEK                        |
+---------+----------+----------------------------------------------------------+
| ID      | SEVERITY | DESKRIPSI SINGKAT                                        |
+---------+----------+----------------------------------------------------------+
| VULN-01 | HIGH     | Kebocoran Judul Dokumen Sangat Rahasia di /audit-trail   |
| VULN-02 | HIGH     | Arbitrary File Path Tampering pada ArchiveController@store |
| VULN-03 | HIGH     | Hardcoded Third-Party API Key di root script test-ai.php |
| VULN-04 | MEDIUM   | Silent Fallback Watermark Mengunduh PDF Rahasia Polos    |
| VULN-05 | MEDIUM   | Logika getBaseQuery Terbalik (Hanya Membatasi Admin)     |
| VULN-06 | MEDIUM   | Null Pointer Exception (HTTP 500) pada Classification Null|
| VULN-07 | LOW      | Rute Template UI /ui/* Terbuka Publik untuk Tamu         |
| VULN-08 | LOW      | Ketiadaan Test Boundary untuk Guest Redirect pada 8 Rute |
| VULN-09 | LOW      | Ketiadaan Kolom file_size pada Skema Tabel archives      |
| VULN-10 | LOW      | Inkompatibilitas Dialek Database MySQL vs SQLite (MONTH) |
+---------+----------+----------------------------------------------------------+
```

### Rincian Temuan Kerentanan

#### 1. [VULN-01 - HIGH] Kebocoran Judul Dokumen Sangat Rahasia pada Audit Trail
- **Lokasi Kode**: `app/Http/Controllers/AuditTrailController.php:12` & `resources/views/pages/tables/audit-trail.blade.php:38`
- **Mekanisme Celah**: `AuditTrailController` melakukan kueri seluruh log `ArchiveLog::with(['user', 'archive'])->latest()->paginate(15)` tanpa memfilter level klasifikasi arsip.
- **Dampak**: Admin yang dilarang melihat arsip Level 4 ("Sangat Rahasia") pada halaman `/archives` dapat melihat judul dokumen, pengunggah, pengunduh, dan waktu unduh dokumen rahasia tersebut dengan membuka menu `/audit-trail`.

#### 2. [VULN-02 - HIGH] Arbitrary File Path Tampering pada Penyimpanan Arsip
- **Lokasi Kode**: `app/Http/Controllers/ArchiveController.php:81` & `resources/views/pages/form/form-elements.blade.php:44`
- **Mekanisme Celah**: Form penyimpanan akhir menerima `file_path` dari hidden input formulir (`<input type="hidden" name="file_path">`) tanpa validasi kepemilikan sesi atau pembatasan direktori.
- **Dampak**: Penyerang dapat mengirimkan nilai `file_path` sembarang (misal `../../.env` atau berkas milik user lain) melalui POST request, yang kemudian tersimpan di database dan dapat diunduh melalui endpoint download.

#### 3. [VULN-03 - HIGH] Hardcoded API Secret Key pada Root Script
- **Lokasi Kode**: `test-ai.php:10`
- **Mekanisme Celah**: Terdapat token API aktif yang disematkan sebagai nilai default:
  `env('OPENAI_COMPATIBLE_API_KEY', 'tp-_lYP75AYTC7l6GbWcSWJSR0-CSSBle6j8uoYC_NAAv0')`.
- **Dampak**: Kunci rahasia API berisiko bocor ke repositori publik atau disalahgunakan pihak ketiga.

#### 4. [VULN-04 - MEDIUM] Silent Fallback Watermark Mengunduh Dokumen Rahasia Tanpa Tanda Air
- **Lokasi Kode**: `app/Services/WatermarkService.php:67-70` & `app/Http/Controllers/ArchiveController.php:129-139`
- **Mekanisme Celah**: Jika FPDI gagal memproses berkas PDF versi 1.5+ (akibat kompresi object stream), blok `catch` mengembalikan berkas asli. Controller mendeteksi path yang sama dan langsung mengunduh berkas mentah.
- **Dampak**: Dokumen Sangat Rahasia terdistribusi ke luar tanpa watermark pelacak dan tanpa pencatatan peringatan keamanan pada sistem.

#### 5. [VULN-05 - MEDIUM] Logika Base Query Terbalik (Default Permissive)
- **Lokasi Kode**: `app/Repositories/ArchiveRepository.php:48-60`
- **Mekanisme Celah**: Pengecekan otorisasi menggunakan kondisi `if ($user && $user->role === 'Admin') { $q->where('level', '<=', 3); }`.
- **Dampak**: Jika sistem menambahkan role baru di masa depan (misal `Operator` atau `Staf`), atau jika kueri dieksekusi di luar sesi web pengguna, kueri secara otomatis membuka seluruh arsip Level 4. Seharusnya hanya role `Pimpinan` yang diizinkan melihat Level 4 (prinsip *least privilege*).

#### 6. [VULN-06 - MEDIUM] Potensi Null Pointer Exception (HTTP 500) pada Arsip Tanpa Klasifikasi
- **Lokasi Kode**: `app/Policies/ArchivePolicy.php:29` & `app/Http/Controllers/ArchiveController.php:119`
- **Mekanisme Celah**: Evaluasi `$archive->classification->level` mengasumsikan relasi classification selalu ada. Padahal di skema database, `classification_id` bersifat nullable dengan `nullOnDelete()`.
- **Dampak**: Jika klasifikasi dihapus atau dokumen tidak memiliki klasifikasi, aplikasi mengalami fatal crash (HTTP 500) saat user mencoba melihat atau mengunduh arsip.

---

## 5. Kepatuhan Standar Rekayasa Perangkat Lunak (AGENTS.md)

### 5.1. Evaluasi Pola Service-Repository
Standar `AGENTS.md` Bagian 1 secara mutlak mewajibkan:
> *Controller: Hanya bertugas menerima request, validasi, meneruskan ke Service, dan mengembalikan response. Dilarang keras menulis logika query database di Controller.*  
> *Repository: Murni operasi CRUD ke database.*

**Hasil Audit**: **Tidak Patuh (Non-Compliant)**.
- `ArchiveController`: Menginjeksi repository secara langsung, memanggil kueri model Eloquent langsung (`Category::all()`, `Classification::all()`), melakukan insert log langsung (`ArchiveLog::create`), dan memanggil `$archive->delete()` langsung.
- `DashboardController`: Menginjeksi repository secara langsung dan memanggil 4 agregasi data tanpa layer `DashboardService`.
- `AuditTrailController`: Menjalankan kueri Eloquent berelasi langsung di controller tanpa repository atau service.
- `AiClassificationService`: Melakukan kueri Eloquent model langsung (`Category::where`, `Classification::where`) tanpa melalui repository.

### 5.2. Evaluasi SoftDeletes
- Model `Archive` dan `Category`: **Patuh** (`use SoftDeletes;` dan kolom `deleted_at`).
- Model `Classification`: **Belum Patuh** (tidak mengimplementasikan `SoftDeletes`).
- Kueri Agregasi Kategori: Mengabaikan status soft-delete kategori pada join tabel.

---

## 6. Rencana Aksi Perbaikan (Remediation Roadmap)

Untuk mencapai status **100% Terpenuhi (Fully Satisfied)** dan mengamankan seluruh batas RBAC, disarankan langkah perbaikan bertahap berikut:

### Fase 1: Perbaikan Keamanan & Penambalan Celah RBAC (Mendesak)
1. **Perbaiki Kebocoran Audit Trail (VULN-01)**:
   - Tambahkan filter hak akses pada `AuditTrailController`: jika pengguna ber-role `Admin`, hanya tampilkan log yang arsipnya memiliki level klasifikasi $\le 3$ (atau arsip yang telah dihapus).
2. **Amankan Input `file_path` (VULN-02)**:
   - Simpan mapping upload temporer di sesi pengguna (`session(['uploaded_temp_file' => ...])`) atau simpan token upload terenkripsi, bukan mempercayai hidden input mentah dari browser.
3. **Hapus Hardcoded API Key (VULN-03)**:
   - Bersihkan token dari berkas `test-ai.php` dan pastikan konfigurasi selalu merujuk pada `.env`.
4. **Terapkan Null-Safe Operator (VULN-06)**:
   - Ubah `$archive->classification->level` menjadi `$archive->classification?->level ?? 1` di seluruh Policy dan Controller.
5. **Koreksi Logika Least Privilege pada Repository (VULN-05)**:
   - Balik logika otorisasi pada `ArchiveRepository::getBaseQuery()`: Batasi level $\le 3$ secara default untuk seluruh pengguna, kecuali jika pengguna terbukti memiliki role `Pimpinan`.

### Fase 2: Penyempurnaan 6 Kebutuhan Utama PRD
1. **Kebutuhan 1 (Klasifikasi, Search & Filter)**:
   - Koreksi seeder level 2 dari `'Penting'` menjadi `'Terbatas'`.
   - Tambahkan dropdown filter klasifikasi dan form input pencarian teks pada `basic-tables.blade.php`.
   - Tambahkan parameter `search` dan `classification_id` pada `ArchiveController::index` dan `ArchiveRepository::getAllPaginated`.
2. **Kebutuhan 3 & 4 (Retensi Arsip & Inkompatibilitas Dialek)**:
   - Implementasikan kolom `retention_period` dan `retention_date` pada tabel `archives` beserta service kalkulasi jadwal retensi.
   - Ganti fungsi MySQL `MONTH(created_at)` dengan ekspresi portabel Laravel/Carbon atau driver-specific handling (misal `strftime('%m', created_at)` untuk SQLite).
   - Tambahkan filter dropdown kategori pada view tabel arsip dan filter `whereNull('categories.deleted_at')` pada query agregasi.
3. **Kebutuhan 5 (Laporan & Ekspor)**:
   - Tambahkan migrasi kolom `file_size` (unsignedBigInteger) pada tabel `archives` dan simpan ukuran file saat proses upload.
   - Perbaiki filter tanggal ekspor agar mencakup seluruh hari (`end_date . ' 23:59:59'`).
   - Sediakan menu mandiri Laporan (`/reports`) di sidebar navigasi.
   - Tambahkan baris total arsip pada ekspor PDF dan Excel.

### Fase 3: Refaktorisasi Arsitektur & Peningkatan Test Suite
1. **Kepatuhan Service-Repository**:
   - Pindahkan seluruh pemanggilan model Eloquent di Controller ke Service/Repository masing-masing.
   - Buat `DashboardService` dan `AuditTrailService`.
2. **Perluasan Test Suite**:
   - Tambahkan feature test untuk `DashboardController` (menguji Total Arsip, Grafik Kategori, dan Grafik Bulanan).
   - Tambahkan test validasi berkas upload (ekstensi terlarang, batas ukuran 10MB).
   - Tambahkan feature test pengujian guest redirect untuk seluruh rute terproteksi.
   - Tambahkan unit test untuk real `WatermarkService` menggunakan berkas PDF dummy.

---

## 7. Kesimpulan Akhir

Basis kode **SIAP-AI** telah memiliki fondasi fungsionalitas yang solid, khususnya pada integrasi AI untuk klasifikasi dokumen, alur pemrosesan upload form-elements, mekanisme dynamic watermarking, dan penegakan policy dasar RBAC (14 test suite lulus 100%).

Namun, untuk memenuhi seluruh kriteria PRD dan standar baku proyek, aplikasi masih memerlukan:
1. Penyelesaian fitur filter pencarian, koreksi seeder kerahasiaan, dan sistem retensi arsip.
2. Penambahan menu mandiri Laporan dan perbaikan skema database `file_size`.
3. Penutupan 3 celah keamanan prioritas tinggi (terutama kebocoran audit trail dan path traversal).
4. Penyelarasan controller dengan pola arsitektur *Service-Repository* sesuai mandat `AGENTS.md`.
