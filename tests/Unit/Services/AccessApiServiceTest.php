<?php

namespace Tests\Unit\Services;

use App\Services\Access\AccessApiService;
use App\Services\Access\Exceptions\AccessApiException;
use App\Services\Access\Exceptions\AccessAuthenticationException;
use App\Services\Access\Exceptions\AccessAuthorizationException;
use App\Services\Access\Exceptions\AccessConnectionException;
use App\Services\Access\Exceptions\AccessInactiveUserException;
use App\Services\Access\Exceptions\AccessNotFoundException;
use App\Services\Access\Exceptions\AccessRateLimitException;
use App\Services\Access\Exceptions\AccessServerException;
use App\Services\Access\Exceptions\AccessUnexpectedResponseException;
use App\Services\Access\Exceptions\AccessValidationException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.access', [
            'url' => 'https://access.example.test',
            'system_key' => 'test-system-key',
            'timeout' => 7,
        ]);

        Http::preventStrayRequests();
    }

    public function test_login_returns_typed_authentication_data(): void
    {
        Http::fake([
            'https://access.example.test/api/auth/login' => Http::response([
                'message' => 'Login correcto.',
                'data' => [
                    'access_token' => 'test-access-token',
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => 25,
                        'name' => 'Ana',
                        'email' => 'ana@example.test',
                    ],
                    'system' => [
                        'id' => 6,
                        'nombre' => 'Nube Municipal',
                    ],
                    'roles' => ['nube_colaborador'],
                    'permissions' => [
                        'nube_inicio_ver',
                        'nube_mis_archivos_ver',
                    ],
                ],
            ]),
        ]);

        $result = $this->service()->login(
            'ana@example.test',
            'a-test-password',
        );

        $this->assertSame('test-access-token', $result->accessToken);
        $this->assertSame('Bearer', $result->tokenType);
        $this->assertSame(25, $result->user['id']);
        $this->assertSame(['nube_colaborador'], $result->roles);
        $this->assertSame([
            'nube_inicio_ver',
            'nube_mis_archivos_ver',
        ], $result->permissions);

        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://access.example.test/api/auth/login'
            && $request->method() === 'POST'
            && $request['email'] === 'ana@example.test'
            && $request['password'] === 'a-test-password'
            && $request['system_key'] === 'test-system-key'
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_current_user_sends_bearer_token_and_system_key(): void
    {
        Http::fake([
            'https://access.example.test/api/auth/me*' => Http::response([
                'message' => 'Consulta exitosa.',
                'data' => [
                    'user' => [
                        'id' => 25,
                        'name' => 'Ana',
                        'email' => 'ana@example.test',
                    ],
                    'roles' => ['nube_colaborador', 'nube_colaborador'],
                    'permissions' => [
                        'nube_inicio_ver',
                        'nube_inicio_ver',
                        'nube_publicos_ver',
                    ],
                ],
            ]),
        ]);

        $result = $this->service()->currentUser('current-test-token');

        $this->assertNull($result->accessToken);
        $this->assertSame(['nube_colaborador'], $result->roles);
        $this->assertSame([
            'nube_inicio_ver',
            'nube_publicos_ver',
        ], $result->permissions);

        Http::assertSent(fn (Request $request): bool => str_starts_with(
            $request->url(),
            'https://access.example.test/api/auth/me?'
        )
            && str_contains($request->url(), 'system_key=test-system-key')
            && $request->hasHeader(
                'Authorization',
                'Bearer current-test-token'
            ));
    }

    public function test_logout_revokes_the_current_bearer_token(): void
    {
        Http::fake([
            'https://access.example.test/api/auth/logout' => Http::response([
                'message' => 'Sesión cerrada correctamente.',
            ]),
        ]);

        $this->service()->logout('logout-test-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://access.example.test/api/auth/logout'
            && $request->hasHeader(
                'Authorization',
                'Bearer logout-test-token'
            ));
    }

    public function test_forgot_password_sends_the_login_return_url(): void
    {
        Http::fake([
            'https://access.example.test/api/auth/forgot-password' => Http::response([
                'message' => 'Se envió el enlace para restablecer la contraseña.',
            ]),
        ]);

        $message = $this->service()->forgotPassword(
            'ana@example.test',
            'https://nube.example.test/login',
        );

        $this->assertSame(
            'Se envió el enlace para restablecer la contraseña.',
            $message,
        );
        Http::assertSent(fn (Request $request): bool => $request['email']
            === 'ana@example.test'
            && $request['login_url'] === 'https://nube.example.test/login');
    }

    public function test_integration_users_returns_a_typed_collection(): void
    {
        Http::fake([
            'https://access.example.test/api/integrations/users*' => Http::response([
                'data' => [
                    [
                        'id' => 25,
                        'name' => 'Ana',
                        'permisos' => 'nube_inicio_ver',
                    ],
                ],
            ]),
        ]);

        $result = $this->service()->integrationUsers('users-test-token');

        $this->assertCount(1, $result->items);
        $this->assertSame(25, $result->items[0]['id']);
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            'system_key=test-system-key'
        ) && $request->hasHeader(
            'Authorization',
            'Bearer users-test-token'
        ));
    }

    public function test_change_password_returns_the_api_data(): void
    {
        Http::fake([
            'https://access.example.test/api/integrations/users/25/password' => Http::response([
                'message' => 'La contraseña se actualizó correctamente.',
                'data' => [
                    'user_id' => 25,
                    'password_changed_at' => '2026-06-22T20:30:00.000000Z',
                ],
            ]),
        ]);

        $result = $this->service()->changePassword(
            'password-test-token',
            25,
            'old-test-password',
            'new-test-password',
        );

        $this->assertSame(25, $result['user_id']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request['current_password'] === 'old-test-password'
            && $request['password'] === 'new-test-password'
            && $request->hasHeader(
                'Authorization',
                'Bearer password-test-token'
            ));
    }

    public function test_departments_supports_search_and_pagination_metadata(): void
    {
        Http::fake([
            'https://access.example.test/api/departamentos*' => Http::response([
                'message' => 'Consulta exitosa',
                'data' => [
                    [
                        'id' => 2,
                        'nombre' => 'Tecnologías de la Información',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'total' => 1,
                ],
            ]),
        ]);

        $result = $this->service()->departments('Tecnologías');

        $this->assertCount(1, $result->items);
        $this->assertSame(1, $result->meta['total']);
        Http::assertSent(fn (Request $request): bool => str_contains(
            urldecode($request->url()),
            'search=Tecnologías'
        ) && ! $request->hasHeader('Authorization'));
    }

    /**
     * @param  class-string<AccessApiException>  $exceptionClass
     */
    #[DataProvider('errorResponses')]
    public function test_http_errors_are_mapped_to_safe_exceptions(
        int $status,
        string $exceptionClass,
    ): void {
        Http::fake([
            '*' => Http::response([
                'message' => 'Internal API detail that must not be exposed.',
            ], $status),
        ]);

        $this->expectException($exceptionClass);

        $this->service()->login('ana@example.test', 'test-password');
    }

    /**
     * @return iterable<string, array{int, class-string<AccessApiException>}>
     */
    public static function errorResponses(): iterable
    {
        yield 'unauthenticated' => [401, AccessAuthenticationException::class];
        yield 'forbidden' => [403, AccessAuthorizationException::class];
        yield 'not found' => [404, AccessNotFoundException::class];
        yield 'validation' => [422, AccessValidationException::class];
        yield 'rate limit' => [429, AccessRateLimitException::class];
        yield 'server error' => [500, AccessServerException::class];
    }

    public function test_validation_errors_are_available_without_request_secrets(): void
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Error de validación.',
                'errors' => [
                    'email' => ['El correo es obligatorio.'],
                    'password' => ['The value test-password is invalid.'],
                ],
            ], 422),
        ]);

        try {
            $this->service()->login('ana@example.test', 'test-password');
            $this->fail('An AccessValidationException was not thrown.');
        } catch (AccessValidationException $exception) {
            $this->assertSame(422, $exception->statusCode);
            $this->assertArrayHasKey('email', $exception->errors);
            $this->assertSame(
                ['El campo contiene un valor no válido.'],
                $exception->errors['password'],
            );
            $this->assertStringNotContainsString(
                'test-password',
                json_encode([
                    $exception->getMessage(),
                    $exception->errors,
                ], JSON_THROW_ON_ERROR),
            );
        }
    }

    public function test_inactive_user_is_distinguished_from_missing_system_access(): void
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'El usuario se encuentra inactivo.',
            ], 403),
        ]);

        $this->expectException(AccessInactiveUserException::class);

        $this->service()->login('ana@example.test', 'test-password');
    }

    public function test_connection_failures_are_mapped_without_exposing_details(): void
    {
        Http::fake([
            '*' => Http::failedConnection('Sensitive connection detail.'),
        ]);

        try {
            $this->service()->currentUser('test-token');
            $this->fail('An AccessConnectionException was not thrown.');
        } catch (AccessConnectionException $exception) {
            $this->assertSame(
                'El sistema de accesos no está disponible.',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString(
                'Sensitive connection detail.',
                $exception->getMessage(),
            );
        }
    }

    public function test_invalid_success_payload_is_rejected(): void
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Login correcto.',
                'data' => [
                    'user' => ['id' => 25],
                ],
            ]),
        ]);

        $this->expectException(AccessUnexpectedResponseException::class);

        $this->service()->login('ana@example.test', 'test-password');
    }

    private function service(): AccessApiService
    {
        return new AccessApiService;
    }
}
