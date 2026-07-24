<?php

namespace App\Services\Access\Data;

final readonly class AccessAuthData
{
    /**
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>|null  $system
     * @param  list<string>  $roles
     * @param  list<string>  $permissions
     */
    public function __construct(
        public ?string $accessToken,
        public string $tokenType,
        public array $user,
        public ?array $system,
        public array $roles,
        public array $permissions,
    ) {}
}
