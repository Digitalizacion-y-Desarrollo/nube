<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccessPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permissions = $request->session()->get('access.permissions', []);

        abort_unless(
            is_array($permissions)
            && (
                in_array($permission, $permissions, true)
                || in_array('nube_administracion_administrar', $permissions, true)
            ),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
