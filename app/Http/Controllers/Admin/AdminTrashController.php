<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterAdminTrashRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\Admin\AdminTrashService;
use App\Services\Files\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AdminTrashController extends Controller
{
    public function __construct(
        private readonly AdminTrashService $trash,
        private readonly FileStorageService $storage,
    ) {}

    public function index(FilterAdminTrashRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.trash', [
            ...$this->layoutData($request),
            'trashedFiles' => $this->trash->files($filters),
            'trashedFolders' => $this->trash->folders($filters),
            'summary' => $this->trash->summary(),
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'people' => User::query()
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email']),
            'retentionDays' => $this->trash->retentionDays(),
            'canOperate' => $request->user()->hasPermission('nube_administracion_administrar'),
        ]);
    }

    public function restoreFile(Request $request, string $file): RedirectResponse
    {
        $file = File::onlyTrashed()->findOrFail($file);
        $this->authorize('restoreAdministrative', $file);
        $file->loadMissing('owner');

        $destination = $this->trash->restoreDestinationFor($file);

        try {
            $restored = $this->storage->restore($file, $destination);
            $this->audit($request, 'admin.trash.file_restored', File::class, $restored->id, [
                'display_name' => $restored->display_name,
                'owner_id' => $restored->owner_id,
                'department_id' => $restored->department_id,
                'restored_to_folder_id' => $destination?->id,
                'restored_to_root' => $destination === null,
            ]);

            return back()->with('status', $destination === null
                ? "El archivo «{$restored->display_name}» fue restaurado a la raíz de su clasificación."
                : "El archivo «{$restored->display_name}» fue restaurado en «{$destination->name}».");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'admin_trash_error',
                'No fue posible restaurar el archivo. No se realizaron cambios.',
            );
        }
    }

    public function purgeFile(Request $request, string $file): RedirectResponse
    {
        $file = File::onlyTrashed()->findOrFail($file);
        $this->authorize('forceDeleteAdministrative', $file);
        $this->requireNameConfirmation($request, $file->display_name);

        $name = $file->display_name;
        $details = [
            'display_name' => $name,
            'owner_id' => $file->owner_id,
            'department_id' => $file->department_id,
            'deleted_at' => $file->deleted_at?->toISOString(),
            'deleted_by' => $file->deleted_by,
        ];

        try {
            $this->storage->forceDelete($file);
            $this->audit($request, 'admin.trash.file_purged', File::class, $file->id, $details);

            return back()->with(
                'status',
                "El archivo «{$name}» fue eliminado definitivamente junto con su copia física.",
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'admin_trash_error',
                'No fue posible eliminar el archivo definitivamente. No se realizaron cambios.',
            );
        }
    }

    public function restoreFolder(Request $request, string $folder): RedirectResponse
    {
        $folder = Folder::onlyTrashed()->findOrFail($folder);
        $this->authorize('restoreAdministrative', $folder);

        try {
            $restored = $this->trash->restoreFolder($folder);
            $this->audit($request, 'admin.trash.folder_restored', Folder::class, $restored->id, [
                'name' => $restored->name,
                'owner_id' => $restored->owner_id,
                'department_id' => $restored->department_id,
                'parent_id' => $restored->parent_id,
                'logical_path' => $restored->path_cache,
            ]);

            return back()->with(
                'status',
                "La carpeta «{$restored->name}» fue restaurada en {$restored->path_cache}.",
            );
        } catch (RuntimeException $exception) {
            return back()->with('admin_trash_error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'admin_trash_error',
                'No fue posible restaurar la carpeta. No se realizaron cambios.',
            );
        }
    }

    public function purgeFolder(Request $request, string $folder): RedirectResponse
    {
        $folder = Folder::onlyTrashed()->findOrFail($folder);
        $this->authorize('forceDeleteAdministrative', $folder);
        $this->requireNameConfirmation($request, $folder->name);

        $name = $folder->name;
        $details = [
            'name' => $name,
            'owner_id' => $folder->owner_id,
            'department_id' => $folder->department_id,
            'deleted_at' => $folder->deleted_at?->toISOString(),
            'deleted_by' => $folder->deleted_by,
        ];

        try {
            $this->trash->purgeFolder($folder);
            $this->audit($request, 'admin.trash.folder_purged', Folder::class, $folder->id, $details);

            return back()->with(
                'status',
                "La carpeta «{$name}» fue eliminada definitivamente.",
            );
        } catch (RuntimeException $exception) {
            return back()->with('admin_trash_error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'admin_trash_error',
                'No fue posible eliminar la carpeta definitivamente. No se realizaron cambios.',
            );
        }
    }

    /**
     * Confirmación reforzada: la eliminación definitiva sólo procede cuando el
     * superusuario escribe el nombre exacto del recurso. Se valida en el
     * servidor para que no dependa del diálogo del navegador.
     */
    private function requireNameConfirmation(Request $request, string $name): void
    {
        $request->validate([
            'confirmation' => ['required', 'string', Rule::in([$name])],
        ], [
            'confirmation.required' => 'Escribe el nombre exacto del elemento para confirmar.',
            'confirmation.in' => 'El nombre no coincide; la eliminación definitiva fue cancelada.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function audit(
        Request $request,
        string $action,
        string $resourceType,
        string $resourceId,
        array $details,
    ): void {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => $details,
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
