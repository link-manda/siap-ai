<?php

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    // Create necessary seed data
    $this->category = Category::create(['name' => 'Test Category']);

    // Create classifications
    $this->classBiasa = Classification::create(['name' => 'Biasa', 'level' => 1]);
    $this->classRahasia = Classification::create(['name' => 'Rahasia', 'level' => 3]);
    $this->classSangatRahasia = Classification::create(['name' => 'Sangat Rahasia', 'level' => 4]);

    // Create users
    $this->adminUser = User::factory()->create(['role' => 'Admin']);
    $this->pimpinanUser = User::factory()->create(['role' => 'Pimpinan']);

    // Create an archive that is Sangat Rahasia
    $this->secretArchive = Archive::create([
        'title' => 'Top Secret Doc',
        'file_path' => 'private/archives/topsecret.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classSangatRahasia->id,
        'uploader_id' => $this->adminUser->id, // Even if Admin uploaded it!
    ]);

    // Create an archive that is Biasa
    $this->normalArchive = Archive::create([
        'title' => 'Normal Doc',
        'file_path' => 'private/archives/normal.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classBiasa->id,
        'uploader_id' => $this->adminUser->id,
    ]);
});

it('hides sangat rahasia documents from admin in datatable', function () {
    $response = $this->actingAs($this->adminUser)->get(route('archives.index'));

    $response->assertStatus(200);
    // Should see normal
    $response->assertSee('Normal Doc');
    // Should NOT see top secret
    $response->assertDontSee('Top Secret Doc');
});

it('shows sangat rahasia documents to pimpinan in datatable', function () {
    $response = $this->actingAs($this->pimpinanUser)->get(route('archives.index'));

    $response->assertStatus(200);
    // Should see both
    $response->assertSee('Normal Doc');
    $response->assertSee('Top Secret Doc');
});

it('forbids admin from downloading sangat rahasia documents', function () {
    $response = $this->actingAs($this->adminUser)->get(route('archives.download', $this->secretArchive->id));

    $response->assertStatus(403);
});

it('allows pimpinan to download sangat rahasia documents', function () {
    // We need an actual file to prevent 404 from Storage::download
    Storage::disk('local')->put('private/archives/topsecret.pdf', 'dummy content');

    $response = $this->actingAs($this->pimpinanUser)->get(route('archives.download', $this->secretArchive->id));

    $response->assertStatus(200);
});

it('forbids admin from deleting sangat rahasia documents', function () {
    $response = $this->actingAs($this->adminUser)->delete(route('archives.destroy', $this->secretArchive->id));

    $response->assertStatus(403);

    $this->assertDatabaseHas('archives', [
        'id' => $this->secretArchive->id,
        'deleted_at' => null,
    ]);
});

it('forbids admin from streaming or previewing sangat rahasia documents', function () {
    $previewResponse = $this->actingAs($this->adminUser)->get(route('archives.preview', $this->secretArchive->id));
    $previewResponse->assertStatus(403);

    $streamResponse = $this->actingAs($this->adminUser)->get(route('archives.stream', $this->secretArchive->id));
    $streamResponse->assertStatus(403);
});

it('allows pimpinan to stream and preview sangat rahasia documents', function () {
    Storage::disk('local')->put('private/archives/topsecret.pdf', 'dummy content');

    $previewResponse = $this->actingAs($this->pimpinanUser)->get(route('archives.preview', $this->secretArchive->id));
    $previewResponse->assertStatus(200);

    $streamResponse = $this->actingAs($this->pimpinanUser)->get(route('archives.stream', $this->secretArchive->id));
    $streamResponse->assertStatus(200);
});
