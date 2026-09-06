<?php

use App\Livewire\ArchiveTable;
use App\Livewire\AuditTrailTable;
use App\Livewire\UserTable;
use App\Models\Archive;
use App\Models\ArchiveLog;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

it('renders archive table and filters reactively by search', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Umum']);
    $classification = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $archiveA = Archive::create([
        'title' => 'Surat Keputusan Kepala Puskesmas',
        'file_path' => 'private/archives/sk.pdf',
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploader_id' => $user->id,
    ]);

    $archiveB = Archive::create([
        'title' => 'Laporan Vaksinasi Balita',
        'file_path' => 'private/archives/vaksin.pdf',
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploader_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ArchiveTable::class)
        ->assertSee('Surat Keputusan')
        ->assertSee('Laporan Vaksinasi')
        ->set('search', 'Vaksinasi')
        ->assertSee('Laporan Vaksinasi')
        ->assertDontSee('Surat Keputusan');
});

it('hides level 4 confidential archives from regular administrator in archive table', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $category = Category::create(['name' => 'Umum']);
    $secretClassification = Classification::firstOrCreate(['level' => 4], ['name' => 'Sangat Rahasia']);

    $secretArchive = Archive::create([
        'title' => 'Dokumen Sangat Rahasia Negara',
        'file_path' => 'private/archives/rahasia.pdf',
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $secretClassification->id,
        'uploader_id' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(ArchiveTable::class)
        ->assertDontSee('Dokumen Sangat Rahasia Negara');
});

it('renders audit trail table and switches tabs reactively', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');

    $category = Category::create(['name' => 'Umum']);
    $classification = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $archive = Archive::create([
        'title' => 'Dokumen Audit Sample',
        'file_path' => 'private/archives/sample.pdf',
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploader_id' => $user->id,
    ]);

    ArchiveLog::create([
        'archive_id' => $archive->id,
        'user_id' => $user->id,
        'action' => 'VIEW',
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($user);

    Livewire::test(AuditTrailTable::class)
        ->assertSee('Dokumen Audit Sample')
        ->assertSee('VIEW')
        ->call('setTab', 'system')
        ->assertSet('tab', 'system')
        ->assertSee('Aktivitas Sistem');
});

it('renders user table and filters by search', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $pimpinan = User::factory()->create(['name' => 'dr. Pimpinan Puskesmas', 'email' => 'pimpinan.test@puskesmas.go.id']);
    $pimpinan->assignRole('Pimpinan');

    $staff = User::factory()->create(['name' => 'Staff Administrasi', 'email' => 'staff.test@puskesmas.go.id']);
    $staff->assignRole('Administrator');

    $this->actingAs($pimpinan);

    Livewire::test(UserTable::class)
        ->assertSee('dr. Pimpinan Puskesmas')
        ->assertSee('Staff Administrasi')
        ->set('search', 'Administrasi')
        ->assertSee('Staff Administrasi')
        ->assertDontSee('dr. Pimpinan Puskesmas');
});
