<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class AdminUserService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $users = User::query()
            ->with([
                'department:id,name',
                'roles:id,name,display_name',
            ])
            ->withCount([
                'files',
                'files as trashed_files_count' => fn (Builder $query): Builder => $query->onlyTrashed(),
                'permissions',
            ])
            ->withSum('files as active_storage_bytes', 'size_bytes')
            ->withSum(
                ['files as trashed_storage_bytes' => fn (Builder $query): Builder => $query->onlyTrashed()],
                'size_bytes',
            )
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('external_id', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('department_id', $id),
            )
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn (Builder $query): Builder => $query->where(
                    'active',
                    ($filters['status'] ?? 'all') === 'active',
                ),
            )
            ->when(
                $filters['role'] ?? null,
                fn (Builder $query, string $role): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $query): Builder => $query->where('name', $role),
                ),
            )
            ->orderBy('name')
            ->orderBy('last_name')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        $users->getCollection()->each(function (User $user): void {
            $activeBytes = (int) ($user->active_storage_bytes ?? 0);
            $trashedBytes = (int) ($user->trashed_storage_bytes ?? 0);

            $user->setAttribute('active_storage', $this->formatBytes($activeBytes));
            $user->setAttribute('trashed_storage', $this->formatBytes($trashedBytes));
            $user->setAttribute('total_storage', $this->formatBytes($activeBytes + $trashedBytes));
        });

        return $users;
    }

    /**
     * @return array<string, int|string>
     */
    public function summary(): array
    {
        $storage = File::withTrashed()
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN size_bytes ELSE 0 END), 0) as trashed_bytes')
            ->firstOrFail();

        $activeBytes = (int) $storage->active_bytes;
        $trashedBytes = (int) $storage->trashed_bytes;

        return [
            'users' => User::query()->count(),
            'active_users' => User::query()->where('active', true)->count(),
            'inactive_users' => User::query()->where('active', false)->count(),
            'storage' => $this->formatBytes($activeBytes + $trashedBytes),
            'active_storage' => $this->formatBytes($activeBytes),
            'trashed_storage' => $this->formatBytes($trashedBytes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $user): array
    {
        $user->loadMissing([
            'department:id,name,abbreviation',
            'roles:id,name,display_name',
            'permissions:id,name,display_name',
        ]);

        $fileStats = File::withTrashed()
            ->where('owner_id', $user->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as trashed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN size_bytes ELSE 0 END), 0) as trashed_bytes')
            ->firstOrFail();

        $activeBytes = (int) $fileStats->active_bytes;
        $trashedBytes = (int) $fileStats->trashed_bytes;

        return [
            'listedUser' => $user,
            'summary' => [
                'active_files' => (int) $fileStats->active_count,
                'trashed_files' => (int) $fileStats->trashed_count,
                'total_files' => (int) $fileStats->active_count + (int) $fileStats->trashed_count,
                'active_storage' => $this->formatBytes($activeBytes),
                'trashed_storage' => $this->formatBytes($trashedBytes),
                'total_storage' => $this->formatBytes($activeBytes + $trashedBytes),
            ],
            'userFiles' => File::withTrashed()
                ->where('owner_id', $user->id)
                ->with([
                    'department:id,name',
                    'folder:id,name',
                ])
                ->latest('updated_at')
                ->paginate(10, ['*'], 'files_page')
                ->withQueryString(),
            'recentActivity' => $this->recentActivity($user),
        ];
    }

    public function formatBytes(int $bytes): string
    {
        return Number::fileSize(max(0, $bytes), precision: 1);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    private function recentActivity(User $user): Collection
    {
        return AuditLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(15)
            ->get();
    }
}
