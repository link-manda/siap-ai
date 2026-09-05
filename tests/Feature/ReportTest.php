<?php

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Test Category']);

    $this->classSangatRahasia = Classification::create(['name' => 'Sangat Rahasia', 'level' => 4]);
    $this->classBiasa = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $this->pimpinanUser = User::factory()->create(['role' => 'Pimpinan']);
    $this->adminUser = User::factory()->create(['role' => 'Admin']);

    Archive::create([
        'title' => 'Top Secret Archive',
        'file_path' => 'dummy.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classSangatRahasia->id,
        'uploader_id' => $this->pimpinanUser->id,
    ]);

    Archive::create([
        'title' => 'Public Archive',
        'file_path' => 'dummy2.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classBiasa->id,
        'uploader_id' => $this->pimpinanUser->id,
    ]);
});

it('allows pimpinan to export all archives to excel', function () {
    $response = $this->actingAs($this->pimpinanUser)->get(route('archives.export.excel'));
    $response->assertStatus(200);
    // 2 archives = Top Secret and Public
});

it('allows pimpinan to export all archives to pdf', function () {
    $response = $this->actingAs($this->pimpinanUser)->get(route('archives.export.pdf'));
    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

it('filters level 4 archives from admin in excel export', function () {
    $repo = app(ArchiveRepositoryInterface::class);

    $this->actingAs($this->adminUser);

    $archives = $repo->getAllForExport();

    expect($archives)->toHaveCount(1);
    expect($archives->first()->title)->toBe('Public Archive');
});
