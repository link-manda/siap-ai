# Bulk Upload & AI Processing PRD

**Date:** 2026-09-06
**Status:** Draft
**Scope:** Standard

## 1. Overview
Pembaruan fitur unggah arsip untuk mendukung pemrosesan massal (*bulk upload*) hingga maksimal 5 file secara bersamaan. Fitur ini dirancang untuk mempercepat alur kerja pengguna dalam mengunggah banyak dokumen, sekaligus menjaga batasan penggunaan token AI. Antarmuka pengguna (UI) akan diperbarui menjadi format dua panel (kiri untuk daftar antrean, kanan untuk form edit) yang dinamis.

## 2. Core Behaviors

### 2.1 Alur Unggah Massal (Bulk Upload)
- Pengguna dapat memilih atau menyeret (*drag-and-drop*) hingga 5 file sekaligus ke area unggah.
- Sistem akan membatasi maksimal 5 file per sesi unggah. Jika pengguna memilih lebih dari 5, sistem akan menampilkan peringatan dan hanya memproses 5 file pertama.
- Segera setelah file dipilih, sistem akan langsung memulai pemrosesan AI untuk mengekstrak informasi (kategori, klasifikasi, ringkasan, dll.) pada masing-masing file secara paralel/berurutan di latar belakang.
- Terdapat animasi *loading* (*progress indicator*) individual untuk setiap file pada panel daftar antrean untuk menunjukkan status pemrosesan (e.g. *Uploading*, *AI Processing*, *Done*, *Failed*).

### 2.2 Panel Daftar Antrean (Kiri)
- Menampilkan daftar semua file yang sedang/telah diunggah pada sesi ini.
- **Setiap item file pada daftar memiliki:**
  - Nama file dan status AI.
  - **Tombol Preview:** Membuka modal *quick preview* untuk melihat pratinjau dokumen (berfungsi sama seperti fitur pratinjau di tabel arsip).
  - **Tombol Batal (Cancel):** Menghapus file dari daftar antrean. Jika file sedang diproses AI, proses tersebut diabaikan di sisi klien.

### 2.3 Panel Form Edit (Kanan)
- Menggunakan pola antarmuka "Pilih untuk Edit".
- Ketika pengguna mengklik salah satu file yang sudah selesai diproses pada panel kiri, panel kanan akan menampilkan formulir yang terisi dengan hasil ekstraksi AI dari file tersebut.
- Pengguna dapat mengedit detail file (Judul, Nomor Dokumen, Kategori, Klasifikasi, Ringkasan) secara manual.
- Setiap perubahan yang dilakukan pada form akan langsung tersimpan sementara (secara lokal di state/memori peramban) untuk file yang sedang dipilih.

### 2.4 Finalisasi & Simpan (Save)
- Terdapat satu tombol utama **"Simpan Semua"** (berada di posisi yang mudah diakses, e.g. di bawah daftar antrean atau di atas panel).
- Saat ditekan, tombol ini akan mengirimkan *payload* berisi seluruh data (form) dari file-file yang ada di antrean ke server dalam satu permintaan (atau berurutan).
- Sistem menyimpan arsip-arsip tersebut ke *database*.

## 3. Out of Scope & Deferred
- **Deferred:** Batas unggah file di atas 5 (ditunda untuk menjaga stabilitas *browser* dan batas token AI per menit).
- **Deferred:** Fitur mengekspor hasil AI (contoh: ke PDF) sebelum menyimpan arsip ke sistem.
- **Out of Scope:** Dukungan luring (*offline*) untuk mengekstrak data dari dokumen.

## 4. Dependencies & Constraints
- Modifikasi ini sangat bergantung pada state management di sisi klien (Vanilla JS atau Alpine.js yang sudah ada di `app.js`).
- Harus dipastikan *endpoint* `processAi` saat ini siap menerima *request* berbarengan (*concurrent*), atau perlu diatur agar pemrosesan per file dikirim secara bergantian (berurutan) dari sisi klien untuk mencegah *rate-limiting* API AI.
- Skema pengiriman saat menekan "Simpan Semua" memerlukan penyesuaian di `ArchiveController@store` agar dapat menangani *array* dari beberapa arsip sekaligus, bukan hanya satu objek tunggal.
