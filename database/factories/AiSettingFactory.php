<?php

namespace Database\Factories;

use App\Models\AiSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSetting>
 */
class AiSettingFactory extends Factory
{
    protected $model = AiSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'openai-compatible',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'sk-test-'.fake()->uuid(),
            'model' => 'gpt-41',
            'timeout' => 60,
            'is_active' => true,
        ];
    }

    public function gemini(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/',
            'model' => 'gemini-2.5-flash',
        ]);
    }

    public function ollama(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'ollama',
            'base_url' => 'http://localhost:11434',
            'api_key' => null,
            'model' => 'llama3.2',
        ]);
    }
}
