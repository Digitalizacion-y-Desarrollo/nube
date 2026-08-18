<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeAdminFileVisibilityRequest;
use App\Http\Requests\Admin\FilterAdminFilesRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\User;
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdminFileController extends Controller
{
    public function __construct(
        private readonly FileStorageService $storage,
    ) {}

    public function index(FilterAdminFilesRequest $request): View
    {
        $filters = $request->validated();
        $status = $filters['status'] ?? 'all';
        $query = match ($status) {
            'active' => File::query(),
            'trashed' => File::onlyTrashed(),
            default => File::withTrashed(),
        };

        $files = $query
            ->with([
                'owner:id,name,last_name,email',
                'department:id,name',
                'folder:id,name',
                'collaborators:id,name,last_name,email',
            ])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('display_name', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('department_id', $id),
            )
            ->when(
                $filters['user_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('owner_id', $id),
            )
            ->when(
                $filters['visibility'] ?? null,
                fn (Builder $query, string $visibility): Builder => $query->where('visibility', $visibility),
            )
            ->when(
                $filters['type'] ?? null,
                fn (Builder $query, string $type): Builder => $query->where('extension', $type),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('uploaded_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('uploaded_at', '<=', $date),
            )
            ->latest('updated_at')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        $departmentUsersByDepartment = User::query()
            ->with('roles:id,name,display_name')
            ->where('active', true)
            ->whereIn(
                'department_id',
                $files->getCollection()->pluck('department_id')->filter()->unique(),
            )
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id', 'department_id', 'name', 'last_name', 'email'])
            ->groupBy('department_id')
            ->map(fn ($users) => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'position' => null,
                'role' => $user->roles->pluck('display_name')->filter()->join(', '),
            ]));

        return view('admin.files', [
            ...$this->layoutData($request),
            'files' => $files,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'owners' => User::query()
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email']),
            'fileTypes' => File::withTrashed()
                ->whereNotNull('extension')
                ->where('extension', '!=', '')
                ->distinct()
                ->orderBy('extension')
                ->pluck('extension'),
            'departmentUsersByDepartment' => $departmentUsersByDepartment,
            'filters' => $filters,
            'canOperate' => $request->user()->hasPermission(
                'nube_administracion_administrar',
            ),
        ]);
    }

    public function show(Request $request, string $file): View
    {
        $file = File::withTrashed()
            ->with([
                'owner:id,name,last_name,email',
                'department:id,name',
                'folder:id,name',
                'collaborators:id,name,last_name,email',
            ])
            ->findOrFail($file);

        $this->authorize('viewAdministrative', $file);
        $this->audit($request, 'admin.file.metadata_viewed', $file);

        $departmentUsers = User::query()
            ->with('roles:id,name,display_name')
            ->where('active', true)
            ->where('department_id', $file->department_id)
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id', 'department_id', 'name', 'last_name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'position' => null,
                'role' => $user->roles->pluck('display_name')->filter()->join(', '),
            ]);

        return view('admin.file-show', [
            ...$this->layoutData($request),
            'file' => $file,
            'departmentUsers' => $departmentUsers,
            'canOperate' => $request->user()->hasPermission('nube_administracion_administrar'),
        ]);
    }

    public function download(Request $request, File $file): StreamedResponse
    {
        $this->authorize('downloadAdministrative', $file);
        abort_unless($this->storage->exists($file), 404);

        $this->audit($request, 'admin.file.downloaded', $file);

        return Storage::disk($file->disk)->download($file->path, $file->display_name);
    }

    public function changeVisibility(
        ChangeAdminFileVisibilityRequest $request,
        File $file,
    ): RedirectResponse {
        $visibility = FileVisibility::from($request->validated('visibility'));
        $this->authorize('changeVisibilityAdministrative', [$file, $visibility]);
        $file->loadMissing('owner');
        $before = $file->visibility->value;
        $configurationOnly = $file->visibility === FileVisibility::Collaborative
            && $visibility === FileVisibility::Collaborative;
        $collaborationScope = $visibility === FileVisibility::Collaborative
            ? CollaborationScope::from($request->validated('collaboration_scope'))
            : null;
        $collaboratorIds = array_map(
            'intval',
            $request->validated('collaborators', []),
        );

        try {
            $file = $this->storage->changeVisibility(
                $file,
                $visibility,
                $collaborationScope,
                $collaboratorIds,
                $request->validated('collaborator_permissions', []),
            );
            $file->load('collaborators');
            $this->audit($request, $configurationOnly
                ? 'admin.file.sharing_configured'
                : 'admin.file.visibility_changed', $file, [
                    'before' => $before,
                    'after' => $visibility->value,
                    'collaboration_scope' => $collaborationScope?->value,
                    'collaborator_ids' => $collaborationScope === CollaborationScope::Selected
                        ? $collaboratorIds
                        : [],
                ]);

            return back()->with(
                'status',
                $configurationOnly
                    ? "Se actualizó el acceso colaborativo de «{$file->display_name}» con {$file->collaborators->count()} colaborador(es)."
                    : ($collaborationScope === CollaborationScope::Selected
                        ? "El archivo «{$file->display_name}» ahora es colaborativo con {$file->collaborators->count()} colaborador(es)."
                        : "El archivo «{$file->display_name}» ahora es {$visibility->label()}."),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'admin_file_error',
                'No fue posible cambiar la clasificación. No se realizaron cambios.',
            );
        }
    }

    public function destroy(Request $request, File $file): RedirectResponse
    {
        $this->authorize('deleteAdministrative', $file);
        $file->loadMissing('owner');

        try {
            $file = $this->storage->delete($file);
            $this->audit($request, 'admin.file.trashed', $file);

            return back()->with(
                'status',
                "El archivo «{$file->display_name}» fue enviado a la papelera.",
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'admin_file_error',
                'No fue posible enviar el archivo a la papelera. No se realizaron cambios.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function audit(
        Request $request,
        string $action,
        File $file,
        array $details = [],
    ): void {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'display_name' => $file->display_name,
                'owner_id' => $file->owner_id,
                'department_id' => $file->department_id,
                ...$details,
            ],
            'created_at' => now(),
        ]);
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
