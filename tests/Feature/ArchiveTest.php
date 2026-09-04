<?php

use App\Models\User;
use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\ArchiveLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Facades\Ai;
use App\Ai\Agents\ArchiveClassifier;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::create(['name' => 'Kesehatan']);
    $this->classification = Classification::create(['name' => 'Biasa']);
});

it('processes uploaded document with AI and returns structured data', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    // Fake the AI agent's structured response
    $fakeResponse = [
        'category_name' => 'Kesehatan',
        'classification_name' => 'Biasa',
        'summary' => 'Ini adalah dokumen tentang kesehatan.',
        'confidence_score' => 95
    ];
    ArchiveClassifier::fake([$fakeResponse]);

    $response = $this->actingAs($this->user)
        ->postJson(route('archives.process-ai'), [
            'document' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.category_id', $this->category->id)
        ->assertJsonPath('data.classification_id', $this->classification->id)
        ->assertJsonPath('data.summary', 'Ini adalah dokumen tentang kesehatan.')
        ->assertJsonPath('data.ai_confidence_score', 95);

    // Ensure file was stored temporarily
    Storage::disk('local')->assertExists($response->json('data.file_path'));
});

it('prevents guests from downloading archives', function () {
    $archive = Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'user_id' => $this->user->id,
        'title' => 'Guest Download Test',
        'file_path' => 'dummy/path.pdf',
        'file_type' => 'pdf'
    ]);

    $response = $this->get(route('archives.download', $archive->id));

    $response->assertRedirect(route('login'));
});

it('allows authenticated users to download archives and logs the action', function () {
    Storage::fake('local');
    $filePath = 'archives/test-doc.pdf';
    Storage::disk('local')->put($filePath, 'dummy content');

    $archive = Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'user_id' => $this->user->id,
        'file_path' => $filePath,
        'file_type' => 'pdf',
        'title' => 'Test Doc'
    ]);

    $response = $this->actingAs($this->user)->get(route('archives.download', $archive->id));

    $response->assertDownload('Test Doc.pdf');

    // Check Audit Trail
    $this->assertDatabaseHas('archive_logs', [
        'archive_id' => $archive->id,
        'user_id' => $this->user->id,
        'action' => 'DOWNLOAD',
    ]);
});

it('soft deletes an archive', function () {
    $archive = Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'user_id' => $this->user->id,
        'title' => 'Soft Delete Test',
        'file_path' => 'dummy/path2.pdf',
        'file_type' => 'pdf'
    ]);

    $response = $this->actingAs($this->user)->delete(route('archives.destroy', $archive->id));

    $response->assertRedirect(route('archives.index'));

    // Assert soft deleted
    $this->assertSoftDeleted('archives', [
        'id' => $archive->id
    ]);
});
