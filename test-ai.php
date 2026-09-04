<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$url = config('ai.providers.openai-compatible.url') ?? env('OPENAI_COMPATIBLE_URL', 'https://tokenportal.id/v1');
$key = config('ai.providers.openai-compatible.key') ?? env('OPENAI_COMPATIBLE_API_KEY', 'tp-_lYP75AYTC7l6GbWcSWJSR0-CSSBle6j8uoYC_NAAv0');
$model = 'nvidia/nemotron-3-super-120b-a12b:free';

echo "Testing URL: {$url}\n";

// Test models endpoint
$response = Http::withToken($key)->get("{$url}/models");
echo "Models endpoint status: " . $response->status() . "\n";
if ($response->status() !== 200) {
    echo "Body: " . substr($response->body(), 0, 500) . "\n";
}

// Test chat completions
$chatResponse = Http::withToken($key)->post("{$url}/chat/completions", [
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => 'Hello']
    ]
]);
echo "Chat completions status: " . $chatResponse->status() . "\n";
if ($chatResponse->status() !== 200) {
    echo "Body: " . substr($chatResponse->body(), 0, 500) . "\n";
} else {
    echo "Success! Response: " . json_encode($chatResponse->json()) . "\n";
}
