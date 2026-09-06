<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('renders flux layout shell for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('SIAP-AI');
    $response->assertSee('Dashboard');
    $response->assertSee('Manajemen Arsip');
    $response->assertSee('Upload Arsip (AI)');
    $response->assertSee('Audit Trail');
});

it('shows user management menu to pimpinan and hides it from regular admin', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    // Pimpinan can see Pengguna & Role
    $responsePimpinan = $this->actingAs($pimpinan)->get(route('dashboard'));
    $responsePimpinan->assertStatus(200);
    $responsePimpinan->assertSee('Pengguna & Role');

    // Administrator cannot see Pengguna & Role
    $responseAdmin = $this->actingAs($admin)->get(route('dashboard'));
    $responseAdmin->assertStatus(200);
    $responseAdmin->assertDontSee('Pengguna & Role');
});

it('renders login page with siap-ai branding and without dead links', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
    $response->assertSee('SIAP-AI');
    $response->assertSee('Masuk ke Sistem');
    $response->assertDontSee('/signup');
    $response->assertDontSee('auth-logo.svg');
});

it('renders profile page with authenticated user details', function () {
    $user = User::factory()->create(['name' => 'Dokter Penguji', 'email' => 'dokter@puskesmas.test']);

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertStatus(200);
    $response->assertSee('Profil Pengguna');
    $response->assertSee('Dokter Penguji');
    $response->assertSee('dokter@puskesmas.test');
    $response->assertDontSee('Musharof');
});
