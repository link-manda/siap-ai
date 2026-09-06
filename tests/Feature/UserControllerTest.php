<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('allows pimpinan to view users index', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permission = Permission::firstOrCreate(['name' => 'manage-users']);
    $role = Role::firstOrCreate(['name' => 'Pimpinan']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole('Pimpinan');

    $response = $this->actingAs($user)->get(route('users.index'));
    $response->assertStatus(200);
});
