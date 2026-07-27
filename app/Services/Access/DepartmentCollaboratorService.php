<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentCollaboratorService
{
    public function __construct(
        private readonly AccessApiService $accessApi,
    ) {}

    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     last_name: string|null,
     *     email: string,
     *     position: string|null,
     *     role: string|null
     * }>
     */
    public function for(User $currentUser, string $token): Collection
    {
        $currentUser->loadMissing('department:id,name');

        if ($currentUser->department === null) {
            return collect();
        }

        $departmentName = $this->normalize($currentUser->department->name);
        $currentExternalId = (string) $currentUser->external_id;
        $remoteUsers = $this->accessApi->integrationUsers($token)->items;

        return DB::transaction(function () use (
            $currentUser,
            $currentExternalId,
            $departmentName,
            $remoteUsers,
        ): Collection {
            return collect($remoteUsers)
                ->filter(fn (array $remoteUser): bool => $this->isEligible(
                    $remoteUser,
                    $currentExternalId,
                    $departmentName,
                ))
                ->unique(fn (array $remoteUser): string => (string) $remoteUser['id'])
                ->map(function (array $remoteUser) use ($currentUser): array {
                    $externalId = trim((string) $remoteUser['id']);
                    $email = trim((string) $remoteUser['email']);
                    $lastName = trim(implode(' ', array_filter([
                        $this->string($remoteUser['apellido_paterno'] ?? null),
                        $this->string($remoteUser['apellido_materno'] ?? null),
                    ])));

                    $user = User::query()->updateOrCreate(
                        ['external_id' => $externalId],
                        [
                            'department_id' => $currentUser->department_id,
                            'name' => trim((string) $remoteUser['name']),
                            'last_name' => $lastName !== '' ? $lastName : null,
                            'email' => $email,
                            'active' => true,
                            'last_synced_at' => now(),
                        ],
                    );

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'position' => $this->string($remoteUser['cargo'] ?? null),
                        'role' => $this->string($remoteUser['rol'] ?? null),
                    ];
                })
                ->sortBy([
                    ['name', 'asc'],
                    ['last_name', 'asc'],
                ])
                ->values();
        });
    }

    /**
     * @param  array<string, mixed>  $remoteUser
     */
    private function isEligible(
        array $remoteUser,
        string $currentExternalId,
        string $departmentName,
    ): bool {
        $externalId = $this->string($remoteUser['id'] ?? null);
        $name = $this->string($remoteUser['name'] ?? null);
        $email = $this->string($remoteUser['email'] ?? null);
        $remoteDepartment = $this->string($remoteUser['departamento'] ?? null);

        return $externalId !== null
            && $externalId !== $currentExternalId
            && $name !== null
            && $email !== null
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && $remoteDepartment !== null
            && $this->normalize($remoteDepartment) === $departmentName
            && ($remoteUser['activo'] ?? true) !== false;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->squish()
            ->toString();
    }

    private function string(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
