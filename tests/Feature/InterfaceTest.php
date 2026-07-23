<?php

namespace Tests\Feature;

use Tests\TestCase;

class InterfaceTest extends TestCase
{
    public function test_dashboard_renders_the_initial_application_shell(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nube Municipal')
            ->assertSee('Buenos días, Carlos')
            ->assertSee('Archivos Recientes')
            ->assertSee('data-theme-toggle', false)
            ->assertDontSee('Almacenamiento')
            ->assertDontSee('Espacio utilizado')
            ->assertDontSee('12.4 GB / 50 GB utilizados');
    }

    public function test_login_renders_the_approved_form_structure(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Iniciar Sesión')
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Correo electrónico')
            ->assertSee('Mantener mi sesión iniciada');
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);
    }
}
