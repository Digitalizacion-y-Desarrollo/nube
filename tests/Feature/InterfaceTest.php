<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_the_initial_application_shell_for_an_authorized_session(): void
    {
        $user = User::factory()->create(['name' => 'Carlos']);
        $permission = Permission::factory()->create(['name' => 'nube_inicio_ver']);
        $user->permissions()->attach($permission, ['created_at' => now()]);

        $this->actingAs($user)
            ->withSession([
                'access.token' => 'test-token',
                'access.permissions' => ['nube_inicio_ver'],
                'access.validated_at' => now()->timestamp,
            ])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nube Municipal')
            ->assertSee('Buenos días, Carlos')
            ->assertSee('Archivos Recientes')
            ->assertSee('data-theme-toggle', false);
    }

    public function test_login_renders_the_approved_figma_form_structure(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Nube Empresarial')
            ->assertSee('Iniciar Sesión')
            ->assertSee('Correo electrónico')
            ->assertSee('Mantener mi sesión iniciada')
            ->assertSee('¿Olvidaste tu contraseña?');
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['email', 'password']);
    }
}
