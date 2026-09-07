<?php

namespace App\Http\Requests;

use App\Models\AiSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TestAiConnectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage', AiSetting::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:openai-compatible,gemini,openai,ollama,deepseek,anthropic'],
            'base_url' => [
                'nullable',
                'url',
                'required_if:provider,openai-compatible,ollama',
            ],
            'api_key' => ['nullable', 'string'],
            'model' => ['required', 'string', 'max:100'],
            'timeout' => ['nullable', 'integer', 'min:3', 'max:15'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'Provider AI wajib dipilih.',
            'provider.in' => 'Provider AI yang dipilih tidak valid.',
            'base_url.url' => 'Format Base URL tidak valid (harus berupa URL valid).',
            'base_url.required_if' => 'Base URL wajib diisi jika memilih OpenAI-Compatible atau Ollama.',
            'model.required' => 'Nama Model AI wajib diisi.',
            'timeout.integer' => 'Batas waktu (timeout) harus berupa angka detik.',
            'timeout.min' => 'Batas waktu minimal tes koneksi adalah 3 detik.',
            'timeout.max' => 'Batas waktu maksimal tes koneksi adalah 15 detik.',
        ];
    }
}
