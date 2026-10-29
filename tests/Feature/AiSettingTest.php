<?php

use App\Helpers\MenuHelper;
use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiSettingService;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Pimpinan']);
    Role::firstOrCreate(['name' => 'Administrator']);
    Role::firstOrCreate(['name' => 'Staf']);
});

it('allows pimpinan to view ai settings page', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->get(route('settings.ai'));

    $response->assertStatus(200);
    $response->assertSee('Pengaturan Provider AI');
    $response->assertSee('Khusus Pimpinan');
});

it('denies administrator from accessing ai settings page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $response = $this->actingAs($admin)->get(route('settings.ai'));

    $response->assertStatus(403);
});

it('denies staf from accessing ai settings page', function () {
    $staf = User::factory()->create();
    $staf->assignRole('Staf');

    $response = $this->actingAs($staf)->get(route('settings.ai'));

    $response->assertStatus(403);
});

it('redirects unauthenticated guest to login', function () {
    $response = $this->get(route('settings.ai'));

    $response->assertRedirect(route('login'));
});

it('validates required fields when saving ai settings', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => '',
        'model' => '',
    ]);

    $response->assertSessionHasErrors(['provider', 'model']);
});

it('requires valid base_url when provider is openai-compatible or ollama', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => 'openai-compatible',
        'base_url' => '',
        'model' => 'gpt-4o',
    ]);

    $response->assertSessionHasErrors(['base_url']);
});

it('successfully saves and updates ai settings', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => 'gemini',
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/',
        'api_key' => 'secret-gemini-key-12345',
        'model' => 'gemini-2.5-flash',
        'timeout' => 45,
    ]);

    $response->assertRedirect(route('settings.ai'));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('ai_settings', [
        'provider' => 'gemini',
        'model' => 'gemini-2.5-flash',
        'timeout' => 45,
        'is_active' => true,
    ]);
});

it('encrypts api key in the database table', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $plainKey = 'super-secret-api-key-xyz987';

    $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => 'openai',
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => $plainKey,
        'model' => 'gpt-4o',
        'timeout' => 60,
    ]);

    // Inspect raw database record
    $rawRecord = DB::table('ai_settings')->where('provider', 'openai')->first();
    expect($rawRecord)->not->toBeNull();
    expect($rawRecord->api_key)->not->toBe($plainKey); // Ciphertext in DB

    // Inspect decrypted value through Eloquent cast
    $model = AiSetting::where('provider', 'openai')->first();
    expect($model->api_key)->toBe($plainKey);
});

it('preserves existing encrypted api key when update request submits empty key', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $originalKey = 'retained-secret-key-123';

    // First save
    $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => 'openai',
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => $originalKey,
        'model' => 'gpt-4o',
        'timeout' => 60,
    ]);

    // Second save with empty api_key
    $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => 'openai',
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => '',
        'model' => 'gpt-4o-mini',
        'timeout' => 30,
    ]);

    $model = AiSetting::where('is_active', true)->first();
    expect($model->model)->toBe('gpt-4o-mini');
    expect($model->api_key)->toBe($originalKey); // Preserved!
});

it('does not expose plaintext api key in spatie activity logs', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $plainKey = 'sensitive-unlogged-token-999';

    $this->actingAs($pimpinan)->put(route('settings.ai.update'), [
        'provider' => 'anthropic',
        'base_url' => 'https://api.anthropic.com/v1',
        'api_key' => $plainKey,
        'model' => 'claude-3-5-sonnet-latest',
        'timeout' => 60,
    ]);

    $activities = Activity::where('subject_type', AiSetting::class)->get();
    expect($activities->count())->toBeGreaterThan(0);

    foreach ($activities as $activity) {
        $propertiesJson = json_encode($activity->properties);
        expect($propertiesJson)->not->toContain($plainKey);
    }
});

it('handles test connection endpoint and returns formatted json response', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->postJson(route('settings.ai.test'), [
        'provider' => 'openai-compatible',
        'base_url' => 'http://127.0.0.1:99999/non-existent',
        'api_key' => 'test-key',
        'model' => 'gpt-4o',
        'timeout' => 5,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'latency_ms',
    ]);
});

it('falls back gracefully to default config if ai settings table is empty', function () {
    AiSetting::truncate();

    $service = app(AiSettingService::class);
    $activeSetting = $service->getActiveSetting();

    expect($activeSetting)->toBeInstanceOf(AiSetting::class);
    expect($activeSetting->provider)->toBe(config('ai.default', 'openai'));
    expect($activeSetting->model)->toBe('gpt-41');
});

it('displays pengaturan ai in sidebar menu only for pimpinan', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    // As Pimpinan
    $this->actingAs($pimpinan);
    $pimpinanNav = collect(MenuHelper::getMainNavItems())->flatMap(function ($item) {
        return isset($item['group']) ? $item['items'] : [$item];
    });
    $hasAiMenuPimpinan = $pimpinanNav->contains('name', 'Pengaturan AI');
    expect($hasAiMenuPimpinan)->toBeTrue();

    // As Administrator
    $this->actingAs($admin);
    $adminNav = collect(MenuHelper::getMainNavItems())->flatMap(function ($item) {
        return isset($item['group']) ? $item['items'] : [$item];
    });
    $hasAiMenuAdmin = $adminNav->contains('name', 'Pengaturan AI');
    expect($hasAiMenuAdmin)->toBeFalse();
});

it('applies dynamic runtime overrides in ai classification service', function () {
    AiSetting::truncate();
    AiSetting::create([
        'provider' => 'gemini',
        'base_url' => 'https://custom-gemini-endpoint.local/v1',
        'api_key' => 'secret-runtime-key',
        'model' => 'gemini-2.5-flash',
        'timeout' => 90,
        'is_active' => true,
    ]);

    $service = app(AiSettingService::class);
    $activeSetting = $service->getActiveSetting();

    expect($activeSetting->provider)->toBe('gemini');
    expect($activeSetting->model)->toBe('gemini-2.5-flash');
    expect($activeSetting->api_key)->toBe('secret-runtime-key');
});

it('denies unauthorized roles from updating ai settings', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->put(route('settings.ai.update'), [
        'provider' => 'openai',
        'model' => 'gpt-4o',
    ])->assertStatus(403);
})->with(['Administrator', 'Staf']);

it('denies unauthorized roles from testing ai connection', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->postJson(route('settings.ai.test'), [
        'provider' => 'openai',
        'model' => 'gpt-4o',
    ])->assertStatus(403);
})->with(['Administrator', 'Staf']);

it('does not expose plaintext api key in html view or json serialization', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $plainKey = 'strictly-confidential-api-key-888';

    AiSetting::truncate();
    $setting = AiSetting::create([
        'provider' => 'openai',
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => $plainKey,
        'model' => 'gpt-4o',
        'timeout' => 60,
        'is_active' => true,
    ]);

    // Ensure model serialization hides api_key
    $serialized = $setting->toArray();
    expect($serialized)->not->toHaveKey('api_key');
    expect(json_encode($setting))->not->toContain($plainKey);

    // Ensure GET view does not leak the plain key in HTML DOM
    $response = $this->actingAs($pimpinan)->get(route('settings.ai'));
    $response->assertStatus(200);
    $response->assertDontSee($plainKey);
});

it('sanitizes query parameters from base_url to prevent sensitive token exposure', function () {
    $setting = new AiSetting([
        'provider' => 'gemini',
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/?key=super-secret-query-param-token',
        'model' => 'gemini-2.5-flash',
    ]);
    $setting->save();

    expect($setting->base_url)->not->toContain('super-secret-query-param-token');
    expect($setting->base_url)->toBe('https://generativelanguage.googleapis.com/v1beta');
});

it('blocks dangerous cloud metadata endpoints from test connection', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->postJson(route('settings.ai.test'), [
        'provider' => 'openai-compatible',
        'base_url' => 'http://169.254.169.254/latest/meta-data/',
        'model' => 'gpt-4o',
        'timeout' => 5,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
        'message' => 'Akses ke metadata server dilarang demi keamanan.',
    ]);
});

it('restores original runtime configuration after test connection', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    config(['ai.providers.openai-compatible.url' => 'https://original-url.com']);
    config(['ai.providers.openai-compatible.key' => 'original-key']);

    $this->actingAs($pimpinan)->postJson(route('settings.ai.test'), [
        'provider' => 'openai-compatible',
        'base_url' => 'https://temporary-test-url.com',
        'api_key' => 'temp-key',
        'model' => 'gpt-4o',
        'timeout' => 5,
    ]);

    expect(config('ai.providers.openai-compatible.url'))->toBe('https://original-url.com');
    expect(config('ai.providers.openai-compatible.key'))->toBe('original-key');
});

it('validates timeout maximum limit of 15 seconds on test connection', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->postJson(route('settings.ai.test'), [
        'provider' => 'openai-compatible',
        'base_url' => 'https://api.openai.com/v1',
        'model' => 'gpt-4o',
        'timeout' => 300, // Exceeds 15s limit
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['timeout']);
});

it('renders aiSettingsManager cleanly without nested raw json in x-data attribute', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $response = $this->actingAs($pimpinan)->get(route('settings.ai'));
    $response->assertStatus(200);

    // Attribute x-data must not contain unescaped raw JSON objects that break HTML attribute parsing
    $response->assertDontSee('x-data="aiSettingsManager({', false);
    $response->assertSee('x-data="aiSettingsManager()"', false);
    $response->assertSee('const aiInitialSetting =', false);
});
