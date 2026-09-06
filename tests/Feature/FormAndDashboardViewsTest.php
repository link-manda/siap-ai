<?php

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->user = User::factory()->create();
    $this->category = Category::create(['name' => 'Umum']);
    $this->classification = Classification::create(['name' => 'Biasa', 'level' => 1]);
});

test('dashboard view renders metric cards and charts successfully', function () {
    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Dashboard Ringkasan');
    $response->assertSee('Total Arsip Tersimpan');
    $response->assertSee('Komposisi Kategori Arsip');
});

test('bulk upload view renders with flux fields and dropzone', function () {
    $response = $this->actingAs($this->user)->get(route('archives.create'));

    $response->assertStatus(200);
    $response->assertSee('Unggah Arsip Massal (AI)');
    $response->assertSee('1. Pilih Dokumen (Maksimal 5)');
    $response->assertSee('Pilih Berkas (PDF, Word, Excel, Gambar)');
});

test('user create and edit views render for authorized pimpinan', function () {
    $permission = Permission::firstOrCreate(['name' => 'manage-users']);
    $role = Role::firstOrCreate(['name' => 'Pimpinan']);
    $role->givePermissionTo($permission);
    $this->user->assignRole('Pimpinan');

    $targetUser = User::factory()->create();

    $createResponse = $this->actingAs($this->user)->get(route('users.create'));
    $createResponse->assertStatus(200);
    $createResponse->assertSee('Tambah Pengguna Baru');

    $editResponse = $this->actingAs($this->user)->get(route('users.edit', $targetUser));
    $editResponse->assertStatus(200);
    $editResponse->assertSee('Edit Pengguna');
});

test('archive preview view renders with flux components and watermark', function () {
    $archive = Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'user_id' => $this->user->id,
        'title' => 'Dokumen Kontrak Kerja',
        'document_number' => 'DOC/2026/001',
        'archive_code' => 'ARC-001',
        'file_path' => 'dummy/test.pdf',
        'file_type' => 'pdf',
        'summary' => 'Ringkasan dokumen kontrak kerja staf.',
    ]);

    $response = $this->actingAs($this->user)->get(route('archives.preview', $archive->id));

    $response->assertStatus(200);
    $response->assertSee('Dokumen Kontrak Kerja');
    $response->assertSee('Dilihat oleh');
    $response->assertSee('Ringkasan Eksekutif AI');
});
