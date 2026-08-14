<?php

namespace App\Services\Admin;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class AdminDepartmentService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Department::query()
            ->with('parent:id,name')
            ->withCount([
                'users',
                'users as active_users_count' => fn (Builder $query): Builder => $query->where('active', true),
                'files',
                'files as trashed_files_count' => fn (Builder $query): Builder => $query->onlyTrashed(),
                'folders',
                'folders as trashed_folders_count' => fn (Builder $query): Builder => $query->onlyTrashed(),
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
                        ->orWhere('abbreviation', 'like', "%{$search}%");
                });
            })
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn (Builder $query): Builder => $query->where(
                    'active',
                    ($filters['status'] ?? 'all') === 'active',
                ),
            )
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Department $department): array
    {
        $department->loadMissing([
            'parent:id,name',
            'children:id,parent_id,name,active',
        ]);

        $fileStats = File::withTrashed()
            ->where('department_id', $department->id)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as trashed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN size_bytes ELSE 0 END), 0) as trashed_bytes')
            ->firstOrFail();

        $folderStats = Folder::withTrashed()
            ->where('department_id', $department->id)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as trashed_count')
            ->firstOrFail();

        $users = User::query()
            ->where('department_id', $department->id)
            ->with('roles:id,name,display_name')
            ->orderBy('name')
            ->orderBy('last_name')
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();

        $files = File::query()
            ->where('department_id', $department->id)
            ->whereIn('visibility', [
                FileVisibility::Collaborative->value,
                FileVisibility::Public->value,
            ])
            ->with([
                'owner:id,name,last_name,email',
                'folder:id,name',
            ])
            ->latest('updated_at')
            ->paginate(10, ['*'], 'files_page')
            ->withQueryString();

        return [
            'department' => $department,
            'summary' => [
                'users' => User::query()->where('department_id', $department->id)->count(),
                'active_users' => User::query()
                    ->where('department_id', $department->id)
                    ->where('active', true)
                    ->count(),
                'files' => (int) $fileStats->active_count,
                'trashed_files' => (int) $fileStats->trashed_count,
                'folders' => (int) $folderStats->active_count,
                'trashed_folders' => (int) $folderStats->trashed_count,
                'active_bytes' => (int) $fileStats->active_bytes,
                'trashed_bytes' => (int) $fileStats->trashed_bytes,
                'total_bytes' => (int) $fileStats->active_bytes + (int) $fileStats->trashed_bytes,
                'active_storage' => $this->formatBytes((int) $fileStats->active_bytes),
                'trashed_storage' => $this->formatBytes((int) $fileStats->trashed_bytes),
                'total_storage' => $this->formatBytes(
                    (int) $fileStats->active_bytes + (int) $fileStats->trashed_bytes,
                ),
            ],
            'departmentUsers' => $users,
            'departmentFiles' => $files,
            'recentActivity' => $this->recentActivity($department),
        ];
    }

    public function formatBytes(int $bytes): string
    {
        return Number::fileSize(max(0, $bytes), precision: 1);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    private function recentActivity(Department $department): Collection
    {
        return AuditLog::query()
            ->with('user:id,name,last_name,email')
            ->where(function (Builder $query) use ($department): void {
                $query
                    ->whereIn(
                        'user_id',
                        User::query()
                            ->select('id')
                            ->where('department_id', $department->id),
                    )
                    ->orWhere(function (Builder $query) use ($department): void {
                        $query
                            ->where('resource_type', File::class)
                            ->whereIn(
                                'resource_id',
                                File::withTrashed()
                                    ->select('id')
                                    ->where('department_id', $department->id),
                            );
                    })
                    ->orWhere(function (Builder $query) use ($department): void {
                        $query
                            ->where('resource_type', Folder::class)
                            ->whereIn(
                                'resource_id',
                                Folder::withTrashed()
                                    ->select('id')
                                    ->where('department_id', $department->id),
                            );
                    });
            })
            ->latest('created_at')
            ->limit(10)
            ->get();
    }
}
