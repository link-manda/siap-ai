<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('can resolve spatie roles on User model', function () {
    $user = User::factory()->create();
    
    // Create a spatie role
    $role = Role::create(['name' => 'Administrator']);
    
    // Assign role
    $user->assignRole('Administrator');
    
    // Assert the user has the role
    expect($user->hasRole('Administrator'))->toBeTrue();
    expect($user->hasRole('Pimpinan'))->toBeFalse();
});
