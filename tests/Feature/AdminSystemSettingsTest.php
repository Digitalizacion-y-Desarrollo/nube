<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.access', [
            'url' => 'https://access.example.test',
            'system_key' => 'test-system-key',
            'timeout' => 5,
            'session_check_interval' => 300,
        ]);
    }

    public function test_settings_report_upload_limits_disk_storage_and_retention(): void
    {
        $superuser = $this->superuser();
        $active = File::factory()->create(['size_bytes' => 4096]);
        $trashed = File::factory()->create(['size_bytes' => 1024]);
        $trashed->delete();

        $response = $this->authenticated($superuser)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Configuración operativa')
            ->assertSee('200.0 MB')
            ->assertSee('upload_max_filesize')
            ->assertSee('storage/app/nube')
            ->assertSee('5.0 KB')
            ->assertSee('4.0 KB activo')
            ->assertSee('1.0 KB en papelera')
            ->assertSee('30 días')
            ->assertSee('files:purge-trash')
            ->assertSee('No existe enlace público de almacenamiento');

        // Las extensiones se escriben en minúscula; la mayúscula es sólo visual.
        foreach (['pdf', 'docx', 'xlsx', 'zip'] as $extension) {
            $response->assertSee(">{$extension}<", false);
        }

        $this->assertNotNull($active->id);
    }

    public function test_settings_never_reveal_secrets_or_physical_file_paths(): void
    {
        $superuser = $this->superuser();
        File::factory()->create([
            'display_name' => 'Contrato.pdf',
            'path' => 'departamentos/ruta-que-no-debe-mostrarse.pdf',
            'stored_name' => 'nombre-fisico-oculto.pdf',
            'checksum' => str_repeat('a', 64),
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('access.example.test')
            ->assertSee('Configurada')
            ->assertDontSee('test-system-key')
            ->assertDontSee('test-token')
            ->assertDontSee('departamentos/ruta-que-no-debe-mostrarse.pdf')
            ->assertDontSee('nombre-fisico-oculto.pdf')
            ->assertDontSee(str_repeat('a', 64))
            ->assertDontSee(base_path(), false);
    }

    public function test_settings_do_not_call_the_access_api_when_the_panel_loads(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $superuser = $this->superuser();

        $this->authenticated($superuser)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Sin comprobar');

        Http::assertNothingSent();
    }

    public function test_live_check_reports_a_successful_connection_and_is_audited(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://access.example.test/api/auth/me*' => Http::response([
                'message' => 'Sesión válida.',
                'data' => [
                    'user' => [
                        'id' => 25,
                        'name' => 'Ana',
                        'email' => 'ana@example.test',
                    ],
                    'roles' => ['superuser'],
                    'permissions' => ['nube_inicio_ver'],
                ],
            ]),
        ]);

        $superuser = $this->superuser();

        $this->authenticated($superuser)
            ->from(route('admin.settings'))
            ->post(route('admin.settings.check'))
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('status');

        $this->authenticated($superuser)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('En línea');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.settings.connection_checked',
        ]);
    }

    public function test_live_check_reports_an_unavailable_api_without_leaking_details(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://access.example.test/api/auth/me*' => Http::response(
                ['message' => 'Servicio no disponible.'],
                500,
            ),
        ]);

        $superuser = $this->superuser();

        $this->authenticated($superuser)
            ->from(route('admin.settings'))
            ->post(route('admin.settings.check'))
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('admin_settings_error');

        $this->authenticated($superuser)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Con incidencias')
            ->assertDontSee('Servicio no disponible.');
    }

    public function test_settings_are_reserved_for_superusers_and_expose_no_configuration_writes(): void
    {
        $superuser = $this->superuser();
        $regularUser = User::factory()->create();

        $this->get(route('admin.settings'))->assertRedirect(route('login'));

        $this->authenticated($regularUser, superuser: false)
            ->get(route('admin.settings'))
            ->assertForbidden();

        $this->authenticated($regularUser, superuser: false)
            ->post(route('admin.settings.check'))
            ->assertForbidden();

        $this->authenticated($superuser)
            ->patch(route('admin.settings'), ['trash_retention_days' => 1])
            ->assertMethodNotAllowed();

        $this->authenticated($superuser)
            ->delete(route('admin.settings'))
            ->assertMethodNotAllowed();

        $this->assertSame(30, (int) config('nube.trash_retention_days'));
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

    private function authenticated(User $user, bool $superuser = true): static
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
            'access.roles' => $superuser ? ['superuser'] : [],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
