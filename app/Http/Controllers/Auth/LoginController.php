<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Services\Access\AccessApiService;
use App\Services\Access\AccessUserSynchronizer;
use App\Services\Access\Exceptions\AccessApiException;
use App\Services\Access\Exceptions\AccessAuthenticationException;
use App\Services\Access\Exceptions\AccessAuthorizationException;
use App\Services\Access\Exceptions\AccessConnectionException;
use App\Services\Access\Exceptions\AccessInactiveUserException;
use App\Services\Access\Exceptions\AccessServerException;
use App\Services\Access\Exceptions\AccessValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        LoginRequest $request,
        AccessApiService $accessApi,
        AccessUserSynchronizer $synchronizer,
    ): RedirectResponse {
        $credentials = $request->validated();

        try {
            $authData = $accessApi->login($credentials['email'], $credentials['password']);

            if (! in_array('nube_inicio_ver', $authData->permissions, true)) {
                if ($authData->accessToken !== null) {
                    try {
                        $accessApi->logout($authData->accessToken);
                    } catch (Throwable) {
                        // No se conserva el token cuando falta el permiso de entrada.
                    }
                }

                return $this->failure(
                    $request,
                    'Tu cuenta no tiene el permiso para acceder a la nube. Contacta al administrador de tu departamento.',
                    'permission',
                );
            }

            $user = $synchronizer->synchronize($authData, isLogin: true);

            $request->session()->regenerate();
            Auth::login($user);
            $request->session()->put([
                'access.token' => $authData->accessToken,
                'access.permissions' => $authData->permissions,
                'access.roles' => $authData->roles,
                'access.validated_at' => now()->timestamp,
            ]);

            config(['session.expire_on_close' => ! $request->boolean('remember')]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.login',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => ['system' => $authData->system['nombre'] ?? null],
                'created_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'));
        } catch (AccessAuthenticationException) {
            return $this->failure(
                $request,
                'Las credenciales ingresadas son incorrectas. Verifica tu correo y contraseña.',
                'credentials',
            );
        } catch (AccessInactiveUserException) {
            return $this->failure(
                $request,
                'Tu cuenta se encuentra inactiva. Contacta a Recursos Humanos o al área de TI.',
                'inactive',
            );
        } catch (AccessAuthorizationException) {
            return $this->failure(
                $request,
                'Tu cuenta no tiene acceso a Nube Municipal. Contacta al administrador de tu departamento.',
                'permission',
            );
        } catch (AccessValidationException $exception) {
            return back()
                ->withInput($request->safe()->only('email', 'remember'))
                ->withErrors($exception->errors)
                ->with('auth_error', 'Revisa los datos ingresados e intenta nuevamente.')
                ->with('auth_error_type', 'validation');
        } catch (AccessConnectionException|AccessServerException) {
            return $this->failure(
                $request,
                'No se pudo conectar con el sistema de accesos. Intenta nuevamente.',
                'connection',
            );
        } catch (AccessApiException) {
            return $this->failure(
                $request,
                'El sistema de accesos no pudo procesar la solicitud. Intenta nuevamente.',
                'connection',
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure(
                $request,
                'No se pudo iniciar sesión en este momento. Intenta nuevamente.',
                'connection',
            );
        }
    }

    private function failure(
        LoginRequest $request,
        string $message,
        string $type,
    ): RedirectResponse {
        return back()
            ->withInput($request->safe()->only('email', 'remember'))
            ->with('auth_error', $message)
            ->with('auth_error_type', $type);
    }
}
