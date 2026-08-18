<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Antes de estas páginas, un 403 o 404 mostraba la vista genérica sin marca
 * de Laravel. Las pruebas cubren tanto la apariencia como un riesgo real
 * detectado durante la implementación: la vista 503 no puede depender de la
 * sesión, porque el modo mantenimiento intercepta la petición antes de que
 * la sesión arranque.
 */
class CustomErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_forbidden_admin_route_shows_the_branded_403_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'access.token' => 'test-token',
                'access.permissions' => ['nube_inicio_ver'],
                'access.roles' => [],
                'access.validated_at' => now()->timestamp,
            ])
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Error 403')
            ->assertSee('No tienes acceso a esta página')
            ->assertSee('Volver a mi nube')
            ->assertDontSee('Whoops', false);
    }

    public function test_an_unknown_folder_identifier_shows_the_branded_404_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'access.token' => 'test-token',
                'access.permissions' => ['nube_mis_archivos_ver'],
                'access.validated_at' => now()->timestamp,
            ])
            ->get('/mis-archivos/00000000-0000-0000-0000-000000000000')
            ->assertNotFound()
            ->assertSee('Error 404')
            ->assertSee('No encontramos lo que buscabas');
    }

    public function test_a_guest_hitting_a_missing_route_sees_the_guest_call_to_action(): void
    {
        $this->get('/esta-ruta-no-existe')
            ->assertNotFound()
            ->assertSee('Error 404')
            ->assertSee('Ir a iniciar sesión')
            ->assertDontSee('Volver a mi nube');
    }

    public function test_every_custom_error_view_renders_without_an_authenticated_session(): void
    {
        // Simula el punto más frágil: ninguna de estas vistas debe asumir que
        // hay un usuario autenticado ni que la sesión ya arrancó.
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view("errors.{$code}")->render();

            $this->assertStringContainsString("Error {$code}", $html);
        }
    }

    public function test_the_maintenance_page_does_not_touch_the_session_guard(): void
    {
        // Reproduce exactamente el riesgo detectado: renderizar 503 sin que
        // exista sesión en la petición no debe lanzar
        // "Session store not set on request".
        $html = view('errors.503')->render();

        $this->assertStringContainsString('En mantenimiento', $html);
        $this->assertStringNotContainsString('Volver a mi nube', $html);
        $this->assertStringNotContainsString('Ir a iniciar sesión', $html);
    }
}
