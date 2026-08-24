<?php

namespace App\Services\Sharing;

use App\Enums\CollaboratorPermission;
use Illuminate\Support\Carbon;

class CollaboratorPermissionService
{
    /**
     * @param  list<int>  $collaboratorIds
     * @param  array<int|string, list<string>>  $permissionsByUser
     * @return array<int, array<string, bool|Carbon>>
     */
    public function pivotData(
        array $collaboratorIds,
        array $permissionsByUser = [],
        ?string $expiresAt = null,
    ): array {
        return collect($collaboratorIds)
            ->mapWithKeys(function (int $userId) use ($permissionsByUser, $expiresAt): array {
                $assigned = $permissionsByUser[$userId]
                    ?? $permissionsByUser[(string) $userId]
                    ?? CollaboratorPermission::defaults();

                return [$userId => $this->attributes($assigned, $expiresAt)];
            })
            ->all();
    }

    /**
     * @param  list<string>  $assigned
     * @return array<string, bool|Carbon>
     */
    private function attributes(array $assigned, ?string $expiresAt = null): array
    {
        $assigned = array_values(array_unique($assigned));
        $attributes = ['created_at' => now(), 'expires_at' => $expiresAt];

        foreach (CollaboratorPermission::cases() as $permission) {
            $attributes[$permission->pivotColumn()] = in_array(
                $permission->value,
                $assigned,
                true,
            );
        }

        return $attributes;
    }
}
