<?php

namespace App\Policies;

use App\Enums\CollaborationScope;
use App\Enums\CollaboratorPermission;
use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\Folders\SharedFolderInheritanceService;

class FilePolicy
{
    public function upload(
        User $user,
        ?Folder $folder = null,
        FileVisibility $visibility = FileVisibility::Private,
    ): bool {
        if (($root = $this->inheritance()->rootFor($folder)) !== null) {
            return ! $folder->trashed()
                && $this->inheritance()->canAccess($user, $root);
        }
        if (! $this->can($user, $this->permission($visibility, 'subir'))) {
            return false;
        }

        if ($folder !== null
            && $visibility === FileVisibility::Private
            && $folder->owner_id === $user->id
            && ! $folder->trashed()) {
            return true;
        }

        return $this->validDestination($user, $folder);
    }

    public function view(User $user, File $file): bool
    {
        return $this->canAccess($user, $file)
            && $this->can(
                $user,
                $file->area_external_id === null
                    ? $this->permissionFor($user, $file, 'ver')
                    : 'nube_area_ver',
            );
    }

    public function download(User $user, File $file): bool
    {
        return $this->canAccess($user, $file)
            && $this->canUseCollaborativePermission(
                $user,
                $file,
                CollaboratorPermission::Download,
            )
            && $this->can($user, $this->permissionFor($user, $file, 'descargar'));
    }

    public function update(User $user, File $file): bool
    {
        return $this->canManage(
            $user,
            $file,
            CollaboratorPermission::Rename,
        )
            && $this->can($user, $this->permissionFor($user, $file, 'renombrar'));
    }

    public function move(User $user, File $file, ?Folder $destination = null): bool
    {
        if ($this->inheritance()->rootForFile($file) !== null
            || $this->inheritance()->rootFor($destination) !== null) {
            return false;
        }
        return $this->canManage(
            $user,
            $file,
            CollaboratorPermission::Move,
        )
            && $this->can($user, $this->permissionFor($user, $file, 'mover'))
            && $this->validDestination(
                $user,
                $destination,
                $file->department_id,
                allowSharedCollaborative: true,
            )
            && ($destination === null
                || ($destination->department_id === $file->department_id
                    && $destination->visibility === $file->visibility));
    }

    public function delete(User $user, File $file): bool
    {
        return $this->canManage(
            $user,
            $file,
            CollaboratorPermission::Delete,
        )
            && $this->can($user, $this->permissionFor($user, $file, 'eliminar'));
    }

    public function changeVisibility(
        User $user,
        File $file,
        FileVisibility $visibility,
    ): bool {
        if ($this->inheritance()->rootForFile($file) !== null) {
            return false;
        }
        return ($file->visibility !== $visibility
                || (in_array($visibility, [
                    FileVisibility::Private,
                    FileVisibility::Collaborative,
                ], true) && $this->canUpdateSharing($user, $file)))
            && $this->canClassify($user, $file)
            && $this->can($user, $this->permission($file->visibility, 'publicar'));
    }

    public function restore(User $user, File $file, ?Folder $destination = null): bool
    {
        if ($this->inheritance()->rootFor($destination) !== null) {
            return false;
        }
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

    public function viewAdministrative(User $user, File $file): bool
    {
        return $this->isAdministrativeOperator($user);
    }

    public function downloadAdministrative(User $user, File $file): bool
    {
        return ! $file->trashed() && $this->isAdministrativeOperator($user);
    }

    public function changeVisibilityAdministrative(
        User $user,
        File $file,
        FileVisibility $visibility,
    ): bool {
        return ! $file->trashed()
            && ($file->visibility !== $visibility
                || $visibility === FileVisibility::Collaborative)
            && $this->isAdministrativeOperator($user);
    }

    public function deleteAdministrative(User $user, File $file): bool
    {
        return ! $file->trashed() && $this->isAdministrativeOperator($user);
    }

    public function restoreAdministrative(User $user, File $file): bool
    {
        return $file->trashed() && $this->isAdministrativeOperator($user);
    }

    public function forceDeleteAdministrative(User $user, File $file): bool
    {
        return $file->trashed() && $this->isAdministrativeOperator($user);
    }

    private function canAccess(User $user, File $file): bool
    {
        if ($file->trashed()) {
            return false;
        }

        if (($root = $this->inheritance()->rootForFile($file)) !== null) {
            return $this->inheritance()->canAccess($user, $root);
        }

        return match ($file->visibility) {
            FileVisibility::Private => $file->owner_id === $user->id
                || ($file->collaboration_scope === CollaborationScope::Selected
                    && $file->collaboratorCan($user, CollaboratorPermission::View)),
            FileVisibility::Collaborative => $this->hasCollaborativeAccess($user, $file),
            FileVisibility::Public => true,
        };
    }

    private function canManage(
        User $user,
        File $file,
        CollaboratorPermission $permission,
    ): bool {
        if ($file->trashed()) {
            return false;
        }

        if (($root = $this->inheritance()->rootForFile($file)) !== null) {
            return $this->inheritance()->canAccess($user, $root)
                && ($root->owner_id === $user->id || $this->isAreaAdmin($user)
                    || $root->collaboratorCan($user, $permission));
        }

        if ($file->visibility === FileVisibility::Collaborative) {
            if ($file->area_external_id !== null && ! $this->isInArea($file->area_external_id)) {
                return $file->collaboration_scope === CollaborationScope::Selected
                    && $file->collaboratorCan($user, $permission);
            }
            return ($file->department_id === $user->department_id
                    && ($file->owner_id === $user->id || $this->isAreaAdmin($user)))
                || ($file->collaboration_scope === CollaborationScope::Selected
                    && $file->collaboratorCan($user, $permission));
        }

        if ($file->visibility === FileVisibility::Private
            && $file->collaboration_scope === CollaborationScope::Selected
            && $file->collaboratorCan($user, $permission)) {
            return true;
        }

        if ($file->visibility === FileVisibility::Public
            && $this->can($user, 'nube_administracion_administrar')) {
            return true;
        }

        return $file->owner_id === $user->id;
    }

    private function canClassify(User $user, File $file): bool
    {
        if ($file->trashed()) {
            return false;
        }

        if ($this->inheritance()->rootForFile($file) !== null) {
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

    private function canUpdateSharing(User $user, File $file): bool
    {
        return $file->owner_id === $user->id || $this->isAreaAdmin($user);
    }

    private function validDestination(
        User $user,
        ?Folder $folder,
        ?int $resourceDepartmentId = null,
        bool $allowSharedCollaborative = false,
    ): bool {
        if ($folder === null) {
            return $user->department_id !== null;
        }

        $departmentId = $resourceDepartmentId ?? $user->department_id;

        return ! $folder->trashed()
            && $folder->department_id === $departmentId
            && (
                $folder->owner_id === $user->id
                || $this->can($user, 'nube_administracion_administrar')
                || (
                    $allowSharedCollaborative
                    && $folder->visibility === FileVisibility::Collaborative
                    && (
                        ($folder->department_id === $user->department_id && $this->isAreaAdmin($user))
                        || ($folder->department_id === $user->department_id
                            && $folder->collaboration_scope !== CollaborationScope::Selected)
                        || $folder->collaboratorCan(
                            $user,
                            CollaboratorPermission::View,
                        )
                    )
                )
            );
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
        if ($file->area_external_id !== null) {
            return ($this->isInArea($file->area_external_id) && $this->can($user, 'nube_area_ver'))
                || ($file->collaboration_scope === CollaborationScope::Selected
                    && $file->collaboratorCan($user, CollaboratorPermission::View));
        }

        return $this->canUseCollaborativePermission(
            $user,
            $file,
            CollaboratorPermission::View,
        );
    }

    private function isInArea(string $areaExternalId): bool
    {
        $currentArea = session('access.department.children.0.id');

        return (is_string($currentArea) || is_int($currentArea))
            && (string) $currentArea === $areaExternalId;
    }

    private function canUseCollaborativePermission(
        User $user,
        File $file,
        CollaboratorPermission $permission,
    ): bool {
        if (($root = $this->inheritance()->rootForFile($file)) !== null) {
            return $this->inheritance()->canAccess($user, $root)
                && ($root->owner_id === $user->id || $this->isAreaAdmin($user)
                    || $root->collaboratorCan($user, $permission));
        }
        if ($file->visibility === FileVisibility::Private
            && $file->collaboration_scope === CollaborationScope::Selected) {
            return $file->owner_id === $user->id || $file->collaboratorCan($user, $permission);
        }

        if ($file->visibility !== FileVisibility::Collaborative) {
            return true;
        }

        if ($file->department_id === $user->department_id
            && ($file->owner_id === $user->id || $this->isAreaAdmin($user))) {
            return true;
        }

        if ($file->collaboration_scope !== CollaborationScope::Selected) {
            return $file->department_id === $user->department_id
                && in_array($permission, [
                    CollaboratorPermission::View,
                    CollaboratorPermission::Download,
                ], true);
        }

        return $file->collaboratorCan($user, $permission);
    }

    private function permission(FileVisibility $visibility, string $action): string
    {
        if ($visibility === FileVisibility::Private) {
            return match ($action) {
                'subir' => 'nube.archivos.subir',
                'descargar' => 'nube.archivos.descargar',
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

    private function permissionFor(User $user, File $file, string $action): string
    {
        if ($file->visibility === FileVisibility::Private
            && $file->owner_id !== $user->id
            && $file->collaboration_scope === CollaborationScope::Selected) {
            return "nube_departamento_{$action}";
        }

        return $this->permission($file->visibility, $action);
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
