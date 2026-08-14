<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Profile\AvatarStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AvatarStorageService $avatars,
    ) {}

    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('department:id,name', 'roles:id,name,display_name');

        return view('profile.edit', [
            'user' => $this->userData($user),
            'permissions' => $this->permissions($request),
            'profileUser' => $user,
            'hasAvatar' => $this->avatars->exists($user),
            'maxSizeKb' => (int) config('nube.avatars.max_size_kb', 10240),
            'allowedExtensions' => (array) config('nube.avatars.extensions', ['jpg', 'jpeg', 'png']),
        ]);
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->avatars->store($user, $request->file('avatar'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'profile_error',
                'No fue posible actualizar tu foto de perfil. No se realizaron cambios.',
            );
        }

        $this->audit($request, 'profile.avatar_updated');

        return back()->with('status', 'Tu foto de perfil fue actualizada.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasAvatar()) {
            return back()->with('profile_error', 'No tienes una foto de perfil que quitar.');
        }

        try {
            $this->avatars->delete($user);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'profile_error',
                'No fue posible quitar tu foto de perfil. No se realizaron cambios.',
            );
        }

        $this->audit($request, 'profile.avatar_removed');

        return back()->with('status', 'Se restauró la foto de perfil predeterminada.');
    }

    /**
     * Sirve la foto del usuario autenticado. Cada quien accede únicamente a la
     * suya, y siempre a través del controlador: el disco es privado.
     */
    public function avatar(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->avatars->exists($user), 404);

        return response(
            $this->avatars->contents($user),
            200,
            [
                'Content-Type' => $this->avatars->mimeType($user),
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function audit(Request $request, string $action): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'resource_type' => User::class,
            'resource_id' => (string) $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function userData(User $user): array
    {
        return [
            'name' => trim("{$user->name} {$user->last_name}"),
            'first_name' => $user->name,
            'department' => $user->department?->name ?? 'Sin departamento',
            'avatar' => $user->avatarUrl(),
        ];
    }

    /**
     * @return list<string>
     */
    private function permissions(Request $request): array
    {
        $permissions = $request->session()->get('access.permissions', []);

        return is_array($permissions) ? array_values($permissions) : [];
    }
}
