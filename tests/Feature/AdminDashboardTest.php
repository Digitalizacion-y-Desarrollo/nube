<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_real_active_deleted_and_storage_totals(): void
    {
        $alpha = Department::factory()->create([
            'name' => 'Dirección Alpha',
            'abbreviation' => 'ALPHA',
            'active' => true,
        ]);
        $beta = Department::factory()->create([
            'name' => 'Dirección Beta',
            'abbreviation' => 'BETA',
            'active' => false,
        ]);
        $superuser = User::factory()->create([
            'department_id' => $alpha->id,
            'name' => 'Sofía',
        ]);
        $ana = User::factory()->create([
            'department_id' => $alpha->id,
            'name' => 'Ana',
            'last_name' => 'Consumo',
            'active' => true,
        ]);
        $bruno = User::factory()->create([
            'department_id' => $beta->id,
            'name' => 'Bruno',
            'last_name' => 'Consumo',
            'active' => false,
        ]);
        $this->assignSuperuserRole($superuser);

        Folder::factory()->create([
            'owner_id' => $ana->id,
            'department_id' => $alpha->id,
            'name' => 'Activa',
        ]);
        Folder::factory()->create([
            'owner_id' => $ana->id,
            'department_id' => $alpha->id,
            'name' => 'Eliminada',
            'deleted_at' => now(),
        ]);

        $this->file($ana, $alpha, 2 * 1024 * 1024, FileVisibility::Private);
        $this->file($ana, $alpha, 1024 * 1024, FileVisibility::Public);
        $this->file(
            $ana,
            $alpha,
            4 * 1024 * 1024,
            FileVisibility::Collaborative,
            deleted: true,
        );
        $this->file($bruno, $beta, 5 * 1024 * 1024, FileVisibility::Private);

        $response = $this->authenticated($superuser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Estado del almacenamiento')
            ->assertSee('Departamentos con mayor consumo')
            ->assertSee('Usuarios con mayor consumo')
            ->assertSeeInOrder(['Dirección Alpha', 'Dirección Beta'])
            ->assertSeeInOrder(['Ana Consumo', 'Bruno Consumo']);

        $response->assertViewHas(
            'storage',
            fn (array $storage): bool => $storage['active_bytes'] === 8 * 1024 * 1024
                && $storage['deleted_bytes'] === 4 * 1024 * 1024
                && $storage['total_bytes'] === 12 * 1024 * 1024
                && $storage['active'] === '8.0 MB'
                && $storage['deleted'] === '4.0 MB'
                && $storage['total'] === '12 MB',
        );
        $response->assertViewHas('summary', function (array $summary): bool {
            $items = collect($summary)->keyBy('label');

            return $items->get('Archivos activos')['value'] === '3'
                && $items->get('Archivos activos')['hint'] === '1 archivo en papelera'
                && $items->get('Carpetas activas')['value'] === '1'
                && $items->get('Carpetas activas')['hint'] === '1 carpeta en papelera'
                && $items->get('Usuarios sincronizados')['value'] === '3'
                && $items->get('Departamentos')['value'] === '2';
        });
        $response->assertViewHas('visibility', function (array $visibility): bool {
            $items = collect($visibility)->keyBy('tone');

            return $items->get('private')['active_count'] === 2
                && $items->get('private')['deleted_count'] === 0
                && $items->get('collaborative')['active_count'] === 0
                && $items->get('collaborative')['deleted_count'] === 1
                && $items->get('public')['active_count'] === 1;
        });
        $response->assertViewHas(
            'topDepartments',
            fn ($departments): bool => $departments->pluck('name')->all() === [
                'Dirección Alpha',
                'Dirección Beta',
            ]
                && $departments->first()['active'] === '3.0 MB'
                && $departments->first()['deleted'] === '4.0 MB'
                && $departments->first()['total'] === '7.0 MB',
        );
        $response->assertViewHas(
            'topUsers',
            fn ($users): bool => $users->pluck('name')->all() === [
                'Ana Consumo',
                'Bruno Consumo',
            ]
                && $users->first()['total'] === '7.0 MB',
        );
    }

    public function test_dashboard_has_zero_and_empty_states_without_files(): void
    {
        $superuser = User::factory()->create();
        $this->assignSuperuserRole($superuser);

        $this->authenticated($superuser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('0 B')
            ->assertSee('Sin elementos en papelera')
            ->assertSee('Aún no hay consumo por departamento.')
            ->assertSee('Aún no hay consumo por usuario.')
            ->assertViewHas(
                'storage',
                fn (array $storage): bool => $storage['active_bytes'] === 0
                    && $storage['deleted_bytes'] === 0
                    && $storage['total_bytes'] === 0,
            )
            ->assertViewHas('topDepartments', fn ($items): bool => $items->isEmpty())
            ->assertViewHas('topUsers', fn ($items): bool => $items->isEmpty());
    }

    private function file(
        User $owner,
        Department $department,
        int $size,
        FileVisibility $visibility,
        bool $deleted = false,
    ): File {
        return File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'size_bytes' => $size,
            'visibility' => $visibility,
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    private function assignSuperuserRole(User $user): void
    {
        $role = Role::factory()->create([
            'name' => 'superuser',
            'display_name' => 'Superusuario',
        ]);

        $user->roles()->attach($role, ['created_at' => now()]);
    }

    private function authenticated(User $user): static
    {
        $permission = Permission::query()->create([
            'name' => 'nube_inicio_ver',
            'display_name' => 'Ver inicio',
        ]);
        $user->permissions()->attach($permission, ['created_at' => now()]);

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => ['nube_inicio_ver'],
            'access.roles' => ['superuser'],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
