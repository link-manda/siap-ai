<?php

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use App\Services\ArchiveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::create(['name' => 'Kesehatan']);
    $this->classification = Classification::create(['name' => 'Biasa', 'level' => 1]);
});

it('aggregates storage stats correctly in dashboard payload', function () {
    // 1. Active archive (retention >= today)
    Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->user->id,
        'title' => 'Active Archive 1',
        'file_path' => 'dummy/path1.pdf',
        'file_type' => 'pdf',
        'file_size' => 1000,
        'retention_date' => Carbon::tomorrow(),
    ]);

    // 2. Active archive (null retention)
    Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->user->id,
        'title' => 'Active Archive 2',
        'file_path' => 'dummy/path2.pdf',
        'file_type' => 'pdf',
        'file_size' => 1500,
        'retention_date' => null,
    ]);

    // 3. Expired archive (retention < today)
    Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->user->id,
        'title' => 'Expired Archive',
        'file_path' => 'dummy/path3.pdf',
        'file_type' => 'pdf',
        'file_size' => 2000,
        'retention_date' => Carbon::yesterday(),
    ]);

    // 4. Trashed archive
    $trashed = Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->user->id,
        'title' => 'Trashed Archive',
        'file_path' => 'dummy/path4.pdf',
        'file_type' => 'pdf',
        'file_size' => 3000,
        'retention_date' => Carbon::tomorrow(),
    ]);
    $trashed->delete(); // Soft delete it

    // Get the service
    $service = app(ArchiveService::class);

    // Simulate auth (so getBaseQuery filters correctly)
    $this->actingAs($this->user);

    $stats = $service->getDashboardStats();

    expect($stats)->toHaveKeys(['active_size', 'expired_size', 'trash_size']);

    // active_size = 1000 + 1500 = 2500
    expect($stats['active_size'])->toBe(2500);

    // expired_size = 2000
    expect($stats['expired_size'])->toBe(2000);

    // trash_size = 3000
    expect($stats['trash_size'])->toBe(3000);
});

it('shows storage depletion bar for Pimpinan role and hides it for others', function () {
    Role::firstOrCreate(['name' => 'Pimpinan']);
    Role::firstOrCreate(['name' => 'Staf']);

    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('Pimpinan');

    $staf = User::factory()->create();
    $staf->assignRole('Staf');

    // Act as Pimpinan
    $this->actingAs($pimpinan)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Penggunaan Kapasitas Penyimpanan');

    // Act as Staf
    $this->actingAs($staf)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Penggunaan Kapasitas Penyimpanan');
});

it('aggregates ai stats correctly in dashboard payload and renders Zero-JS view', function () {
    Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->user->id,
        'title' => 'AI High Doc',
        'file_path' => 'dummy/ai1.pdf',
        'file_type' => 'pdf',
        'file_size' => 1000,
        'ai_confidence_score' => 90,
    ]);

    Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->user->id,
        'title' => 'AI Low Doc',
        'file_path' => 'dummy/ai2.pdf',
        'file_type' => 'pdf',
        'file_size' => 1000,
        'ai_confidence_score' => 50,
    ]);

    $service = app(ArchiveService::class);
    $this->actingAs($this->user);

    $stats = $service->getDashboardStats();

    expect($stats)->toHaveKey('aiStats');
    expect($stats['aiStats']['average_confidence'])->toBe(70);
    expect($stats['aiStats']['low_confidence_count'])->toBe(1);
    expect($stats['aiStats']['total_ai_processed'])->toBe(2);

    // Test View rendering
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('ApexCharts')
        ->assertSee('Skor Keyakinan AI')
        ->assertSee('Antrean Verifikasi AI')
        ->assertSee('Top Kategori Arsip')
        ->assertSee('Tingkat Kerahasiaan Dokumen');
});
