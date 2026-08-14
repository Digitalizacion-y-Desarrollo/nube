<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Admin\AdminSystemStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly AdminSystemStatusService $status,
    ) {}

    public function index(Request $request): View
    {
        $validatedAt = $request->session()->get('access.validated_at');

        return view('admin.settings', [
            ...$this->layoutData($request),
            'uploads' => $this->status->uploads(),
            'storage' => $this->status->storage(),
            'trash' => $this->status->trash(),
            'accessApi' => $this->status->accessApi(
                is_numeric($validatedAt) ? (int) $validatedAt : null,
            ),
        ]);
    }

    /**
     * Comprobación en vivo del API de Accesos. No modifica configuración: sólo
     * ejecuta la petición y guarda el resultado para mostrarlo en el panel.
     */
    public function check(Request $request): RedirectResponse
    {
        $token = $request->session()->get('access.token');
        $result = $this->status->probeAccessApi(
            is_string($token) ? $token : null,
        );

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin.settings.connection_checked',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => ['state' => $result['state']],
            'created_at' => now(),
        ]);

        return back()->with(
            $result['state'] === 'online' ? 'status' : 'admin_settings_error',
            $result['message'],
        );
    }

    /**
     * @return array{user: array<string, string>}
     */
    private function layoutData(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('department:id,name');

        return [
            'user' => [
                'name' => trim("{$user->name} {$user->last_name}"),
                'department' => $user->department?->name ?? 'Sin departamento',
                'avatar' => $user->avatarUrl(),
            ],
        ];
    }
}
