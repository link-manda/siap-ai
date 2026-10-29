# LAPORAN EVALUASI KEAMANAN & AUDIT MIDDLEWARE
## Fitur: Pratinjau dan Streaming Arsip Multiformat (SIAP-AI)

- **Tanggal Audit**: 5 September 2026
- **Auditor**: Software Engineering & Security Audit Team (Reviewer / Security Auditor)
- **Target Aplikasi**: SIAP-AI (Sistem Informasi Arsip Pintar berbasis AI)
- **Ruang Lingkup**: Endpoint `stream` & `preview` pada `ArchiveController`, view `resources/views/pages/archives/preview.blade.php`, konfigurasi rute & middleware pada `routes/web.php`, otorisasi `ArchivePolicy`, skema database `archives`, serta dependensi terkait (`WatermarkService`, `ArchiveRepository`, `ArchiveObserver`).
- **Pedoman Keamanan**: `laravel-best-practices` (`rules/security.md`, `rules/routing.md`, `rules/validation.md`, `rules/blade-views.md`), Standar Arsitektur SIAP-AI (`AGENTS.md`), OWASP Top 10.
- **Batasan Audit**: Bersifat audit murni (*read-only*), tanpa modifikasi kode aplikasi (*zero code modifications*).

---

## DAFTAR ISI
1. [Ringkasan Eksekutif (Executive Summary)](#1-ringkasan-eksekutif-executive-summary)
2. [Ruang Lingkup & Metodologi Audit](#2-ruang-lingkup--metodologi-audit)
3. [Analisis Kerentanan RBAC (Level 4 / Sangat Rahasia)](#3-analisis-kerentanan-rbac-level-4--sangat-rahasia)
4. [Analisis Keamanan Rendering Frontend (Word / Excel / PDF)](#4-analisis-keamanan-rendering-frontend-word--excel--pdf)
5. [Analisis Mekanisme & Kebocoran Watermark](#5-analisis-mekanisme--kebocoran-watermark)
6. [Analisis Path Traversal, Validasi File & Keamanan HTTP](#6-analisis-path-traversal-validasi-file--keamanan-http)
7. [Matriks Evaluasi Efektivitas Kontrol Keamanan Saat Ini](#7-matriks-evaluasi-efektivitas-kontrol-keamanan-saat-ini)
8. [Rencana Remediasi & Rekomendasi Patch (Laravel Best Practices)](#8-rencana-remediasi--rekomendasi-patch-laravel-best-practices)
9. [Kesimpulan Akhir Auditor](#9-kesimpulan-akhir-auditor)

---

## 1. Ringkasan Eksekutif (Executive Summary)

Audit keamanan menyeluruh dilakukan terhadap fitur baru **"Pratinjau dan Streaming Arsip Multiformat"** pada sistem SIAP-AI. Fitur ini dirancang untuk memungkinkan pengguna melihat langsung arsip dokumen dalam berbagai format (PDF, Gambar, Word `.doc`/`.docx`, dan Excel `.xls`/`.xlsx`) melalui peramban web dengan proteksi *watermark* dinamis dan pembatasan hak akses berbasis peran (*Role-Based Access Control* / RBAC).

### Ringkasan Temuan Utama:
1. **CRITICAL - Otorisasi Bypass Dokumen Level 4 (Sangat Rahasia) via Parameter Re-registration**:
   Pengguna dengan peran `Admin` (yang dilarang mengakses dokumen Level 4) dapat mendaftarkan ulang *file path* fisik dokumen Level 4 yang tersimpan di server melalui endpoint `POST /archives` dengan memanipulasi `classification_id` ke Level 1 (`Biasa`). Setelah tersimpan, dokumen Sangat Rahasia tersebut dapat di-*stream*, di-*preview*, dan diunduh secara penuh tanpa *watermark* dan tanpa halangan kebijakan otorisasi pada level policy (`ArchiveController.php:84–106`, `ArchivePolicy.php:28–30`).
2. **CRITICAL - Kerentanan Stored DOM XSS pada Perenderan Word (`mammoth.js`)**:
   File `resources/views/pages/archives/preview.blade.php` (baris 70–74) langsung menyuntikkan hasil konversi HTML Word (`result.value`) ke dalam DOM menggunakan `target.innerHTML` tanpa proses sanitasi (ketiadaan pustaka DOMPurify). Dokumen Word yang memuat hyperlink dengan skema `javascript:` atau tag HTML berbahaya dapat mengeksekusi skrip dalam konteks sesi peramban pengguna.
3. **HIGH - Kerentanan DOM Injection pada Perenderan Spreadsheet Excel (`xlsx.js`)**:
   Konversi tabel Excel ke string HTML oleh SheetJS langsung disuntikkan ke `target.innerHTML` setelah manipulasi *string replacement* sederhana tanpa sanitasi DOM, membuka celah injeksi HTML/atribut pada peramban (`preview.blade.php:78–87`).
4. **HIGH - Iframe PDF Tanpa Sandbox (Same-Origin Execution)**:
   Perenderan PDF menggunakan `<iframe src="${streamUrl}">` tanpa atribut `sandbox` pada domain/origin yang sama, memungkinkan objek scriptable (PDF embedded JS `/JS` atau MIME confusion) dieksekusi dengan akses penuh ke *cookies*, DOM induk, dan sesi aktif (`preview.blade.php:53–59`).
5. **HIGH - Kebocoran Total Watermark pada Dokumen Word & Excel Level 4**:
   Logika *watermark* server-side pada `ArchiveController::stream()` dan `ArchiveController::download()` secara eksklusif hanya memproses format PDF dan Gambar. Dokumen Word dan Excel Sangat Rahasia di-*stream* dan diunduh dalam format biner mentah (*raw original binary*) tanpa watermark server sama sekali (`ArchiveController.php:133–151`, `ArchiveController.php:203–206`).
6. **HIGH - Fail-Open pada Kegagalan Watermarking Server**:
   Jika terjadi pengecualian (*exception*) saat menerapkan *watermark* PDF/Gambar di `WatermarkService`, sistem mencatat log *warning* tetapi tetap menyajikan file asli yang tidak diberi watermark kepada klien (*fail-open* alih-alih *fail-safe* / abort) (`ArchiveController.php:142–150`, `WatermarkService.php:67–70`).
7. **HIGH - Bypass Watermark Sisi Klien Secara Trivial**:
   Watermark visual pada `preview.blade.php` diimplementasikan murni menggunakan elemen CSS overlay (`pointer-events-none`). Pengguna dapat menghapus atau menonaktifkan watermark tersebut dalam hitungan detik melalui Developer Tools, atau langsung mengunduh biner utuh via tab *Network* atau *Fetch API* (`preview.blade.php:19–28, 63`).
8. **HIGH - Null Pointer Exception, Fall-Through Access & Missing Role Check pada `ArchivePolicy`**:
   `ArchivePolicy::view()` mengakses `$archive->classification->level` tanpa null-check. Jika klasifikasi arsip bernilai `null`, di lingkungan web Laravel memicu HTTP 500 (`ErrorException`), sementara dalam evaluasi logika murni PHP `null <= 3` bernilai `true` (`bool(true)`), yang secara tidak sengaja memberikan akses tidak sah kepada pengguna non-Pimpinan (`ArchivePolicy.php:28–30`). Selain itu, ketiadaan pemeriksaan eksplisit `if ($user->role === 'Admin')` menyebabkan peran apapun selain Pimpinan otomatis mewarisi akses Level 1–3 (*Missing Role Enforcement*).

### Matriks Keparahan Kerentanan:
| ID Temuan | Kategori | Deskripsi Singkat | Tingkat Keparahan | Status Kontrol Saat Ini |
|---|---|---|:---:|:---:|
| **SEC-RBAC-01** | RBAC / Otorisasi | Bypass Dokumen Level 4 via Re-Registrasi `file_path` | **CRITICAL** | **Tidak Efektif** |
| **SEC-RBAC-02** | RBAC / Stabilitas | Null Pointer Exception & Fall-Through Access pada `ArchivePolicy` | **HIGH** | **Rentan** |
| **SEC-RBAC-03** | RBAC / Information Leak | Kebocoran Metadata Klasifikasi & Database Query di Blade View | **MEDIUM** | **Rentan & Non-compliant** |
| **SEC-RBAC-04** | Audit Logging | Ketiadaan Audit Trail untuk Akses Endpoint `preview` & `export` | **MEDIUM** | **Parsial** |
| **SEC-RBAC-05** | RBAC / Arsitektur | Inkonsistensi Policy `viewAny` & Ketiadaan Otorisasi pada Export | **MEDIUM** | **Rentan** |
| **SEC-FE-01** | Frontend / XSS | Injeksi DOM / Stored XSS via Mammoth.js (Word) | **CRITICAL** | **Tidak Ada Kontrol** |
| **SEC-FE-02** | Frontend / XSS | Injeksi DOM via SheetJS (Excel) | **HIGH** | **Tidak Ada Kontrol** |
| **SEC-FE-03** | Frontend / Sandbox | Unsandboxed Iframe untuk Streaming PDF | **HIGH** | **Tidak Ada Kontrol** |
| **SEC-FE-04** | Frontend / Sanitasi | Injeksi Pesan Error pada DOM (`err.message`) | **LOW** | **Rentan** |
| **SEC-FE-05** | Coding Standard | Pelanggaran Standar Blade-JS Interpolation (`AGENTS.md`) | **LOW** | **Non-compliant** |
| **SEC-WM-01** | Watermark Leak | Tidak Ada Watermark Server untuk Word & Excel Level 4 | **HIGH** | **Tidak Efektif** |
| **SEC-WM-02** | Watermark Leak | Fail-Open Server-Side Watermark pada Exception | **HIGH** | **Rentan** |
| **SEC-WM-03** | Watermark Bypass | Manipulasi Parameter `file_type` untuk Menghindari Watermark | **HIGH** | **Tidak Efektif** |
| **SEC-WM-04** | Watermark Leak | Trivial Client-Side CSS Watermark Stripping | **HIGH** | **Tidak Efektif** |
| **SEC-WM-05** | Watermark Geometry | Kelemahan Geometri Watermark & Semantik Teks | **MEDIUM** | **Sub-optimal** |
| **SEC-FS-01** | Path / Input | Validasi `file_path` Menerima Direktori | **MEDIUM** | **Rentan** |
| **SEC-FS-02** | HTTP Headers | Ketiadaan `Cache-Control`, `nosniff`, CSP & Quote Injection di `Content-Disposition` | **MEDIUM** | **Tidak Efektif** |
| **SEC-FS-03** | Rate Limiting | Ketiadaan Throttle & Risiko Resource Exhaustion DoS | **MEDIUM** | **Rentan** |
| **SEC-FS-04** | Storage / Temp | Akumulasi File Sementara Tanpa Garbage Collection di `storage/app/temp` | **LOW** | **Rentan** |
| **SEC-FS-05** | HTTP / Audit Trail | Ketiadaan Proteksi CORP & Side-Effect Audit Log pada Request GET | **LOW** | **Rentan** |

---

## 2. Ruang Lingkup & Metodologi Audit

### 2.1. Berkas yang Diaudit
Audit dilakukan secara mendalam pada berkas-berkas berikut:
1. `app/Http/Controllers/ArchiveController.php` (Fokus: metode `stream`, `preview`, `store`, `processAi`, `download`, `exportExcel`, `exportPdf`).
2. `routes/web.php` (Definisi rute dan grup middleware `auth`).
3. `app/Policies/ArchivePolicy.php` (Logika otorisasi peran `Admin` vs `Pimpinan` dan evaluasi level klasifikasi).
4. `resources/views/pages/archives/preview.blade.php` (Implementasi antarmuka perenderan frontend Word/Excel/PDF dan *watermark overlay*).
   > *Catatan Lokasi Berkas*: Pada deskripsi tugas awal tercantum `resources/views/archives/preview.blade.php`, namun berdasarkan struktur repositori aktual dan deklarasi pada `ArchiveController::preview()` baris 222 (`return view('pages.archives.preview', compact('archive'));`), berkas yang aktif digunakan adalah `resources/views/pages/archives/preview.blade.php`.
5. `app/Services/WatermarkService.php` (Layanan injeksi watermark PDF via FPDI dan Gambar via Intervention Image).
6. `app/Repositories/ArchiveRepository.php` (Query database dasar dan *scoping* hak akses `getBaseQuery`).
7. `app/Observers/ArchiveObserver.php` (Event listener pencatatan log audit).
8. `database/migrations/2026_09_04_151607_create_archives_table.php` (Skema integritas data dan nullability).
9. `database/migrations/2026_09_04_151608_create_archive_logs_table.php` (Skema foreign key audit log).
10. `resources/views/pages/tables/basic-tables.blade.php` (Dropdown filter klasifikasi).

### 2.2. Metodologi
- **Static Code Analysis & Logic Tracing**: Menelusuri alur eksekusi data dari permintaan HTTP masuk (*request*), validasi data, evaluasi *gate/policy*, interaksi penyimpanan fisik (*filesystem*), hingga pembentukan respon HTTP (*response header & body*).
- **Adversarial Runtime Verification (Tinker & Pest)**: Menguji hipotesis celah keamanan secara langsung di lingkungan PHP/Laravel menggunakan Artisan Tinker dan Pest feature tests (misalnya verifikasi `Storage::exists('private/archives')`, evaluasi `null <= 3`, dan re-registrasi `file_path`).
- **Client-Side Security Review**: Menginspeksi integrasi pustaka pihak ketiga JavaScript (`mammoth.browser.js`, `xlsx.full.min.js`), manipulasi DOM via `innerHTML`, penanganan *error event*, ketiadaan DOMPurify, dan resistensi *anti-screenshot overlay*.
- **Role-Based Access Control Matrix**: Menguji pemisahan wewenang antara pengguna `Pimpinan` dan `Admin` pada level 1 (Biasa), 2 (Terbatas), 3 (Rahasia), dan 4 (Sangat Rahasia).

---

## 3. Analisis Kerentanan RBAC (Level 4 / Sangat Rahasia)

### 3.1. [SEC-RBAC-01] Otorisasi Bypass Dokumen Level 4 via Re-Registrasi `file_path` (CRITICAL)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 84–95, Baris 106
  - `app/Policies/ArchivePolicy.php`: Baris 28–30
- **Bukti Kode (Evidence)**:
  Pada `ArchiveController::store()`:
  ```php
  84:             'file_path' => [
  85:                 'required',
  86:                 'string',
  87:                 function ($attribute, $value, $fail) {
  88:                     if (! str_starts_with($value, 'private/archives/') || str_contains($value, '..')) {
  89:                         $fail('File path tidak valid.');
  90:                     }
  91:                     if (! Storage::exists($value)) {
  92:                         $fail('File tidak ditemukan di server.');
  93:                     }
  94:                 },
  95:             ],
  ...
  106:         $this->archiveService->storeArchive($validated, Auth::id());
  ```
  Pada `ArchivePolicy::view()`:
  ```php
  22:     public function view(User $user, Archive $archive): bool
  23:     {
  24:         if ($user->role === 'Pimpinan') {
  25:             return true;
  26:         }
  27: 
  28:         // Admin can only view if classification level is <= 3
  29:         return $archive->classification->level <= 3;
  30:     }
  ```
- **Mekanisme Eksploitasi**:
  1. Pengguna dengan peran `Admin` dilarang oleh kebijakan `ArchivePolicy` untuk melihat (`view`), melihat pratinjau (`preview`), mengalirkan (`stream`), maupun mengunduh (`download`) arsip dengan klasifikasi Level 4 (*Sangat Rahasia*).
  2. Ketika sebuah dokumen Sangat Rahasia diunggah (misalnya oleh Pimpinan), file tersebut tersimpan secara fisik di disk privat pada path `private/archives/<hash>.<ext>`.
  3. Form simpan arsip (`POST /archives`) menerima parameter `file_path` langsung dari input pengguna tanpa memverifikasi kepemilikan sesi (*ownership*), tanpa mengaitkan ke token unggah sementara (*temporary upload token*), dan tanpa memeriksa apakah path file tersebut sudah diasosiasikan dengan arsip berkategori Sangat Rahasia.
  4. Pengguna `Admin` dapat mengirimkan permintaan HTTP `POST /archives` dengan menyertakan nilai `file_path` dari file Sangat Rahasia tersebut, namun sengaja menyetel `classification_id = 5` (Level 1, Biasa).
  5. Sistem membuat rekaman arsip baru di database yang merujuk ke file fisik yang sama dengan klasifikasi Level 1.
  6. Ketika `Admin` mengakses `/archives/{new_id}/stream` atau `/archives/{new_id}/preview`, `ArchivePolicy::view()` mengevaluasi `classification->level <= 3` sebagai **TRUE** (`1 <= 3`).
  7. Dokumen rahasia berhasil diakses dan dibaca secara penuh oleh `Admin`. Selain itu, karena klasifikasinya tercatat sebagai Level 1, sistem bahkan **tidak menyertakan watermark server** sama sekali saat dokumen dialirkan.
- **Dampak Bisnis & Keamanan**: Kerahasiaan data medis/pasien Puskesmas berstatus Sangat Rahasia bocor sepenuhnya ke akun staf/Admin biasa.

### 3.2. [SEC-RBAC-02] Null Pointer Exception, Fall-Through Access & Missing Role Check pada `ArchivePolicy` & `ArchiveController` (HIGH)
- **File Terdampak**:
  - `app/Policies/ArchivePolicy.php`: Baris 22–30
  - `app/Http/Controllers/ArchiveController.php`: Baris 133, Baris 186
  - `database/migrations/2026_09_04_151607_create_archives_table.php`: Baris 22
- **Bukti Kode (Evidence)**:
  Pada skema database (`create_archives_table.php`):
  ```php
  22:             $table->foreignId('classification_id')->nullable()->constrained()->nullOnDelete();
  ```
  Pada `ArchivePolicy`:
  ```php
  22:     public function view(User $user, Archive $archive): bool
  23:     {
  24:         if ($user->role === 'Pimpinan') {
  25:             return true;
  26:         }
  27: 
  28:         // Admin can only view if classification level is <= 3
  29:         return $archive->classification->level <= 3;
  30:     }
  ```
  Pada `ArchiveController` (`stream` dan `download`):
  ```php
  133:         if ($archive->classification->level === 4) {
  ...
  186:         if ($archive->classification->level === 4) {
  ```
- **Mekanisme Masalah**:
  1. **Null Pointer Exception / DoS pada Policy**: Kolom `classification_id` pada tabel `archives` bersifat *nullable* dan memiliki relasi `nullOnDelete()`. Jika data klasifikasi dihapus dari database atau arsip tersimpan tanpa klasifikasi yang valid, relasi `$archive->classification` bernilai `null`. Dalam konteks otorisasi web Laravel, pemanggilan `$archive->classification->level` pada `ArchivePolicy.php:29` memicu `ErrorException: Attempt to read property "level" on null` yang mengembalikan HTTP 500 (*Internal Server Error*).
  2. **Null Pointer Exception pada Controller (`stream` & `download`)**: Terlebih lagi, pada `ArchiveController.php:133` (metode `stream`) dan baris 186 (metode `download`), controller mengevaluasi `$archive->classification->level === 4` secara langsung tanpa operator null-safe (`?->`). Akibatnya, bahkan untuk pengguna `Pimpinan` yang lolos pengecekan policy di baris 24, pemanggilan endpoint `stream` atau `download` pada arsip tanpa klasifikasi tetap berujung fatal pada HTTP 500 *unhandled exception*.
  3. **Fall-Through Truthy Evaluation**: Dalam evaluasi logika murni PHP (atau lingkungan dengan peredam error), pembacaan properti pada null menghasilkan `null`. Dalam PHP dinamis, ekspresi perbandingan `null <= 3` menghasilkan nilai boolean **TRUE** (`bool(true)`). Akibatnya, arsip tanpa klasifikasi secara tidak sengaja diizinkan untuk diakses oleh non-Pimpinan.
  4. **Missing Role Check (Ketiadaan Pemeriksaan Peran Admin)**: Baris 24 hanya memeriksa `if ($user->role === 'Pimpinan')`. Jika kondisi tidak terpenuhi, kode langsung mengeksekusi baris 29 tanpa memastikan bahwa `$user->role === 'Admin'`. Apabila sistem menambahkan peran lain di masa mendatang (misalnya `Staf`, `Auditor`, `Tamu`, atau `Operator`), seluruh peran tersebut secara otomatis mewarisi hak akses ke seluruh arsip Level 1, 2, dan 3. Hal ini melanggar prinsip *Default Deny* dan *Principle of Least Privilege*.

### 3.3. [SEC-RBAC-03] Kebocoran Metadata Klasifikasi Sangat Rahasia & Database Query di Blade View (MEDIUM)
- **File Terdampak**:
  - `resources/views/pages/tables/basic-tables.blade.php`: Baris 38–41
  - `app/Http/Controllers/ArchiveController.php`: Baris 37–44
- **Bukti Kode (Evidence)**:
  Pada `basic-tables.blade.php`:
  ```blade
  38:                     @foreach(\App\Models\Classification::all() as $classification)
  39:                         <option value="{{ $classification->id }}" {{ request('classification_id') == $classification->id ? 'selected' : '' }}>{{ $classification->name }}</option>
  40:                     @endforeach
  ```
  Pada `ArchiveController::index()`:
  ```php
  37:         $archives = $this->archiveRepository->getAllPaginated(10, $filters);
  38:         $categories = Category::all();
  39: 
  40:         return view('pages.tables.basic-tables', [
  41:             'title' => 'Manajemen Arsip',
  42:             'archives' => $archives,
  43:             'categories' => $categories,
  44:         ]);
  ```
- **Mekanisme Masalah**:
  1. **Pelanggaran Laravel Best Practices**: `ArchiveController::index` mengirimkan `$categories` ke view, namun lupa mengirimkan data klasifikasi. Akibatnya, template Blade menjalankan kueri database langsung `\App\Models\Classification::all()` di dalam template Blade. Ini melanggar aturan `rules/blade-views.md`: *"Keep database access out of Blade views and prevent hidden N+1 queries"*.
  2. **Kebocoran Metadata**: View menampilkan seluruh klasifikasi yang ada di database termasuk "Sangat Rahasia" ke akun Admin. Walaupun `ArchiveRepository::getBaseQuery()` menyaring baris tabel arsip agar Admin tidak dapat melihat dokumen Level 4, keberadaan opsi "Sangat Rahasia" membocorkan struktur keamanan institusi dan membingungkan pengguna non-Pimpinan.

### 3.4. [SEC-RBAC-04] Ketiadaan Audit Trail pada Endpoint Preview & Export Serta Batasan Skema Enum Log (MEDIUM)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 122–128 (pada `stream`), Baris 212–223 (pada `preview`), Baris 240–268 (pada `exportExcel` dan `exportPdf`)
  - `database/migrations/2026_09_04_151608_create_archive_logs_table.php`: Baris 18
- **Bukti Kode (Evidence)**:
  Pada `ArchiveController::stream()`:
  ```php
  122:         ArchiveLog::create([
  123:             'archive_id' => $archive->id,
  124:             'user_id' => Auth::id(),
  125:             'action' => 'VIEW',
  126:             'ip_address' => request()->ip(),
  127:             'user_agent' => request()->userAgent(),
  128:         ]);
  ```
  Sedangkan pada `ArchiveController::preview()`:
  ```php
  212:     public function preview($id)
  213:     {
  214:         $archive = $this->archiveRepository->findById($id);
  215: 
  216:         if (! $archive) {
  217:             abort(404);
  218:         }
  219: 
  220:         $this->authorize('view', $archive);
  221: 
  222:         return view('pages.archives.preview', compact('archive'));
  223:     }
  ```
  Pada skema database `create_archive_logs_table.php`:
  ```php
  18:             $table->enum('action', ['UPLOAD', 'VIEW', 'DOWNLOAD', 'EDIT', 'DELETE', 'RESTORE']);
  ```
- **Mekanisme Masalah**:
  1. Endpoint `preview` sama sekali tidak mencatat aktivitas ke tabel `archive_logs`. Pencatatan log hanya terjadi jika skrip sisi klien berhasil memanggil endpoint `stream`. Jika klien memblokir eksekusi JavaScript, mengalami kegagalan jaringan, atau peramban menutup sebelum request `fetch` selesai, audit trail tidak memiliki rekam jejak bahwa pengguna telah membuka halaman dokumen rahasia tersebut.
  2. **Batasan Skema Database Enum `action`**: Kolom `action` pada tabel `archive_logs` dibatasi oleh skema database: `$table->enum('action', ['UPLOAD', 'VIEW', 'DOWNLOAD', 'EDIT', 'DELETE', 'RESTORE'])`. Skema ini **tidak memuat** nilai `'PREVIEW'`. Jika pengembang mencoba mencatat log pada `preview()` menggunakan nilai `'PREVIEW'` tanpa menjalankan migrasi skema terlebih dahulu, basis data (MySQL/PostgreSQL) akan melemparkan `QueryException / Data truncated error` yang memicu HTTP 500. Oleh karena itu, remediasi harus memigrasikan kolom enum atau menyelaraskan aksi menggunakan `'action' => 'VIEW'`.
  3. Metode `exportExcel` dan `exportPdf` (baris 240–268) tidak memanggil otorisasi policy (`$this->authorize('viewAny', Archive::class)`), dan hanya mengandalkan pemanggilan massal kueri repositori.

### 3.5. [SEC-RBAC-05] Inkonsistensi Arsitektur Policy `viewAny` & Ketiadaan Otorisasi pada Endpoint Export (MEDIUM)
- **File Terdampak**:
  - `app/Policies/ArchivePolicy.php`: Baris 14–17
  - `app/Http/Controllers/ArchiveController.php`: Baris 240–268
- **Bukti Kode (Evidence)**:
  Pada `ArchivePolicy.php`:
  ```php
  14:     public function viewAny(User $user): bool
  15:     {
  16:         return false;
  17:     }
  ```
  Pada `ArchiveController.php` (`exportExcel` dan `exportPdf`):
  ```php
  240:     public function exportExcel(Request $request, ReportExportService $exportService)
  241:     {
  ...
  249:         $archives = $this->archiveRepository->getAllForExport($filters);
  ...
  255:     public function exportPdf(Request $request, ReportExportService $exportService)
  256:     {
  ...
  264:         $archives = $this->archiveRepository->getAllForExport($filters);
  ```
- **Mekanisme Masalah**:
  1. **Inkonsistensi Policy Framework**: `ArchivePolicy::viewAny()` dikonfigurasi dengan nilai kembali statis `return false;`. Di sisi controller, `ArchiveController::index()`, `exportExcel()`, dan `exportPdf()` sama sekali tidak menerapkan otorisasi policy (`$this->authorize('viewAny', Archive::class)`).
  2. Apabila di masa mendatang pengembang menerapkan standar `laravel-best-practices` (`rules/routing.md` & `rules/security.md`) dengan mendaftarkan `$this->authorizeResource(Archive::class)` di konstruktor controller atau memanggil `authorize('viewAny')`, seluruh halaman indeks dan ekspor arsip akan langsung memicu HTTP 403 (*Forbidden*) untuk semua peran, termasuk pengguna `Pimpinan`.
  3. Ketiadaan otorisasi eksplisit pada endpoint ekspor (`exportExcel` dan `exportPdf`) juga melewatkan lapisan otorisasi Laravel, murni mengandalkan *scoping* query di level repositori tanpa perlindungan *gate* pertahanan berlapis (*defense-in-depth*).

---

## 4. Analisis Keamanan Rendering Frontend (Word / Excel / PDF)

### 4.1. [SEC-FE-01] Injeksi DOM / Stored XSS pada Konversi Dokumen Word (`mammoth.js`) (CRITICAL)
- **File Terdampak**:
  - `resources/views/pages/archives/preview.blade.php`: Baris 70–74
- **Bukti Kode (Evidence)**:
  ```javascript
  70:                     if (['docx', 'doc'].includes(fileType)) {
  71:                         window.mammoth.convertToHtml({arrayBuffer: buffer})
  72:                             .then(function(result) {
  73:                                 target.innerHTML = `<div class="text-left w-full max-w-4xl prose">${result.value}</div>`;
  74:                             })
  ```
- **Mekanisme Kerentanan**:
  1. Pustaka `mammoth.js` bertugas mengonversi elemen dokumen Word (`.docx`) menjadi tag HTML (paragraf, heading, tabel, gambar, pranala).
  2. Dokumentasi resmi `mammoth.js` secara eksplisit menyatakan:
     > *"Mammoth generates HTML. If you are displaying this HTML in a browser, you should sanitize it using a library such as DOMPurify to prevent XSS attacks."*
  3. Dokumen Word dapat memuat tautan atau elemen berformat XML dengan skema URI berbahaya, seperti:
     `<w:hyperlink r:id="rId1" history="1"><w:r><w:t>Klik Di Sini</w:t></w:r></w:hyperlink>` di mana relasi `rId1` mengarah ke `javascript:alert(document.cookie)`.
  4. Baris 73 langsung menetapkan `result.value` ke `target.innerHTML` **tanpa proses sanitasi HTML sama sekali**. Pustaka `DOMPurify` tidak dimuat di aplikasi (`package.json` tidak menyertakan `dompurify`).
  5. Akibatnya, ketika pengguna membuka pratinjau dokumen Word yang telah dipersiapkan oleh penyerang, script jahat akan dieksekusi secara otomatis atau saat elemen diklik, memungkinkan pencurian token sesi, pembajakan aksi pengguna (*session hijacking*), dan defacement internal.

### 4.2. [SEC-FE-02] Kerentanan Injeksi DOM pada Perenderan Excel (`xlsx.js`) (HIGH)
- **File Terdampak**:
  - `resources/views/pages/archives/preview.blade.php`: Baris 78–87
- **Bukti Kode (Evidence)**:
  ```javascript
  78:                     } else if (['xlsx', 'xls'].includes(fileType)) {
  79:                         const workbook = window.XLSX.read(buffer, {type: 'array'});
  80:                         const firstSheet = workbook.SheetNames[0];
  81:                         const htmlstr = window.XLSX.utils.sheet_to_html(workbook.Sheets[firstSheet], { id: "excel-table" });
  82:                         
  83:                         // Add some tailwind classes to the generated table string
  84:                         let styledHtml = htmlstr.replace('<table', '<table class="w-full table-auto border-collapse border border-gray-300"');
  85:                         styledHtml = styledHtml.replace(/<td/g, '<td class="border border-gray-300 px-4 py-2"');
  86:                         
  87:                         target.innerHTML = `<div class="overflow-x-auto w-full">${styledHtml}</div>`;
  88:                     }
  ```
- **Mekanisme Kerentanan**:
  1. `window.XLSX.utils.sheet_to_html` menghasilkan dokumen HTML penuh (`<html><head>...<body><table>...</table></body></html>`).
  2. Modifikasi atribut hanya mengandalkan `replace('<table', ...)` dan `replace(/<td/g, ...)`.
  3. Sel spreadsheet atau nama lembar kerja (*sheet name*) yang memuat teks dengan karakter kutip atau entitas khusus yang dirancang untuk memecah atribut (misalnya atribut `data-v` atau formula kustom) disuntikkan secara mentah ke `target.innerHTML`.
  4. Ketiadaan sanitasi DOMPurify memungkinkan penyerang menyematkan muatan XSS di dalam sel spreadsheet atau nama sheet yang akan dieksekusi saat pratinjau dimuat.

### 4.3. [SEC-FE-03] Unsandboxed Iframe untuk Perenderan PDF Sisi Klien (HIGH)
- **File Terdampak**:
  - `resources/views/pages/archives/preview.blade.php`: Baris 53–59
- **Bukti Kode (Evidence)**:
  ```javascript
  53:             let element = '';
  54:             if(fileType === 'pdf') {
  55:                 element = `<iframe src="${streamUrl}" class="w-full h-full border-0"></iframe>`;
  56:             } else {
  57:                 element = `<img src="${streamUrl}" class="max-w-full object-contain" />`;
  58:             }
  59:             target.innerHTML = element;
  ```
- **Mekanisme Kerentanan**:
  1. Tag `<iframe>` yang dibuat tidak memiliki atribut `sandbox` (misalnya `sandbox="allow-scripts"` atau pembatasan origin).
  2. Sumber iframe (`streamUrl`) berada pada *origin* yang sama persis (*same origin*) dengan aplikasi web utama (`/archives/{id}/stream`).
  3. Format PDF mendukung skrip tertanam (*embedded JavaScript actions* via kamus `/JS` atau `/JavaScript` pada objek PDF). Jika peramban atau pembaca PDF terintegrasi mengeksekusi skrip tersebut, skrip akan berjalan dalam konteks origin aplikasi web, memberikan akses membaca DOM induk (`window.parent`), *LocalStorage*, dan *session cookies*.
  4. Selain itu, jika file yang disimpan bertipe MIME `text/html` atau `image/svg+xml` yang disamarkan dengan ekstensi `.pdf`, iframe akan mengeksekusi HTML/SVG tersebut secara penuh di origin aplikasi.

### 4.4. [SEC-FE-04] Injeksi Pesan Error pada Handler Penanganan Kesalahan (LOW)
- **File Terdampak**:
  - `resources/views/pages/archives/preview.blade.php`: Baris 76, Baris 91
- **Bukti Kode (Evidence)**:
  ```javascript
  76: target.innerHTML = `<div class="text-red-500">Error memuat dokumen Word: ${err.message}</div>`;
  ...
  91: target.innerHTML = `<div class="text-red-500">Error mengambil dokumen: ${err.message}</div>`;
  ```
- **Mekanisme Kerentanan**:
  Properti `err.message` digabungkan langsung ke dalam template string HTML dan dimasukkan ke `innerHTML`. Jika parser menghasilkan pesan error yang menyertakan fragmen nama tag XML atau entitas dari dokumen yang korup, fragmen tersebut berpotensi diinterpretasikan sebagai markup HTML oleh peramban.

### 4.5. [SEC-FE-05] Pelanggaran Standar Penulisan JavaScript & Blade SIAP-AI (LOW)
- **File Terdampak**:
  - `resources/views/pages/archives/preview.blade.php`: Baris 46–47
- **Bukti Kode (Evidence)**:
  ```blade
  46:         const fileType = "{{ strtolower($archive->file_type) }}";
  47:         const streamUrl = "{{ route('archives.stream', $archive->id) }}";
  ```
- **Kesesuaian Standar**:
  Berdasarkan berkas aturan proyek `AGENTS.md` Bagian 3:
  > *"Aturan Javascript: Tidak boleh menyisipkan fungsi PHP/Blade ({{ }}) secara langsung ke dalam blok algoritma/fungsi Javascript. Semua transisi data Blade ke JS harus menggunakan deklarasi variabel const atau let di bagian paling atas blok <script>, atau menggunakan direktif @json()."*
  Penggunaan tanda kutip ganda `""` dengan interpolasi Blade `{{ }}` di dalam JavaScript rentan terhadap sintaks error jika data mengandung karakter kutip atau kontrol yang tidak lolos sanitasi.

---

## 5. Analisis Mekanisme & Kebocoran Watermark

### 5.1. [SEC-WM-01] Ketiadaan Watermark Server pada Dokumen Word & Excel Level 4 (HIGH)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 133–151 (pada `stream()`), Baris 186–207 (pada `download()`)
  - `app/Services/WatermarkService.php`: Baris 33–40
- **Bukti Kode (Evidence)**:
  Pada `ArchiveController::stream()`:
  ```php
  133:         if ($archive->classification->level === 4) {
  134:             $ext = strtolower($archive->file_type);
  135:             if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
  136:                 $watermarkedPath = $this->watermarkService->applyWatermark(
  137:                     $archive,
  138:                     Auth::user(),
  139:                     request()->ip()
  140:                 );
  ...
  150:             }
  151:         }
  ```
  Pada `ArchiveController::download()`:
  ```php
  203:             } else {
  204:                 // If it's docx/xlsx, flash a warning but still download original
  205:                 session()->flash('warning', 'File asli diunduh. Format ini tidak mendukung watermark keamanan otomatis.');
  206:             }
  207:         }
  ...
  209:         return Storage::download($archive->file_path, $archive->title.'.'.$archive->file_type);
  ```
  Pada `WatermarkService::applyWatermark()`:
  ```php
  33:         if ($ext === 'pdf') {
  34:             return $this->watermarkPdf($sourcePath, $tempFile, $text);
  35:         } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
  36:             return $this->watermarkImage($sourcePath, $tempFile, $text);
  37:         }
  38: 
  39:         return $sourcePath;
  ```
- **Mekanisme Masalah**:
  1. Jika sebuah arsip memiliki klasifikasi Level 4 (*Sangat Rahasia*) namun format filenya adalah Word (`.docx`, `.doc`) atau Excel (`.xlsx`, `.xls`), kondisi `in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])` pada baris 135 bernilai **FALSE**.
  2. Alur kode langsung melompat ke baris 157 (`return response()->file($filePath, ...);`) pada `stream()` dan baris 209 (`return Storage::download(...)`) pada `download()`.
  3. Dokumen Word atau Excel Sangat Rahasia disajikan **secara utuh dan bersih tanpa watermark apapun dari sisi server**.
  4. Karena file ini dikirim langsung ke peramban untuk dibaca oleh `fetch(streamUrl)`, seluruh biner dokumen rahasia dapat diambil dan disimpan tanpa ada jejak digital atau identitas pengguna yang tertanam pada file fisik.

### 5.2. [SEC-WM-02] Fail-Open Architecture pada Kegagalan Watermarking Server (HIGH)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 142–150 (pada `stream()`), Baris 196–202 (pada `download()`)
  - `app/Services/WatermarkService.php`: Baris 67–70, Baris 90–92
- **Bukti Kode (Evidence)**:
  Pada `WatermarkService::watermarkPdf()`:
  ```php
  67:         } catch (\Exception $e) {
  68:             // Fallback to original if PDF is encrypted or unsupported version
  69:             return $sourcePath;
  70:         }
  ```
  Pada `ArchiveController::stream()`:
  ```php
  142:                 if ($watermarkedPath !== $filePath) {
  143:                     return response()->file($watermarkedPath, [
  144:                         'Content-Type' => mime_content_type($watermarkedPath),
  145:                         'Content-Disposition' => 'inline; filename="'.$archive->title.'.'.$archive->file_type.'"',
  146:                     ])->deleteFileAfterSend(true);
  147:                 } else {
  148:                     Log::warning("Watermark failed or bypassed for archive ID: {$archive->id} by user ".Auth::user()->email);
  149:                 }
  ```
  Pada `ArchiveController::download()`:
  ```php
  196:                 if ($watermarkedPath !== Storage::path($archive->file_path)) {
  197:                     return response()->download($watermarkedPath, $archive->title.'.'.$archive->file_type)
  198:                         ->deleteFileAfterSend(true);
  199:                 } else {
  200:                     Log::warning("Watermark failed or bypassed for archive ID: {$archive->id} by user ".Auth::user()->email);
  201:                     session()->flash('warning', 'Gagal menerapkan watermark keamanan. File asli diunduh.');
  202:                 }
  ```
- **Mekanisme Masalah**:
  1. Pustaka FPDI (versi gratis) tidak mendukung PDF versi 1.5+ dengan tabel *cross-reference stream* terkompresi atau PDF yang memiliki enkripsi/password. Ketika dokumen semacam itu diproses, blok `catch (\Exception $e)` dieksekusi dan mengembalikan `$sourcePath` (file asli tanpa watermark).
  2. Pada `ArchiveController::stream()`, ketika `$watermarkedPath === $filePath`, controller hanya menulis pesan peringatan di log sistem (`Log::warning`), kemudian **tetap menyajikan file asli yang tidak di-watermark ke pengguna pada baris 157**. Demikian pula pada `download()`, sistem hanya memberikan flash session message dan tetap mengunduh file asli.
  3. Ini merupakan pelanggaran serius terhadap prinsip *Fail-Safe Defaults*: pada dokumen berklasifikasi Sangat Rahasia, jika proteksi watermark gagal diterapkan, sistem wajib membatalkan pengiriman dokumen (*abort 500 / 422*) daripada membiarkan file rahasia bocor tanpa penanda keamanan.

### 5.3. [SEC-WM-03] Bypass Watermark via Manipulasi Ekstensi `file_type` (HIGH)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 96, Baris 134–135
- **Bukti Kode (Evidence)**:
  Pada `store()`:
  ```php
  96:             'file_type' => ['required', 'string'],
  ```
  Pada `stream()`:
  ```php
  134:             $ext = strtolower($archive->file_type);
  135:             if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
  ```
- **Mekanisme Masalah**:
  1. Nilai kolom `file_type` disimpan langsung dari input pengguna tanpa divalidasi silang terhadap ekstensi riil file fisik pada disk.
  2. Jika pengguna mengunggah file PDF Level 4, tetapi saat submit form `store` memanipulasi parameter `file_type` menjadi `docx` atau `bin`, nilai `$archive->file_type` di database tersimpan sebagai `docx`.
  3. Saat dokumen dialirkan melalui `stream()`, baris 135 mengecek ekstensi dari database (`$archive->file_type`), yang bernilai `docx`, sehingga proses watermarking dilewati.
  4. Pada baris 158, `mime_content_type($filePath)` memeriksa file riil di disk dan mengembalikan `application/pdf`. File PDF rahasia tersebut dialirkan tanpa watermark!

### 5.4. [SEC-WM-04] Bypass Watermark Sisi Klien Secara Trivial (HIGH)
- **File Terdampak**:
  - `resources/views/pages/archives/preview.blade.php`: Baris 19–28, Baris 63
- **Bukti Kode (Evidence)**:
  ```blade
  19:         <!-- Watermark Overlay (Anti Screenshot) -->
  20:         <div class="absolute inset-0 pointer-events-none z-50 flex items-center justify-center opacity-30 overflow-hidden">
  21:             <div class="w-[200%] h-[200%] flex flex-wrap -rotate-45 items-center justify-center pointer-events-none" style="gap: 50px;">
  22:                 @for($i=0; $i<50; $i++)
  23:                     <span class="text-2xl font-bold text-gray-400 select-none">
  24:                         Dilihat oleh {{ Auth::user()->name }}<br>{{ request()->ip() }}
  25:                     </span>
  26:                 @endfor
  27:             </div>
  28:         </div>
  ```
- **Mekanisme Masalah**:
  1. Lapisan watermark pada antarmuka peramban hanya berupa elemen HTML `<div>` dengan kelas CSS Tailwind (`pointer-events-none opacity-30`).
  2. Pengguna mana pun dapat menghilangkan watermark ini seketika dengan membuka menu *Inspect Element* (F12) dan menghapus elemen DOM tersebut atau menambahkan aturan CSS `display: none !important;`.
  3. Sifat `pointer-events-none` bahkan memfasilitasi pengguna untuk menyalin teks di bawah overlay tanpa halangan.
  4. Untuk format Word dan Excel, file diunduh secara penuh melalui `fetch(streamUrl)` (baris 63). Pengguna dapat membuka tab *Network* di Developer Tools atau mengetik `window.open(streamUrl)` di konsol untuk mengunduh biner murni dokumen tanpa watermark.

### 5.5. [SEC-WM-05] Kelemahan Geometri & Inkonsistensi Semantik pada `WatermarkService` (MEDIUM)
- **File Terdampak**:
  - `app/Services/WatermarkService.php`: Baris 16–21, Baris 59–61, Baris 80–85
- **Bukti Kode (Evidence)**:
  ```php
  16:         $text = sprintf(
  17:             'DIUNDUH OLEH: %s | IP: %s | TANGGAL: %s',
  18:             $user->name,
  19:             $ipAddress,
  20:             now()->format('Y-m-d H:i:s')
  21:         );
  ...
  59:                 $y = $size['height'] - 10;
  60:                 $pdf->SetXY(0, $y);
  61:                 $pdf->Cell($size['width'], 10, $text, 0, 0, 'C');
  ```
- **Mekanisme Masalah**:
  1. **Inkonsistensi Semantik**: Teks watermark bertuliskan `"DIUNDUH OLEH: ..."` meskipun aksi yang dilakukan oleh pengguna adalah pratinjau (*streaming/view*), bukan download.
  2. **Kelemahan Geometri**: Watermark pada PDF diletakkan di koordinat `$size['height'] - 10` (tepat 10 mm di bagian paling bawah halaman) dalam satu baris horisontal statis. Penempatan ini sangat mudah dihilangkan dengan memotong margin bawah dokumen (*cropping*) menggunakan perangkat lunak PDF sederhana. Watermark seharusnya dicetak menyilang secara diagonal (45 derajat) dengan transparansi di tengah badan teks halaman (*full-page diagonal stamp*).

---

## 6. Analisis Path Traversal, Validasi File & Keamanan HTTP

### 6.1. [SEC-FS-01] Validasi Menerima Direktori sebagai File Path (MEDIUM)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 84–95
- **Bukti Kode (Evidence)**:
  ```php
  88:                     if (! str_starts_with($value, 'private/archives/') || str_contains($value, '..')) {
  89:                         $fail('File path tidak valid.');
  90:                     }
  91:                     if (! Storage::exists($value)) {
  92:                         $fail('File tidak ditemukan di server.');
  93:                     }
  ```
- **Mekanisme Masalah**:
  1. Aturan validasi memeriksa apakah string diawali dengan `private/archives/` dan tidak mengandung `..`.
  2. Jika pengguna mengirimkan nilai persis `'private/archives/'` atau `'private/archives'`, fungsi `Storage::exists('private/archives')` mengembalikan nilai `true` karena direktori tersebut ada di filesystem.
  3. Ketika path direktori tersebut tersimpan di database dan dipanggil oleh `stream()`:
     ```php
     157: return response()->file($filePath, ...);
     ```
     Symfony `BinaryFileResponse` akan mengalami kegagalan (*FileException: The file does not exist or is a directory*) yang memicu HTTP 500 (*unhandled server exception*).
  4. Validasi seharusnya memastikan bahwa target path berstatus sebagai file (`is_file`) dan bukan direktori.

### 6.2. [SEC-FS-02] Ketiadaan Header Keamanan, Cache-Control & Risiko Injeksi Header pada Respon Stream (MEDIUM)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 143–146, Baris 157–160
- **Bukti Kode (Evidence)**:
  Pada `ArchiveController::stream()`:
  ```php
  143:                     return response()->file($watermarkedPath, [
  144:                         'Content-Type' => mime_content_type($watermarkedPath),
  145:                         'Content-Disposition' => 'inline; filename="'.$archive->title.'.'.$archive->file_type.'"',
  146:                     ])->deleteFileAfterSend(true);
  ...
  157:         return response()->file($filePath, [
  158:             'Content-Type' => mime_content_type($filePath),
  159:             'Content-Disposition' => 'inline; filename="'.$archive->title.'.'.$archive->file_type.'"',
  160:         ]);
  ```
- **Mekanisme Masalah**:
  1. **Header Injection / Content-Disposition Splitting**: Nilai `$archive->title` tidak disanitasi dari tanda kutip ganda `"` atau karakter baris baru `\r\n`. Jika judul dokumen memuat karakter kutip (misalnya `Laporan "Tahunan"`), header `Content-Disposition` akan rusak dan peramban dapat menginterpretasikan parameter filename secara tidak terduga.
  2. **Ketiadaan `X-Content-Type-Options: nosniff`**: Respon stream tidak menyertakan header proteksi sniffing MIME type. Jika pengguna mengunggah file polimorfik atau teks yang mengandung script, peramban dapat mengeksekusi konten tersebut.
  3. **Ketiadaan `Content-Security-Policy`**: Respon inline tidak dibatasi oleh CSP, sehingga jika dokumen yang di-*stream* di-render oleh peramban (misalnya file SVG yang dianggap gambar), script yang disematkan dapat berjalan leluasa.
  4. **Ketiadaan Header `Cache-Control` (`no-store, private`)**: Respon streaming file biner pada baris 143–146 dan 157–160 sama sekali tidak mengatur header kontrol cache (`Cache-Control: private, no-store, no-cache, must-revalidate` atau `Pragma: no-cache`). Akibatnya, peramban klien atau proxy perantara jaringan dapat menyimpan salinan cache dokumen rahasia/sangat rahasia secara lokal di penyimpanan disk, sehingga dokumen sensitif dapat diakses secara offline setelah pengguna logout atau diekstrak oleh pengguna lain pada komputer bersama (*shared workstation*).

### 6.3. [SEC-FS-03] Ketiadaan Rate Limiting & Risiko Resource Exhaustion DoS (MEDIUM)
- **File Terdampak**:
  - `routes/web.php`: Baris 22–33
  - `app/Http/Controllers/ArchiveController.php`: Baris 59 (`set_time_limit(120);`)
- **Bukti Kode (Evidence)**:
  Pada `routes/web.php`:
  ```php
  22:     Route::prefix('archives')->name('archives.')->group(function () {
  ...
  27:         Route::post('/process-ai', [ArchiveController::class, 'processAi'])->name('process-ai');
  ...
  30:         Route::get('/{id}/stream', [ArchiveController::class, 'stream'])->name('stream');
  31:         Route::get('/{id}/preview', [ArchiveController::class, 'preview'])->name('preview');
  ```
- **Mekanisme Masalah**:
  1. Seluruh endpoint pemrosesan arsip (`stream`, `preview`, `process-ai`) berada di bawah middleware umum `auth` tanpa middleware pembatas laju (`throttle`).
  2. Endpoint `stream` untuk PDF Level 4 melakukan operasi komputasi intensif (membuka PDF via FPDI, mengimpor halaman satu per satu, dan merender ulang font di memori) pada **setiap permintaan HTTP**.
  3. Pengguna terotentikasi dapat membanjiri (*flooding*) endpoint `stream` atau `process-ai` (yang memiliki batas waktu eksekusi hingga 120 detik) dengan skrip otomatis, menghabiskan alokasi worker PHP-FPM dan memicu *Denial of Service* (DoS) terhadap pengguna lain.

### 6.4. [SEC-FS-04] Akumulasi File Sementara Tanpa Garbage Collection di Storage (LOW)
- **File Terdampak**:
  - `app/Services/WatermarkService.php`: Baris 26–31
  - `app/Http/Controllers/ArchiveController.php`: Baris 146
- **Bukti Kode (Evidence)**:
  ```php
  26:         $tempDir = storage_path('app/temp');
  27:         if (! is_dir($tempDir)) {
  28:             mkdir($tempDir, 0755, true);
  29:         }
  30: 
  31:         $tempFile = $tempDir.'/'.uniqid('wm_').'.'.$ext;
  ```
- **Mekanisme Masalah**:
  1. Ketika proses watermarking dipanggil, file baru dibuat di `storage/app/temp/wm_<uniqid>.<ext>`.
  2. Meskipun `deleteFileAfterSend(true)` dipasang di controller, jika klien memutuskan koneksi HTTP di tengah jalan (*client aborted*), terjadi timeout jaringan, atau exception terjadi sebelum pengiriman selesai, berkas sementara tersebut tetap tersimpan di disk.
  3. Tidak ada scheduled task (`php artisan schedule:run`) yang membersihkan berkas usang di `storage/app/temp/`, berisiko menghabiskan kapasitas disk server dalam jangka panjang.

### 6.5. [SEC-FS-05] Ketiadaan Proteksi Cross-Origin Resource Policy (CORP) & Side-Effect Audit Trail pada Request GET (LOW)
- **File Terdampak**:
  - `app/Http/Controllers/ArchiveController.php`: Baris 111–128
  - `routes/web.php`: Baris 30
- **Bukti Kode (Evidence)**:
  Pada `ArchiveController.php`:
  ```php
  111:     public function stream($id)
  112:     {
  ...
  121:         // Record Audit Log for VIEW
  122:         ArchiveLog::create([
  123:             'archive_id' => $archive->id,
  124:             'user_id' => Auth::id(),
  125:             'action' => 'VIEW',
  126:             'ip_address' => request()->ip(),
  127:             'user_agent' => request()->userAgent(),
  128:         ]);
  ```
  Pada `routes/web.php`:
  ```php
  30:         Route::get('/{id}/stream', [ArchiveController::class, 'stream'])->name('stream');
  ```
- **Mekanisme Masalah**:
  1. **Side-Effect pada HTTP Safe Method (GET)**: Berdasarkan standar HTTP RFC 9110 dan prinsip RESTful Laravel, metode `GET` harus bersifat *safe* dan *idempotent*. Namun pada `ArchiveController::stream()`, setiap permintaan `GET` memicu penulisan rekaman database baru pada tabel `archive_logs` (baris 122–128).
  2. **Audit Trail Pollution via Cross-Site Inclusion**: Karena endpoint ini diakses via GET tanpa CSRF token dan tidak menyertakan header `Cross-Origin-Resource-Policy: same-origin`, situs eksternal atau email HTML phishing dapat menyertakan elemen gambar `<img src="https://siap-ai.domain/archives/{id}/stream">`. Saat browser pengguna terotentikasi merender elemen tersebut, peramban mengirimkan cookie sesi secara otomatis, memicu injeksi log audit `VIEW` palsu di sistem SIAP-AI yang dapat memanipulasi akuntabilitas audit forensik.

---

## 7. Matriks Evaluasi Efektivitas Kontrol Keamanan Saat Ini

| Tujuan Keamanan (*Security Objective*) | Kontrol Keamanan Saat Ini | Evaluasi Efektivitas | Analisis Celah & Bukti Kode |
|---|---|---|---|
| **Pencegahan Akses Dokumen Level 4 oleh Admin** | `ArchivePolicy::view()` & `ArchiveRepository::getBaseQuery()` | **GAGAL (Ineffective)** | Admin dapat mendaftarkan ulang file path dokumen Sangat Rahasia pada form store dengan klasifikasi Level 1 (`ArchiveController.php:84–106`). |
| **Pencegahan Kebocoran File Mentah Level 4** | Watermark dinamis via `WatermarkService` | **GAGAL (Ineffective)** | File Word & Excel Level 4 dilewati tanpa watermark (`ArchiveController.php:135, 205`). Kegagalan FPDI memicu fail-open menyajikan file asli (`ArchiveController.php:148, 201`). |
| **Pencegahan Unduhan Tanpa Izin pada Pratinjau** | Watermark CSS overlay `preview.blade.php` | **GAGAL (Ineffective)** | Watermark hanya manipulasi visual DOM (`preview.blade.php:20–28`). File biner utuh dapat ditarik via `fetch(streamUrl)` dari tab Network. |
| **Integritas Penayangan Dokumen Word/Excel** | Perenderan `mammoth.js` & `xlsx.js` sisi klien | **RENTAN (Vulnerable)** | Eksekusi `target.innerHTML` tanpa sanitasi HTML DOMPurify membuka celah Stored DOM XSS (`preview.blade.php:73, 87`). |
| **Isolasi Eksekusi Skrip pada PDF** | Perenderan `<iframe src="${streamUrl}">` | **RENTAN (Vulnerable)** | Iframe berjalan di same-origin tanpa atribut `sandbox`, mengizinkan skrip PDF dieksekusi di domain aplikasi (`preview.blade.php:55`). |
| **Audit Trail & Akuntabilitas Pengguna** | Logging `ArchiveLog::create` pada `stream()` | **PARSIAL (Partially Effective)** | Endpoint `preview()` dan `export` tidak mencatat log sama sekali (`ArchiveController.php:212–223, 240–268`). Aksi viewing dicatat dengan teks watermark "DIUNDUH OLEH". Permintaan GET memicu efek samping log audit (`ArchiveController.php:122–128`). Skema enum `action` tidak menyertakan `'PREVIEW'` (`create_archive_logs_table.php:18`). |
| **Pencegahan Path Traversal & Invalid Input** | Validasi `str_starts_with` & `str_contains('..')` | **CUKUP (Moderately Effective)** | Mencegah `..`, namun mengizinkan path direktori dan tidak membatasi kepemilikan file sementara antar sesi pengguna (`ArchiveController.php:88`). |
| **Proteksi Cache Dokumen Rahasia & Header Keamanan** | Default header `response()->file()` Laravel | **GAGAL (Ineffective)** | Ketiadaan `Cache-Control: private, no-store`, `nosniff`, CSP, dan CORP (`ArchiveController.php:143–146, 157–160`). |
| **Otorisasi Konsisten pada Index & Bulk Export** | Query filter `ArchiveRepository::getBaseQuery` | **PARSIAL (Partially Effective)** | `ArchivePolicy::viewAny` hardcoded `false` (`ArchivePolicy.php:14–17`), sementara `exportExcel` & `exportPdf` tidak melakukan otorisasi policy (`ArchiveController.php:240–268`). |

---

## 8. Rencana Remediasi & Rekomendasi Patch (Laravel Best Practices)

Berdasarkan pedoman `laravel-best-practices` (`rules/security.md`, `rules/routing.md`, `rules/validation.md`, `rules/blade-views.md`), berikut adalah rekomendasi perbaikan kode secara komprehensif:

### 8.1. Perbaikan Otorisasi & Validasi File Path (`ArchiveController.php` & `ArchivePolicy.php`)
1. **Gunakan Token Sesi / Draft Table untuk Unggahan Sementara**:
   Jangan izinkan klien mengirimkan string `file_path` secara bebas. Simpan metadata file hasil `processAi` di dalam sesi pengguna (`session()->put("draft_{$draftId}", $path)`) atau buat tabel `draft_uploads` dengan relasi `user_id`. Pada metode `store()`, ambil path file dari sesi/tabel draft tersebut.
2. **Validasi File Path Fisik dan Cegah Direktori**:
   ```php
   // app/Http/Requests/StoreArchiveRequest.php
   'file_path' => [
       'required',
       'string',
       function ($attribute, $value, $fail) {
           if (! str_starts_with($value, 'private/archives/') || str_contains($value, '..')) {
               $fail('File path tidak valid.');
           }
           $fullPath = Storage::path($value);
           if (! Storage::exists($value) || ! is_file($fullPath)) {
               $fail('File fisik tidak valid atau berupa direktori.');
           }
       },
   ],
   ```
3. **Terapkan Default Deny & Null-Safety pada `ArchivePolicy`**:
   ```php
   // app/Policies/ArchivePolicy.php
   public function view(User $user, Archive $archive): bool
   {
       if ($user->role === 'Pimpinan') {
           return true;
       }

       if ($user->role === 'Admin') {
           // Pastikan klasifikasi ada dan bernilai <= 3
           $level = $archive->classification?->level;
           return $level !== null && $level <= 3;
       }

       // Default Deny untuk peran lain
       return false;
   }
   ```
4. **Perbaiki Nilai Kembalian `viewAny` & Tambahkan Otorisasi pada Export**:
   ```php
   // app/Policies/ArchivePolicy.php
   public function viewAny(User $user): bool
   {
       // Izinkan pengguna terotentikasi melihat katalog arsip
       return in_array($user->role, ['Admin', 'Pimpinan']);
   }

   // app/Http/Controllers/ArchiveController.php (pada exportExcel & exportPdf)
   $this->authorize('viewAny', Archive::class);
   ```

### 8.2. Perbaikan Keamanan Rendering Frontend (`preview.blade.php`)
1. **Pasang dan Gunakan DOMPurify via NPM Bundling**:
   Hindari pemanggilan pustaka keamanan via external CDN di view Blade agar kompatibel dengan intranet Puskesmas (offline) dan kebijakan ketat Content Security Policy (`script-src 'self'`). Pasang melalui npm:
   ```bash
   npm install dompurify
   npm install --save-dev @types/dompurify
   ```
   Daftarkan di `resources/js/app.js`:
   ```javascript
   import DOMPurify from 'dompurify';
   window.DOMPurify = DOMPurify;
   ```
   Lalu terapkan sanitasi sebelum manipulasi DOM di `resources/views/pages/archives/preview.blade.php`:
   ```javascript
   // Word (mammoth.js)
   window.mammoth.convertToHtml({arrayBuffer: buffer})
       .then(function(result) {
           const cleanHtml = window.DOMPurify.sanitize(result.value);
           target.innerHTML = `<div class="text-left w-full max-w-4xl prose">${cleanHtml}</div>`;
       })
       .catch(function(err) {
           target.textContent = 'Error memuat dokumen Word: ' + err.message;
       });

   // Excel (xlsx.js)
   const cleanExcelHtml = window.DOMPurify.sanitize(styledHtml);
   target.innerHTML = `<div class="overflow-x-auto w-full">${cleanExcelHtml}</div>`;
   ```
2. **Terapkan Sandbox pada Iframe PDF**:
   ```html
   <iframe src="${streamUrl}" sandbox="allow-scripts" class="w-full h-full border-0"></iframe>
   ```
3. **Patuhi Aturan Blade-JS Interpolation (`AGENTS.md`)**:
   Gunakan `@json()` untuk mencegah syntax breakage:
   ```javascript
   const fileType = @json(strtolower($archive->file_type));
   const streamUrl = @json(route('archives.stream', $archive->id));
   ```

### 8.3. Perbaikan Fail-Safe Watermark & Dukungan Format Office
1. **Ubah Menjadi Fail-Safe (Fail-Closed)**:
   Jika watermarking gagal pada dokumen Level 4, batalkan respon dan jangan sajikan dokumen mentah:
   ```php
   if ($archive->classification?->level === 4) {
       $watermarkedPath = $this->watermarkService->applyWatermark($archive, Auth::user(), request()->ip());
       
       if ($watermarkedPath === $filePath) {
           Log::critical("Security Alert: Watermarking failed for Level 4 Archive ID: {$archive->id}");
           abort(500, 'Dokumen keamanan tinggi tidak dapat dialirkan karena kegagalan sistem proteksi.');
       }

       return response()->file($watermarkedPath, [...])->deleteFileAfterSend(true);
   }
   ```
2. **Konversi Dokumen Office ke PDF Sebelum Streaming**:
   Untuk format `.docx` dan `.xlsx` berkategori Level 4, lakukan konversi sisi server ke PDF (misalnya menggunakan LibreOffice headless / Gotenberg) agar dapat dibubuhi watermark fisik sebelum dikirim ke klien.
3. **Pencatatan Audit Trail untuk Preview & Keselarasan Skema Enum**:
   Tambahkan pencatatan log pada `ArchiveController::preview()`. Perhatikan batasan skema enum database (`create_archive_logs_table.php:18`):
   - **Opsi A (Rekomendasi Cepat - Tanpa Migrasi)**: Gunakan aksi `'action' => 'VIEW'` yang sudah diizinkan oleh enum skema saat ini:
     ```php
     ArchiveLog::create([
         'archive_id' => $archive->id,
         'user_id' => Auth::id(),
         'action' => 'VIEW',
         'ip_address' => request()->ip(),
         'user_agent' => request()->userAgent(),
     ]);
     ```
   - **Opsi B (Pemisahan Aksi Preview)**: Buat file migrasi baru untuk memperluas kolom enum agar menyertakan `'PREVIEW'` (`$table->enum('action', ['UPLOAD', 'VIEW', 'PREVIEW', 'DOWNLOAD', 'EDIT', 'DELETE', 'RESTORE'])->change();`) sebelum menuliskan log berstatus `PREVIEW`.

### 8.4. Sanitasi Header HTTP, Rate Limiting, Signed URLs & Pembersihan File Temp
1. **Header Keamanan Lengkap & RFC 6266 Content-Disposition**:
   Gunakan `HeaderUtils::makeDisposition()` bawaan Symfony/Laravel untuk menangani encoding karakter internasional/kutip secara aman:
   ```php
   use Symfony\Component\HttpFoundation\HeaderUtils;

   $disposition = HeaderUtils::makeDisposition(
       HeaderUtils::DISPOSITION_INLINE,
       $archive->title . '.' . $archive->file_type
   );

   return response()->file($filePath, [
       'Content-Type' => mime_content_type($filePath),
       'Content-Disposition' => $disposition,
       'X-Content-Type-Options' => 'nosniff',
       'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self';",
       'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
       'Pragma' => 'no-cache',
       'Cross-Origin-Resource-Policy' => 'same-origin',
   ]);
   ```
2. **Terapkan Middleware Throttle**:
   ```php
   // routes/web.php
   Route::get('/{id}/stream', [ArchiveController::class, 'stream'])
       ->middleware('throttle:30,1')
       ->name('stream');
   ```
3. **Gunakan Temporary Signed URLs untuk Streaming**:
   ```php
   // Pada ArchiveController::preview:
   $streamUrl = URL::temporarySignedRoute(
       'archives.stream',
       now()->addMinutes(15),
       ['id' => $archive->id]
   );

   // Pada routes/web.php:
   Route::get('/{id}/stream', [ArchiveController::class, 'stream'])
       ->middleware(['auth', 'signed'])
       ->name('stream');
   ```
4. **Jadwalkan Pembersihan Direktori Temp**:
   Buat scheduled task di `app/Console/Kernel.php` atau `routes/console.php`:
   ```php
   Schedule::call(function () {
       $files = Storage::disk('local')->files('temp');
       foreach ($files as $file) {
           if (now()->subHours(2)->timestamp > Storage::disk('local')->lastModified($file)) {
               Storage::disk('local')->delete($file);
           }
       }
   })->hourly();
   ```

---

## 9. Kesimpulan Akhir Auditor

Implementasi fitur "Pratinjau dan Streaming Arsip Multiformat" pada SIAP-AI berhasil menyajikan fleksibilitas penayangan multi-dokumen bagi pengguna. Namun, fitur ini memiliki **celah keamanan kritikal pada lapisan otorisasi (RBAC), sanitasi antarmuka klien (DOM XSS), dan kegagalan desain watermark (fail-open & format omission)** yang dapat mengompromikan dokumen Sangat Rahasia (Level 4) Puskesmas.

Sesuai instruksi audit (tanpa modifikasi kode langsung oleh tim auditor), laporan evaluasi ini didokumentasikan secara resmi untuk dijadikan acuan perbaikan prioritas tinggi (*P1 Patching*) oleh tim pengembang aplikasi SIAP-AI.
