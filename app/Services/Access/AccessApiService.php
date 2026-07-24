<?php

namespace App\Services\Access;

use App\Services\Access\Data\AccessAuthData;
use App\Services\Access\Data\AccessCollectionData;
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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class AccessApiService
{
    private readonly string $baseUrl;

    private readonly ?string $systemKey;

    private readonly int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.access.url'), '/');
        $systemKey = config('services.access.system_key');
        $this->systemKey = is_string($systemKey) && $systemKey !== ''
            ? $systemKey
            : null;
        $this->timeout = (int) config('services.access.timeout', 10);
    }

    public function login(string $email, string $password): AccessAuthData
    {
        $this->requireValue($email, 'email');
        $this->requireValue($password, 'password');

        $response = $this->send(
            fn (): Response => $this->request()->post('/api/auth/login', [
                'email' => $email,
                'password' => $password,
                'system_key' => $this->requiredSystemKey(),
            ])
        );

        return $this->authData($response, requiresToken: true);
    }

    public function currentUser(string $token): AccessAuthData
    {
        $this->requireValue($token, 'token');

        $response = $this->send(
            fn (): Response => $this->authenticatedRequest($token)
                ->get('/api/auth/me', [
                    'system_key' => $this->requiredSystemKey(),
                ])
        );

        return $this->authData($response, requiresToken: false);
    }

    public function logout(string $token): void
    {
        $this->requireValue($token, 'token');

        $response = $this->send(
            fn (): Response => $this->authenticatedRequest($token)
                ->post('/api/auth/logout')
        );

        $this->payload($response);
    }

    public function forgotPassword(string $email, string $loginUrl): string
    {
        $this->requireValue($email, 'email');
        $this->requireValue($loginUrl, 'login_url');

        $response = $this->send(
            fn (): Response => $this->request()->post('/api/auth/forgot-password', [
                'email' => $email,
                'login_url' => $loginUrl,
            ])
        );
        $payload = $this->payload($response);

        return is_string($payload['message'] ?? null)
            ? $payload['message']
            : 'Solicitud procesada correctamente.';
    }

    public function integrationUsers(string $token): AccessCollectionData
    {
        $this->requireValue($token, 'token');

        $response = $this->send(
            fn (): Response => $this->authenticatedRequest($token)
                ->get('/api/integrations/users', [
                    'system_key' => $this->requiredSystemKey(),
                ])
        );

        return $this->collectionData($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function changePassword(
        string $token,
        int $userId,
        string $currentPassword,
        string $password,
    ): array {
        $this->requireValue($token, 'token');
        $this->requireValue($currentPassword, 'current_password');
        $this->requireValue($password, 'password');

        if ($userId < 1) {
            throw new InvalidArgumentException('The user ID must be positive.');
        }

        $response = $this->send(
            fn (): Response => $this->authenticatedRequest($token)
                ->put("/api/integrations/users/{$userId}/password", [
                    'current_password' => $currentPassword,
                    'password' => $password,
                ])
        );

        return $this->data($response);
    }

    public function departments(?string $search = null): AccessCollectionData
    {
        $query = [];

        if ($search !== null && trim($search) !== '') {
            $query['search'] = trim($search);
        }

        $response = $this->send(
            fn (): Response => $this->request()->get('/api/departamentos', $query)
        );

        return $this->collectionData($response);
    }

    private function request(): PendingRequest
    {
        if ($this->baseUrl === '') {
            throw new LogicException('ACCESS_API_URL is not configured.');
        }

        if ($this->timeout < 1) {
            throw new LogicException('ACCESS_TIMEOUT must be greater than zero.');
        }

        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout(min(5, $this->timeout));
    }

    private function authenticatedRequest(string $token): PendingRequest
    {
        return $this->request()->withToken($token);
    }

    private function requiredSystemKey(): string
    {
        if ($this->systemKey === null) {
            throw new LogicException('ACCESS_SYSTEM_KEY is not configured.');
        }

        return $this->systemKey;
    }

    private function requireValue(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("The {$field} value is required.");
        }
    }

    /**
     * @param  callable(): Response  $callback
     */
    private function send(callable $callback): Response
    {
        try {
            return $callback();
        } catch (ConnectionException) {
            throw new AccessConnectionException(
                'El sistema de accesos no está disponible.',
            );
        }
    }

    private function authData(
        Response $response,
        bool $requiresToken,
    ): AccessAuthData {
        $data = $this->data($response);
        $accessToken = $data['access_token'] ?? null;
        $user = $data['user'] ?? null;

        if (
            ! is_array($user)
            || ($requiresToken && (! is_string($accessToken) || $accessToken === ''))
        ) {
            throw new AccessUnexpectedResponseException(
                'El sistema de accesos devolvió una respuesta no válida.',
                $response->status(),
            );
        }

        return new AccessAuthData(
            accessToken: is_string($accessToken) ? $accessToken : null,
            tokenType: is_string($data['token_type'] ?? null)
                ? $data['token_type']
                : 'Bearer',
            user: $user,
            system: is_array($data['system'] ?? null) ? $data['system'] : null,
            roles: $this->stringList($data['roles'] ?? []),
            permissions: $this->stringList($data['permissions'] ?? []),
        );
    }

    private function collectionData(Response $response): AccessCollectionData
    {
        $payload = $this->payload($response);
        $items = $payload['data'] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw new AccessUnexpectedResponseException(
                'El sistema de accesos devolvió una colección no válida.',
                $response->status(),
            );
        }

        return new AccessCollectionData(
            items: array_values(array_filter($items, 'is_array')),
            meta: is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Response $response): array
    {
        $payload = $this->payload($response);
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new AccessUnexpectedResponseException(
                'El sistema de accesos devolvió datos no válidos.',
                $response->status(),
            );
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Response $response): array
    {
        if (! $response->successful()) {
            $this->throwForResponse($response);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new AccessUnexpectedResponseException(
                'El sistema de accesos devolvió una respuesta no válida.',
                $response->status(),
            );
        }

        return $payload;
    }

    private function throwForResponse(Response $response): never
    {
        $status = $response->status();
        $errors = $this->safeErrors($response->json('errors'));
        $message = is_string($response->json('message'))
            ? $response->json('message')
            : '';

        throw match ($status) {
            401 => new AccessAuthenticationException(
                'No fue posible autenticar la solicitud.',
                $status,
            ),
            403 => Str::contains(Str::lower($message), ['inactiv', 'inhabilit'])
                ? new AccessInactiveUserException(
                    'La cuenta del usuario está inactiva.',
                    $status,
                )
                : new AccessAuthorizationException(
                    'El usuario no tiene acceso al sistema solicitado.',
                    $status,
                ),
            404 => new AccessNotFoundException(
                'El recurso solicitado no existe en el sistema de accesos.',
                $status,
            ),
            422 => new AccessValidationException(
                'Los datos enviados al sistema de accesos no son válidos.',
                $status,
                $errors,
            ),
            429 => new AccessRateLimitException(
                'Se superó el límite de solicitudes al sistema de accesos.',
                $status,
            ),
            default => $status >= 500
                ? new AccessServerException(
                    'El sistema de accesos no está disponible.',
                    $status,
                )
                : new AccessApiException(
                    'La solicitud al sistema de accesos no pudo completarse.',
                    $status,
                ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function safeErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [];
        }

        $safeErrors = [];

        foreach ($errors as $field => $messages) {
            if (! is_string($field)) {
                continue;
            }

            $safeErrors[$field] = preg_match(
                '/password|token|system_key|secret/i',
                $field,
            ) === 1
                ? ['El campo contiene un valor no válido.']
                : $messages;
        }

        return $safeErrors;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $values,
            fn (mixed $value): bool => is_string($value) && $value !== '',
        )));
    }
}
