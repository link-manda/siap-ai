# Fitur Pratinjau (Preview) Arsip Multiformat

**Date:** 2026-09-05  
**Status:** Draft

## Ringkasan
Menambahkan dukungan unggahan untuk dokumen Microsoft Office (DOCX, XLSX) serta menyediakan fitur pratinjau (*preview*) langsung di peramban (PDF, Gambar, Word, Excel) menggunakan perenderan sisi klien (*client-side rendering*) yang ringan dan terlindungi oleh filter *Role-Based Access Control* (RBAC) serta lapisan *Watermark CSS*.

## Konteks & Masalah
Saat ini, sistem SIAP-AI hanya menerima format PDF dan Gambar. Berdasarkan umpan balik dari pengguna, tipe dokumen arsip institusi seringkali berupa *draft* surat berbentuk Word (.docx) atau data laporan berbentuk Excel (.xlsx).
Selain itu, staf harus selalu mengunduh arsip terlebih dahulu hanya untuk melihat isinya. Hal ini tidak praktis dan meningkatkan risiko tersebarnya dokumen rahasia ke penyimpanan lokal perangkat tanpa pengawasan. Oleh karena itu, dibutuhkan fitur pratinjau langsung yang aman tanpa perlu aplikasi tambahan.

## Tujuan & Kriteria Kesuksesan
**Tujuan:**
- Memungkinkan staf mengunggah arsip berformat DOCX, DOC, XLSX, dan XLS.
- Memungkinkan pengguna membaca dokumen secara langsung melalui *browser* tanpa harus mengunduhnya.
- Mencegah dokumen rahasia diakses oleh pihak yang tidak memiliki otorisasi, baik secara API maupun UI.

**Kriteria Kesuksesan (Success Criteria):**
- Pengguna dapat memilih tipe file `docx` dan `xlsx` saat proses *upload*.
- Terdapat tombol "Preview" di tabel dasbor arsip (di samping tombol Download).
- Saat "Preview" diklik, layar menampilkan dokumen (PDF, Gambar, Word, atau Excel) dengan tata letak yang memadai untuk dibaca.
- Terdapat lapisan semi-transparan (*watermark CSS*) di atas area *preview* yang memuat info Pengekstrak (Nama User & IP) untuk menyulitkan *screenshot* anonim.
- Jika pengguna adalah Admin dan dokumen merupakan klasifikasi Sangat Rahasia (Level 4), akses *preview* akan langsung ditolak (HTTP 403) sesuai standar RBAC SIAP-AI.

## Pengalaman Pengguna (User Flows)
1. **Unggah Arsip:** Di halaman *Upload*, pengguna kini dapat menelusuri (*browse*) atau *drag-and-drop* file Office (.docx / .xlsx).
2. **Melihat Daftar:** Pada tabel *Manajemen Arsip*, staf melihat aksi tambahan yakni "Preview" berikon mata.
3. **Membaca Dokumen:** Saat tombol diklik, modal/halaman baru akan terbuka:
   - Jika PDF/Gambar: dirender dengan penampil bawaan *browser* atau pustaka ringan.
   - Jika Word: Teks dan paragraf dirender ke dalam HTML (melalui pustaka `mammoth.js` atau setara).
   - Jika Excel: Data dirender dalam bentuk tabel HTML statis (melalui pustaka `SheetJS` atau setara).
4. **Proteksi Layar:** Saat sedang membaca, pengguna akan melihat teks pola samar (misal: "Dilihat oleh Admin - 192.168.x.x") yang melayang di atas konten pembaca.

## Batasan Ruang Lingkup (Out of Scope)
- **Fidelitas Sempurna Dokumen Office:** Fitur pratinjau Word/Excel difokuskan hanya untuk *keterbacaan teks*. Format rumit seperti grafik batang, makro (VBA), atau presisi margin cetak tidak dijamin tampil persis seperti di Microsoft Office.
- **Konversi Dokumen Server-Side:** Kita tidak akan memasang LibreOffice atau *service* konversi PDF di *server* (menjaga server tetap ringan).
- **Pengeditan:** Pratinjau bersifat *Read-Only*. Fitur ini tidak dirancang untuk mengedit konten Excel atau Word di *browser*.

## Asumsi & Ketergantungan
- Pustaka *Client-Side* (misal Mammoth/SheetJS) dapat memproses dokumen hingga batas maksimum unggahan server (saat ini 10 MB) tanpa menyebabkan peramban (*browser*) menjadi *hang*.
- API *endpoint* yang mengirimkan *raw file* ke peramban untuk di-*render* wajib dilindungi dengan `ArchivePolicy` yang sama dengan *endpoint* fitur Unduh.
