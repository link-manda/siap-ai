# Kebutuhan Fitur: Pemberian Tanda Air (Watermarking) Keamanan
**Dibuat:** 2026-09-05

## 1. Konteks dan Tujuan (Problem Frame)
Dokumen dengan klasifikasi "Sangat Rahasia" (Level 4) memiliki risiko tinggi jika bocor ke pihak eksternal. Saat ini, sistem telah membatasi akses (RBAC) di mana hanya peran `Pimpinan` yang dapat mengunduh dokumen tersebut. Namun, jika dokumen tersebut diunduh dan dibagikan (sengaja atau tidak) melalui perangkat pribadi, tidak ada cara untuk melacak siapa sumber kebocorannya.

Fitur ini bertujuan untuk menambahkan identitas unik (*watermark*) secara dinamis ke dalam dokumen sensitif tepat pada saat pengunduhan.

## 2. Kriteria Sukses (Success Criteria)
- Dokumen PDF dan Gambar (JPG/PNG) berlevel "Sangat Rahasia" yang diunduh wajib memuat teks identitas pengunduh.
- *Watermark* memuat 3 elemen utama: **Nama Pengunduh, Alamat IP Pengunduh, dan Tanggal Unduh**.
- Dokumen dengan format yang tidak didukung (DOCX, XLSX) tetap dapat diunduh tanpa *error*, disertai pesan peringatan keamanan di antarmuka sistem.

## 3. Cakupan dan Alur (Scope Boundaries)
**Termasuk dalam cakupan:**
- Memodifikasi *controller* pengunduhan arsip.
- Menggunakan pustaka pihak ketiga untuk manipulasi PDF (misal: FPDI/FPDF) dan Gambar (misal: Intervention Image) secara langsung di memori (*on-the-fly*).
- Menampilkan pesan peringatan *flash/session* kepada pengguna jika dokumen berlevel 4 bukan berformat PDF/Gambar.

**Di luar cakupan (Out of Scope):**
- Modifikasi *watermark* untuk format dokumen Microsoft Office (Word, Excel, PowerPoint).
- Proses pra-pembuatan (*pre-generation*) *watermark* melalui antrean (*queue*) di latar belakang (pendekatan *on-the-fly* dinilai cukup untuk volume saat ini).
- Penerapan *watermark* untuk dokumen berlevel 1 hingga 3.

## 4. Prasyarat & Asumsi
- **Asumsi Eksekusi:** Sistem memiliki ekstensi PHP yang diperlukan (misal: *GD/Imagick* untuk gambar).
- **Asumsi Kinerja:** Modifikasi PDF/Gambar *on-the-fly* tidak akan menyebabkan *timeout* asalkan ukuran *file* masih dalam batas normal dokumen operasional.

## 5. Pertanyaan Tertunda (Outstanding Questions)
- Tidak ada (*Clear for planning*). Semua keputusan produk, prioritas pelacakan (Nama, IP, Tanggal), dan kebijakan penanganan format yang tidak didukung telah disetujui pengguna.
