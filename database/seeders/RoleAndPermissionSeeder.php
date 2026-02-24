<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'clubs.create', 'clubs.update', 'clubs.delete', 'clubs.view-any',
            'courts.create', 'courts.update', 'courts.delete',
            'games.view-any', 'games.delete',
            'players.view-any', 'players.update', 'players.delete',
            'users.manage', 'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $clubManager = Role::firstOrCreate(['name' => 'club_manager']);
        $clubManager->givePermissionTo([
            'clubs.create', 'clubs.update',
            'courts.create', 'courts.update',
        ]);

        Role::firstOrCreate(['name' => 'player']);
    }
}
