<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Antes de este cambio, el panel de filtros del explorador (10 campos) y de
 * cada listado administrativo se mostraba siempre expandido, obligando a
 * desplazarse en móvil antes de ver cualquier contenido. `<details>` nativo
 * lo colapsa por defecto; app.js lo abre en escritorio para no alterar el
 * comportamiento que ya existía ahí.
 */
class CollapsibleFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_explorer_filters_are_collapsible_and_keep_their_tour_anchor(): void
    {
        $this->authenticated(['nube_mis_archivos_ver'])
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('data-collapsible-filters', false)
            ->assertSee('data-collapsible-chevron', false)
            ->assertSee('data-tour="explorer-filters"', false)
            ->assertSee('Buscar y filtrar');
    }

    public function test_admin_listings_use_collapsible_filters_instead_of_an_always_open_form(): void
    {
        $superuser = $this->superuser();

        foreach (['admin.files', 'admin.trash', 'admin.users', 'admin.departments', 'admin.audit'] as $route) {
            $this->authenticated([], $superuser)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-collapsible-filters', false);
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(array $permissions, ?User $user = null): static
    {
        $user ??= User::factory()->create();
        $permissionNames = array_values(array_unique(['nube_inicio_ver', ...$permissions]));

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );
            $user->permissions()->syncWithoutDetaching([$permission->id => ['created_at' => now()]]);
        }

        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $permissionNames,
            'access.roles' => $user->hasRole('superuser') ? ['superuser'] : [],
            'access.validated_at' => now()->timestamp,
        ]);
    }

    private function superuser(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superuser', 'display_name' => 'Superusuario']);
        $user->roles()->attach($role, ['created_at' => now()]);
        $user->unsetRelation('roles');

        return $user;
    }
}
