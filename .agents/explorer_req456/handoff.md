# Laporan Investigasi Kebutuhan 4, 5, dan 6 (SIAP-AI)

**Tanggal Investigasi**: 2026-09-05  
**Investigator**: Explorer Agent (`explorer_req456`)  
**Target Proyek**: `/Users/brahmanda/Sites/siap-ai`  
**Skill Acuan**: `laravel-best-practices`

---

## 1. Observation

### 1.1. Status Ringkasan Kebutuhan

| No | Kebutuhan PRD & Request | Status Verifikasi | Ringkasan Bukti Utama |
|---|---|---|---|
| **4** | **Arsip berdasarkan Kategori** (statistik & distribusi per kategori) | **Sebagian Terpenuhi** *(Partially Satisfied)* | Statistik dan chart Donut kategori tersedia (`ArchiveRepository.php:67-79`, `DashboardController.php:17`, `ecommerce.blade.php:60-71`), namun UI tabel arsip (`basic-tables.blade.php`) sama sekali **tidak memiliki elemen filter/dropdown kategori**. Soft delete pada tabel kategori belum dicegah pada query join, dan `Category::all()` dipanggil langsung di controller. |
| **5** | **Laporan** (Dasbor Charts & Ekspor PDF/Excel) | **Sebagian Terpenuhi** *(Partially Satisfied)* | Visualisasi chart dashboard lengkap (`ecommerce.blade.php`), ekspor PDF via DomPDF (`ReportExportService.php:30-80`) dan ekspor Excel/CSV via SimpleExcelWriter (`ReportExportService.php:12-28`) berfungsi. Namun: **tidak ada halaman/menu Laporan tersendiri**, ekspor Excel mereferensikan kolom `$archive->file_size` yang **tidak ada di skema database**, filter tanggal tidak mencakup record pada `end_date` setelah 00:00:00, dan aksi ekspor tidak tercatat di audit log. |
| **6** | **Menu Upload / Download Arsip** (Watermark, validasi file, dynamic watermark rendering, RBAC download permissions) | **Terpenuhi** *(Satisfied)* | Alur upload 2-step dengan validasi mimes (`pdf,jpg,jpeg,png`) & max size 10MB (`ArchiveController.php:60`), download terproteksi RBAC policy (`ArchivePolicy.php:22-30`), watermark dinamis untuk Level 4 (Sangat Rahasia) menyisipkan nama pengguna, IP, dan timestamp pada PDF & gambar (`WatermarkService.php:14-93`). Seluruh 14 test suite Pest berstatus lulus. Ditemukan catatan celah keamanan: judul dokumen rahasia bocor di halaman `/audit-trail` untuk role Admin. |

---

### 1.2. Observasi Kode Rinci per Komponen

#### A. Kebutuhan 4: Arsip berdasarkan Kategori

1. **Repository Layer (`app/Repositories/ArchiveRepository.php`)**:
   - Baris 67–79:
     ```php
     public function getCategoryStats(): array
     {
         $stats = $this->getBaseQuery()
             ->join('categories', 'archives.category_id', '=', 'categories.id')
             ->selectRaw('categories.name as category, COUNT(archives.id) as total')
             ->groupBy('categories.id', 'categories.name')
             ->get();
             
         return [
             'labels' => $stats->pluck('category')->toArray(),
             'series' => $stats->pluck('total')->toArray()
         ];
     }
     ```
   - Baris 15–17 (`getAllPaginated`):
     ```php
     if (!empty($filters['category_id'])) {
         $query->where('category_id', $filters['category_id']);
     }
     ```
   - Baris 53–57: `$this->getBaseQuery()` membatasi klasifikasi `level <= 3` jika pengguna memiliki role `Admin`.

2. **Controller Layer (`app/Http/Controllers/DashboardController.php` & `ArchiveController.php`)**:
   - `DashboardController.php` baris 17 & 24:
     ```php
     $categoryStats = $this->archiveRepository->getCategoryStats();
     ...
     return view('pages.dashboard.ecommerce', [
         ...
         'categoryStats' => $categoryStats,
     ]);
     ```
   - `ArchiveController.php` baris 30 & 35–36:
     ```php
     $filters = [
         'category_id' => $request->get('category_id'),
         'start_date' => $request->get('start_date'),
         'end_date' => $request->get('end_date'),
     ];
     $archives = $this->archiveRepository->getAllPaginated(10, $filters);
     $categories = Category::all();
     ```

3. **View Layer (`resources/views/pages/dashboard/ecommerce.blade.php` & `pages/tables/basic-tables.blade.php`)**:
   - `ecommerce.blade.php` baris 29–34:
     ```html
     <div class="col-span-12 md:col-span-6">
         <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark h-full">
             <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-6 text-center">Komposisi Kategori</h4>
             <div id="categoryChart" class="flex justify-center"></div>
         </div>
     </div>
     ```
   - `ecommerce.blade.php` baris 60–71: Inisialisasi ApexCharts Donut dengan data `@json($categoryStats)`.
   - `basic-tables.blade.php` baris 1–73: **Sama sekali tidak ada form `<select>` filter kategori atau form pencarian**. Hanya tombol aksi ekspor dan upload di header serta tabel data.

---

#### B. Kebutuhan 5: Laporan (Dasbor Charts & Ekspor PDF/Excel)

1. **Dashboard Charts (`app/Http/Controllers/DashboardController.php` & `ecommerce.blade.php`)**:
   - Menghasilkan 4 metrik:
     - `totalArchives` (`ArchiveRepository.php:62-65`) -> Card Total Arsip (`ecommerce.blade.php:8-20`)
     - `monthlyStats` (`ArchiveRepository.php:95-115`) -> Bar Chart ApexCharts (`ecommerce.blade.php:22-26, 48-58`)
     - `categoryStats` (`ArchiveRepository.php:67-79`) -> Donut Chart ApexCharts (`ecommerce.blade.php:29-34, 60-71`)
     - `classificationStats` (`ArchiveRepository.php:81-93`) -> Pie Chart ApexCharts (`ecommerce.blade.php:36-41, 73-84`)

2. **Ekspor Service (`app/Services/ReportExportService.php`)**:
   - Baris 12–28 (`exportToExcel`):
     ```php
     public function exportToExcel(Collection $archives): StreamedResponse
     {
         return response()->streamDownload(function () use ($archives) {
             $writer = SimpleExcelWriter::stream('php://output', 'csv');

             foreach ($archives as $archive) {
                 $writer->addRow([
                     'Judul Arsip' => $archive->title,
                     'Kategori' => $archive->category ? $archive->category->name : '-',
                     'Klasifikasi' => $archive->classification ? $archive->classification->name : '-',
                     'Ukuran File (KB)' => round($archive->file_size / 1024, 2),
                     'Pengunggah' => $archive->uploader ? $archive->uploader->name : '-',
                     'Tanggal Unggah' => $archive->created_at->format('Y-m-d H:i:s'),
                 ]);
             }
         }, 'Laporan_Arsip_' . date('Y-m-d') . '.csv');
     }
     ```
   - Baris 30–37 (`exportToPdf`):
     ```php
     public function exportToPdf(Collection $archives)
     {
         $html = $this->generatePdfHtml($archives);
         $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
         return $pdf->download('Laporan_Arsip_' . date('Y-m-d') . '.pdf');
     }
     ```
   - Baris 39–80: Template HTML landscape A4 dengan tabel rekapitulasi 6 kolom (No, Judul Arsip, Kategori, Klasifikasi, Pengunggah, Tanggal Unggah).

3. **Observasi Defek Kritis Skema Database (`database/migrations/2026_09_04_151607_create_archives_table.php`)**:
   - Kolom-kolom tabel `archives`:
     `id`, `document_number`, `title`, `document_date`, `file_path`, `file_type`, `category_id`, `classification_id`, `uploaded_by`, `extracted_text`, `summary`, `ai_model`, `ai_confidence_score`, `softDeletes()`, `timestamps()`.
   - **Ketiadaan kolom**: Tabel `archives` **TIDAK MEMILIKI kolom `file_size`**.
   - Model `app/Models/Archive.php` baris 14–27 juga tidak mendaftarkan `file_size` pada `$fillable`.
   - Akibatnya: `$archive->file_size` pada `ReportExportService.php:22` bernilai `null`, sehingga ekspresi `round($archive->file_size / 1024, 2)` menghasilkan `0` (atau memicu peringatan tipe PHP) untuk seluruh baris hasil ekspor.

4. **Filter Tanggal pada Repository (`ArchiveRepository.php:20 & 126`)**:
   ```php
   if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
       $query->whereBetween('archives.created_at', [$filters['start_date'], $filters['end_date']]);
   }
   ```
   - Jika pengguna hanya menginput salah satu tanggal (hanya `start_date` atau hanya `end_date`), filter **diabaikan total**.
   - Penggunaan `whereBetween` pada string tanggal `['2026-09-01', '2026-09-05']` mengonversi rentang menjadi `2026-09-01 00:00:00` s/d `2026-09-05 00:00:00`. Seluruh arsip yang dibuat pada tanggal 5 September setelah pukul 00:00:00 terpotong (*truncated*) dari hasil laporan.

5. **Ketiadaan Menu & Halaman Laporan**:
   - `app/Helpers/MenuHelper.php` baris 7–30 hanya mendaftarkan 4 menu: Dashboard (`/`), Manajemen Arsip (`/archives`), Upload Arsip (AI) (`/archives/create`), dan Audit Trail (`/audit-trail`).
   - Tidak ada rute `GET /reports` atau menu "Laporan". Tombol ekspor hanya menempel sebagai tautan di halaman tabel arsip.

---

#### C. Kebutuhan 6: Menu Upload / Download Arsip & Watermarking

1. **Validasi File Upload (`ArchiveController.php:59-62`)**:
   ```php
   $request->validate([
       'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
       'extracted_text' => ['nullable', 'string'],
   ]);
   ```
   - Tipe file dibatasi ketat: PDF, JPG, JPEG, PNG.
   - Ukuran maksimum: 10.240 KB (10 MB).
   - File disimpan pada storage privat: `app/Services/ArchiveService.php:23` -> `$file->store('private/archives')`.

2. **Alur Frontend Form Upload (`resources/views/pages/form/form-elements.blade.php`)**:
   - Input file dengan atribut `accept=".pdf,.jpg,.jpeg,.png"` (baris 20–22).
   - Eksekusi AJAX asinkron ke `POST /archives/process-ai` dengan status loading spinner (baris 100–167).
   - Pengisian form hasil ekstraksi AI secara otomatis (title, category, classification, summary, confidence score).
   - Reviewer dapat mengubah hasil pilihan kategori/klasifikasi sebelum menyimpan via form finalisasi ke `POST /archives`.

3. **Otorisasi RBAC Download (`app/Policies/ArchivePolicy.php`)**:
   - Baris 22–30:
     ```php
     public function view(User $user, Archive $archive): bool
     {
         if ($user->role === 'Pimpinan') {
             return true;
         }

         // Admin can only view if classification level is <= 3
         return $archive->classification->level <= 3;
     }
     ```
   - Pada `ArchiveController.php:104`: `$this->authorize('view', $archive);`.
   - Hasil evaluasi:
     - Role **Pimpinan**: Dapat mengunduh Level 1, 2, 3, dan 4.
     - Role **Admin**: Otomatis ditolak (HTTP 403 Forbidden) jika mengunduh arsip Level 4 (Sangat Rahasia).
     - **Tamu / Unauthenticated**: Dicegat oleh middleware `auth` pada `routes/web.php:16` -> HTTP 302 Redirect ke `/login`.

4. **Dynamic Watermark Rendering (`app/Services/WatermarkService.php`)**:
   - Baris 16–21:
     ```php
     $text = sprintf(
         'DIUNDUH OLEH: %s | IP: %s | TANGGAL: %s',
         $user->name,
         $ipAddress,
         now()->format('Y-m-d H:i:s')
     );
     ```
   - **PDF Watermarking** (`watermarkPdf`, baris 42–71):
     - Memakai `setasign\Fpdi\Fpdi`.
     - Melakukan iterasi seluruh halaman PDF (`$pageNo = 1; $pageNo <= $pageCount`).
     - Menimpa teks merah Arial Bold 12pt di margin bawah ($y = height - 10, center align).
     - Menghasilkan file temporer di `storage/app/temp/wm_{uniqid}.pdf`.
   - **Image Watermarking** (`watermarkImage`, baris 73–93):
     - Memakai `Intervention\Image\ImageManager` dengan driver `Gd`.
     - Menuliskan teks watermark merah transparan (`rgba(255, 0, 0, 0.8)`) di posisi tengah bawah gambar.
   - **Download Response & Cleanup** (`ArchiveController.php:129-132`):
     - Mengembalikan `response()->download($watermarkedPath, ...)->deleteFileAfterSend(true)`. File temporer langsung dihapus dari disk server setelah transmisi selesai.
     - Untuk format non-watermarkable (e.g. docx): Menampilkan session flash warning bahwa file asli diunduh tanpa watermark otomatis.

5. **Pencatatan Audit Log Download (`ArchiveController.php:110-116`)**:
   - Setiap unduhan arsip dicatat ke tabel `archive_logs` dengan `action => 'DOWNLOAD'`, `user_id`, `ip_address`, dan `user_agent`.

---

### 1.3. Observasi Pelanggaran Arsitektur Service-Repository (AGENTS.md)

Aturan `AGENTS.md` menyatakan:
> *Controller: Hanya bertugas menerima request, melakukan validasi ($request->validate()), meneruskan data ke Service, dan mengembalikan response. Dilarang keras menulis logika query database di Controller.*
> *Repository: Menyediakan akses data (Eloquent/Query). Murni operasi CRUD ke database.*

Ditemukan pelanggaran langsung di beberapa Controller:
1. `app/Http/Controllers/ArchiveController.php`:
   - Baris 36: `$categories = Category::all();` (Direct Eloquent query di Controller).
   - Baris 49–50: `'categories' => Category::all()`, `'classifications' => Classification::all()` (Direct Eloquent query).
   - Baris 110–116: `ArchiveLog::create([...]);` (Direct Eloquent write di Controller saat download).
   - Baris 152: `$archive->delete();` (Memanggil query delete model langsung di Controller, mengabaikan `$this->archiveRepository->delete($id)`).
   - Baris 165 & 178: Controller memanggil repository `getAllForExport($filters)` lalu mengoper collection ke Service, alih-alih mendelegasikan proses bisnis langsung ke Service (`$exportService->exportExcel($filters)`).
2. `app/Http/Controllers/AuditTrailController.php`:
   - Baris 12: `$logs = ArchiveLog::with(['user', 'archive'])->latest()->paginate(15);` (Direct Eloquent query tanpa Repository/Service).
3. `app/Http/Controllers/DashboardController.php`:
   - Menginjeksi `ArchiveRepositoryInterface` langsung ke Controller dan memanggil query agregasi langsung tanpa layer `DashboardService` / `ArchiveService`.

---

### 1.4. Observasi Keamanan & Celah Akses (RBAC Vulnerabilities)

1. **Information Leakage di Halaman Audit Trail (`AuditTrailController.php:12` & `pages/tables/audit-trail.blade.php:38`)**:
   - Halaman `/audit-trail` tidak memiliki otorisasi level kerahasiaan sama sekali.
   - Kolom `Arsip Terkait` menampilkan `{{ $log->archive->title ?? 'Arsip Dihapus' }}` untuk semua log.
   - Jika pengguna Pimpinan mengunduh atau Admin mengunggah arsip berkategori "Sangat Rahasia" (Level 4), judul dokumen rahasia tersebut **terpampang jelas bagi pengguna ber-role Admin** di tabel audit trail. Padahal dokumen tersebut sengaja disembunyikan dari tabel `/archives`.
2. **Injeksi File Path pada Finalisasi Form (`ArchiveController.php:81-82`)**:
   - Form `store()` menerima `file_path` dari input tersembunyi (`<input type="hidden" name="file_path" id="file_path">`).
   - Validasi hanya memeriksa `'file_path' => ['required', 'string']`.
   - Tidak ada validasi kepemilikan token session atau pengecekan bahwa path tersebut benar-benar file yang baru saja diunggah oleh user aktif di direktori `private/archives/`.
3. **Silent Fallback pada Kegagalan Watermark PDF (`WatermarkService.php:67-70` & `ArchiveController.php:129-139`)**:
   - Jika file PDF menggunakan kompresi objek/cross-reference stream PDF versi 1.5+ yang tidak didukung FPDI gratis, blok `catch (\Exception $e)` mengembalikan `$sourcePath` asli.
   - Pada `ArchiveController.php:129`, kondisi `if ($watermarkedPath !== Storage::path($archive->file_path))` menjadi bernilai `false`.
   - Alur eksekusi turun ke baris 139: `return Storage::download(...)`.
   - Dokumen Sangat Rahasia (Level 4) **terunduh dalam kondisi file mentah polos tanpa watermark dan tanpa peringatan apapun** kepada pengguna maupun pencatatan log insiden keamanan.
4. **Sanitasi Karakter Nama File Download (`ArchiveController.php:130 & 139`)**:
   - Parameter nama file pada `response()->download(..., $archive->title . '.' . $archive->file_type)` menggunakan judul mentah. Jika judul mengandung karakter khusus, spasi ganda, atau tanda kutip, header `Content-Disposition` berpotensi terdistorsi pada browser klien.

---

## 2. Logic Chain

1. **Status Kebutuhan 4 (Sebagian Terpenuhi)**:
   - *Premis*: Kebutuhan mensyaratkan statistik dan distribusi arsip per kategori serta kemampuan menyaring arsip berdasarkan kategori.
   - *Fakta*: `ArchiveRepository::getCategoryStats()` berhasil mengelompokkan arsip per kategori dan diekspos ke Donut Chart ApexCharts di Dashboard. Filter data pada level query repository (`category_id`) juga telah ditulis.
   - *Kekurangan*: Halaman utama arsip (`basic-tables.blade.php`) sama sekali belum menyematkan dropdown atau form filter kategori. Pengguna biasa tidak memiliki mekanisme UI untuk memfilter data per kategori. Selain itu, join tabel kategori tidak mengecek soft delete (`deleted_at`), dan pemanggilan data kategori di Controller melanggar prinsip arsitektur Service-Repository.
   - *Deduksi*: Kebutuhan analitik dan query data terpenuhi, namun antarmuka interaktif dan kepatuhan arsitektur belum tuntas, sehingga dinilai **Sebagian Terpenuhi**.

2. **Status Kebutuhan 5 (Sebagian Terpenuhi)**:
   - *Premis*: Kebutuhan mensyaratkan dashboard charts dan fitur ekspor Laporan dalam format PDF dan Excel dengan dukungan filter waktu dan kategori.
   - *Fakta*: Grafik batang bulanan, donut kategori, pie klasifikasi, dan card total arsip tampil dengan baik di dashboard. Service ekspor PDF (DomPDF) dan Excel/CSV (SimpleExcelWriter) telah berfungsi dan terhubung dengan rute unduh.
   - *Kekurangan*: 
     - Tidak tersedia menu atau modul "Laporan" terdedikasi di aplikasi (hanya 2 tombol ekspor di atas tabel arsip).
     - Skema database tabel `archives` tidak memiliki kolom `file_size`, sehingga kolom ukuran file di hasil ekspor selalu bernilai 0 KB.
     - Filter rentang waktu gagal menyaring arsip pada hari terakhir rentang (`end_date`) setelah 00:00:00 dan tidak mendukung filter satu batas tanggal.
     - Ekspor dokumen tidak dicatat dalam jejak audit (`archive_logs`).
   - *Deduksi*: Fungsionalitas ekspor dan analitik dasar tersedia, namun defek skema, cacat logika filter tanggal, ketiadaan audit log, dan ketiadaan menu laporan menjadikannya berstatus **Sebagian Terpenuhi**.

3. **Status Kebutuhan 6 (Terpenuhi)**:
   - *Premis*: Kebutuhan mensyaratkan menu upload dan download arsip dengan validasi file (ekstensi mimes & ukuran), watermark dinamis berbasis identitas pengguna/kerahasiaan, serta otorisasi RBAC download.
   - *Fakta*: 
     - Menu upload tersedia di sidebar (`/archives/create`), rute aktif, tampilan form form-elements lengkap dengan integrasi AI.
     - Validasi file memverifikasi mime type (`pdf,jpg,jpeg,png`) dan batas ukuran 10 MB (`max:10240`).
     - Menu download aktif via tombol aksi di tabel arsip.
     - Otorisasi RBAC berjalan tepat melalui `ArchivePolicy::view`: Admin dilarang keras (403) mengunduh arsip Level 4, sedangkan Pimpinan diizinkan.
     - Dynamic watermark berjalan pada PDF dan gambar dengan menyematkan identitas pemohon, alamat IP, dan stempel waktu, serta menghapus file temporer pasca-unduh.
     - Seluruh pengujian otomatis di `tests/Feature/` (14 assertions) berstatus 100% Passed.
   - *Deduksi*: Semua poin spesifikasi inti Kebutuhan 6 berhasil dibuktikan dengan implementasi kode konkret dan lulus verifikasi pengujian, sehingga dinilai **Terpenuhi**, dengan sejumlah rekomendasi penguatan keamanan di luar fungsionalitas dasar.

---

## 3. Caveats

1. **Cakupan Pengujian OCR**: Investigasi mencatat bahwa metode `ArchiveService::processUpload()` menggunakan simulasi string fallback jika teks ekstraksi kosong (`"Simulasi isi dokumen: " . $fileName`). Library OCR fisik (Tesseract/Poppler) belum diinstal di level sistem operasi, sesuai batasan lingkup PRD Section 3.1.
2. **Library FPDI pada PDF Kompleks**: Pengujian watermark di test suite menggunakan mock objek (`ArchiveWatermarkTest.php:49`). Dalam operasional produksi, file PDF dengan versi 1.5+ yang memiliki object streams terkompresi membutuhkan parser komersial (`FPDI PDF-Parser`) agar tidak memicu silent fallback.
3. **Format Excel vs CSV**: `ReportExportService::exportToExcel()` menggunakan `SimpleExcelWriter::stream('php://output', 'csv')` yang menghasilkan file berekstensi `.csv` (bukan biner `.xlsx`). Meskipun dapat dibuka langsung oleh Microsoft Excel, format data teknisnya adalah comma-separated values.
4. **Asumsi Role Pengguna**: Sistem mengasumsikan hanya terdapat 2 role tetap sesuai enum pada migrasi `users`: `'Admin'` dan `'Pimpinan'`.

---

## 4. Conclusion

### 4.1. Kesimpulan Status Kebutuhan

1. **Kebutuhan 4 (Arsip berdasarkan Kategori)**: **Sebagian Terpenuhi**
   - *Implementasi Positif*: Query analitik `ArchiveRepository::getCategoryStats()`, integrasi ApexCharts Donut di Dashboard, dan filter `category_id` di repository.
   - *Tindakan Perbaikan*: Tambahkan dropdown filter `<select name="category_id">` pada `resources/views/pages/tables/basic-tables.blade.php`, tambahkan klausul `whereNull('categories.deleted_at')` pada join repository, dan alihkan `Category::all()` dari Controller ke Service/Repository.

2. **Kebutuhan 5 (Laporan: Dasbor Charts & Ekspor PDF/Excel)**: **Sebagian Terpenuhi**
   - *Implementasi Positif*: 4 visualisasi dashboard interaktif, ekspor PDF landscape DomPDF, dan ekspor data via streaming.
   - *Tindakan Perbaikan*: 
     - Tambahkan migrasi kolom `file_size` (unsignedBigInteger) pada tabel `archives` agar kalkulasi ukuran file pada ekspor akurat.
     - Perbaiki logika filter tanggal di `ArchiveRepository` menggunakan `whereDate()` atau menambahkan waktu `' 23:59:59'` pada `end_date`.
     - Buat menu dan halaman Laporan (`/reports`) mandiri di sidebar sesuai arsitektur PRD FR-05.
     - Tambahkan pencatatan audit log untuk aksi ekspor (`EXPORT_EXCEL`, `EXPORT_PDF`) di `ArchiveLog`.

3. **Kebutuhan 6 (Upload / Download, Watermark, Validasi & RBAC)**: **Terpenuhi**
   - *Implementasi Positif*: Validasi berkas ketat (mimes & 10MB), alur upload AI interaktif, proteksi hak akses download berbasis policy RBAC, dan dynamic watermark rendering (PDF & Gambar) yang bersih dari sisa file sampah.
   - *Tindakan Perbaikan*:
     - Lindungi kebocoran judul dokumen Level 4 di halaman `/audit-trail` bagi pengguna non-pimpinan.
     - Validasi keabsahan path temporer file upload sebelum disimpan di `store()`.
     - Berikan peringatan jika watermark PDF gagal diterapkan akibat versi PDF tidak kompatibel, bukan silent fallback.
     - Perbaiki pelanggaran Service-Repository di `ArchiveController` (pindahkan pembuatan log unduh dan penghapusan arsip ke Service layer).

---

## 5. Verification Method

Untuk memverifikasi seluruh temuan secara independen, jalankan langkah-langkah berikut pada terminal proyek:

### 5.1. Menjalankan Seluruh Test Suite
Jalankan perintah Pest untuk memastikan seluruh skenario RBAC, Watermark, dan Ekspor saat ini dalam kondisi hijau (lulus):
```bash
vendor/bin/pest
```
*Hasil terverifikasi saat investigasi*: `14 passed, 33 assertions`.

### 5.2. Verifikasi Spesifik per Kebutuhan
1. **Verifikasi RBAC Download & Datatable (Kebutuhan 6)**:
   ```bash
   vendor/bin/pest tests/Feature/ArchiveRbacTest.php
   ```
2. **Verifikasi Watermark PDF & Peringatan Format Lain (Kebutuhan 6)**:
   ```bash
   vendor/bin/pest tests/Feature/ArchiveWatermarkTest.php
   ```
3. **Verifikasi Filter Ekspor Laporan Admin vs Pimpinan (Kebutuhan 5)**:
   ```bash
   vendor/bin/pest tests/Feature/ReportTest.php
   ```

### 5.3. Verifikasi Bug Ketiadaan Kolom `file_size`
Jalankan pengecekan skema tabel arsip via Tinker:
```bash
php artisan tinker --execute 'var_dump(\Illuminate\Support\Facades\Schema::hasColumn("archives", "file_size"));'
```
*Output*: `bool(false)` — Membuktikan kolom `file_size` tidak ada di database sehingga ekspor laporan menghasilkan angka 0.

### 5.4. Verifikasi Ketiadaan UI Filter Kategori di Tabel
Periksa isi berkas view tabel arsip:
```bash
grep -in "category_id" resources/views/pages/tables/basic-tables.blade.php
```
*Output*: Tidak ditemukan kecocokan (0 matches) — Membuktikan UI filter kategori belum diimplementasikan di view blade.
