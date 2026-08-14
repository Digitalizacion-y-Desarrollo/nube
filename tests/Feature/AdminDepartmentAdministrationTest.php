<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_inventory_reports_users_resources_storage_and_sync_state(): void
    {
        $superuser = $this->superuser();
        $department = Department::factory()->create([
            'name' => 'Tecnología',
            'abbreviation' => 'TI',
            'active' => true,
            'last_synced_at' => '2026-08-11 12:30:00',
        ]);
        User::factory()->count(2)->create([
            'department_id' => $department->id,
            'active' => true,
        ]);
        User::factory()->create([
            'department_id' => $department->id,
            'active' => false,
        ]);
        File::factory()->create([
            'department_id' => $department->id,
            'size_bytes' => 2048,
        ]);
        $trashed = File::factory()->create([
            'department_id' => $department->id,
            'size_bytes' => 1024,
        ]);
        $trashed->delete();
        Folder::factory()->create(['department_id' => $department->id]);

        $this->authenticated($superuser)
            ->get(route('admin.departments'))
            ->assertOk()
            ->assertSee('Tecnología')
            ->assertSee('TI')
            ->assertSee('2.0 KB')
            ->assertSee('1.0 KB en papelera')
            ->assertSee('11/08/2026 12:30')
            ->assertSee(route('admin.departments.show', $department), false);
    }

    public function test_department_inventory_can_filter_by_name_and_status(): void
    {
        $superuser = $this->superuser();
        Department::factory()->create(['name' => 'Tecnología', 'active' => true]);
        Department::factory()->create(['name' => 'Tesorería', 'active' => false]);

        $this->authenticated($superuser)
            ->get(route('admin.departments', [
                'q' => 'Tecno',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee('Tecnología')
            ->assertDontSee('Tesorería');

        $this->authenticated($superuser)
            ->get(route('admin.departments', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Tesorería')
            ->assertDontSee('Tecnología');
    }

    public function test_department_detail_shows_related_users_shared_files_and_activity_without_sensitive_data(): void
    {
        $superuser = $this->superuser();
        $department = Department::factory()->create(['name' => 'Obras Públicas']);
        $otherDepartment = Department::factory()->create(['name' => 'Finanzas']);
        $owner = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
        ]);
        $otherUser = User::factory()->create([
            'department_id' => $otherDepartment->id,
            'email' => 'otro@example.test',
        ]);
        $collaborative = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Proyecto colaborativo.pdf',
            'visibility' => FileVisibility::Collaborative,
            'path' => 'departamentos/ruta-que-no-debe-mostrarse.pdf',
        ]);
        File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Manual público.pdf',
            'visibility' => FileVisibility::Public,
        ]);
        File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Documento privado.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        AuditLog::query()->create([
            'user_id' => $owner->id,
            'action' => 'file.downloaded',
            'resource_type' => File::class,
            'resource_id' => $collaborative->id,
            'created_at' => now(),
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Obras Públicas')
            ->assertSee('Ana Pérez')
            ->assertSee('Proyecto colaborativo.pdf')
            ->assertSee('Manual público.pdf')
            ->assertDontSee('Documento privado.pdf')
            ->assertSee('file.downloaded')
            ->assertDontSee('departamentos/ruta-que-no-debe-mostrarse.pdf')
            ->assertSee(route('admin.users', ['department_id' => $department->id]), false)
            ->assertSee(route('admin.files', ['department_id' => $department->id]), false)
            ->assertSee(route('admin.files.show', $collaborative), false);

        $this->authenticated($superuser)
            ->get(route('admin.users', ['department_id' => $department->id]))
            ->assertOk()
            ->assertSee('ana@example.test')
            ->assertDontSee($otherUser->email);
    }

    public function test_departments_remain_read_only_locally(): void
    {
        $superuser = $this->superuser();
        $department = Department::factory()->create(['name' => 'Solo consulta']);

        $this->authenticated($superuser)
            ->post('/admin/departamentos', ['name' => 'No permitido'])
            ->assertMethodNotAllowed();

        $this->authenticated($superuser)
            ->patch(route('admin.departments.show', $department), [
                'name' => 'No permitido',
            ])
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Solo consulta',
        ]);
    }

    private function superuser(): User
    {
        $user = User::factory()->create();
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
            'access.roles' => ['superuser'],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
