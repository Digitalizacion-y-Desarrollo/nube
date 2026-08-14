<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSupervisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_inventory_reports_department_roles_permissions_storage_and_session_data(): void
    {
        $superuser = $this->superuser();
        $department = Department::factory()->create(['name' => 'Obras Públicas']);
        $role = Role::factory()->create([
            'name' => 'admin_area',
            'display_name' => 'Administrador de área',
        ]);
        $permission = Permission::factory()->create([
            'name' => 'nube_archivos_descargar',
            'display_name' => 'Descargar archivos',
        ]);
        $listedUser = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
            'active' => true,
            'last_login_at' => '2026-08-10 09:15:00',
            'last_synced_at' => '2026-08-11 12:30:00',
        ]);
        $listedUser->roles()->attach($role, ['created_at' => now()]);
        $listedUser->permissions()->attach($permission, ['created_at' => now()]);

        File::factory()->create([
            'owner_id' => $listedUser->id,
            'department_id' => $department->id,
            'size_bytes' => 4096,
        ]);
        $trashed = File::factory()->create([
            'owner_id' => $listedUser->id,
            'department_id' => $department->id,
            'size_bytes' => 1024,
        ]);
        $trashed->delete();

        $this->authenticated($superuser)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee('Obras Públicas')
            ->assertSee('Administrador de área')
            ->assertSee('4.0 KB')
            ->assertSee('1.0 KB en papelera')
            ->assertSee('10/08/2026 09:15')
            ->assertSee(route('admin.users.show', $listedUser), false);
    }

    public function test_user_inventory_can_filter_by_search_department_role_and_status(): void
    {
        $superuser = $this->superuser();
        $technology = Department::factory()->create(['name' => 'Tecnología']);
        $treasury = Department::factory()->create(['name' => 'Tesorería']);
        $role = Role::factory()->create(['name' => 'admin_area', 'display_name' => 'Administrador de área']);

        $active = User::factory()->create([
            'department_id' => $technology->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
            'active' => true,
        ]);
        $active->roles()->attach($role, ['created_at' => now()]);

        User::factory()->create([
            'department_id' => $treasury->id,
            'name' => 'Bruno',
            'last_name' => 'López',
            'email' => 'bruno@example.test',
            'active' => false,
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.users', ['q' => 'ana@example']))
            ->assertOk()
            ->assertSee('ana@example.test')
            ->assertDontSee('bruno@example.test');

        $this->authenticated($superuser)
            ->get(route('admin.users', ['department_id' => $treasury->id]))
            ->assertOk()
            ->assertSee('bruno@example.test')
            ->assertDontSee('ana@example.test');

        $this->authenticated($superuser)
            ->get(route('admin.users', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('bruno@example.test')
            ->assertDontSee('ana@example.test');

        $this->authenticated($superuser)
            ->get(route('admin.users', ['role' => 'admin_area']))
            ->assertOk()
            ->assertSee('ana@example.test')
            ->assertDontSee('bruno@example.test');
    }

    public function test_user_detail_shows_permissions_files_and_activity_without_sensitive_data(): void
    {
        $superuser = $this->superuser();
        $department = Department::factory()->create(['name' => 'Obras Públicas']);
        $listedUser = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
            'last_login_at' => '2026-08-10 09:15:00',
            'last_synced_at' => '2026-08-11 12:30:00',
        ]);
        $listedUser->permissions()->attach(
            Permission::factory()->create([
                'name' => 'nube_archivos_descargar',
                'display_name' => 'Descargar archivos',
            ]),
            ['created_at' => now()],
        );

        $activeFile = File::factory()->create([
            'owner_id' => $listedUser->id,
            'department_id' => $department->id,
            'display_name' => 'Informe anual.pdf',
            'visibility' => FileVisibility::Private,
            'stored_name' => 'nombre-fisico-oculto.pdf',
            'path' => 'departamentos/ruta-que-no-debe-mostrarse.pdf',
            'checksum' => str_repeat('a', 64),
            'size_bytes' => 4096,
        ]);
        $trashedFile = File::factory()->create([
            'owner_id' => $listedUser->id,
            'department_id' => $department->id,
            'display_name' => 'Borrador eliminado.pdf',
            'size_bytes' => 1024,
        ]);
        $trashedFile->delete();

        $otherFile = File::factory()->create([
            'department_id' => $department->id,
            'display_name' => 'Archivo de otra persona.pdf',
        ]);

        AuditLog::query()->create([
            'user_id' => $listedUser->id,
            'action' => 'file.downloaded',
            'resource_type' => File::class,
            'resource_id' => $activeFile->id,
            'ip_address' => '10.0.0.5',
            'created_at' => now(),
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.users.show', $listedUser))
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee('Obras Públicas')
            ->assertSee('nube_archivos_descargar')
            ->assertSee('10/08/2026 09:15')
            ->assertSee('11/08/2026 12:30')
            ->assertSee('Informe anual.pdf')
            ->assertSee('Borrador eliminado.pdf')
            ->assertSee('En papelera')
            ->assertDontSee($otherFile->display_name)
            ->assertSee('file.downloaded')
            ->assertSee('10.0.0.5')
            ->assertDontSee('departamentos/ruta-que-no-debe-mostrarse.pdf')
            ->assertDontSee('nombre-fisico-oculto.pdf')
            ->assertDontSee(str_repeat('a', 64))
            ->assertSee(route('admin.files', ['user_id' => $listedUser->id]), false)
            ->assertSee(route('admin.departments.show', $department), false);
    }

    public function test_user_supervision_is_reserved_for_the_superuser_role(): void
    {
        $regularUser = User::factory()->create();

        $this->get(route('admin.users'))->assertRedirect(route('login'));

        $this->authenticated($regularUser)
            ->get(route('admin.users'))
            ->assertForbidden();

        $this->authenticated($regularUser)
            ->get(route('admin.users.show', $regularUser))
            ->assertForbidden();
    }

    public function test_users_remain_read_only_locally(): void
    {
        $superuser = $this->superuser();
        $listedUser = User::factory()->create([
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'active' => true,
        ]);

        $this->authenticated($superuser)
            ->post('/admin/usuarios', ['name' => 'No permitido'])
            ->assertMethodNotAllowed();

        $this->authenticated($superuser)
            ->patch(route('admin.users.show', $listedUser), [
                'name' => 'No permitido',
                'active' => false,
            ])
            ->assertMethodNotAllowed();

        $this->authenticated($superuser)
            ->delete(route('admin.users.show', $listedUser))
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('users', [
            'id' => $listedUser->id,
            'name' => 'Ana',
            'active' => true,
        ]);
    }

    private function superuser(): User
    {
        $user = User::factory()->create([
            'name' => 'Supervisión',
            'last_name' => 'Central',
            'email' => 'superusuario@example.test',
        ]);
        $role = Role::factory()->create([
            'name' => 'superuser',
            'display_name' => 'Superusuario',
        ]);
        $user->roles()->attach($role, ['created_at' => now()]);
        $user->unsetRelation('roles');

        return $user;
    }

    private function authenticated(User $user): static
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => 'nube_inicio_ver'],
            ['display_name' => 'nube_inicio_ver'],
        );
        $user->permissions()->syncWithoutDetaching([
            $permission->id => ['created_at' => now()],
        ]);
        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => ['nube_inicio_ver'],
            'access.roles' => $user->hasRole('superuser') ? ['superuser'] : [],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
