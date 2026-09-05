# Kebutuhan Fitur: Laporan & Visualisasi Dasbor (Pemenuhan PRD Akhir)
**Dibuat:** 2026-09-05

## 1. Konteks dan Tujuan (Problem Frame)
Berdasarkan tinjauan terhadap kebutuhan pengguna (*User Needs / PRD*), sistem SIAP-AI telah memenuhi 5 dari 6 pilar utama (Klasifikasi, Total, Filter Waktu, Filter Kategori, Upload/Download). Pilar terakhir yang belum terpenuhi adalah **Laporan**.

Fitur Laporan ini dibutuhkan agar Pimpinan dan Admin tidak hanya melihat data dalam bentuk baris tabel murni, tetapi juga dapat memahami tren pengarsipan melalui visualisasi grafik, serta mampu mencetak/mengekspor daftar arsip tersebut untuk keperluan pelaporan eksternal atau audit fisik (Excel/PDF).

## 2. Kriteria Sukses (Success Criteria)
- Halaman Dasbor (`/dashboard`) menampilkan metrik visual (grafik batang/garis/lingkaran) mengenai jumlah dokumen yang diunggah per bulan, distribusi kategori, dan komposisi klasifikasi kerahasiaan.
- Halaman Indeks Arsip memiliki tombol "Ekspor Laporan" yang memungkinkan pengguna mengunduh daftar arsip yang sedang difilter ke dalam format **Excel** dan **PDF**.
- Baik grafik dasbor maupun dokumen hasil ekspor wajib patuh pada **Aturan RBAC**: Admin sama sekali tidak boleh melihat eksistensi dokumen "Sangat Rahasia" dalam statistik total maupun baris laporan ekspor.

## 3. Cakupan dan Alur (Scope Boundaries)
**Termasuk dalam cakupan:**
- Penambahan fungsi agregasi pada *Repository* untuk memberi asupan data ke grafik dasbor.
- Penggunaan pustaka (*library*) untuk *export* Excel (misal: `spatie/simple-excel` atau `maatwebsite/excel`) dan PDF (misal: `barryvdh/laravel-dompdf` atau Mpdf).
- Data ekspor memuat kolom: Judul Arsip, Kategori, Klasifikasi, Tanggal Unggah, dan Pengunggah.

**Di luar cakupan (Out of Scope):**
- Modul *Custom Report Builder* (di mana pengguna bisa memilih-milih kolom sendiri secara dinamis). Ekspor akan menggunakan format *template* statis yang sudah ditentukan.
- Pengiriman laporan otomatis terjadwal via Email (dapat ditunda untuk fase pengembangan masa depan).

## 4. Prasyarat & Asumsi
- **Pustaka Ekspor:** Diasumsikan server dapat dipasangi pustaka tambahan untuk perenderan HTML-ke-PDF dan pembuat CSV/XLSX.
- **Konsistensi RBAC:** Data laporan akan di-_query_ melalui `ArchiveRepository` yang sama dengan tabel, untuk memastikan logika filter "Sangat Rahasia" (Level <= 3) bagi Admin tetap berjalan otomatis tanpa duplikasi kode logika.

## 5. Pertanyaan Tertunda (Outstanding Questions)
- Tidak ada. Kesepakatan mengenai integrasi *Chart* dan *Export* ganda (Excel/PDF) dengan batasan RBAC telah dikonfirmasi dan siap untuk perencanaan teknis.
