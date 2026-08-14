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

class SuperuserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_the_administration_panel(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_and_legacy_administration_permission_are_forbidden(): void
    {
        $regularUser = User::factory()->create();
        $this->authenticated($regularUser)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $legacyAdministrator = User::factory()->create();
        $this->authenticated($legacyAdministrator, ['nube_administracion_administrar'])
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $userWithIncorrectRole = User::factory()->create();
        $incorrectRole = Role::factory()->create([
            'name' => 'superusuario',
            'display_name' => 'Superusuario',
        ]);
        $userWithIncorrectRole->roles()->attach($incorrectRole, ['created_at' => now()]);

        $this->authenticated($userWithIncorrectRole)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_superuser_can_open_every_administrative_section_and_return_to_personal_cloud(): void
    {
        $superuser = User::factory()->create([
            'name' => 'Sofía',
            'last_name' => 'Administradora',
        ]);
        $this->assignSuperuserRole($superuser);

        $routes = [
            'admin.dashboard' => 'Administración de Nube Municipal',
            'admin.files' => 'Inventario de archivos',
            'admin.departments' => 'Departamentos sincronizados',
            'admin.users' => 'Usuarios sincronizados',
            'admin.trash' => 'Papelera global',
            'admin.audit' => 'Bitácora del sistema',
            'admin.settings' => 'Configuración operativa',
        ];

        foreach ($routes as $route => $content) {
            $this->authenticated($superuser)
                ->get(route($route))
                ->assertOk()
                ->assertSee($content)
                ->assertSee('Volver a mi nube')
                ->assertSee(route('dashboard'), false);
        }
    }

    public function test_panel_shows_real_summary_and_resource_data_without_physical_paths(): void
    {
        $department = Department::factory()->create(['name' => 'Tecnologías']);
        $superuser = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Sofía',
        ]);
        $owner = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Francisco',
            'last_name' => 'López',
        ]);
        $this->assignSuperuserRole($superuser);

        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'name' => 'Contratos',
            'visibility' => FileVisibility::Collaborative,
        ]);
        $file = File::factory()->create([
            'folder_id' => $folder->id,
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Contrato 2026.pdf',
            'path' => 'departamentos/privado/no-exponer.pdf',
            'visibility' => FileVisibility::Collaborative,
        ]);
        AuditLog::query()->create([
            'user_id' => $owner->id,
            'action' => 'file.uploaded',
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('file.uploaded')
            ->assertSee('Francisco López');

        $this->authenticated($superuser)
            ->get(route('admin.files'))
            ->assertOk()
            ->assertSee('Contrato 2026.pdf')
            ->assertSee('Tecnologías')
            ->assertDontSee('departamentos/privado/no-exponer.pdf');
    }

    public function test_personal_navigation_only_exposes_administration_to_superusers(): void
    {
        $regularUser = User::factory()->create();

        $this->authenticated($regularUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.dashboard'), false);

        $superuser = User::factory()->create();
        $this->assignSuperuserRole($superuser);

        $this->authenticated($superuser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee(route('admin.dashboard'), false);
    }

    private function assignSuperuserRole(User $user): void
    {
        $role = Role::factory()->create([
            'name' => 'superuser',
            'display_name' => 'Superusuario',
        ]);

        $user->roles()->attach($role, ['created_at' => now()]);
        $user->unsetRelation('roles');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions = []): static
    {
        $permissionNames = array_values(array_unique([
            'nube_inicio_ver',
            ...$permissions,
        ]));

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );
            $user->permissions()->syncWithoutDetaching([
                $permission->id => ['created_at' => now()],
            ]);
        }

        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $permissionNames,
            'access.roles' => $user->roles()->pluck('name')->all(),
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
