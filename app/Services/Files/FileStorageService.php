<?php

namespace App\Services\Files;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Notifications\DepartmentFileUploadedNotification;
use App\Notifications\FileSharedNotification;
use App\Notifications\PublicFileUploadedNotification;
use App\Services\Sharing\CollaboratorPermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification as NotificationBase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FileStorageService
{
    public function __construct(
        private readonly CollaboratorPermissionService $collaboratorPermissions,
    ) {}

    public function upload(
        UploadedFile $upload,
        User $owner,
        ?Folder $folder,
        FileVisibility $visibility = FileVisibility::Private,
        ?CollaborationScope $collaborationScope = null,
        array $collaboratorIds = [],
        array $permissionsByUser = [],
        ?string $displayName = null,
    ): File {
        $extension = strtolower($upload->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().($extension === '' ? '' : ".{$extension}");
        $departmentId = $folder?->department_id ?? $owner->department_id;
        $directory = $this->directoryFor(
            $owner,
            $folder,
            $visibility,
            $departmentId,
        );
        $path = Storage::disk('nube')->putFileAs($directory, $upload, $storedName);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('No fue posible guardar el archivo.');
        }

        try {
            $file = DB::transaction(function () use (
                $upload,
                $owner,
                $folder,
                $storedName,
                $path,
                $extension,
                $visibility,
                $collaborationScope,
                $collaboratorIds,
                $permissionsByUser,
                $departmentId,
                $displayName,
            ): File {
                $safeOriginalName = $this->safeOriginalName($upload);

                $file = File::query()->create([
                    'folder_id' => $folder?->id,
                    'owner_id' => $owner->id,
                    'department_id' => $departmentId,
                    'original_name' => $safeOriginalName,
                    'display_name' => $displayName ?? $safeOriginalName,
                    'stored_name' => $storedName,
                    'disk' => 'nube',
                    'path' => $path,
                    'extension' => $extension,
                    'mime_type' => $upload->getMimeType(),
                    'size_bytes' => $upload->getSize(),
                    'visibility' => $visibility,
                    'collaboration_scope' => $visibility === FileVisibility::Collaborative
                        ? ($collaborationScope ?? CollaborationScope::Department)
                        : null,
                    'checksum' => hash_file('sha256', $upload->getRealPath()) ?: null,
                    'uploaded_at' => now(),
                ]);

                $file->collaborators()->sync(
                    $visibility === FileVisibility::Collaborative
                        && $collaborationScope === CollaborationScope::Selected
                            ? $this->collaboratorPermissions->pivotData(
                                $collaboratorIds,
                                $permissionsByUser,
                            )
                            : [],
                );

                return $file;
            });
        } catch (Throwable $exception) {
            Storage::disk('nube')->delete($path);

            throw $exception;
        }

        $this->notifyUpload($file, $owner, $collaborationScope, $collaboratorIds);

        return $file;
    }

    public function rename(File $file, string $displayName): File
    {
        return DB::transaction(function () use ($file, $displayName): File {
            $file->update(['display_name' => $displayName]);

            return $file->refresh();
        });
    }

    public function move(File $file, ?Folder $destination): File
    {
        $oldPath = $file->path;
        $newPath = $this->directoryFor(
            $file->owner,
            $destination,
            $file->visibility,
            $file->department_id,
        ).'/'.$file->stored_name;

        if ($oldPath === $newPath) {
            return $file;
        }

        $this->movePhysicalFile($file->disk, $oldPath, $newPath);

        try {
            return DB::transaction(function () use ($file, $destination, $newPath): File {
                $file->update([
                    'folder_id' => $destination?->id,
                    'path' => $newPath,
                ]);

                return $file->refresh();
            });
        } catch (Throwable $exception) {
            $this->rollbackPhysicalMove($file->disk, $newPath, $oldPath);

            throw $exception;
        }
    }

    public function delete(File $file): File
    {
        $oldPath = $file->path;
        $trashPath = $this->trashDirectory($file->owner).'/'.$file->stored_name;

        $this->movePhysicalFile($file->disk, $oldPath, $trashPath);

        try {
            DB::transaction(function () use ($file, $trashPath): void {
                $file->update(['path' => $trashPath]);
                $file->delete();
            });

            return $file;
        } catch (Throwable $exception) {
            $this->rollbackPhysicalMove($file->disk, $trashPath, $oldPath);

            throw $exception;
        }
    }

    public function restore(File $file, ?Folder $destination): File
    {
        $trashPath = $file->path;
        $restoredPath = $this->directoryFor(
            $file->owner,
            $destination,
            $file->visibility,
            $file->department_id,
        ).'/'.$file->stored_name;

        $this->movePhysicalFile($file->disk, $trashPath, $restoredPath);

        try {
            return DB::transaction(function () use ($file, $destination, $restoredPath): File {
                $file->restore();
                $file->update([
                    'folder_id' => $destination?->id,
                    'path' => $restoredPath,
                ]);

                return $file->refresh();
            });
        } catch (Throwable $exception) {
            $this->rollbackPhysicalMove($file->disk, $restoredPath, $trashPath);

            throw $exception;
        }
    }

    public function exists(File $file): bool
    {
        return Storage::disk($file->disk)->exists($file->path);
    }

    public function changeVisibility(
        File $file,
        FileVisibility $visibility,
        ?CollaborationScope $collaborationScope = null,
        array $collaboratorIds = [],
        array $permissionsByUser = [],
    ): File {
        $previousCollaboratorIds = $file->collaboration_scope === CollaborationScope::Selected
            ? $file->collaborators()->pluck('id')->all()
            : [];
        $oldPath = $file->path;
        $sameVisibility = $file->visibility === $visibility;
        $newPath = $sameVisibility
            ? $oldPath
            : $this->directoryFor(
                $file->owner,
                null,
                $visibility,
                $file->department_id,
            ).'/'.$file->stored_name;
        $physicalMoveRequired = $oldPath !== $newPath;

        if ($physicalMoveRequired) {
            $this->movePhysicalFile($file->disk, $oldPath, $newPath);
        }

        try {
            $updatedFile = DB::transaction(function () use (
                $file,
                $visibility,
                $newPath,
                $collaborationScope,
                $collaboratorIds,
                $permissionsByUser,
                $sameVisibility,
            ): File {
                $file->update([
                    'folder_id' => $sameVisibility ? $file->folder_id : null,
                    'visibility' => $visibility,
                    'collaboration_scope' => $visibility === FileVisibility::Collaborative
                        ? ($collaborationScope ?? CollaborationScope::Department)
                        : null,
                    'path' => $newPath,
                ]);

                $file->collaborators()->sync(
                    $visibility === FileVisibility::Collaborative
                        && $collaborationScope === CollaborationScope::Selected
                            ? $this->collaboratorPermissions->pivotData(
                                $collaboratorIds,
                                $permissionsByUser,
                            )
                            : [],
                );

                return $file->refresh();
            });
        } catch (Throwable $exception) {
            if ($physicalMoveRequired) {
                $this->rollbackPhysicalMove($file->disk, $newPath, $oldPath);
            }

            throw $exception;
        }

        if ($visibility === FileVisibility::Collaborative && $collaborationScope === CollaborationScope::Selected) {
            $newCollaboratorIds = array_values(array_diff($collaboratorIds, $previousCollaboratorIds));
            $this->notifySharedCollaborators($updatedFile, $newCollaboratorIds);
        }

        return $updatedFile;
    }

    public function forceDelete(File $file): void
    {
        if (! $file->trashed()) {
            throw new RuntimeException('El archivo debe estar en la papelera.');
        }

        $filesystem = Storage::disk($file->disk);

        if ($filesystem->exists($file->path) && ! $filesystem->delete($file->path)) {
            throw new RuntimeException('No fue posible eliminar el archivo físico.');
        }

        $file->forceDelete();
    }

    private function directoryFor(
        User $owner,
        ?Folder $folder,
        FileVisibility $visibility,
        ?int $departmentId = null,
    ): string {
        $location = $folder === null ? 'raiz' : "carpetas/{$folder->id}";
        $departmentId ??= $owner->department_id;

        return match ($visibility) {
            FileVisibility::Private => "departamentos/{$departmentId}/usuarios/{$owner->id}/privados/{$location}",
            FileVisibility::Collaborative => "departamentos/{$departmentId}/colaborativos/{$location}",
            FileVisibility::Public => "departamentos/{$departmentId}/publicos/{$location}",
        };
    }

    private function trashDirectory(User $owner): string
    {
        return "papelera/usuarios/{$owner->id}";
    }

    private function safeOriginalName(UploadedFile $upload): string
    {
        $name = trim(basename(str_replace('\\', '/', $upload->getClientOriginalName())));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return Str::limit(
            $name === '' || in_array($name, ['.', '..'], true) ? 'archivo' : $name,
            255,
            '',
        );
    }

    private function movePhysicalFile(string $disk, string $from, string $to): void
    {
        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($from)) {
            throw new RuntimeException('El archivo físico no está disponible.');
        }

        if ($filesystem->exists($to) || ! $filesystem->move($from, $to)) {
            throw new RuntimeException('No fue posible mover el archivo físico.');
        }
    }

    private function rollbackPhysicalMove(string $disk, string $from, string $to): void
    {
        $filesystem = Storage::disk($disk);

        if ($filesystem->exists($from) && ! $filesystem->exists($to)) {
            $filesystem->move($from, $to);
        }
    }

    /**
     * @param  list<int>  $collaboratorIds
     */
    private function notifyUpload(
        File $file,
        User $owner,
        ?CollaborationScope $collaborationScope,
        array $collaboratorIds,
    ): void {
        if ($file->visibility === FileVisibility::Public) {
            $this->notifyRecipients(
                $this->activeUsersWithPermission('nube_publicos_ver', except: $owner->id),
                new PublicFileUploadedNotification($file, $owner),
            );

            return;
        }

        if ($file->visibility !== FileVisibility::Collaborative) {
            return;
        }

        if ($collaborationScope === CollaborationScope::Selected) {
            $this->notifySharedCollaborators($file, $collaboratorIds, $owner);

            return;
        }

        $this->notifyRecipients(
            $this->activeUsersWithPermission(
                'nube_departamento_ver',
                except: $owner->id,
                departmentId: $file->department_id,
            ),
            new DepartmentFileUploadedNotification($file, $owner),
        );
    }

    /**
     * @param  list<int>  $collaboratorIds
     */
    private function notifySharedCollaborators(
        File $file,
        array $collaboratorIds,
        ?User $actor = null,
    ): void {
        if ($collaboratorIds === []) {
            return;
        }

        $actor ??= Auth::user();

        if (! $actor instanceof User) {
            return;
        }

        $recipients = User::query()
            ->whereIn('id', $collaboratorIds)
            ->where('id', '!=', $actor->id)
            ->get();

        $this->notifyRecipients($recipients, new FileSharedNotification($file, $actor));
    }

    /**
     * @return EloquentCollection<int, User>
     */
    private function activeUsersWithPermission(
        string $permission,
        int $except,
        ?int $departmentId = null,
    ): EloquentCollection {
        return User::query()
            ->where('active', true)
            ->where('id', '!=', $except)
            ->when(
                $departmentId !== null,
                fn (Builder $query): Builder => $query->where('department_id', $departmentId),
            )
            ->whereHas(
                'permissions',
                fn (Builder $query): Builder => $query->where('name', $permission),
            )
            ->get();
    }

    /**
     * @param  EloquentCollection<int, User>  $recipients
     */
    private function notifyRecipients(EloquentCollection $recipients, NotificationBase $notification): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, $notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
