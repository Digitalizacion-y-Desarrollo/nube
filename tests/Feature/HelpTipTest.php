<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica la ayuda puntual (x-ui.help-tip) en los cuatro controles señalados
 * como no obvios: el alcance de colaboración, la confirmación por nombre
 * exacto en la papelera administrativa, la comprobación del API de Accesos y
 * el filtro de origen de la auditoría. A diferencia de los recorridos de
 * driver.js, este componente no depende de JavaScript para existir en el
 * HTML: sólo el mostrar/ocultar el panel es JS.
 */
class HelpTipTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaboration_scope_explains_department_versus_selected_people(): void
    {
        $user = User::factory()->create();
        $this->grant($user, ['nube_inicio_ver', 'nube_mis_archivos_ver', 'nube.archivos.subir']);

        $this->actingAs($user)
            ->withSession($this->accessSession($user))
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('data-help-tip', false)
            ->assertSee('Qué significa cada alcance de colaboración')
            ->assertSee('Personas específicas')
            ->assertSee('ver, descargar, renombrar, mover, eliminar');
    }

    public function test_trash_purge_confirmation_explains_why_the_exact_name_is_required(): void
    {
        $superuser = $this->superuser(withAdministrationPermission: true);
        $file = File::factory()->create(['display_name' => 'Contrato.pdf']);
        $file->delete();

        $this->authenticated($superuser)
            ->get(route('admin.trash'))
            ->assertOk()
            ->assertSee('data-help-tip', false)
            ->assertSee('Por qué se pide escribir el nombre')
            ->assertSee('evita eliminar el elemento');
    }

    public function test_settings_explains_the_difference_between_last_validation_and_live_check(): void
    {
        $superuser = $this->superuser();

        $this->authenticated($superuser)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('data-help-tip', false)
            ->assertSee('Diferencia entre esta fecha y la comprobación en vivo')
            ->assertSee('Comprobar conexión ahora');
    }

    public function test_audit_scope_filter_explains_administrative_versus_user_events(): void
    {
        $superuser = $this->superuser();

        $this->authenticated($superuser)
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('data-help-tip', false)
            ->assertSee('Cómo se distingue el origen de un evento')
            ->assertSee('Operación normal de la nube personal');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grant(User $user, array $permissions): void
    {
        foreach (array_unique($permissions) as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );

            $user->permissions()->syncWithoutDetaching([
                $permission->id => ['created_at' => now()],
            ]);
        }

        $user->unsetRelation('permissions');
    }

    /**
     * @return array<string, mixed>
     */
    private function accessSession(User $user): array
    {
        return [
            'access.token' => 'test-token',
            'access.permissions' => $user->permissions()->pluck('name')->all(),
            'access.validated_at' => now()->timestamp,
        ];
    }

    private function superuser(bool $withAdministrationPermission = false): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'name' => 'superuser',
            'display_name' => 'Superusuario',
        ]);
        $user->roles()->attach($role, ['created_at' => now()]);

        if ($withAdministrationPermission) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => 'nube_administracion_administrar'],
                ['display_name' => 'Administrar nube'],
            );
            $user->permissions()->attach($permission, ['created_at' => now()]);
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

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
            'access.permissions' => $user->permissions()->pluck('name')->all(),
            'access.roles' => ['superuser'],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
