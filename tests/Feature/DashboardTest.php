<?php

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use App\Services\ArchiveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
