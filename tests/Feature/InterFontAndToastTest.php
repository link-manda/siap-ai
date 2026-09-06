<?php

use App\Models\User;

it('standardizes typography on inter across login and authenticated layouts', function () {
    $user = User::factory()->create();

    // Test fullscreen / login layout
    $loginResponse = $this->get(route('login'));
    $loginResponse->assertStatus(200);
    $loginResponse->assertSee('family=inter:400,500,600,700', false);
    $loginResponse->assertDontSee('instrument-sans', false);
    $loginResponse->assertDontSee('Outfit', false);

    // Test authenticated app layout
    $appResponse = $this->actingAs($user)->get(route('dashboard'));
    $appResponse->assertStatus(200);
    $appResponse->assertSee('family=inter:400,500,600,700', false);
    $appResponse->assertDontSee('instrument-sans', false);
    $appResponse->assertDontSee('Outfit', false);
});

it('verifies css configuration has inter font and puskesmas green accent theme', function () {
    $cssPath = resource_path('css/app.css');
    $cssContent = file_get_contents($cssPath);

    // Font verification
    expect($cssContent)->not->toContain('Outfit');
    expect($cssContent)->toContain("--font-sans: 'Inter', sans-serif;");

    // Puskesmas Green Theme Accent verification
    expect($cssContent)->toContain('--color-accent: var(--color-green-600);');
    expect($cssContent)->toContain('--color-accent-content: var(--color-green-600);');
    expect($cssContent)->toContain('--color-accent-foreground: var(--color-white);');
    expect($cssContent)->toContain('--color-accent-content: var(--color-green-400);');
});

it('renders global flux toast container in both layouts', function () {
    $user = User::factory()->create();

    $loginResponse = $this->get(route('login'));
    $loginResponse->assertStatus(200);
    $loginResponse->assertSee('<ui-toast', false);
    $loginResponse->assertSee('position="top right"', false);

    $appResponse = $this->actingAs($user)->get(route('dashboard'));
    $appResponse->assertStatus(200);
    $appResponse->assertSee('<ui-toast', false);
    $appResponse->assertSee('position="top right"', false);
});

it('bridges session success flash to flux toast with 5000ms duration', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Arsip berhasil disimpan!'])
        ->get(route('archives.index'));

    $response->assertStatus(200);
    $response->assertSee('window.SiapToast', false);
    $response->assertSee('Arsip berhasil disimpan!', false);
    $response->assertSee('success', false);
    $response->assertSee('5000', false);
});

it('bridges session error flash to persistent danger flux toast with zero duration', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['error' => 'Gagal memproses dokumen.'])
        ->get(route('archives.index'));

    $response->assertStatus(200);
    $response->assertSee('window.SiapToast', false);
    $response->assertSee('Gagal memproses dokumen.', false);
    $response->assertSee('danger', false);
    $response->assertSee('Kesalahan', false);
    $response->assertSee(', 0);', false);
});

it('verifies livewire tables no longer contain legacy inline session banners', function () {
    $archiveTableView = file_get_contents(resource_path('views/livewire/archive-table.blade.php'));
    $userTableView = file_get_contents(resource_path('views/livewire/user-table.blade.php'));

    expect($archiveTableView)->not->toContain("session('success')");
    expect($archiveTableView)->not->toContain("session()->has('success')");

    expect($userTableView)->not->toContain("session('success')");
    expect($userTableView)->not->toContain("session('error')");
    expect($userTableView)->not->toContain("session()->has('success')");
    expect($userTableView)->not->toContain("session()->has('error')");
});
