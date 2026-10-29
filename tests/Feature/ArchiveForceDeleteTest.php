<?php

use App\Models\Archive;
use App\Models\ArchiveLog;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->category = Category::create(['name' => 'Kesehatan']);
    $this->classification = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan']);
    $stafRole = Role::firstOrCreate(['name' => 'Staf']);

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole($pimpinanRole);

    $this->staf = User::factory()->create();
    $this->staf->assignRole($stafRole);
});

it('allows pimpinan to permanently force delete an archive from trash without query exception', function () {
    Storage::disk('local')->put('private/archives/test_doc.pdf', 'dummy content');

    $archive = Archive::create([
        'title' => 'Dokumen Hapus Permanen',
        'file_path' => 'private/archives/test_doc.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->pimpinan->id,
    ]);

    // Soft delete first so it enters trash
    $archive->delete();
    expect(Archive::onlyTrashed()->find($archive->id))->not->toBeNull();

    // Verify soft delete logged to archive_logs
    expect(ArchiveLog::where('archive_id', $archive->id)->where('action', 'DELETE')->exists())->toBeTrue();

    // Perform force delete as Pimpinan
    $response = $this->actingAs($this->pimpinan)
        ->delete(route('archives.force-destroy', $archive->id));

    $response->assertRedirect(route('archives.trash'));
    $response->assertSessionHas('success', 'Arsip berhasil dihapus permanen!');

    // Verify row is physically purged from archives
    expect(Archive::withTrashed()->find($archive->id))->toBeNull();

    // Verify file is cleaned up from storage disk
    Storage::disk('local')->assertMissing('private/archives/test_doc.pdf');
});

it('still logs DELETE and RESTORE actions in archive_logs during regular soft delete lifecycle', function () {
    $archive = Archive::create([
        'title' => 'Dokumen Siklus Hidup',
        'file_path' => 'private/archives/doc_lifecycle.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->pimpinan->id,
    ]);

    // Regular soft delete
    $archive->delete();

    expect(ArchiveLog::where('archive_id', $archive->id)->where('action', 'DELETE')->exists())->toBeTrue();

    // Restore via controller
    $response = $this->actingAs($this->pimpinan)
        ->post(route('archives.restore', $archive->id));

    $response->assertRedirect(route('archives.trash'));
    $response->assertSessionHas('success', 'Arsip berhasil dikembalikan!');

    expect(ArchiveLog::where('archive_id', $archive->id)->where('action', 'RESTORE')->exists())->toBeTrue();
});

it('denies non-pimpinan from performing force delete', function () {
    $archive = Archive::create([
        'title' => 'Dokumen Terlindungi',
        'file_path' => 'private/archives/protected.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'category_id' => $this->category->id,
        'classification_id' => $this->classification->id,
        'uploaded_by' => $this->staf->id,
    ]);

    $archive->delete();

    $response = $this->actingAs($this->staf)
        ->delete(route('archives.force-destroy', $archive->id));

    $response->assertForbidden();
    expect(Archive::onlyTrashed()->find($archive->id))->not->toBeNull();
});
