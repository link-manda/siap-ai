<?php

use App\Livewire\ArchiveTable;
use App\Models\Archive;
use App\Models\ArchiveAttachment;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Livewire\Livewire;

function createArchiveWithAttachmentForWarningTest($userId)
{
    $category = Category::firstOrCreate(['name' => 'Kesehatan']);
    $classification = Classification::firstOrCreate(['name' => 'Biasa']);

    $archive = Archive::create([
        'title' => 'Archive with Attachment',
        'file_path' => 'private/archives/test.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploaded_by' => $userId,
    ]);

    ArchiveAttachment::create([
        'archive_id' => $archive->id,
        'file_name' => 'doc1.pdf',
        'file_path' => 'private/attachments/doc1.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
    ]);

    ArchiveAttachment::create([
        'archive_id' => $archive->id,
        'file_name' => 'doc2.pdf',
        'file_path' => 'private/attachments/doc2.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
    ]);

    return $archive;
}

it('calculates attachment count to delete in livewire confirmDelete', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    $archive = createArchiveWithAttachmentForWarningTest($user->id);

    Livewire::actingAs($user)
        ->test(ArchiveTable::class)
        ->call('confirmDelete', $archive->id)
        ->assertSet('attachmentCountToDelete', 2)
        ->assertDispatched('modal-show', name: 'delete-reason-modal'); // Flux modals usually listen to window events or are handled by Flux directly, but we can just check the property
});

it('eager loads soft-deleted attachment count in trash page', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    $archive = createArchiveWithAttachmentForWarningTest($user->id);

    // Soft delete the archive (which cascades to attachments)
    $archive->delete();

    $response = $this->actingAs($user)->get(route('archives.trash'));

    $response->assertStatus(200);
    // Assert that the page renders the attachment count (2) for this archive
    $response->assertSeeText('Arsip ini memiliki 2 lampiran pendukung di tempat sampah.');
});
