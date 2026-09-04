<?php

namespace App\Services;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use Illuminate\Http\UploadedFile;
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
        // Save file securely to private storage
        $path = $file->store('private/archives');
        
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();

        // If no text is provided, in a real app we would run OCR here. 
        // For now, we simulate OCR text if empty.
        $textToAnalyze = $extractedText ?: "Simulasi isi dokumen: " . $fileName;

        // Call AI Service
        $aiResult = $this->aiClassificationService->classifyDocument($textToAnalyze);

        return [
            'title' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_path' => $path,
            'file_type' => $fileType,
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
        return $this->archiveRepository->create($validatedData);
    }
}
