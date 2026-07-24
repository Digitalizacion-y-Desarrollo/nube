<?php

namespace App\Http\Middleware;

use App\Services\Access\AccessApiService;
use App\Services\Access\AccessUserSynchronizer;
use App\Services\Access\Exceptions\AccessApiException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureAccessSession
{
    public function __construct(
        private readonly AccessApiService $accessApi,
        private readonly AccessUserSynchronizer $synchronizer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->session()->get('access.token');
        $permissions = $request->session()->get('access.permissions', []);

        if (
            ! Auth::check()
            || ! is_string($token)
            || $token === ''
            || ! is_array($permissions)
            || ! in_array('nube_inicio_ver', $permissions, true)
        ) {
            return $this->endSession($request, 'Tu sesión no es válida. Inicia sesión nuevamente.');
        }

        $lastValidation = (int) $request->session()->get('access.validated_at', 0);
        $interval = max(0, (int) config('services.access.session_check_interval', 300));

        if ($interval === 0 || $lastValidation <= now()->subSeconds($interval)->timestamp) {
            try {
                $authData = $this->accessApi->currentUser($token);

                if (! in_array('nube_inicio_ver', $authData->permissions, true)) {
                    return $this->endSession($request, 'Tu cuenta no tiene el permiso nube_inicio_ver. Contacta al administrador de tu departamento.');
                }

                $user = $this->synchronizer->synchronize($authData);
                Auth::login($user);
                $request->session()->put([
                    'access.permissions' => $authData->permissions,
                    'access.roles' => $authData->roles,
                    'access.validated_at' => now()->timestamp,
                ]);
            } catch (AccessApiException) {
                return $this->endSession($request, 'Tu sesión expiró o no pudo validarse. Inicia sesión nuevamente.');
            } catch (Throwable $exception) {
                report($exception);

                return $this->endSession($request, 'Tu sesión no pudo sincronizarse. Inicia sesión nuevamente.');
            }
        }

        return $next($request);
    }

    private function endSession(Request $request, string $message): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('auth_error', $message);
    }
}
