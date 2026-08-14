<?php

namespace App\Models\Concerns;

use App\Enums\CollaboratorPermission;
use App\Models\User;

trait HasCollaboratorPermissions
{
    public function collaboratorCan(
        User $user,
        CollaboratorPermission $permission,
    ): bool {
        if ($this->relationLoaded('collaborators')) {
            $collaborator = $this->collaborators->firstWhere('id', $user->id);

            return $collaborator !== null
                && (bool) $collaborator->pivot->{$permission->pivotColumn()};
        }

        return $this->collaborators()
            ->whereKey($user->id)
            ->wherePivot($permission->pivotColumn(), true)
            ->exists();
    }
}
