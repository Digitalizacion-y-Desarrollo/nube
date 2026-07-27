<?php

namespace App\Http\Controllers;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Http\Requests\ChangeFolderVisibilityRequest;
use App\Http\Requests\RenameFolderRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\Access\DepartmentCollaboratorService;
use App\Services\Access\Exceptions\AccessApiException;
use App\Services\Folders\FolderPathService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;

class FolderController extends Controller
{
    public function __construct(
        private readonly FolderPathService $paths,
        private readonly DepartmentCollaboratorService $departmentCollaborators,
    ) {}

    public function mine(Request $request, ?Folder $folder = null): View
    {
        if ($folder !== null) {
            $this->guardFolderForSection($request, $folder, 'mine');
        }

        return $this->renderSection(
            request: $request,
            section: 'mine',
            title: 'Mis archivos',
            description: 'Tus carpetas y archivos privados.',
            folders: $this->folderQuery($folder)->when(
                $folder === null,
                fn (Builder $query): Builder => $query
                    ->where('owner_id', $request->user()->id)
                    ->where('visibility', FileVisibility::Private),
            ),
            files: $this->fileQuery($folder)->when(
                $folder === null,
                fn (Builder $query): Builder => $query
                    ->where('owner_id', $request->user()->id)
                    ->where('visibility', FileVisibility::Private),
            ),
            currentFolder: $folder,
        );
    }

    public function department(Request $request, ?Folder $folder = null): View
    {
        if ($folder !== null) {
            $this->guardFolderForSection($request, $folder, 'department');
        }

        return $this->renderSection(
            request: $request,
            section: 'department',
            title: 'Mi departamento',
            description: 'Contenido colaborativo de tu departamento.',
            folders: $this->folderQuery($folder)->when(
                $folder === null,
                fn (Builder $query): Builder => $query
                    ->where('department_id', $request->user()->department_id)
                    ->where('visibility', FileVisibility::Collaborative),
            ),
            files: $this->fileQuery($folder)->when(
                $folder === null,
                fn (Builder $query): Builder => $query
                    ->where('department_id', $request->user()->department_id)
                    ->where('visibility', FileVisibility::Collaborative),
            ),
            currentFolder: $folder,
        );
    }

    public function public(Request $request, ?Folder $folder = null): View
    {
        if ($folder !== null) {
            $this->guardFolderForSection($request, $folder, 'public');
        }

        return $this->renderSection(
            request: $request,
            section: 'public',
            title: 'Públicos',
            description: 'Contenido interno disponible para todas las personas autenticadas.',
            folders: $this->folderQuery($folder)->when(
                $folder === null,
                fn (Builder $query): Builder => $query
                    ->where('visibility', FileVisibility::Public),
            ),
            files: $this->fileQuery($folder)->when(
                $folder === null,
                fn (Builder $query): Builder => $query
                    ->where('visibility', FileVisibility::Public),
            ),
            currentFolder: $folder,
        );
    }

    public function trash(Request $request): View
    {
        $folders = Folder::onlyTrashed()
            ->where('owner_id', $request->user()->id)
            ->where(function (Builder $query): void {
                $query->whereNull('parent_id')
                    ->orWhereDoesntHave('parent', fn (Builder $parent): Builder => $parent->onlyTrashed());
            });

        $files = File::onlyTrashed()
            ->where('owner_id', $request->user()->id);

        return $this->renderSection(
            request: $request,
            section: 'trash',
            title: 'Papelera',
            description: 'Elementos eliminados que todavía pueden restaurarse.',
            folders: $folders,
            files: $files,
            trashed: true,
        );
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $parent = isset($validated['parent_id'])
            ? Folder::query()->findOrFail($validated['parent_id'])
            : null;
        $visibility = FileVisibility::from($validated['visibility']);
        $collaborationScope = $visibility === FileVisibility::Collaborative
            ? CollaborationScope::from($validated['collaboration_scope'])
            : null;

        $this->authorize('create', [Folder::class, $parent, $visibility]);

        $folder = DB::transaction(function () use (
            $request,
            $validated,
            $parent,
            $visibility,
            $collaborationScope,
        ): Folder {
            $folder = Folder::query()->create([
                'parent_id' => $parent?->id,
                'owner_id' => $request->user()->id,
                'department_id' => $request->user()->department_id,
                'name' => $validated['name'],
                'visibility' => $visibility,
                'collaboration_scope' => $collaborationScope,
                'path_cache' => $this->paths->pathFor($parent, $validated['name']),
            ]);

            $folder->collaborators()->sync(
                $visibility === FileVisibility::Collaborative
                    && $collaborationScope === CollaborationScope::Selected
                    ? collect($validated['collaborators'] ?? [])
                        ->mapWithKeys(fn (int $id): array => [
                            $id => ['created_at' => now()],
                        ])
                        ->all()
                    : [],
            );

            $this->auditFolder($request, 'folder.created', $folder, [
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'logical_path' => $folder->path_cache,
                'visibility' => $visibility->value,
                'collaboration_scope' => $collaborationScope?->value,
                'collaborator_ids' => $validated['collaborators'] ?? [],
            ]);

            return $folder;
        });

        return $this->redirectToLocation($parent, $visibility)
            ->with('status', "La carpeta «{$folder->name}» fue creada.");
    }

    public function update(RenameFolderRequest $request, Folder $folder): RedirectResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $oldName = $folder->name;
        $oldPath = $folder->path_cache ?: $this->paths->logicalPath($folder);
        $parent = $folder->parent()->first();

        DB::transaction(function () use ($request, $folder, $validated, $oldName, $oldPath, $parent): void {
            $folder->update([
                'name' => $validated['name'],
                'path_cache' => $this->paths->pathFor($parent, $validated['name']),
            ]);

            $this->paths->refreshDescendantPaths($folder);

            $this->auditFolder($request, 'folder.renamed', $folder, [
                'old_name' => $oldName,
                'new_name' => $folder->name,
                'old_logical_path' => $oldPath,
                'new_logical_path' => $folder->path_cache,
            ]);
        });

        return back()->with('status', "La carpeta «{$folder->name}» fue renombrada.");
    }

    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        if ($folder->children()->exists() || $folder->files()->exists()) {
            return back()->with(
                'folder_error',
                'La carpeta debe estar vacía antes de eliminarla.',
            );
        }

        DB::transaction(function () use ($request, $folder): void {
            $details = [
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'logical_path' => $folder->path_cache ?: $this->paths->logicalPath($folder),
            ];

            $folder->delete();
            $this->auditFolder($request, 'folder.deleted', $folder, $details);
        });

        return back()->with('status', "La carpeta «{$folder->name}» fue enviada a la papelera.");
    }

    public function changeVisibility(
        ChangeFolderVisibilityRequest $request,
        Folder $folder,
    ): RedirectResponse {
        $visibility = FileVisibility::from($request->validated('visibility'));
        $collaborationScope = $visibility === FileVisibility::Collaborative
            ? CollaborationScope::from($request->validated('collaboration_scope'))
            : null;
        $oldVisibility = $folder->visibility;
        $parent = $folder->parent()->first();

        $this->authorize('changeVisibility', [$folder, $visibility]);

        DB::transaction(function () use (
            $request,
            $folder,
            $visibility,
            $collaborationScope,
            $oldVisibility,
        ): void {
            $folder->update([
                'visibility' => $visibility,
                'collaboration_scope' => $collaborationScope,
            ]);

            $collaboratorIds = $visibility === FileVisibility::Collaborative
                && $collaborationScope === CollaborationScope::Selected
                    ? $request->validated('collaborators', [])
                    : [];

            $folder->collaborators()->sync(
                collect($collaboratorIds)
                    ->mapWithKeys(fn (int $id): array => [
                        $id => ['created_at' => now()],
                    ])
                    ->all(),
            );

            $this->auditFolder($request, 'folder.visibility_changed', $folder, [
                'name' => $folder->name,
                'old_visibility' => $oldVisibility->value,
                'new_visibility' => $visibility->value,
                'collaboration_scope' => $collaborationScope?->value,
                'collaborator_ids' => $collaboratorIds,
            ]);
        });

        return $this->redirectToLocation($parent, $visibility)
            ->with('status', "La carpeta «{$folder->name}» ahora es {$visibility->label()}.");
    }

    private function renderSection(
        Request $request,
        string $section,
        string $title,
        string $description,
        Builder $folders,
        Builder $files,
        ?Folder $currentFolder = null,
        bool $trashed = false,
    ): View {
        $request->user()->loadMissing('permissions:id,name');

        $folderItems = $folders
            ->with([
                'owner:id,name,last_name',
                'collaborators:id,name,last_name',
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (Folder $folder): bool => $trashed
                || ($currentFolder === null && in_array($section, ['mine', 'public'], true))
                || ($currentFolder === null
                    && $section === 'department'
                    && $folder->collaboration_scope !== CollaborationScope::Selected)
                || $request->user()->can('view', $folder))
            ->map(fn (Folder $folder): array => $this->folderItem(
                $request,
                $folder,
                $section,
                $trashed,
            ));

        $fileItems = $files
            ->with([
                'owner:id,name,last_name',
                'collaborators:id,name,last_name',
            ])
            ->latest($trashed ? 'deleted_at' : 'uploaded_at')
            ->get()
            ->filter(fn (File $file): bool => $trashed
                || ($currentFolder === null && in_array($section, ['mine', 'public'], true))
                || ($currentFolder === null
                    && $section === 'department'
                    && $file->collaboration_scope !== CollaborationScope::Selected)
                || $request->user()->can('view', $file))
            ->map(fn (File $file): array => $this->fileItem(
                $request,
                $file,
                $section,
                $trashed,
            ));

        $destinationFolders = Folder::query()
            ->where('owner_id', $request->user()->id)
            ->where('department_id', $request->user()->department_id)
            ->orderBy('path_cache')
            ->get(['id', 'name', 'path_cache', 'visibility']);

        $uploadVisibilityOptions = $section === 'trash'
            ? collect()
            : collect(FileVisibility::cases())
                ->filter(fn (FileVisibility $visibility): bool => $request->user()->can(
                    'upload',
                    [File::class, $currentFolder, $visibility],
                ))
                ->map(fn (FileVisibility $visibility): array => [
                    'value' => $visibility->value,
                    'label' => $visibility->label(),
                ])
                ->values();

        $folderVisibilityOptions = $section === 'trash'
            ? collect()
            : collect(FileVisibility::cases())
                ->filter(fn (FileVisibility $visibility): bool => $request->user()->can(
                    'create',
                    [Folder::class, $currentFolder, $visibility],
                ))
                ->map(fn (FileVisibility $visibility): array => [
                    'value' => $visibility->value,
                    'label' => $visibility->label(),
                ])
                ->values();

        $departmentUsers = collect();
        $departmentUsersError = null;
        $needsDepartmentUsers = $folderVisibilityOptions->contains(
            'value',
            FileVisibility::Collaborative->value,
        ) || $uploadVisibilityOptions->contains(
            'value',
            FileVisibility::Collaborative->value,
        ) || $folderItems->contains(
            fn (array $item): bool => collect($item['visibility_options'])
                ->contains('value', FileVisibility::Collaborative->value),
        ) || $fileItems->contains(
            fn (array $item): bool => collect($item['visibility_options'])
                ->contains('value', FileVisibility::Collaborative->value),
        );

        if ($section !== 'trash' && $needsDepartmentUsers) {
            $token = $request->session()->get('access.token');

            try {
                if (! is_string($token) || $token === '') {
                    throw new LogicException('The access token is not available.');
                }

                $departmentUsers = $this->departmentCollaborators->for(
                    $request->user(),
                    $token,
                );
            } catch (AccessApiException|LogicException $exception) {
                report($exception);
                $departmentUsersError = 'No fue posible cargar las personas del departamento. Recarga la página para volver a intentarlo.';
            }
        }

        $defaultUploadVisibility = match ($section) {
            'department' => FileVisibility::Collaborative->value,
            'public' => FileVisibility::Public->value,
            default => FileVisibility::Private->value,
        };

        return view('folders.index', [
            'user' => $this->userData($request->user()),
            'permissions' => $request->session()->get('access.permissions', []),
            'section' => $section,
            'title' => $title,
            'description' => $description,
            'items' => $folderItems->concat($fileItems),
            'folderCount' => $folderItems->count(),
            'fileCount' => $fileItems->count(),
            'currentFolder' => $currentFolder,
            'logicalPath' => $currentFolder === null
                ? '/'
                : ($currentFolder->path_cache ?: $this->paths->logicalPath($currentFolder)),
            'breadcrumbs' => $this->breadcrumbs($request, $section, $title, $currentFolder),
            'parentUrl' => $currentFolder === null
                ? null
                : $this->sectionRoute($section, $currentFolder->parent),
            'canCreateFolder' => $folderVisibilityOptions->isNotEmpty(),
            'folderVisibilityOptions' => $folderVisibilityOptions,
            'canUploadFile' => $uploadVisibilityOptions->isNotEmpty(),
            'uploadVisibilityOptions' => $uploadVisibilityOptions,
            'defaultUploadVisibility' => $defaultUploadVisibility,
            'destinationFolders' => $destinationFolders,
            'departmentUsers' => $departmentUsers,
            'departmentUsersError' => $departmentUsersError,
            'trashRetentionDays' => max(1, (int) config('nube.trash_retention_days', 30)),
        ]);
    }

    private function folderQuery(?Folder $parent): Builder
    {
        return Folder::query()->where('parent_id', $parent?->id);
    }

    private function fileQuery(?Folder $parent): Builder
    {
        return File::query()->where('folder_id', $parent?->id);
    }

    private function guardFolderForSection(
        Request $request,
        Folder $folder,
        string $section,
    ): void {
        foreach ($this->paths->ancestorsAndSelf($folder) as $ancestor) {
            abort_unless($request->user()->can('view', $ancestor), 404);
        }
    }

    /**
     * @return array<int, array{label: string, url?: string}>
     */
    private function breadcrumbs(
        Request $request,
        string $section,
        string $title,
        ?Folder $folder,
    ): array {
        $items = [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => $title, 'url' => $this->sectionRoute($section)],
        ];

        if ($folder === null) {
            return $items;
        }

        foreach ($this->paths->ancestorsAndSelf($folder) as $ancestor) {
            $this->authorize('view', $ancestor);
            $items[] = [
                'label' => $ancestor->name,
                'url' => $this->sectionRoute($section, $ancestor),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function folderItem(
        Request $request,
        Folder $folder,
        string $section,
        bool $trashed,
    ): array {
        $visibilityOptions = collect(FileVisibility::cases())
            ->reject(fn (FileVisibility $visibility): bool => $visibility === $folder->visibility)
            ->filter(fn (FileVisibility $visibility): bool => $request->user()->can(
                'changeVisibility',
                [$folder, $visibility],
            ))
            ->map(fn (FileVisibility $visibility): array => [
                'value' => $visibility->value,
                'label' => $visibility->label(),
            ])
            ->values()
            ->all();

        return [
            'id' => $folder->id,
            'type' => 'folder',
            'name' => $folder->name,
            'icon' => 'folder',
            'owner' => $folder->owner?->name,
            'date' => ($trashed ? $folder->deleted_at : $folder->updated_at)?->diffForHumans(),
            'size' => null,
            'visibility' => $folder->visibility->value,
            'visibility_label' => $folder->visibility->label(),
            'sharing_label' => $this->sharingLabel(
                $folder->visibility,
                $folder->collaboration_scope,
                $folder->collaborators->count(),
            ),
            'url' => $trashed ? null : $this->sectionRoute($section, $folder),
            'can_rename' => ! $trashed && $request->user()->can('update', $folder),
            'can_delete' => ! $trashed && $request->user()->can('delete', $folder),
            'can_download' => false,
            'can_move' => false,
            'can_change_visibility' => ! $trashed && $visibilityOptions !== [],
            'visibility_options' => $visibilityOptions,
            'can_restore' => false,
            'can_force_delete' => false,
            'purge_label' => null,
            'purge_at' => null,
            'update_url' => route('folders.update', $folder),
            'delete_url' => route('folders.destroy', $folder),
            'visibility_url' => route('folders.visibility', $folder),
            'rename_modal_id' => "rename-folder-{$folder->id}",
            'delete_modal_id' => "delete-folder-{$folder->id}",
            'visibility_modal_id' => "visibility-folder-{$folder->id}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileItem(
        Request $request,
        File $file,
        string $section,
        bool $trashed,
    ): array {
        $retentionDays = max(1, (int) config('nube.trash_retention_days', 30));
        $purgeAt = $trashed ? $file->deleted_at?->copy()->addDays($retentionDays) : null;
        $daysRemaining = $purgeAt === null
            ? null
            : max(0, (int) ceil(now()->diffInDays($purgeAt, false)));
        $visibilityOptions = collect(FileVisibility::cases())
            ->reject(fn (FileVisibility $visibility): bool => $visibility === $file->visibility)
            ->filter(fn (FileVisibility $visibility): bool => $request->user()->can(
                'changeVisibility',
                [$file, $visibility],
            ))
            ->map(fn (FileVisibility $visibility): array => [
                'value' => $visibility->value,
                'label' => $visibility->label(),
            ])
            ->values()
            ->all();

        return [
            'id' => $file->id,
            'type' => 'file',
            'name' => $file->display_name,
            'icon' => $this->fileIcon($file->extension),
            'owner' => $file->owner?->name,
            'date' => ($trashed ? $file->deleted_at : $file->uploaded_at)?->diffForHumans(),
            'size' => $this->formatBytes($file->size_bytes),
            'visibility' => $file->visibility->value,
            'visibility_label' => $file->visibility->label(),
            'sharing_label' => $this->sharingLabel(
                $file->visibility,
                $file->collaboration_scope,
                $file->collaborators->count(),
            ),
            'url' => null,
            'folder_id' => $file->folder_id,
            'can_download' => ! $trashed && $request->user()->can('download', $file),
            'can_rename' => ! $trashed && $request->user()->can('update', $file),
            'can_move' => ! $trashed && $request->user()->can('move', [$file, null]),
            'can_delete' => ! $trashed && $request->user()->can('delete', $file),
            'can_change_visibility' => ! $trashed && $visibilityOptions !== [],
            'visibility_options' => $visibilityOptions,
            'can_restore' => $trashed && $request->user()->can('restore', [$file, null]),
            'can_force_delete' => $trashed && $request->user()->can('forceDelete', $file),
            'purge_label' => $daysRemaining === null
                ? null
                : ($daysRemaining === 0
                    ? 'Pendiente de eliminación automática'
                    : "Se elimina en {$daysRemaining} día(s)"),
            'purge_at' => $purgeAt?->translatedFormat('d M Y, H:i'),
            'download_url' => route('files.download', $file),
            'update_url' => route('files.update', $file),
            'move_url' => route('files.move', $file),
            'delete_url' => route('files.destroy', $file),
            'restore_url' => route('files.restore', $file->id),
            'force_delete_url' => route('files.force-destroy', $file->id),
            'visibility_url' => route('files.visibility', $file),
            'rename_modal_id' => "rename-file-{$file->id}",
            'move_modal_id' => "move-file-{$file->id}",
            'delete_modal_id' => "delete-file-{$file->id}",
            'restore_modal_id' => "restore-file-{$file->id}",
            'visibility_modal_id' => "visibility-file-{$file->id}",
        ];
    }

    private function sectionRoute(string $section, ?Folder $folder = null): string
    {
        return match ($section) {
            'mine' => $folder === null
                ? route('folders.mine')
                : route('folders.mine.show', $folder),
            'department' => $folder === null
                ? route('folders.department')
                : route('folders.department.show', $folder),
            'public' => $folder === null
                ? route('folders.public')
                : route('folders.public.show', $folder),
            default => route('folders.trash'),
        };
    }

    private function redirectToLocation(
        ?Folder $parent,
        FileVisibility $visibility,
    ): RedirectResponse {
        $section = match ($parent?->visibility ?? $visibility) {
            FileVisibility::Private => 'mine',
            FileVisibility::Collaborative => 'department',
            FileVisibility::Public => 'public',
        };

        return redirect()->to($this->sectionRoute($section, $parent));
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function auditFolder(
        Request $request,
        string $action,
        Folder $folder,
        array $details,
    ): void {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'resource_type' => Folder::class,
            'resource_id' => $folder->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function userData(User $user): array
    {
        return [
            'name' => trim("{$user->name} {$user->last_name}"),
            'first_name' => $user->name,
            'department' => $user->department?->name ?? 'Sin departamento',
            'avatar' => asset('assets/figma/avatar.png'),
        ];
    }

    private function fileIcon(?string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'jpg', 'jpeg', 'png' => 'file-image',
            'xls', 'xlsx', 'csv' => 'file-chart',
            'pdf' => 'file-badge',
            default => 'file-text',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $value >= 10 ? 0 : 1).' '.$unit;
            }

            $value /= 1024;
        }

        return "{$bytes} B";
    }

    private function sharingLabel(
        FileVisibility $visibility,
        ?CollaborationScope $scope,
        int $collaboratorCount,
    ): string {
        return match ($visibility) {
            FileVisibility::Private => 'Solo el propietario',
            FileVisibility::Public => 'Todas las personas con acceso',
            FileVisibility::Collaborative => $scope === CollaborationScope::Selected
                ? "{$collaboratorCount} persona(s) seleccionada(s)"
                : 'Todo el departamento',
        };
    }
}
