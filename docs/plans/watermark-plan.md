# Rencana Teknis: Pemberian Tanda Air (Watermarking) Keamanan
**Dibuat:** 2026-09-05
**Dokumen Sumber:** `docs/brainstorms/watermark-requirements.md`

## 1. Konteks dan Ruang Lingkup (Scope)
Sistem akan menyisipkan teks identitas (Nama Pengunduh, IP Address, dan Tanggal) ke dalam dokumen PDF dan Gambar (JPG/PNG) yang terklasifikasi sebagai "Sangat Rahasia" (Level 4) tepat sebelum dikirim ke *browser* pengguna. File selain PDF dan Gambar (seperti DOCX) akan diunduh dalam bentuk aslinya.

## 2. Keputusan Arsitektur
- **Pustaka Eksternal:** Kita akan menginstal `setasign/fpdf`, `setasign/fpdi` (untuk manipulasi PDF), dan `intervention/image` (untuk manipulasi gambar).
- **Service Pattern:** Logika pemberian *watermark* akan diisolasi ke dalam `app/Services/WatermarkService.php` agar `ArchiveController` tetap bersih.
- **Peringatan Format (DOCX/XLSX):** Karena HTTP merespons dengan *stream file* saat pengunduhan, sistem tidak bisa merender antarmuka HTML untuk *flash message* secara bersamaan. Sebagai solusinya, kita akan memasukkan *session flash* dan mengembalikan unduhan. Pengguna baru akan melihat pesan peringatan tersebut saat mereka memuat ulang (*refresh*) atau berpindah halaman di sistem.

## 3. Unit Implementasi

### Unit 1: Instalasi Pustaka Dependensi
**Tindakan:**
- Menjalankan `composer require setasign/fpdf setasign/fpdi intervention/image`
- *No code files to create here, just a terminal execution step during implementation.*

### Unit 2: Pembuatan `WatermarkService`
**Berkas:** `app/Services/WatermarkService.php`
- Membuat fungsi `applyWatermark(Archive $archive, User $user, string $ipAddress): string`
- **Alur PDF:** Jika ekstensi adalah `pdf`, gunakan FPDI untuk mengimpor setiap halaman PDF, lalu gunakan FPDF untuk menambahkan teks (warna merah/abu-abu transparan, posisi di bagian bawah tengah halaman).
- **Alur Gambar:** Jika ekstensi adalah `jpg`, `jpeg`, atau `png`, gunakan Intervention Image untuk menambahkan teks *watermark* di bagian bawah gambar.
- **Hasil:** Fungsi menyimpan hasil sementara ke direktori `storage/app/temp` dan mengembalikan *path* absolut file sementara tersebut.

### Unit 3: Integrasi ke `ArchiveController`
**Berkas:** `app/Http/Controllers/ArchiveController.php`
- Injeksi `WatermarkService` ke dalam *constructor*.
- Pada fungsi `download($id)`:
  - Cek jika `$archive->classification->level === 4`.
  - Cek jika tipe file adalah PDF/Gambar. Jika ya, panggil `WatermarkService`, dan gunakan `response()->download($tempPath)->deleteFileAfterSend(true)`.
  - Jika tipe file adalah DOCX/XLSX, panggil `session()->flash('warning', 'File asli diunduh. Format ini tidak mendukung watermark keamanan otomatis.')`, lalu teruskan ke `return Storage::download(...)`.

### Unit 4: Penambahan Pengujian (Test Suite)
**Berkas:** `tests/Feature/ArchiveWatermarkTest.php`
- Membuat skenario tes baru untuk memastikan Pimpinan dapat mengunduh dokumen Level 4 dengan lancar.
- *Test Scenario 1:* Pimpinan mengunduh PDF Level 4 -> Sistem mengembalikan HTTP 200 dan *response* adalah file PDF hasil manipulasi (cek nama file hasil unduhan atau *header* respons).
- *Test Scenario 2:* Pimpinan mengunduh DOCX Level 4 -> Sistem mengembalikan *session warning* namun tetap mengirimkan *file* asli.

## 4. Risiko dan Ketergantungan
- **Kerusakan Dokumen PDF (Corrupt):** Beberapa versi PDF (di atas versi 1.4) yang menggunakan kompresi tingkat lanjut terkadang tidak didukung secara gratis oleh FPDI dasar. Risiko ini ditoleransi untuk prototipe, karena FPDI mendukung 90% PDF standar.
- **Direktori Sementara:** Pastikan `storage/app/temp` dapat ditulisi (*writable*) dan dibersihkan (`deleteFileAfterSend`).

---
*Rencana teknis di atas akan dieksekusi langkah demi langkah. Jika Anda setuju dengan pendekatan integrasi Service dan pustaka pihak ketiga ini, silakan Approved dokumen ini atau balas dengan perintah `/ce-work`!*
