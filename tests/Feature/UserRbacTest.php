<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('can resolve spatie roles on User model', function () {
    $user = User::factory()->create();

    // Assert the user has the role (defaults to Administrator via Factory)
    expect($user->hasRole('Administrator'))->toBeTrue();
    expect($user->hasRole('Pimpinan'))->toBeFalse();
});
