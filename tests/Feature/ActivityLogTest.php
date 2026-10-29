<?php

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs archive creation to spatie activity log', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Kesehatan']);
    $classification = Classification::create(['name' => 'Biasa']);

    $this->actingAs($user);

    $archive = Archive::create([
        'title' => 'Test Log Archive',
        'file_path' => 'private/archives/test.pdf',
        'file_type' => 'pdf',
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'uploader_id' => $user->id,
    ]);

    // Check Spatie Activity Log
    $activity = Activity::where('subject_type', Archive::class)
        ->where('subject_id', $archive->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toEqual('created');
});
