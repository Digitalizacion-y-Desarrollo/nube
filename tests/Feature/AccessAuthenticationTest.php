<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccessAuthenticationTest extends TestCase
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

        Http::preventStrayRequests();
    }

    public function test_valid_user_is_synchronized_and_authenticated_with_effective_permissions(): void
    {
        Http::fake([
            'https://access.example.test/api/auth/login' => Http::response(
                $this->loginPayload(),
            ),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'ana@example.test',
            'password' => 'test-password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('access.token', 'server-token')
            ->assertSessionHas('access.permissions', [
                'nube_inicio_ver',
                'nube_mis_archivos_ver',
            ]);

        $user = User::query()->where('external_id', '25')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Tecnologías de la Información', $user->department?->name);
        $this->assertSame('Dirección General', $user->department?->parent?->name);
        $this->assertSame(['nube_colaborador'], $user->roles()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            ['nube_inicio_ver', 'nube_mis_archivos_ver'],
            $user->permissions()->pluck('name')->all(),
        );
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_permissions_are_replaced_on_the_next_successful_sync(): void
    {
        Http::fakeSequence()
            ->push($this->loginPayload(), 200)
            ->push(['message' => 'Sesión cerrada.'], 200)
            ->push($this->loginPayload([
                'permissions' => ['nube_inicio_ver', 'nube_publicos_ver'],
            ]), 200);

        $this->post(route('login.store'), [
            'email' => 'ana@example.test',
            'password' => 'test-password',
        ])->assertRedirect(route('dashboard'));

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'ana@example.test',
            'password' => 'test-password',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('external_id', '25')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['nube_inicio_ver', 'nube_publicos_ver'],
            $user->permissions()->pluck('name')->all(),
        );
        $this->assertDatabaseHas('permissions', ['name' => 'nube_mis_archivos_ver']);
    }

    public function test_user_without_entry_permission_is_not_authenticated(): void
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://access.example.test/api/auth/logout') {
                return Http::response(['message' => 'Sesión cerrada.']);
            }

            return Http::response($this->loginPayload([
                'permissions' => ['nube_publicos_ver'],
            ]));
        });

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'ana@example.test',
            'password' => 'test-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('auth_error_type', 'permission');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_credentials_and_unavailable_api_have_distinct_states(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Credenciales inválidas.'], 401)]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'ana@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHas('auth_error_type', 'credentials');

        Http::fake(['*' => Http::failedConnection()]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'ana@example.test',
            'password' => 'test-password',
        ])->assertSessionHas('auth_error_type', 'connection');
    }

    public function test_expired_token_invalidates_the_local_session(): void
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response(['message' => 'Token expirado.'], 401)]);

        $this->actingAs($user)
            ->withSession([
                'access.token' => 'expired-token',
                'access.permissions' => ['nube_inicio_ver'],
                'access.validated_at' => now()->subHour()->timestamp,
            ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');

        $this->assertGuest();
    }

    public function test_logout_revokes_remote_token_and_clears_local_session(): void
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response(['message' => 'Sesión cerrada.'])]);

        $this->actingAs($user)
            ->withSession([
                'access.token' => 'logout-token',
                'access.permissions' => ['nube_inicio_ver'],
                'access.validated_at' => now()->timestamp,
            ])
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('action', 'auth.logout')->exists());
        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://access.example.test/api/auth/logout'
            && $request->hasHeader('Authorization', 'Bearer logout-token'));
    }

    public function test_password_recovery_uses_the_local_login_as_return_url(): void
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Se envió el enlace para restablecer la contraseña.',
            ]),
        ]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ana@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://access.example.test/api/auth/forgot-password'
            && $request['email'] === 'ana@example.test'
            && $request['login_url'] === route('login'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function loginPayload(array $overrides = []): array
    {
        $data = array_replace_recursive([
            'access_token' => 'server-token',
            'token_type' => 'Bearer',
            'user' => [
                'id' => 25,
                'name' => 'Ana',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'López',
                'email' => 'ana@example.test',
                'departamento' => [
                    'departamento_padre' => [
                        'id' => 1,
                        'nombre' => 'Dirección General',
                    ],
                    'departamento_hijo' => [
                        'id' => 2,
                        'nombre' => 'Tecnologías de la Información',
                    ],
                ],
            ],
            'system' => ['id' => 4, 'nombre' => 'Nube Empresarial'],
            'roles' => ['nube_colaborador'],
            'permissions' => [
                'nube_inicio_ver',
                'nube_mis_archivos_ver',
            ],
        ], $overrides);

        return ['message' => 'Login correcto.', 'data' => $data];
    }
}
