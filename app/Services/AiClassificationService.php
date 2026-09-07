<?php

namespace App\Services;

use App\Ai\Agents\ArchiveClassifier;
use App\Models\Category;
use App\Models\Classification;

class AiClassificationService
{
    public function __construct(
        protected ?AiSettingService $aiSettingService = null
    ) {
        $this->aiSettingService = $aiSettingService ?? app(AiSettingService::class);
    }

    /**
     * Process document text and return AI classification results mapped to database IDs.
     */
    public function classifyDocument(string $text): array
    {
        $setting = $this->aiSettingService->getActiveSetting();

        // Apply runtime config overrides if defined in setting
        if (! empty($setting->base_url)) {
            config(["ai.providers.{$setting->provider}.url" => $setting->base_url]);
        }
        if (! empty($setting->api_key)) {
            config(["ai.providers.{$setting->provider}.key" => $setting->api_key]);
        }

        // Pass the extracted text to the AI agent with runtime setting overrides
        $response = (new ArchiveClassifier)->prompt(
            "Tolong analisis teks dokumen ini:\n\n<document_text>\n".$text."\n</document_text>",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 120
        );

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
            'ai_model' => $setting->model,
        ];
    }
}
