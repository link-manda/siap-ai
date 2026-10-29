<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'view-archives',
            'manage-users',
            'access-level-4-archives',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions

        // Administrator: can view archives, but CANNOT access level 4
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->syncPermissions(['view-archives']);

        // Pimpinan: can do everything, including accessing level 4 archives
        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan']);
        $pimpinanRole->syncPermissions(['view-archives', 'manage-users', 'access-level-4-archives']);
    }
}
