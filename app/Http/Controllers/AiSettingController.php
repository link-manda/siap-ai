<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestAiConnectionRequest;
use App\Http\Requests\UpdateAiSettingRequest;
use App\Models\AiSetting;
use App\Services\AiSettingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiSettingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected AiSettingService $aiSettingService
    ) {}

    /**
     * Show the form for editing the AI settings.
     */
    public function edit(): View
    {
        $this->authorize('viewAny', AiSetting::class);

        $setting = $this->aiSettingService->getActiveSetting();

        return view('pages.settings.ai', [
            'setting' => $setting ? $setting->only(['provider', 'base_url', 'model', 'timeout']) : null,
            'has_saved_key' => ! empty($setting?->api_key),
        ]);
    }

    /**
     * Update the AI settings in storage.
     */
    public function update(UpdateAiSettingRequest $request): RedirectResponse
    {
        $this->authorize('update', AiSetting::class);

        $this->aiSettingService->saveSetting($request->validated());

        return redirect()
            ->route('settings.ai')
            ->with('status', 'Pengaturan Provider AI berhasil diperbarui!');
    }

    /**
     * Test connection to the AI provider.
     */
    public function testConnection(TestAiConnectionRequest $request): JsonResponse
    {
        $this->authorize('manage', AiSetting::class);

        $result = $this->aiSettingService->testConnection($request->validated());

        return response()->json($result);
    }
}
