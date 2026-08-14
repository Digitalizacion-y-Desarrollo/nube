<?php

namespace App\Policies;

use App\Enums\CollaborationScope;
use App\Enums\CollaboratorPermission;
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

        if ($visibility === FileVisibility::Private
            && $parent->owner_id === $user->id
            && ! $parent->trashed()) {
            return true;
        }

        return $parent->department_id === $user->department_id
            && $this->canClassify($user, $parent);
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->can($user, $this->permission($folder->visibility, 'renombrar'))
            && $this->canManage(
                $user,
                $folder,
                CollaboratorPermission::Rename,
            );
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->can($user, $this->permission($folder->visibility, 'eliminar'))
            && $this->canManage(
                $user,
                $folder,
                CollaboratorPermission::Delete,
            );
    }

    public function move(
        User $user,
        Folder $folder,
        ?Folder $destination = null,
    ): bool {
        if (! $this->can($user, $this->permission($folder->visibility, 'mover'))
            || ! $this->canManage($user, $folder, CollaboratorPermission::Move)) {
            return false;
        }

        if ($destination === null) {
            return true;
        }

        return ! $destination->trashed()
            && $destination->id !== $folder->id
            && $destination->department_id === $folder->department_id
            && $destination->visibility === $folder->visibility
            && $this->canViewCollaborativeDestination($user, $destination);
    }

    public function changeVisibility(
        User $user,
        Folder $folder,
        FileVisibility $visibility,
    ): bool {
        return $folder->visibility !== $visibility
            && $this->canClassify($user, $folder)
            && $this->can($user, $this->permission($folder->visibility, 'publicar'));
    }

    public function viewAdministrative(User $user, Folder $folder): bool
    {
        return $user->hasRole('superuser');
    }

    public function restoreAdministrative(User $user, Folder $folder): bool
    {
        return $folder->trashed() && $this->isAdministrativeOperator($user);
    }

    public function forceDeleteAdministrative(User $user, Folder $folder): bool
    {
        return $folder->trashed() && $this->isAdministrativeOperator($user);
    }

    private function canManage(
        User $user,
        Folder $folder,
        CollaboratorPermission $permission,
    ): bool {
        if ($folder->trashed()) {
            return false;
        }

        if ($folder->visibility === FileVisibility::Collaborative) {
            return $folder->department_id === $user->department_id
                && ($folder->owner_id === $user->id
                    || $this->isAreaAdmin($user)
                    || ($folder->collaboration_scope === CollaborationScope::Selected
                        && $folder->collaboratorCan($user, $permission)));
        }

        if ($folder->visibility === FileVisibility::Public
            && $this->can($user, 'nube_administracion_administrar')) {
            return true;
        }

        return $folder->owner_id === $user->id;
    }

    private function canClassify(User $user, Folder $folder): bool
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

        return $folder->collaboratorCan(
            $user,
            CollaboratorPermission::View,
        );
    }

    private function canViewCollaborativeDestination(
        User $user,
        Folder $folder,
    ): bool {
        if ($folder->visibility !== FileVisibility::Collaborative) {
            return $folder->owner_id === $user->id
                || $this->can($user, 'nube_administracion_administrar');
        }

        return $this->hasCollaborativeAccess($user, $folder);
    }

    private function permission(FileVisibility $visibility, string $action): string
    {
        if ($visibility === FileVisibility::Private) {
            return match ($action) {
                'crear_carpeta' => 'nube_archivos_crear_carpeta',
                'eliminar' => 'nube.archivos.eliminar',
                'publicar' => 'nube.archivos.publicar',
                default => "nube_mis_archivos_{$action}",
            };
        }

        $resource = match ($visibility) {
            FileVisibility::Collaborative => 'nube_departamento',
            FileVisibility::Public => 'nube_publicos',
            FileVisibility::Private => throw new \LogicException('La visibilidad privada ya fue resuelta.'),
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

    private function isAdministrativeOperator(User $user): bool
    {
        return $user->hasRole('superuser')
            && $user->hasPermission('nube_administracion_administrar');
    }
}
