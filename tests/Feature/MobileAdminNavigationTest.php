<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Antes de este cambio, el acceso al panel administrativo sólo aparecía en
 * la barra lateral de escritorio y en el menú hamburguesa móvil; la barra
 * inferior fija de móvil —el patrón de navegación principal en ese
 * tamaño de pantalla— no lo incluía. `>Admin<` (con los signos de tag) evita
 * confundirlo con la palabra completa "Administración" que ya usa la barra
 * lateral.
 */
class MobileAdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_bottom_navigation_bar_offers_a_shortcut_to_the_admin_panel_for_superusers(): void
    {
        $superuser = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superuser', 'display_name' => 'Superusuario']);
        $superuser->roles()->attach($role, ['created_at' => now()]);
        $superuser->unsetRelation('roles');

        $this->authenticated($superuser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Admin<', false)
            ->assertSee(route('admin.dashboard'), false);
    }

    public function test_the_bottom_navigation_bar_hides_the_admin_shortcut_from_regular_users(): void
    {
        $regularUser = User::factory()->create();

        $this->authenticated($regularUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>Admin<', false);
    }

    private function authenticated(User $user): static
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => 'nube_inicio_ver'],
            ['display_name' => 'nube_inicio_ver'],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id => ['created_at' => now()]]);
        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => ['nube_inicio_ver'],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
