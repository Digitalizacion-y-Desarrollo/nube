<?php

namespace App\Policies;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        if ($folder->trashed()) {
            return false;
        }

        return match ($folder->visibility) {
            FileVisibility::Private => $folder->owner_id === $user->id
                && $this->can($user, 'nube_mis_archivos_ver'),
            FileVisibility::Collaborative => $this->hasCollaborativeAccess($user, $folder)
                && $this->can($user, 'nube_departamento_ver'),
            FileVisibility::Public => $this->can($user, 'nube_publicos_ver'),
        };
    }

    public function create(
        User $user,
        ?Folder $parent = null,
        FileVisibility $visibility = FileVisibility::Private,
    ): bool {
        if (! $this->can($user, $this->permission($visibility, 'crear_carpeta'))) {
            return false;
        }

        if ($parent === null) {
            return $user->department_id !== null;
        }

        return $parent->department_id === $user->department_id
            && $this->canManage($user, $parent);
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->can($user, $this->permission($folder->visibility, 'renombrar'))
            && $this->canManage($user, $folder);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->can($user, $this->permission($folder->visibility, 'eliminar'))
            && $this->canManage($user, $folder);
    }

    public function changeVisibility(
        User $user,
        Folder $folder,
        FileVisibility $visibility,
    ): bool {
        return $folder->visibility !== $visibility
            && $this->canManage($user, $folder)
            && $this->can($user, $this->permission($folder->visibility, 'publicar'));
    }

    private function canManage(User $user, Folder $folder): bool
    {
        if ($folder->trashed()) {
            return false;
        }

        if ($folder->visibility === FileVisibility::Collaborative) {
            return $folder->department_id === $user->department_id
                && ($folder->owner_id === $user->id || $this->isAreaAdmin($user));
        }

        if ($folder->visibility === FileVisibility::Public
            && $this->can($user, 'nube_administracion_administrar')) {
            return true;
        }

        return $folder->owner_id === $user->id;
    }

    private function hasCollaborativeAccess(User $user, Folder $folder): bool
    {
        if ($folder->department_id !== $user->department_id) {
            return false;
        }

        if ($folder->owner_id === $user->id || $this->isAreaAdmin($user)) {
            return true;
        }

        if ($folder->collaboration_scope !== CollaborationScope::Selected) {
            return true;
        }

        return $folder->collaborators()->whereKey($user->id)->exists();
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
