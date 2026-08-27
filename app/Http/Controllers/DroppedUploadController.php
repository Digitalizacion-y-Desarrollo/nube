<?php

namespace App\Http\Controllers;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Http\Requests\UploadDroppedItemRequest;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Services\Files\FileStorageService;
use App\Services\Folders\FolderPathService;
use App\Services\Sharing\CollaboratorPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DroppedUploadController extends Controller
{
    public function __construct(
        private readonly FileStorageService $storage,
        private readonly FolderPathService $paths,
        private readonly CollaboratorPermissionService $collaboratorPermissions,
    ) {}

    public function __invoke(UploadDroppedItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $baseFolder = isset($validated['folder_id'])
            ? Folder::query()->findOrFail($validated['folder_id'])
            : null;
        $visibility = FileVisibility::from($validated['visibility']);
        $collaborationScope = ($visibility === FileVisibility::Collaborative || $request->boolean('share_privately'))
            ? CollaborationScope::from($validated['collaboration_scope'])
            : null;
        $segments = explode('/', $validated['relative_path']);
        $fileName = $validated['kind'] === 'file' ? array_pop($segments) : null;

        if ($validated['kind'] === 'file'
            && $request->file('file')->getClientOriginalName() !== $fileName) {
            throw ValidationException::withMessages([
                'relative_path' => 'El nombre del archivo no coincide con su ruta relativa.',
            ]);
        }

        try {
            $folder = $this->ensureFolders(
                $request,
                $baseFolder,
                $segments,
                $visibility,
                $collaborationScope,
                $validated,
            );

            if ($validated['kind'] === 'directory') {
                return response()->json([
                    'message' => "Carpeta «{$folder?->name}» preparada.",
                    'kind' => 'directory',
                ], 201);
            }

            $this->authorize('upload', [File::class, $folder, $visibility]);
            $this->guardFileName($request, $folder, $visibility, (string) $fileName);

            $file = $this->storage->upload(
                $request->file('file'),
                $request->user(),
                $folder,
                $visibility,
                $collaborationScope,
                $validated['collaborators'] ?? [],
                $validated['collaborator_permissions'] ?? [],
                $fileName,
                $validated['sharing_expires_at'] ?? null,
            );

            return response()->json([
                'message' => "Archivo «{$file->display_name}» cargado.",
                'kind' => 'file',
            ], 201);
        } catch (ValidationException|AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No fue posible guardar el elemento. Intenta nuevamente.',
            ], 500);
        }
    }

    /**
     * @param  list<string>  $segments
     * @param  array<string, mixed>  $validated
     */
    private function ensureFolders(
        UploadDroppedItemRequest $request,
        ?Folder $parent,
        array $segments,
        FileVisibility $visibility,
        ?CollaborationScope $collaborationScope,
        array $validated,
    ): ?Folder {
        foreach ($segments as $name) {
            $existing = Folder::query()
                ->where('owner_id', $request->user()->id)
                ->where('visibility', $visibility)
                ->when(
                    $parent === null,
                    fn ($query) => $query->whereNull('parent_id'),
                    fn ($query) => $query->where('parent_id', $parent->id),
                )
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->first();

            if ($existing !== null) {
                $parent = $existing;

                continue;
            }

            $this->authorize('create', [Folder::class, $parent, $visibility]);

            if (mb_strlen($this->paths->pathFor($parent, $name)) > 500) {
                throw ValidationException::withMessages([
                    'relative_path' => 'La ruta de la carpeta es demasiado larga.',
                ]);
            }

            $parent = DB::transaction(function () use (
                $request,
                $parent,
                $name,
                $visibility,
                $collaborationScope,
                $validated,
            ): Folder {
                $folder = Folder::query()->create([
                    'parent_id' => $parent?->id,
                    'owner_id' => $request->user()->id,
                    'department_id' => $parent?->department_id ?? $request->user()->department_id,
                    'name' => $name,
                    'visibility' => $visibility,
                    'collaboration_scope' => $collaborationScope,
                    'path_cache' => $this->paths->pathFor($parent, $name),
                ]);

                $folder->collaborators()->sync(
                    $collaborationScope === CollaborationScope::Selected
                        ? $this->collaboratorPermissions->pivotData(
                            $validated['collaborators'] ?? [],
                            $validated['collaborator_permissions'] ?? [],
                            $validated['sharing_expires_at'] ?? null,
                        )
                        : [],
                );

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'folder.created',
                    'resource_type' => Folder::class,
                    'resource_id' => $folder->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'details' => [
                        'name' => $folder->name,
                        'parent_id' => $folder->parent_id,
                        'logical_path' => $folder->path_cache,
                        'visibility' => $visibility->value,
                        'collaboration_scope' => $collaborationScope?->value,
                        'collaborator_ids' => $validated['collaborators'] ?? [],
                        'source' => 'drag_and_drop',
                    ],
                    'created_at' => now(),
                ]);

                return $folder;
            });
        }

        return $parent;
    }

    private function guardFileName(
        UploadDroppedItemRequest $request,
        ?Folder $folder,
        FileVisibility $visibility,
        string $fileName,
    ): void {
        $query = File::query()
            ->where('folder_id', $folder?->id)
            ->where('visibility', $visibility)
            ->whereRaw('LOWER(display_name) = ?', [Str::lower($fileName)]);

        $visibility === FileVisibility::Private
            ? $query->where('owner_id', $request->user()->id)
            : $query->where('department_id', $folder?->department_id ?? $request->user()->department_id);

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'relative_path' => 'Ya existe un archivo con ese nombre en la carpeta de destino.',
            ]);
        }
    }
}
