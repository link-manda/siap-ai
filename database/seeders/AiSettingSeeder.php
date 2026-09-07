<?php

namespace Database\Seeders;

use App\Models\AiSetting;
use Illuminate\Database\Seeder;

class AiSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AiSetting::firstOrCreate(
            ['is_active' => true],
            [
                'provider' => 'openai-compatible',
                'base_url' => env('OPENAI_COMPATIBLE_URL', 'https://api.openai.com/v1'),
                'api_key' => env('OPENAI_COMPATIBLE_API_KEY'),
                'model' => 'gpt-41',
                'timeout' => 60,
                'is_active' => true,
            ]
        );
    }
}
