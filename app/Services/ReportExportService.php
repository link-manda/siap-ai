<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
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

    public function exportToPdf(Collection $archives)
    {
        $html = $this->generatePdfHtml($archives);
        
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        
        return $pdf->download('Laporan_Arsip_' . date('Y-m-d') . '.pdf');
    }
    
    protected function generatePdfHtml(Collection $archives): string
    {
        $html = '<!DOCTYPE html><html><head><style>
            body { font-family: sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            h2 { text-align: center; }
        </style></head><body>';
        
        $html .= '<h2>Laporan Arsip Dokumen</h2>';
        $html .= '<p>Dicetak pada: ' . now()->format('Y-m-d H:i:s') . '</p>';
        
        $html .= '<table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Arsip</th>
                    <th>Kategori</th>
                    <th>Klasifikasi</th>
                    <th>Pengunggah</th>
                    <th>Tanggal Unggah</th>
                </tr>
            </thead>
            <tbody>';
            
        $no = 1;
        foreach ($archives as $archive) {
            $html .= '<tr>
                <td>' . $no++ . '</td>
                <td>' . htmlspecialchars($archive->title) . '</td>
                <td>' . ($archive->category ? htmlspecialchars($archive->category->name) : '-') . '</td>
                <td>' . ($archive->classification ? htmlspecialchars($archive->classification->name) : '-') . '</td>
                <td>' . ($archive->uploader ? htmlspecialchars($archive->uploader->name) : '-') . '</td>
                <td>' . $archive->created_at->format('Y-m-d H:i:s') . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></body></html>';
        
        return $html;
    }
}
