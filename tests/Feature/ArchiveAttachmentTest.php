<?php

use App\Models\Archive;
use App\Models\ArchiveAttachment;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

function createTestArchive($userId)
{
    $category = Category::firstOrCreate(['name' => 'Kesehatan']);
    $classification = Classification::firstOrCreate(['name' => 'Biasa']);

    return Archive::create([
        'title' => 'Test Archive',
        'file_path' => 'private/archives/test.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploaded_by' => $userId,
    ]);
}

it('can stream an attachment with inline disposition', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    $archive = createTestArchive($user->id);

    $attachment = ArchiveAttachment::create([
        'archive_id' => $archive->id,
        'file_name' => 'document.pdf',
        'file_path' => 'private/attachments/document.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
    ]);

    Storage::put('private/attachments/document.pdf', 'dummy content');

    $response = $this->actingAs($user)
        ->get(route('archives.attachments.stream', $attachment->id));

    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'inline; filename=document.pdf');
});

test('authenticated user can append attachments to existing archive', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan'); // Pimpinan has full access
    $archive = createTestArchive($user->id);

    $file1 = UploadedFile::fake()->create('lampiran1.pdf', 1024, 'application/pdf');
    $file2 = UploadedFile::fake()->create('lampiran2.jpg', 512, 'image/jpeg');

    $response = $this->actingAs($user)->post(route('archives.attachments.store', $archive->id), [
        'attachments' => [$file1, $file2],
    ]);

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('archive_attachments', [
        'archive_id' => $archive->id,
        'file_name' => 'lampiran1.pdf',
    ]);

    $this->assertDatabaseHas('archive_attachments', [
        'archive_id' => $archive->id,
        'file_name' => 'lampiran2.jpg',
    ]);
});

test('user can download attachment', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    $archive = createTestArchive($user->id);

    Storage::disk('local')->put('private/attachments/fake.pdf', 'content');

    $attachment = ArchiveAttachment::create([
        'archive_id' => $archive->id,
        'file_name' => 'fake.pdf',
        'file_path' => 'private/attachments/fake.pdf',
        'file_size' => 100,
        'file_type' => 'pdf',
    ]);

    $response = $this->actingAs($user)->get(route('archives.attachments.download', $attachment->id));
    $response->assertOk();
    $response->assertDownload('fake.pdf');
});

test('user can delete attachment', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    $archive = createTestArchive($user->id);

    $attachment = ArchiveAttachment::create([
        'archive_id' => $archive->id,
        'file_name' => 'fake.pdf',
        'file_path' => 'private/attachments/fake.pdf',
        'file_size' => 100,
        'file_type' => 'pdf',
    ]);

    $response = $this->actingAs($user)->delete(route('archives.attachments.destroy', $attachment->id));
    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('archive_attachments', [
        'id' => $attachment->id,
    ]);
});

test('attachments are cascading soft-deleted when archive is deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    $archive = createTestArchive($user->id);

    $attachment = ArchiveAttachment::create([
        'archive_id' => $archive->id,
        'file_name' => 'fake.pdf',
        'file_path' => 'private/attachments/fake.pdf',
        'file_size' => 100,
        'file_type' => 'pdf',
    ]);

    $archive->delete();

    $this->assertSoftDeleted('archives', ['id' => $archive->id]);
    $this->assertSoftDeleted('archive_attachments', ['id' => $attachment->id]);

    $archive->restore();

    $this->assertDatabaseHas('archives', ['id' => $archive->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('archive_attachments', ['id' => $attachment->id, 'deleted_at' => null]);
});
