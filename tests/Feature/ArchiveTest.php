<?php

use App\Ai\Agents\ArchiveClassifier;
use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Facades\Ai;

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
        'confidence_score' => 95,
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

it('normalizes AI confidence score from 0-1 scale to 0-100 integer scale', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    // Simulate AI returning a float on 0-1 scale (e.g. 0.90 = 90%)
    $fakeResponse = [
        'category_name' => 'Kesehatan',
        'classification_name' => 'Biasa',
        'summary' => 'Dokumen kesehatan.',
        'confidence_score' => 0.90,
    ];
    ArchiveClassifier::fake([$fakeResponse]);

    $response = $this->actingAs($this->user)
        ->postJson(route('archives.process-ai'), [
            'document' => $file,
        ]);

    // 0.90 from AI should be normalized to 90, NOT truncated to 0
    $response->assertOk()
        ->assertJsonPath('data.ai_confidence_score', 90);
});

it('normalizes low AI confidence score from 0-1 scale correctly', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    // Simulate AI returning 0.65 = 65%
    $fakeResponse = [
        'category_name' => 'Kesehatan',
        'classification_name' => 'Biasa',
        'summary' => 'Dokumen kesehatan.',
        'confidence_score' => 0.65,
    ];
    ArchiveClassifier::fake([$fakeResponse]);

    $response = $this->actingAs($this->user)
        ->postJson(route('archives.process-ai'), [
            'document' => $file,
        ]);

    // 0.65 from AI should be normalized to 65, NOT truncated to 0
    $response->assertOk()
        ->assertJsonPath('data.ai_confidence_score', 65);
});

it('handles conversational or non-json AI response gracefully without 500 error', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('5. Perpres Nomor 95 Tahun 2018 tentang SPBE.pdf', 100, 'application/pdf');

    // Simulate conversational plain text without structured JSON
    ArchiveClassifier::fake(['Halo! Saya CodeBuddy Code. Isi teks lengkap dari dokumen tidak tersedia.']);

    $response = $this->actingAs($this->user)
        ->postJson(route('archives.process-ai'), [
            'document' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.category_id', $this->category->id)
        ->assertJsonPath('data.classification_id', $this->classification->id)
        ->assertJsonPath('data.is_ai_degraded', true);

    expect($response->json('data.summary'))->not->toBeEmpty();
});

it('handles AI exception gracefully and provides fallback defaults', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('surat_keputusan.pdf', 100, 'application/pdf');

    // Simulate AI throwing exception
    ArchiveClassifier::fake(function () {
        throw new RuntimeException('AI Service Gateway Timeout');
    });

    $response = $this->actingAs($this->user)
        ->postJson(route('archives.process-ai'), [
            'document' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.category_id', $this->category->id)
        ->assertJsonPath('data.classification_id', $this->classification->id)
        ->assertJsonPath('data.is_ai_degraded', true)
        ->assertJsonPath('data.ai_confidence_score', 0);
});

it('prevents guests from downloading archives', function () {
    $archive = Archive::create([
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'user_id' => $this->user->id,
        'title' => 'Guest Download Test',
        'file_path' => 'dummy/path.pdf',
        'file_type' => 'pdf',
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
        'title' => 'Test Doc',
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
        'file_type' => 'pdf',
    ]);

    $response = $this->actingAs($this->user)->delete(route('archives.destroy', $archive->id));

    $response->assertRedirect(route('archives.index'));

    // Assert soft deleted
    $this->assertSoftDeleted('archives', [
        'id' => $archive->id,
    ]);
});

it('bulk stores multiple archives and logs uploads', function () {
    Storage::fake('local');
    $filePath1 = 'private/archives/bulk1.pdf';
    $filePath2 = 'private/archives/bulk2.pdf';
    Storage::disk('local')->put($filePath1, 'dummy content 1');
    Storage::disk('local')->put($filePath2, 'dummy content 2');

    $response = $this->actingAs($this->user)->postJson(route('archives.bulk-store'), [
        'archives' => [
            [
                'title' => 'Bulk Doc 1',
                'file_path' => $filePath1,
                'file_type' => 'pdf',
                'file_size' => 1024,
                'category_id' => $this->category->id,
                'classification_id' => $this->classification->id,
            ],
            [
                'title' => 'Bulk Doc 2',
                'file_path' => $filePath2,
                'file_type' => 'pdf',
                'file_size' => 2048,
                'category_id' => $this->category->id,
                'classification_id' => $this->classification->id,
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('message', 'Semua arsip berhasil disimpan!');

    $this->assertDatabaseHas('archives', ['title' => 'Bulk Doc 1']);
    $this->assertDatabaseHas('archives', ['title' => 'Bulk Doc 2']);

    // Check Observer logs UPLOAD
    $archive1 = Archive::where('title', 'Bulk Doc 1')->first();
    $this->assertDatabaseHas('archive_logs', [
        'archive_id' => $archive1->id,
        'action' => 'UPLOAD',
    ]);
});

it('rejects bulk store if more than 5 archives', function () {
    $archives = [];
    for ($i = 0; $i < 6; $i++) {
        $archives[] = [
            'title' => 'Doc '.$i,
            'file_path' => 'private/archives/doc'.$i.'.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'category_id' => $this->category->id,
            'classification_id' => $this->classification->id,
        ];
    }

    $response = $this->actingAs($this->user)->postJson(route('archives.bulk-store'), [
        'archives' => $archives,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['archives']);
});
