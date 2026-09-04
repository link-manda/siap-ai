<?php

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use App\Services\WatermarkService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->category = Category::create(['name' => 'Test Category']);

    $this->classSangatRahasia = Classification::create(['name' => 'Sangat Rahasia', 'level' => 4]);
    $this->classBiasa = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $this->pimpinanUser = User::factory()->create(['role' => 'Pimpinan']);

    // Create a dummy PDF
    Storage::disk('local')->put('private/archives/test.pdf', 'dummy pdf content');

    $this->pdfArchive = Archive::create([
        'title' => 'Top Secret PDF',
        'file_path' => 'private/archives/test.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classSangatRahasia->id,
        'uploader_id' => $this->pimpinanUser->id,
    ]);

    // Create a dummy DOCX
    Storage::disk('local')->put('private/archives/test.docx', 'dummy docx content');

    $this->docxArchive = Archive::create([
        'title' => 'Top Secret DOCX',
        'file_path' => 'private/archives/test.docx',
        'file_type' => 'docx',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classSangatRahasia->id,
        'uploader_id' => $this->pimpinanUser->id,
    ]);
});

it('downloads pdf level 4 via watermark service', function () {
    // We mock the watermark service to avoid needing a real valid PDF and fonts in test
    $mockService = Mockery::mock(WatermarkService::class);

    // We expect applyWatermark to be called once and return a temp path
    $tempPath = storage_path('app/temp/test_wm.pdf');
    if (! is_dir(storage_path('app/temp'))) {
        mkdir(storage_path('app/temp'), 0755, true);
    }
    file_put_contents($tempPath, 'watermarked content');

    $mockService->shouldReceive('applyWatermark')
        ->once()
        ->andReturn($tempPath);

    $this->app->instance(WatermarkService::class, $mockService);

    $response = $this->actingAs($this->pimpinanUser)->get(route('archives.download', $this->pdfArchive->id));

    $response->assertStatus(200);

    // The response should be a binary file download
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

it('flashes warning and downloads original for docx level 4', function () {
    $response = $this->actingAs($this->pimpinanUser)->get(route('archives.download', $this->docxArchive->id));

    $response->assertStatus(200);
    $response->assertSessionHas('warning', 'File asli diunduh. Format ini tidak mendukung watermark keamanan otomatis.');
});
