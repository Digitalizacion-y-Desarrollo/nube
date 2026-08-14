<?php

namespace App\Services\Admin;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $files = $this->fileTotals();
        $folders = $this->folderTotals();
        $users = $this->userTotals();
        $departments = $this->departmentTotals();

        return [
            'summary' => [
                [
                    'label' => 'Archivos activos',
                    'value' => number_format($files['active_count']),
                    'hint' => $this->deletedHint($files['deleted_count'], 'archivo'),
                    'icon' => 'file-text',
                ],
                [
                    'label' => 'Carpetas activas',
                    'value' => number_format($folders['active_count']),
                    'hint' => $this->deletedHint($folders['deleted_count'], 'carpeta'),
                    'icon' => 'folder',
                ],
                [
                    'label' => 'Usuarios sincronizados',
                    'value' => number_format($users['total_count']),
                    'hint' => number_format($users['active_count']).' activos · '
                        .number_format($users['inactive_count']).' inactivos',
                    'icon' => 'users',
                ],
                [
                    'label' => 'Departamentos',
                    'value' => number_format($departments['total_count']),
                    'hint' => number_format($departments['active_count']).' activos · '
                        .number_format($departments['inactive_count']).' inactivos',
                    'icon' => 'building',
                ],
                [
                    'label' => 'Espacio utilizado',
                    'value' => $this->formatBytes($files['total_bytes']),
                    'hint' => $this->formatBytes($files['deleted_bytes']).' en papelera',
                    'icon' => 'folder-lock',
                ],
            ],
            'storage' => [
                'active_bytes' => $files['active_bytes'],
                'deleted_bytes' => $files['deleted_bytes'],
                'total_bytes' => $files['total_bytes'],
                'active' => $this->formatBytes($files['active_bytes']),
                'deleted' => $this->formatBytes($files['deleted_bytes']),
                'total' => $this->formatBytes($files['total_bytes']),
                'active_percent' => $this->percentage(
                    $files['active_bytes'],
                    $files['total_bytes'],
                ),
                'deleted_percent' => $this->percentage(
                    $files['deleted_bytes'],
                    $files['total_bytes'],
                ),
            ],
            'visibility' => $this->visibilityDistribution(),
            'topDepartments' => $this->topDepartments(),
            'topUsers' => $this->topUsers(),
            'recentActivity' => AuditLog::query()
                ->with('user:id,name,last_name,email')
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @return array{
     *     active_count: int,
     *     deleted_count: int,
     *     total_count: int,
     *     active_bytes: int,
     *     deleted_bytes: int,
     *     total_bytes: int
     * }
     */
    private function fileTotals(): array
    {
        $stats = File::withTrashed()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as deleted_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN size_bytes ELSE 0 END), 0) as deleted_bytes')
            ->selectRaw('COALESCE(SUM(size_bytes), 0) as total_bytes')
            ->firstOrFail();

        return [
            'active_count' => (int) $stats->active_count,
            'deleted_count' => (int) $stats->deleted_count,
            'total_count' => (int) $stats->total_count,
            'active_bytes' => (int) $stats->active_bytes,
            'deleted_bytes' => (int) $stats->deleted_bytes,
            'total_bytes' => (int) $stats->total_bytes,
        ];
    }

    /**
     * @return array{active_count: int, deleted_count: int, total_count: int}
     */
    private function folderTotals(): array
    {
        $stats = Folder::withTrashed()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as deleted_count')
            ->firstOrFail();

        return [
            'active_count' => (int) $stats->active_count,
            'deleted_count' => (int) $stats->deleted_count,
            'total_count' => (int) $stats->total_count,
        ];
    }

    /**
     * @return array{active_count: int, inactive_count: int, total_count: int}
     */
    private function userTotals(): array
    {
        $stats = User::query()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END), 0) as inactive_count')
            ->firstOrFail();

        return [
            'active_count' => (int) $stats->active_count,
            'inactive_count' => (int) $stats->inactive_count,
            'total_count' => (int) $stats->total_count,
        ];
    }

    /**
     * @return array{active_count: int, inactive_count: int, total_count: int}
     */
    private function departmentTotals(): array
    {
        $stats = Department::query()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END), 0) as inactive_count')
            ->firstOrFail();

        return [
            'active_count' => (int) $stats->active_count,
            'inactive_count' => (int) $stats->inactive_count,
            'total_count' => (int) $stats->total_count,
        ];
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function visibilityDistribution(): array
    {
        $rows = File::withTrashed()
            ->select('visibility')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END), 0) as active_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as deleted_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN size_bytes ELSE 0 END), 0) as active_bytes')
            ->groupBy('visibility')
            ->get()
            ->keyBy(fn (File $file): string => $file->visibility->value);

        return collect(FileVisibility::cases())
            ->map(function (FileVisibility $visibility) use ($rows): array {
                /** @var File|null $row */
                $row = $rows->get($visibility->value);
                $activeCount = (int) ($row?->active_count ?? 0);
                $deletedCount = (int) ($row?->deleted_count ?? 0);
                $totalCount = $activeCount + $deletedCount;

                return [
                    'label' => $visibility->label(),
                    'tone' => $visibility->value,
                    'active_count' => $activeCount,
                    'deleted_count' => $deletedCount,
                    'total_count' => $totalCount,
                    'active_percent' => $this->percentage($activeCount, $totalCount),
                    'active_storage' => $this->formatBytes(
                        (int) ($row?->active_bytes ?? 0),
                    ),
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function topDepartments(): Collection
    {
        $rows = DB::table('departments')
            ->join('files', 'files.department_id', '=', 'departments.id')
            ->select([
                'departments.id',
                'departments.name',
                'departments.abbreviation',
            ])
            ->selectRaw('COUNT(files.id) as files_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN files.deleted_at IS NULL THEN files.size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN files.deleted_at IS NOT NULL THEN files.size_bytes ELSE 0 END), 0) as deleted_bytes')
            ->selectRaw('COALESCE(SUM(files.size_bytes), 0) as total_bytes')
            ->groupBy([
                'departments.id',
                'departments.name',
                'departments.abbreviation',
            ])
            ->orderByDesc('total_bytes')
            ->orderBy('departments.name')
            ->limit(5)
            ->get();

        return $this->rankedConsumption($rows, fn (object $row): array => [
            'name' => (string) $row->name,
            'meta' => $row->abbreviation
                ? (string) $row->abbreviation
                : number_format((int) $row->files_count).' archivos',
        ]);
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function topUsers(): Collection
    {
        $rows = DB::table('users')
            ->join('files', 'files.owner_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->select([
                'users.id',
                'users.name',
                'users.last_name',
                'users.email',
                'departments.name as department_name',
            ])
            ->selectRaw('COUNT(files.id) as files_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN files.deleted_at IS NULL THEN files.size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN files.deleted_at IS NOT NULL THEN files.size_bytes ELSE 0 END), 0) as deleted_bytes')
            ->selectRaw('COALESCE(SUM(files.size_bytes), 0) as total_bytes')
            ->groupBy([
                'users.id',
                'users.name',
                'users.last_name',
                'users.email',
                'departments.name',
            ])
            ->orderByDesc('total_bytes')
            ->orderBy('users.name')
            ->limit(5)
            ->get();

        return $this->rankedConsumption($rows, fn (object $row): array => [
            'name' => trim("{$row->name} {$row->last_name}"),
            'meta' => $row->department_name
                ? (string) $row->department_name
                : (string) $row->email,
        ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  callable(object): array{name: string, meta: string}  $identity
     * @return Collection<int, array<string, int|string>>
     */
    private function rankedConsumption(Collection $rows, callable $identity): Collection
    {
        $largest = max(1, (int) $rows->max('total_bytes'));

        return $rows
            ->values()
            ->map(function (object $row, int $index) use ($identity, $largest): array {
                $identityData = $identity($row);
                $activeBytes = (int) $row->active_bytes;
                $deletedBytes = (int) $row->deleted_bytes;
                $totalBytes = (int) $row->total_bytes;

                return [
                    'rank' => $index + 1,
                    'name' => $identityData['name'],
                    'meta' => $identityData['meta'],
                    'files_count' => (int) $row->files_count,
                    'active_bytes' => $activeBytes,
                    'deleted_bytes' => $deletedBytes,
                    'total_bytes' => $totalBytes,
                    'active' => $this->formatBytes($activeBytes),
                    'deleted' => $this->formatBytes($deletedBytes),
                    'total' => $this->formatBytes($totalBytes),
                    'percent' => $this->percentage($totalBytes, $largest),
                ];
            });
    }

    private function deletedHint(int $count, string $noun): string
    {
        if ($count === 0) {
            return 'Sin elementos en papelera';
        }

        return number_format($count).' '
            .($count === 1 ? $noun : str($noun)->plural())
            .' en papelera';
    }

    private function percentage(int $value, int $total): int
    {
        if ($value === 0 || $total === 0) {
            return 0;
        }

        return min(100, max(1, (int) round(($value / $total) * 100)));
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $value >= 10 ? 0 : 1).' '.$unit;
            }

            $value /= 1024;
        }

        return "{$bytes} B";
    }
}
