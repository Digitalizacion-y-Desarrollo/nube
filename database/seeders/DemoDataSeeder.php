<?php

namespace Database\Seeders;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::query()->firstOrCreate(
            ['external_id' => 'demo-department-rh'],
            [
                'name' => 'Recursos Humanos',
                'abbreviation' => 'RH',
                'active' => true,
                'last_synced_at' => now(),
            ],
        );

        $user = User::query()->firstOrCreate(
            ['external_id' => 'demo-user-carlos'],
            [
                'department_id' => $department->id,
                'name' => 'Carlos',
                'last_name' => 'Martínez',
                'email' => 'carlos.martinez@example.test',
                'active' => true,
                'last_login_at' => now(),
                'last_synced_at' => now(),
            ],
        );

        $role = Role::query()->firstOrCreate(
            ['name' => 'nube_colaborador'],
            ['display_name' => 'Colaborador de Nube'],
        );

        $user->roles()->syncWithoutDetaching([
            $role->id => ['created_at' => now()],
        ]);

        $permissionIds = Permission::query()->pluck('id');
        $user->permissions()->syncWithoutDetaching(
            $permissionIds->mapWithKeys(
                fn (int $id): array => [$id => ['created_at' => now()]]
            )->all()
        );

        Folder::query()->firstOrCreate(
            [
                'parent_id' => null,
                'owner_id' => $user->id,
                'name' => 'Documentos',
                'deleted_at' => null,
            ],
            [
                'department_id' => $department->id,
                'visibility' => FileVisibility::Private,
                'path_cache' => '/Documentos',
            ],
        );
    }
}
