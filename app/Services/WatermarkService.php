<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use setasign\Fpdi\Fpdi;

class WatermarkService
{
    public function applyWatermark(Archive $archive, User $user, string $ipAddress): string
    {
        $text = sprintf(
            'DIUNDUH OLEH: %s | IP: %s | TANGGAL: %s',
            $user->name,
            $ipAddress,
            now()->format('Y-m-d H:i:s')
        );

        $sourcePath = Storage::path($archive->file_path);
        $ext = strtolower($archive->file_type);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir.'/'.uniqid('wm_').'.'.$ext;

        if ($ext === 'pdf') {
            return $this->watermarkPdf($sourcePath, $tempFile, $text);
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return $this->watermarkImage($sourcePath, $tempFile, $text);
        }

        return $sourcePath;
    }

    protected function watermarkPdf(string $sourcePath, string $tempFile, string $text): string
    {
        $pdf = new Fpdi;

        try {
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(255, 0, 0);

                $y = $size['height'] - 10;
                $pdf->SetXY(0, $y);
                $pdf->Cell($size['width'], 10, $text, 0, 0, 'C');
            }

            $pdf->Output('F', $tempFile);

            return $tempFile;
        } catch (\Exception $e) {
            // Fallback to original if PDF is encrypted or unsupported version
            return $sourcePath;
        }
    }

    protected function watermarkImage(string $sourcePath, string $tempFile, string $text): string
    {
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->read($sourcePath);

            // For GD, basic built-in fonts are 1 to 5. We use 5 for largest.
            $image->text($text, (int) ($image->width() / 2), $image->height() - 20, function ($font) {
                $font->file(5);
                $font->color('rgba(255, 0, 0, 0.8)');
                $font->align('center');
                $font->valign('bottom');
            });

            $image->save($tempFile);

            return $tempFile;
        } catch (\Exception $e) {
            return $sourcePath;
        }
    }
}
