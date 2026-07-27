<?php

namespace App\Policies;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;

class FilePolicy
{
    public function upload(
        User $user,
        ?Folder $folder = null,
        FileVisibility $visibility = FileVisibility::Private,
    ): bool {
        if (! $this->can($user, $this->permission($visibility, 'subir'))) {
            return false;
        }

        return $this->validDestination($user, $folder);
    }

    public function view(User $user, File $file): bool
    {
        return $this->canAccess($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'ver'));
    }

    public function download(User $user, File $file): bool
    {
        return $this->canAccess($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'descargar'));
    }

    public function update(User $user, File $file): bool
    {
        return $this->canManage($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'renombrar'));
    }

    public function move(User $user, File $file, ?Folder $destination = null): bool
    {
        return $this->canManage($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'mover'))
            && $this->validDestination($user, $destination, $file->department_id)
            && ($destination === null || $destination->department_id === $file->department_id);
    }

    public function delete(User $user, File $file): bool
    {
        return $this->canManage($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'eliminar'));
    }

    public function changeVisibility(
        User $user,
        File $file,
        FileVisibility $visibility,
    ): bool {
        return $file->visibility !== $visibility
            && $this->canManage($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'publicar'));
    }

    public function restore(User $user, File $file, ?Folder $destination = null): bool
    {
        return $file->trashed()
            && $this->canManageTrashed($user, $file)
            && $this->can($user, 'nube_papelera_restaurar')
            && $this->validDestination($user, $destination, $file->department_id)
            && ($destination === null || $destination->department_id === $file->department_id);
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $file->trashed()
            && $this->canManageTrashed($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'eliminar'));
    }

    private function canAccess(User $user, File $file): bool
    {
        if ($file->trashed()) {
            return false;
        }

        return match ($file->visibility) {
            FileVisibility::Private => $file->owner_id === $user->id,
            FileVisibility::Collaborative => $this->hasCollaborativeAccess($user, $file),
            FileVisibility::Public => true,
        };
    }

    private function canManage(User $user, File $file): bool
    {
        if ($file->trashed()) {
            return false;
        }

        if ($file->visibility === FileVisibility::Collaborative) {
            return $file->department_id === $user->department_id
                && ($file->owner_id === $user->id || $this->isAreaAdmin($user));
        }

        if ($file->visibility === FileVisibility::Public
            && $this->can($user, 'nube_administracion_administrar')) {
            return true;
        }

        return $file->owner_id === $user->id;
    }

    private function validDestination(
        User $user,
        ?Folder $folder,
        ?int $resourceDepartmentId = null,
    ): bool {
        if ($folder === null) {
            return $user->department_id !== null;
        }

        $departmentId = $resourceDepartmentId ?? $user->department_id;

        return ! $folder->trashed()
            && $folder->department_id === $departmentId
            && ($folder->owner_id === $user->id
                || $this->can($user, 'nube_administracion_administrar'));
    }

    private function canManageTrashed(User $user, File $file): bool
    {
        if ($file->visibility === FileVisibility::Collaborative) {
            return $file->department_id === $user->department_id
                && ($file->owner_id === $user->id || $this->isAreaAdmin($user));
        }

        return $file->owner_id === $user->id;
    }

    private function hasCollaborativeAccess(User $user, File $file): bool
    {
        if ($file->department_id !== $user->department_id) {
            return false;
        }

        if ($file->owner_id === $user->id || $this->isAreaAdmin($user)) {
            return true;
        }

        if ($file->collaboration_scope !== CollaborationScope::Selected) {
            return true;
        }

        return $file->collaborators()->whereKey($user->id)->exists();
    }

    private function permission(FileVisibility $visibility, string $action): string
    {
        $resource = match ($visibility) {
            FileVisibility::Private => 'nube_mis_archivos',
            FileVisibility::Collaborative => 'nube_departamento',
            FileVisibility::Public => 'nube_publicos',
        };

        return "{$resource}_{$action}";
    }

    private function can(User $user, string $permission): bool
    {
        return $user->hasPermission($permission)
            || $user->hasPermission('nube_administracion_administrar');
    }

    private function isAreaAdmin(User $user): bool
    {
        return $user->hasRole('admin_area');
    }
}
