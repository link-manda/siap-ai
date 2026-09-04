# Ideation: Fitur Inti SIAP-AI Berikutnya
**Dibuat:** 2026-09-05
**Fokus:** Penambahan Fungsionalitas Inti (Eksplorasi Mandiri / *Surprise me*)

Berdasarkan analisis terhadap arsitektur yang sudah kita bangun (*Service-Repository*, *RBAC Policy*, *Soft Deletes*, integrasi AI) dan standar kebutuhan Sistem Informasi Manajemen Dokumen Elektronik (EDMS) di fasilitas kesehatan (Puskesmas), berikut adalah daftar ide fitur terbaik yang siap diimplementasikan. Ide-ide ini telah melewati proses penyaringan (*critique*) internal.

## Ide-Ide Terpilih (Peringkat Berdasarkan Dampak)

### 1. Ekstraksi Teks (OCR) Lokal Mandiri
- **Mengapa ini lolos:** Karena penyedia AI Anda (*TokenPortal/OpenRouter*) tidak mendukung lampiran *file* (Multimodal), fitur klasifikasi dokumen terancam lumpuh.
- **Konsep:** Kita menginstal pustaka OCR (seperti `thiagoalessio/tesseract_ocr` atau `spatie/pdf-to-text`) di server. Saat dokumen diunggah, server mengekstrak teksnya secara lokal, lalu teks mentah tersebut dikirimkan ke model AI untuk dianalisis dan diklasifikasikan. Ini menyelesaikan masalah 404/401 tanpa perlu mengganti model AI.
- **Kondisi:** Cukup mudah diimplementasikan, sangat berdampak pada fitur utama aplikasi.

### 2. Alur Persetujuan Dokumen (*Approval Workflow*)
- **Mengapa ini lolos:** Dokumen penting seperti "Surat Keputusan" atau "SOP" di Puskesmas biasanya tidak bisa langsung diarsipkan; butuh *review* dari atasan.
- **Konsep:** Menambahkan status pada tabel `archives` (misal: `Draft`, `Pending Review`, `Approved`, `Rejected`). Admin hanya bisa mengunggah sebagai `Draft/Pending Review`, lalu Pimpinan akan memvalidasi (Approve/Reject). Jika statusnya *Rejected*, dokumen dikembalikan ke Admin.
- **Kondisi:** Memanfaatkan struktur RBAC yang baru saja kita bangun. Menambah nilai *enterprise* pada sistem.

### 3. Pemberian Tanda Air (*Watermarking*) Dokumen Sensitif
- **Mengapa ini lolos:** Untuk mencegah kebocoran dokumen tingkat "Rahasia" dan "Sangat Rahasia".
- **Konsep:** Jika Pimpinan mengunduh dokumen "Sangat Rahasia", sistem secara dinamis (*on-the-fly*) menambahkan *watermark* berisi "DIUNDUH OLEH [NAMA/IP] PADA [TANGGAL]" ke dalam file PDF sebelum dikirim ke *browser*. 
- **Kondisi:** Menambah lapisan keamanan fisik setelah lapisan RBAC. Menggunakan pustaka manipulasi PDF PHP (seperti FPDF/FPDI).

### 4. Dasbor Analitik & Laporan Kinerja
- **Mengapa ini lolos:** Pimpinan butuh visualisasi, bukan sekadar tabel data yang panjang.
- **Konsep:** Membuat grafik (menggunakan *library* seperti Chart.js atau ApexCharts bawaan TailAdmin) di halaman `/dashboard`. Grafik menampilkan: tren unggahan dokumen per bulan, persentase dokumen berdasarkan klasifikasi (Biasa vs Rahasia), dan kategori dokumen terbanyak.
- **Kondisi:** Nilai *Return on Investment* (ROI) visual yang sangat tinggi untuk presentasi proyek.

### 5. Pencarian Teks Penuh Berbasis AI (*Semantic / Full-Text Search*)
- **Mengapa ini lolos:** Saat ini fitur pencarian mungkin hanya berdasarkan judul. Bagaimana jika Pimpinan lupa judulnya tapi ingat isinya?
- **Konsep:** Memanfaatkan kolom `extracted_text` dan `summary` di tabel `archives` untuk melakukan pencarian berbasis kata kunci *full-text*.
- **Kondisi:** Bisa diimplementasikan menggunakan Laravel Scout atau *query* Eloquent tingkat lanjut.

### 6. Integrasi SatuSehat Kemenkes (Jangka Panjang)
- **Mengapa ini lolos:** Regulasi pemerintah Indonesia mewajibkan fasilitas kesehatan (termasuk Puskesmas) mengintegrasikan Rekam Medis Elektronik (RME) dengan platform SatuSehat (PMK No 24 Tahun 2022).
- **Konsep:** Membangun *endpoint* atau *job* otomatis yang mengirimkan *metadata* arsip berkategori "Rekam Medis" ke API SatuSehat Kemenkes.
- **Kondisi:** Sangat strategis untuk nilai jual/skripsi, namun membutuhkan pengaturan API Key resmi dari Kemenkes.

---

## ✂️ Ide yang Ditolak (*Rejected*)
* *Digital E-Signature / TTE (Tanda Tangan Elektronik)*: Membutuhkan integrasi dengan BSrE (Balai Sertifikasi Elektronik) yang proses pendaftarannya panjang. Fitur ini berlebihan (YAGNI) untuk fase saat ini.
* *Manajemen Versi Dokumen*: Menambah kompleksitas *database* (memerlukan tabel baru untuk menyimpan setiap iterasi). Kurang cocok jika tujuan utamanya hanya pengarsipan hasil akhir.
