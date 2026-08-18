<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica los anclajes de los recorridos guiados de driver.js: el menú del
 * botón #help y los atributos `data-tour` que cada página expone. El
 * contenido de los pasos vive en JavaScript; aquí sólo se protege el
 * contrato con el DOM del que ese JavaScript depende.
 */
class GuidedTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_the_help_menu_and_its_tour_anchors(): void
    {
        $this->authenticated()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-help-page="dashboard"', false)
            ->assertSee('id="help"', false)
            ->assertSee('data-help-menu-panel', false)
            ->assertSee('data-help-menu-list', false)
            ->assertSee('data-tour="sidebar-nav"', false)
            ->assertSee('data-tour="quick-actions"', false)
            ->assertSee('data-tour="indicators"', false)
            ->assertSee('data-tour="recent-files"', false);
    }

    public function test_explorer_exposes_the_help_menu_and_its_tour_anchors(): void
    {
        $this->authenticated(['nube_mis_archivos_ver'])
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('data-help-page="explorer"', false)
            ->assertSee('data-tour="breadcrumbs"', false)
            ->assertSee('data-tour="explorer-summary"', false)
            ->assertSee('data-tour="explorer-filters"', false)
            ->assertSee('data-tour="explorer-items"', false);
    }

    public function test_pages_without_a_registered_tour_still_render_the_help_button(): void
    {
        // El botón de ayuda es del layout compartido; una página sin recorrido
        // definido (todavía) no debe romperse ni ocultarlo.
        $this->authenticated()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="help"', false);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(array $permissions = []): static
    {
        $user = User::factory()->create(['name' => 'Carlos']);
        $permissionNames = array_values(array_unique(['nube_inicio_ver', ...$permissions]));

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );
            $user->permissions()->attach($permission, ['created_at' => now()]);
        }

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $permissionNames,
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
