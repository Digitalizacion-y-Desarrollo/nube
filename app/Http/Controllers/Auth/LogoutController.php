<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Access\AccessApiService;
use App\Services\Access\Exceptions\AccessApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request, AccessApiService $accessApi): RedirectResponse
    {
        $token = $request->session()->get('access.token');
        $user = Auth::user();

        if (is_string($token) && $token !== '') {
            try {
                $accessApi->logout($token);
            } catch (AccessApiException) {
                // La sesión local siempre debe cerrarse aunque el API no responda.
            }
        }

        if ($user !== null) {
            AuditLog::create([
                'user_id' => $user->getAuthIdentifier(),
                'action' => 'auth.logout',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }
}
