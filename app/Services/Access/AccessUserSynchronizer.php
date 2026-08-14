<?php

namespace App\Services\Access;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\Data\AccessAuthData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnexpectedValueException;

class AccessUserSynchronizer
{
    /**
     * @param  bool  $isLogin  Sólo un inicio de sesión real actualiza `last_login_at`;
     *                         la revalidación periódica de sesión únicamente sincroniza.
     */
    public function synchronize(AccessAuthData $authData, bool $isLogin = false): User
    {
        return DB::transaction(function () use ($authData, $isLogin): User {
            $now = now();
            $department = $this->synchronizeDepartment(
                is_array($authData->user['departamento'] ?? null)
                    ? $authData->user['departamento']
                    : [],
            );

            $externalId = $this->requiredString($authData->user['id'] ?? null, 'user.id');
            $email = $this->requiredString($authData->user['email'] ?? null, 'user.email');
            $name = $this->requiredString($authData->user['name'] ?? null, 'user.name');
            $lastName = trim(implode(' ', array_filter([
                $this->optionalString($authData->user['apellido_paterno'] ?? null),
                $this->optionalString($authData->user['apellido_materno'] ?? null),
            ])));

            $user = User::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'department_id' => $department?->id,
                    'name' => $name,
                    'last_name' => $lastName !== '' ? $lastName : null,
                    'email' => $email,
                    'active' => true,
                    'last_synced_at' => $now,
                    ...($isLogin ? ['last_login_at' => $now] : []),
                ],
            );

            $roleIds = collect($authData->roles)
                ->map(fn (string $role): int => Role::updateOrCreate(
                    ['name' => $role],
                    ['display_name' => $this->displayName($role)],
                )->id)
                ->all();

            $permissionIds = collect($authData->permissions)
                ->map(fn (string $permission): int => Permission::updateOrCreate(
                    ['name' => $permission],
                    ['display_name' => $this->displayName($permission)],
                )->id)
                ->all();

            $user->roles()->syncWithPivotValues($roleIds, ['created_at' => $now]);
            $user->permissions()->syncWithPivotValues($permissionIds, ['created_at' => $now]);

            return $user->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $departmentData
     */
    private function synchronizeDepartment(array $departmentData): ?Department
    {
        $parentData = is_array($departmentData['departamento_padre'] ?? null)
            ? $departmentData['departamento_padre']
            : null;
        $childData = is_array($departmentData['departamento_hijo'] ?? null)
            ? $departmentData['departamento_hijo']
            : null;

        $parent = $parentData === null ? null : $this->upsertDepartment($parentData);

        if ($childData === null) {
            return $parent;
        }

        return $this->upsertDepartment($childData, $parent);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertDepartment(array $data, ?Department $parent = null): Department
    {
        $externalId = $this->requiredString($data['id'] ?? null, 'department.id');

        return Department::updateOrCreate(
            ['external_id' => $externalId],
            [
                'parent_id' => $parent?->id,
                'parent_external_id' => $parent?->external_id,
                'name' => $this->requiredString($data['nombre'] ?? null, 'department.nombre'),
                'abbreviation' => $this->optionalString($data['siglas'] ?? null),
                'active' => (bool) ($data['activo'] ?? true),
                'last_synced_at' => now(),
            ],
        );
    }

    private function requiredString(mixed $value, string $field): string
    {
        $value = $this->optionalString($value);

        if ($value === null) {
            throw new UnexpectedValueException("Missing required access field [{$field}].");
        }

        return $value;
    }

    private function optionalString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function displayName(string $value): string
    {
        return Str::of($value)->replace(['_', '.'], ' ')->headline()->toString();
    }
}
