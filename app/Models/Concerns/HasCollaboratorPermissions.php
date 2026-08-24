<?php

namespace App\Models\Concerns;

use App\Enums\CollaboratorPermission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait HasCollaboratorPermissions
{
    public function collaboratorCan(
        User $user,
        CollaboratorPermission $permission,
    ): bool {
        if ($this->relationLoaded('collaborators')) {
            $collaborator = $this->collaborators->firstWhere('id', $user->id);

            return $collaborator !== null
                && ($collaborator->pivot->expires_at === null || Carbon::parse($collaborator->pivot->expires_at)->isFuture())
                && (bool) $collaborator->pivot->{$permission->pivotColumn()};
        }

        $relation = $this->collaborators();
        $pivotTable = Str::beforeLast($relation->getQualifiedForeignPivotKeyName(), '.');

        return $relation
            ->whereKey($user->id)
            ->wherePivot($permission->pivotColumn(), true)
            ->where(fn ($query) => $query
                ->whereNull("{$pivotTable}.expires_at")
                ->orWhere("{$pivotTable}.expires_at", '>', now()))
            ->exists();
    }
}
