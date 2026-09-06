<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

it('allows pimpinan to view users index', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    
    $permission = Permission::firstOrCreate(['name' => 'manage-users']);
    $role = Role::firstOrCreate(['name' => 'Pimpinan']);
    $role->givePermissionTo($permission);
    
    $user = User::factory()->create();
    $user->assignRole('Pimpinan');
    
    $response = $this->actingAs($user)->get(route('users.index'));
    $response->assertStatus(200);
});
