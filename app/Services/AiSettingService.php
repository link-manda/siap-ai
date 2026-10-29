<?php

namespace App\Services;

use App\Interfaces\AiSettingRepositoryInterface;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

class AiSettingService
{
    public function __construct(
        protected AiSettingRepositoryInterface $repository
    ) {}

    public function getActiveSetting(): AiSetting
    {
        $setting = $this->repository->getActive();

        if ($setting) {
            return $setting;
        }

        // Fallback default from config
        $defaultProvider = config('ai.default', 'openai-compatible');
        $providerConfig = config("ai.providers.{$defaultProvider}", []);

        return new AiSetting([
            'provider' => $defaultProvider,
            'base_url' => $providerConfig['url'] ?? null,
            'api_key' => $providerConfig['key'] ?? null,
            'model' => 'gpt-41',
            'timeout' => 60,
            'is_active' => true,
        ]);
    }

    public function saveSetting(array $data): AiSetting
    {
        $existing = $this->repository->getActive();

        // If api_key is omitted or blank, retain existing key if present
        if (empty($data['api_key']) && $existing && ! empty($existing->api_key)) {
            $data['api_key'] = $existing->api_key;
        }

        $data['is_active'] = true;

        return $this->repository->updateOrCreate(['is_active' => true], $data);
    }

    public function testConnection(array $data): array
    {
        $provider = $data['provider'] ?? 'openai-compatible';
        $baseUrl = $data['base_url'] ?? null;
        $apiKey = $data['api_key'] ?? null;
        $model = $data['model'] ?? 'gpt-41';
        $timeout = min(15, max(3, (int) ($data['timeout'] ?? 10)));

        // Validate endpoint against SSRF
        $ssrfError = $this->validateEndpoint($baseUrl, $provider);
        if ($ssrfError) {
            return [
                'success' => false,
                'message' => $ssrfError,
                'latency_ms' => null,
            ];
        }

        // Retain existing key if blank during test
        if (empty($apiKey)) {
            $existing = $this->repository->getActive();
            if ($existing && $existing->provider === $provider) {
                $apiKey = $existing->api_key;
            }
        }

        // Backup original runtime configuration
        $originalUrl = config("ai.providers.{$provider}.url");
        $originalKey = config("ai.providers.{$provider}.key");

        $startTime = microtime(true);

        try {
            // Apply temporary runtime config overrides
            if (! empty($baseUrl)) {
                config(["ai.providers.{$provider}.url" => $baseUrl]);
            }
            if (! empty($apiKey)) {
                config(["ai.providers.{$provider}.key" => $apiKey]);
            }

            $pingAgent = agent(instructions: 'You are a test ping agent. Respond with OK.');
            $response = $pingAgent->prompt(
                'Ping test from SIAP-AI Puskesmas system. Return OK.',
                provider: $provider,
                model: $model,
                timeout: $timeout,
            );

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'message' => "Koneksi berhasil! Model merespons dalam {$durationMs} ms.",
                'latency_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            Log::warning('AI provider connection test failed: '.$e->getMessage(), [
                'provider' => $provider,
                'base_url' => $baseUrl,
            ]);

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke provider AI. Pastikan URL endpoint dan model dapat diakses dengan benar.',
                'latency_ms' => null,
            ];
        } finally {
            // Guarantee restoration of global configuration
            config([
                "ai.providers.{$provider}.url" => $originalUrl,
                "ai.providers.{$provider}.key" => $originalKey,
            ]);
        }
    }

    /**
     * Validate endpoint URL to mitigate SSRF and cloud metadata access.
     */
    protected function validateEndpoint(?string $url, string $provider): ?string
    {
        if (empty($url)) {
            return null;
        }

        $parsed = parse_url($url);
        if (! isset($parsed['scheme']) || ! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return 'Skema URL harus berupa http:// atau https://.';
        }

        $host = strtolower($parsed['host'] ?? '');
        if (empty($host)) {
            return 'Host URL tidak valid.';
        }

        // Block cloud metadata endpoints
        $blockedHosts = ['169.254.169.254', 'metadata.google.internal', 'metadata'];
        if (in_array($host, $blockedHosts, true)) {
            return 'Akses ke metadata server dilarang demi keamanan.';
        }

        // Block loopback/private ranges for public cloud providers
        if (! in_array($provider, ['ollama', 'openai-compatible'], true)) {
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return 'URL lokal tidak diizinkan untuk penyedia cloud ini.';
            }
        }

        return null;
    }
}
