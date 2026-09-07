<?php

namespace App\Services;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArchiveService
{
    public function __construct(
        protected ArchiveRepositoryInterface $archiveRepository,
        protected AiClassificationService $aiClassificationService
    ) {}

    /**
     * Process an uploaded file and prepare draft archive data with AI recommendations.
     */
    public function processUpload(UploadedFile $file, ?string $extractedText = null): array
    {
        // Save file temporarily to avoid orphan leaks if not saved
        $path = $file->store('temp/archives');

        $fileName = $file->getClientOriginalName();
        $fileType = $file->extension() ?: $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        // If no text is provided, in a real app we would run OCR here.
        // For now, we simulate OCR text if empty.
        $textToAnalyze = $extractedText ?: 'Simulasi isi dokumen: '.$fileName;

        // Call AI Service
        $aiResult = $this->aiClassificationService->classifyDocument($textToAnalyze);

        return [
            'title' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_path' => $path,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'extracted_text' => $textToAnalyze,
            'category_id' => $aiResult['category_id'],
            'classification_id' => $aiResult['classification_id'],
            'summary' => $aiResult['summary'],
            'ai_confidence_score' => $aiResult['ai_confidence_score'],
            'ai_model' => $aiResult['ai_model'],
        ];
    }

    /**
     * Save the reviewed archive data.
     */
    public function storeArchive(array $validatedData, int $userId): Archive
    {
        $validatedData['uploaded_by'] = $userId;
        $validatedData['file_path'] = $this->moveToPrivateStorage($validatedData['file_path']);

        return $this->archiveRepository->create($validatedData);
    }

    /**
     * Save multiple archives in a single transaction.
     */
    public function storeBulkArchives(array $archivesData, int $userId): array
    {
        $savedArchives = [];

        DB::transaction(function () use ($archivesData, $userId, &$savedArchives) {
            $savedArchives = []; // Reset on retry
            foreach ($archivesData as $data) {
                $data['uploaded_by'] = $userId;
                $data['file_path'] = $this->moveToPrivateStorage($data['file_path']);
                $archive = $this->archiveRepository->create($data);
                $savedArchives[] = $archive;
            }
        }, 3);

        return $savedArchives;
    }

    protected function moveToPrivateStorage(string $tempPath): string
    {
        if (str_starts_with($tempPath, 'temp/archives/')) {
            $newPath = str_replace('temp/archives/', 'private/archives/', $tempPath);
            if (Storage::exists($tempPath)) {
                Storage::move($tempPath, $newPath);

                return $newPath;
            }
        }

        return $tempPath;
    }

    public function getDashboardStats(): array
    {
        return array_merge([
            'totalArchives' => $this->archiveRepository->countTotal(),
            'categoryStats' => $this->archiveRepository->getCategoryStats(),
            'classificationStats' => $this->archiveRepository->getClassificationStats(),
            'monthlyStats' => $this->archiveRepository->getMonthlyStats(),
        ], $this->archiveRepository->getStorageStats());
    }
}
