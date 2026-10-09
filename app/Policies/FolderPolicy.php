<?php

namespace App\Policies;

use App\Enums\CollaborationScope;
use App\Enums\CollaboratorPermission;
use App\Enums\FileVisibility;
use App\Models\Folder;
use App\Models\User;
use App\Services\Folders\SharedFolderInheritanceService;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        if ($folder->trashed()) {
            return false;
        }

        if (($root = $this->inheritance()->rootFor($folder)) !== null) {
            return $this->inheritance()->canAccess($user, $root)
                && $this->can($user, $this->permissionFor($user, $root, 'ver'));
        }

        return match ($folder->visibility) {
            FileVisibility::Private => ($folder->owner_id === $user->id
                    || ($folder->collaboration_scope === CollaborationScope::Selected
                        && $folder->collaboratorCan($user, CollaboratorPermission::View)))
                && $this->can($user, $this->permissionFor($user, $folder, 'ver')),
            FileVisibility::Collaborative => $this->hasCollaborativeAccess($user, $folder)
                && $this->can($user, $folder->area_external_id === null ? 'nube_departamento_ver' : 'nube_area_ver'),
            FileVisibility::Public => $this->can($user, 'nube_publicos_ver'),
        };
    }

    public function create(
        User $user,
        ?Folder $parent = null,
        FileVisibility $visibility = FileVisibility::Private,
    ): bool {
        if (($root = $this->inheritance()->rootFor($parent)) !== null) {
            return ! $parent->trashed()
                && $this->inheritance()->canAccess($user, $root)
                && ($root->owner_id === $user->id || $this->isAreaAdmin($user)
                    || $root->collaboratorCan($user, CollaboratorPermission::CreateFolder));
        }
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
        return $this->can($user, $this->permissionFor($user, $folder, 'renombrar'))
            && $this->canManage(
                $user,
                $folder,
                CollaboratorPermission::Rename,
            );
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->can($user, $this->permissionFor($user, $folder, 'eliminar'))
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
        if ($this->inheritance()->rootFor($folder) !== null
            || $this->inheritance()->rootFor($destination) !== null) {
            return false;
        }
        if (! $this->can($user, $this->permissionFor($user, $folder, 'mover'))
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
        $isSharedRootPermissionUpdate = $folder->collaboration_scope === CollaborationScope::Selected
            && ($root = $this->inheritance()->rootFor($folder)) !== null
            && $root->id === $folder->id;

        if ($isSharedRootPermissionUpdate && $folder->visibility === $visibility) {
            return $this->canClassify($user, $folder);
        }

        return ($folder->visibility !== $visibility
                || (in_array($visibility, [
                    FileVisibility::Private,
                    FileVisibility::Collaborative,
                ], true) && $this->canUpdateSharing($user, $folder))
                || $isSharedRootPermissionUpdate)
            && $this->canClassify($user, $folder)
            && $this->can($user, $this->permission($folder->visibility, 'publicar'));
    }

    public function viewAdministrative(User $user, Folder $folder): bool
    {
        return $this->isAdministrativeOperator($user);
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

        if (($root = $this->inheritance()->rootFor($folder)) !== null) {
            return $this->inheritance()->canAccess($user, $root)
                && ($root->owner_id === $user->id || $this->isAreaAdmin($user)
                    || $root->collaboratorCan($user, $permission));
        }

        if ($folder->visibility === FileVisibility::Collaborative) {
            if ($folder->area_external_id !== null && ! $this->isInArea($folder->area_external_id)) {
                return $folder->collaboration_scope === CollaborationScope::Selected
                    && $folder->collaboratorCan($user, $permission);
            }
            return ($folder->department_id === $user->department_id
                    && ($folder->owner_id === $user->id || $this->isAreaAdmin($user)))
                || ($folder->collaboration_scope === CollaborationScope::Selected
                    && $folder->collaboratorCan($user, $permission));
        }

        if ($folder->visibility === FileVisibility::Private
            && $folder->collaboration_scope === CollaborationScope::Selected
            && $folder->collaboratorCan($user, $permission)) {
            return true;
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

        if ($this->inheritance()->isInherited($folder)) {
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

    private function canUpdateSharing(User $user, Folder $folder): bool
    {
        return $folder->owner_id === $user->id || $this->isAreaAdmin($user);
    }

    private function hasCollaborativeAccess(User $user, Folder $folder): bool
    {
        if ($folder->area_external_id !== null) {
            return ($this->isInArea($folder->area_external_id) && $this->can($user, 'nube_area_ver'))
                || ($folder->collaboration_scope === CollaborationScope::Selected
                    && $folder->collaboratorCan($user, CollaboratorPermission::View));
        }

        if ($folder->department_id === $user->department_id
            && ($folder->owner_id === $user->id || $this->isAreaAdmin($user))) {
            return true;
        }

        if ($folder->collaboration_scope !== CollaborationScope::Selected) {
            return $folder->department_id === $user->department_id;
        }

        return $folder->collaboratorCan(
            $user,
            CollaboratorPermission::View,
        );
    }

    private function isInArea(string $areaExternalId): bool
    {
        $currentArea = session('access.department.children.0.id');

        return (is_string($currentArea) || is_int($currentArea))
            && (string) $currentArea === $areaExternalId;
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

    private function permissionFor(User $user, Folder $folder, string $action): string
    {
        if ($folder->visibility === FileVisibility::Private
            && $folder->owner_id !== $user->id
            && $folder->collaboration_scope === CollaborationScope::Selected) {
            return "nube_departamento_{$action}";
        }

        return $this->permission($folder->visibility, $action);
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

    private function inheritance(): SharedFolderInheritanceService
    {
        return app(SharedFolderInheritanceService::class);
    }
}
