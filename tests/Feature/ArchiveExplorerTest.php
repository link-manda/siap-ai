<?php

use App\Livewire\ArchiveExplorer;
use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Livewire\Livewire;

it('renders the archive explorer component', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ArchiveExplorer::class)
        ->assertStatus(200)
        ->assertSee('Document Tree');
});

it('calculates smart folders based on category and classification', function () {
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');

    $category = Category::firstOrCreate(['name' => 'Kesehatan']);
    $classification = Classification::firstOrCreate(['name' => 'Biasa']);

    Archive::create([
        'title' => 'Test Smart Folder',
        'file_path' => 'private/archives/test.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploaded_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ArchiveExplorer::class)
        ->set('groupingMode', 'category_classification')
        ->assertSee('Kesehatan')
        ->assertSee('Biasa');
});

it('dispatches filter event when node is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ArchiveExplorer::class)
        ->call('selectNode', 'node_1', ['categoryId' => 1])
        ->assertDispatched('explorer-filter-updated', ['categoryId' => 1]);
});
