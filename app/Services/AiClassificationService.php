<?php

namespace App\Services;

use App\Ai\Agents\ArchiveClassifier;
use App\Models\Category;
use App\Models\Classification;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiClassificationService
{
    public function __construct(
        protected ?AiSettingService $aiSettingService = null
    ) {
        $this->aiSettingService = $aiSettingService ?? app(AiSettingService::class);
    }

    /**
     * Process document text and return AI classification results mapped to database IDs.
     *
     * @return array{
     *     category_id: ?int,
     *     classification_id: ?int,
     *     summary: string,
     *     ai_confidence_score: int,
     *     ai_model: string,
     *     is_ai_degraded: bool
     * }
     */
    public function classifyDocument(string $text, ?string $fileName = null): array
    {
        $setting = $this->aiSettingService->getActiveSetting();

        // Apply runtime config overrides if defined in setting
        if (! empty($setting->base_url)) {
            config(["ai.providers.{$setting->provider}.url" => $setting->base_url]);
        }
        if (! empty($setting->api_key)) {
            config(["ai.providers.{$setting->provider}.key" => $setting->api_key]);
        }

        $structuredData = null;
        $rawText = '';

        try {
            // Pass the extracted text to the AI agent with runtime setting overrides
            $response = (new ArchiveClassifier)->prompt(
                "Tolong analisis teks dokumen ini:\n\n<document_text>\n".$text."\n</document_text>",
                provider: $setting->provider,
                model: $setting->model,
                timeout: $setting->timeout ?? 120
            );

            $rawText = $response->text ?? '';

            // 1. Check if structured response is populated
            if (isset($response->structured) && is_array($response->structured) && ! empty($response->structured)) {
                $structuredData = $response->structured;
            } elseif (is_array($response) && ! empty($response)) {
                $structuredData = $response;
            } else {
                // 2. Fallback: try parsing JSON from raw text response
                $structuredData = $this->extractJsonFromText($rawText);
            }
        } catch (Throwable $e) {
            Log::warning('AI classification prompt failed: '.$e->getMessage(), [
                'provider' => $setting->provider,
                'model' => $setting->model,
                'file_name' => $fileName,
            ]);
        }

        return $this->resolveClassificationResult($structuredData, $setting->model, $fileName, $rawText);
    }

    /**
     * Extract structured JSON payload from raw text.
     */
    protected function extractJsonFromText(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        // Check if direct json_decode works
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded) && ! empty($decoded)) {
            return $decoded;
        }

        // Try markdown json code fence
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        }

        // Try finding any curly brace object { ... }
        if (preg_match('/(\{.*\})/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Resolve and map AI results or defaults to database records.
     *
     * @return array{
     *     category_id: ?int,
     *     classification_id: ?int,
     *     summary: string,
     *     ai_confidence_score: int,
     *     ai_model: string,
     *     is_ai_degraded: bool
     * }
     */
    protected function resolveClassificationResult(?array $structuredData, string $model, ?string $fileName, string $rawText): array
    {
        $categoryName = $structuredData['category_name'] ?? null;
        $classificationName = $structuredData['classification_name'] ?? null;
        $summary = $structuredData['summary'] ?? null;
        $confidenceScore = null;
        if (isset($structuredData['confidence_score']) && is_numeric($structuredData['confidence_score'])) {
            $raw = (float) $structuredData['confidence_score'];
            // LLMs sometimes return 0-1 scale (probability) instead of 0-100 integer.
            // Normalize: if the value is a non-zero float <= 1.0, treat it as 0-1 scale and convert.
            $confidenceScore = ($raw > 0 && $raw <= 1.0)
                ? (int) round($raw * 100)
                : (int) round($raw);
        }

        $category = null;
        if (! empty($categoryName)) {
            $category = Category::where('name', 'like', "%{$categoryName}%")->first();
        }

        $classification = null;
        if (! empty($classificationName)) {
            $classification = Classification::where('name', 'like', "%{$classificationName}%")->first();
        }

        // Intelligent fallbacks based on fileName if not matched
        if (! $category && $fileName) {
            $lowerName = strtolower($fileName);
            if (str_contains($lowerName, 'sk') || str_contains($lowerName, 'keputusan') || str_contains($lowerName, 'perpres') || str_contains($lowerName, 'permenkes')) {
                $category = Category::where('name', 'like', '%Surat Keputusan%')->first();
            } elseif (str_contains($lowerName, 'masuk')) {
                $category = Category::where('name', 'like', '%Surat Masuk%')->first();
            } elseif (str_contains($lowerName, 'keluar')) {
                $category = Category::where('name', 'like', '%Surat Keluar%')->first();
            } elseif (str_contains($lowerName, 'medis') || str_contains($lowerName, 'pasien')) {
                $category = Category::where('name', 'like', '%Rekam Medis%')->first();
            } elseif (str_contains($lowerName, 'keuangan') || str_contains($lowerName, 'anggaran') || str_contains($lowerName, 'spbe')) {
                $category = Category::where('name', 'like', '%Laporan Keuangan%')->first()
                    ?? Category::where('name', 'like', '%Surat Keputusan%')->first();
            } elseif (str_contains($lowerName, 'pegawai') || str_contains($lowerName, 'sdm')) {
                $category = Category::where('name', 'like', '%Data Pegawai%')->first();
            }
        }

        $categoryId = $category?->id ?? Category::first()?->id;
        $classificationId = $classification?->id ?? Classification::where('level', 1)->first()?->id ?? Classification::first()?->id;

        $isDegraded = empty($structuredData) || empty($structuredData['category_name']);

        if (empty($summary)) {
            $cleanName = $fileName ? pathinfo($fileName, PATHINFO_FILENAME) : 'Dokumen Arsip';
            $summary = "Dokumen arsip Puskesmas: {$cleanName}. Silakan tinjau dan lengkapi rincian dokumen.";
        }

        return [
            'category_id' => $categoryId,
            'classification_id' => $classificationId,
            'summary' => $summary,
            'ai_confidence_score' => $confidenceScore ?? ($isDegraded ? 0 : 50),
            'ai_model' => $model,
            'is_ai_degraded' => $isDegraded,
        ];
    }
}
