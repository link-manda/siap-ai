# Handoff Report — Implementer / Security Auditor (`implementer_r0`)

- **Audit Target**: Fitur "Pratinjau dan Streaming Arsip Multiformat" (SIAP-AI)
- **Working Directory**: `/Users/brahmanda/Sites/siap-ai/.agents/implementer_r0`
- **Output Report**: `/Users/brahmanda/Sites/siap-ai/EVALUATION_REPORT.md`
- **Timestamp**: 2026-09-05T05:55:00Z
- **Integrity Mode**: Demo (Read-only security audit, no code modifications permitted)
- **Domain Skills Applied**: `laravel-best-practices` (`rules/security.md`, `rules/routing.md`, `rules/validation.md`)

---

## 1. Summary of Findings

1. **RBAC Vulnerabilities (Level 4 / Sangat Rahasia)**:
   - **SEC-RBAC-01 (CRITICAL)**: Dokumen Sangat Rahasia (Level 4) dapat di-*bypass* oleh pengguna ber-role `Admin` dengan cara mendaftarkan ulang *file path* fisik yang tersimpan di disk ke form `POST /archives` menggunakan klasifikasi Level 1 (`Biasa`). Setelah tersimpan, `Admin` dapat melakukan `stream`, `preview`, dan `download` secara penuh tanpa watermark dan lolos dari `ArchivePolicy::view()`.
   - **SEC-RBAC-02 (HIGH)**: `ArchivePolicy::view()` mengakses `$archive->classification->level` tanpa *null-safe operator*. Pada arsip dengan `classification_id = null`, terjadi HTTP 500 (`Attempt to read property "level" on null`). Selain itu, ketiadaan *default deny* membuat peran selain Pimpinan mewarisi hak akses Level 1-3.
   - **SEC-RBAC-03 (MEDIUM)**: Dropdown filter pada `basic-tables.blade.php` mengekspos klasifikasi "Sangat Rahasia" kepada `Admin`.
   - **SEC-RBAC-04 (MEDIUM)**: Kunjungan ke `/archives/{id}/preview` tidak mencatat riwayat ke `archive_logs` sama sekali; hanya request AJAX ke `/archives/{id}/stream` yang dicatat.

2. **Frontend Rendering Security (Word / Excel / PDF)**:
   - **SEC-FE-01 (CRITICAL)**: Perenderan Word (`mammoth.js`) langsung menginjeksi `result.value` ke `target.innerHTML` tanpa sanitasi HTML (ketiadaan DOMPurify), membuka celah Stored DOM XSS via tautan berbahaya (misal `javascript:`).
   - **SEC-FE-02 (HIGH)**: Perenderan Excel (`xlsx.js`) langsung menginjeksi tabel HTML hasil konversi SheetJS ke `target.innerHTML` hanya dengan `replace()` string sederhana tanpa pembersihan muatan XSS di sel atau nama sheet.
   - **SEC-FE-03 (HIGH)**: Perenderan PDF menggunakan `<iframe src="${streamUrl}">` tanpa atribut `sandbox` pada same-origin, memungkinkan eksekusi script PDF atau MIME polyglot dalam domain aplikasi.
   - **SEC-FE-04 (LOW)**: Interpolasi pesan error `err.message` langsung ke `innerHTML`.
   - **SEC-FE-05 (LOW)**: Pelanggaran standar `AGENTS.md` terkait interpolasi Blade `{{ }}` di dalam JavaScript.

3. **Watermark Security & Leakage**:
   - **SEC-WM-01 (HIGH)**: Dokumen Word dan Excel Level 4 dilewati tanpa watermark server di `ArchiveController::stream()`. Biner murni dikirim ke browser via `fetch(streamUrl)`.
   - **SEC-WM-02 (HIGH)**: Fail-open pada `ArchiveController::stream()` dan `WatermarkService::watermarkPdf()`; jika terjadi exception saat watermarking PDF/gambar, sistem tetap mengalirkan file asli yang tidak diberi watermark.
   - **SEC-WM-03 (HIGH)**: Manipulasi parameter `file_type` saat penyimpanan memungkinkan penghindaran pemeriksaan watermark server.
   - **SEC-WM-04 (HIGH)**: Watermark tampilan di `preview.blade.php` hanya berupa CSS overlay (`pointer-events-none`) yang dapat dihilangkan dalam 1 detik via Developer Tools.
   - **SEC-WM-05 (MEDIUM)**: Geometri watermark PDF di `WatermarkService` diletakkan di koordinat margin paling bawah (mudah di-crop) dan teks menggunakan semantik "DIUNDUH OLEH" alih-alih "DILIHAT PADA".

4. **Path Traversal & HTTP Security**:
   - **SEC-FS-01 (MEDIUM)**: Validasi `file_path` mengizinkan nama direktori (`private/archives/`) karena `Storage::exists()` mengembalikan true untuk direktori.
   - **SEC-FS-02 (MEDIUM)**: Ketiadaan header `X-Content-Type-Options: nosniff`, CSP, dan tidak adanya sanitasi tanda kutip pada header `Content-Disposition`.
   - **SEC-FS-03 (MEDIUM)**: Tidak ada rate limiting (`throttle`) pada endpoint `stream`, `preview`, dan `process-ai` (risiko resource exhaustion).

---

## 2. File Paths and Lines Cited

- `app/Http/Controllers/ArchiveController.php`:
  - Baris 59: `set_time_limit(120);` (Resource exhaustion risk)
  - Baris 84–95: Validasi `file_path` menerima string tanpa verifikasi kepemilikan dan menerima direktori
  - Baris 96: Validasi `file_type` tanpa enum/allowlist
  - Baris 106: Penyimpanan arsip tanpa verifikasi duplikasi/klaim path file
  - Baris 113–120: `stream()` memanggil `findById` dan `authorize('view', $archive)`
  - Baris 122–128: Audit log `VIEW` dicatat pada `stream`
  - Baris 133–151: Pemeriksaan watermark hanya untuk PDF & Gambar Level 4; fail-open mengirim file asli saat watermark gagal
  - Baris 157–160: Respon inline file tanpa header `nosniff` dan tanpa sanitasi tanda kutip pada `Content-Disposition`
  - Baris 163–210: Logika `download()` dan peringatan flash session
  - Baris 212–223: Metode `preview()` tanpa pencatatan audit log
- `app/Policies/ArchivePolicy.php`:
  - Baris 22–30: Kebijakan `view()` memeriksa `classification->level <= 3` tanpa null-check dan tanpa default-deny
- `resources/views/pages/archives/preview.blade.php`:
  - Baris 19–28: CSS overlay watermark dinamis (`pointer-events-none z-50 opacity-30`)
  - Baris 46–47: Interpolasi Blade langsung di dalam script JS
  - Baris 53–59: Iframe rendering untuk PDF tanpa `sandbox`
  - Baris 63: Pemanggilan `fetch(streamUrl)` yang mengunduh biner mentah file
  - Baris 70–74: Mammoth convertToHtml langsung menginjeksi ke `target.innerHTML` tanpa DOMPurify
  - Baris 76, 91: Error handling menginjeksi `err.message` ke `innerHTML`
  - Baris 78–87: SheetJS table string langsung disuntikkan ke `target.innerHTML` tanpa sanitasi
- `app/Services/WatermarkService.php`:
  - Baris 16–21: Teks watermark statis "DIUNDUH OLEH"
  - Baris 33–40: Percabangan ekstensi (hanya pdf, jpg, jpeg, png; dokumen Word/Excel dilewati)
  - Baris 59–61: Posisi watermark di koordinat terbawah halaman PDF
  - Baris 67–70, 90–92: Pengecualian di-*catch* dan mengembalikan `$sourcePath` asli (fail-open)
- `routes/web.php`:
  - Baris 22–33: Endpoint `stream`, `preview`, `process-ai` hanya dilindungi `auth` tanpa `throttle`
- `app/Repositories/ArchiveRepository.php`:
  - Baris 34–37: `findById` tidak menggunakan `getBaseQuery()`
  - Baris 58–70: `getBaseQuery()` membatasi level <= 3 untuk non-Pimpinan
- `resources/views/pages/tables/basic-tables.blade.php`:
  - Baris 38–41: Menampilkan `Classification::all()` termasuk Sangat Rahasia pada filter pengguna Admin
- `database/migrations/2026_09_04_151607_create_archives_table.php`:
  - Baris 22: `classification_id` nullable dengan `nullOnDelete()`

---

## 3. Explicit List of Verified vs. Unverified Aspects

### Verified (Deep Verification):
- Seluruh rangkaian test suite proyek dijalankan (`php artisan test` / Pest): **16 tests passed, 37 assertions, duration 300ms**.
- Verifikasi keberadaan dan integritas file `EVALUATION_REPORT.md` di direktori root `/Users/brahmanda/Sites/siap-ai/EVALUATION_REPORT.md`.
- Verifikasi baris kode eksak, alur pemanggilan controller, policy, service, repository, dan template blade sesuai daftar rujukan di atas.
- Verifikasi bahwa `Storage::exists('private/archives')` bernilai `true` untuk direktori.
- Verifikasi bahwa `classification_id` bersifat *nullable* di migrasi database dan memicu fatal exception jika `ArchivePolicy::view()` dipanggil pada arsip tanpa klasifikasi.
- Verifikasi bahwa tidak ada file source code aplikasi yang dimodifikasi selama proses audit berlangsung.

### Unverified Aspects:
- Perilaku eksploitasi aktif di peramban langsung (live browser exploit payload execution) tidak diuji coba dengan muatan berbahaya aktif (*weaponized payload*) guna mematuhi batasan keamanan lingkungan.
- Pengujian performa beban tinggi (*load/stress testing*) untuk mengukur ambang batas konkurensi FPDI / GD saat menangani ribuan request watermark simultan.
- Perilaku LibreOffice headless atau Gotenberg jika nantinya diintegrasikan sebagai engine konversi format Office ke PDF.

---

## 4. Known Issues & Risks

- `Fatal Functional Bug`: Potensi `NullPointerException` (HTTP 500) pada `ArchivePolicy::view()` jika dokumen tidak memiliki relasi klasifikasi (`classification_id = null`).
- `Shallow Verification`: Kerentanan XSS pada SheetJS dan Mammoth.js dianalisis secara statis berdasarkan dokumentasi vendor resmi dan aliran data DOM, belum diuji dengan dokumen Office berpayload aktif pada peramban sungguhan.
- `Minor Robustness Risk`: Karakter tanda kutip pada judul arsip dapat merusak header HTTP `Content-Disposition`.
- `Fatal Functional Bug`: Bypass otorisasi Level 4 melalui re-registrasi `file_path` pada `store()` memungkinkan Admin mengakses dokumen rahasia tanpa halangan policy.

---

## 5. Next Steps for Reviewer

1. Periksa kelengkapan file `/Users/brahmanda/Sites/siap-ai/EVALUATION_REPORT.md` terhadap 4 kriteria penerimaan (*Acceptance Criteria*).
2. Verifikasi bahwa tidak ada *git diff* pada source code aplikasi (`git status` bersih selain file laporan evaluasi).
3. Lakukan handoff hasil audit ke Orchestrator dan Sentinel.
