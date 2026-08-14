<?php

namespace App\Services\Files;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\Sharing\CollaboratorPermissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            return DB::transaction(function () use (
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
            ): File {
                $file = File::query()->create([
                    'folder_id' => $folder?->id,
                    'owner_id' => $owner->id,
                    'department_id' => $departmentId,
                    'original_name' => $this->safeOriginalName($upload),
                    'display_name' => $this->safeOriginalName($upload),
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
            return DB::transaction(function () use (
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
}
