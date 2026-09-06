<?php

namespace App\Services;

use App\Ai\Agents\ArchiveClassifier;
use App\Models\Category;
use App\Models\Classification;

class AiClassificationService
{
    /**
     * Process document text and return AI classification results mapped to database IDs.
     */
    public function classifyDocument(string $text): array
    {
        // For development/Tugas Akhir, we pass the extracted text to the AI agent
        $response = (new ArchiveClassifier)->prompt("Tolong analisis teks dokumen ini:\n\n<document_text>\n".$text."\n</document_text>");

        $categoryName = $response['category_name'];
        $classificationName = $response['classification_name'];

        // Map names to IDs, fallback to default if not found
        $category = Category::where('name', 'like', "%{$categoryName}%")->first();
        $classification = Classification::where('name', 'like', "%{$classificationName}%")->first();

        // If not found, just use the first available ones (or null)
        $categoryId = $category ? $category->id : Category::first()?->id;
        $classificationId = $classification ? $classification->id : Classification::first()?->id;

        return [
            'category_id' => $categoryId,
            'classification_id' => $classificationId,
            'summary' => $response['summary'],
            'ai_confidence_score' => $response['confidence_score'],
            'ai_model' => 'gpt-41', // We can capture this dynamically if needed
        ];
    }
}
