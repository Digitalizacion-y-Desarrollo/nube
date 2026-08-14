<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige el permiso funcional administrativo, además del rol `superuser` que ya
 * aplica `EnsureSuperuserRole`. A diferencia de `EnsureAccessPermission`, aquí
 * no existe comodín: `nube_administracion_administrar` debe estar presente de
 * forma explícita en los permisos efectivos de la sesión.
 *
 * Es una segunda barrera antes de las Policies administrativas: si el permiso
 * se retira en Accesos, la siguiente revalidación de sesión cierra el acceso a
 * las rutas de escritura aunque la copia local todavía no se haya actualizado.
 */
class EnsureAdministrativePermission
{
    public const PERMISSION = 'nube_administracion_administrar';

    public function handle(Request $request, Closure $next): Response
    {
        $permissions = $request->session()->get('access.permissions', []);

        abort_unless(
            is_array($permissions) && in_array(self::PERMISSION, $permissions, true),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
