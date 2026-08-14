<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminAuditService
{
    /**
     * Las claves que identifican la ubicación física o la huella de un archivo
     * nunca se muestran, aunque un evento futuro las incluya en `details`.
     */
    private const SENSITIVE_KEY_PATTERN = '/^(path|stored_name|checksum|disk)$/i';

    private const REDACTED_KEY_PATTERN = '/password|passwd|token|authorization|cookie|secret|system[_-]?key|api[_-]?key/i';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $logs = AuditLog::query()
            ->with('user:id,name,last_name,email,department_id', 'user.department:id,name')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('action', 'like', "%{$search}%")
                        ->orWhere('resource_id', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['user_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('user_id', $id),
            )
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->whereIn(
                    'user_id',
                    User::query()->select('id')->where('department_id', $id),
                ),
            )
            ->when(
                $filters['action'] ?? null,
                fn (Builder $query, string $action): Builder => $query->where('action', $action),
            )
            ->when(
                $filters['resource_type'] ?? null,
                fn (Builder $query, string $type): Builder => $type === 'none'
                    ? $query->whereNull('resource_type')
                    : $query->where('resource_type', $type),
            )
            ->when(
                $filters['ip'] ?? null,
                fn (Builder $query, string $ip): Builder => $query->where('ip_address', 'like', "%{$ip}%"),
            )
            ->when(
                ($filters['scope'] ?? 'all') !== 'all',
                fn (Builder $query): Builder => ($filters['scope'] ?? 'all') === 'administrative'
                    ? $query->where('action', 'like', 'admin.%')
                    : $query->where('action', 'not like', 'admin.%'),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
            )
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        $this->attachResourceNames($logs->getCollection());

        return $logs;
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $total = AuditLog::query()->count();
        $administrative = AuditLog::query()->where('action', 'like', 'admin.%')->count();

        return [
            'total' => $total,
            'administrative' => $administrative,
            'user' => $total - $administrative,
            'actors' => AuditLog::query()->whereNotNull('user_id')->distinct()->count('user_id'),
            'last_day' => AuditLog::query()->where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function actions(): Collection
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    /**
     * @return Collection<int, string>
     */
    public function resourceTypes(): Collection
    {
        return AuditLog::query()
            ->select('resource_type')
            ->whereNotNull('resource_type')
            ->distinct()
            ->orderBy('resource_type')
            ->pluck('resource_type');
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(AuditLog $log): array
    {
        $log->loadMissing('user:id,name,last_name,email,department_id', 'user.department:id,name');
        $this->attachResourceNames(collect([$log]));

        return [
            'log' => $log,
            'details' => $this->redact($log->details),
            'relatedLogs' => $this->relatedLogs($log),
        ];
    }

    public function resourceLabel(?string $resourceType): string
    {
        return match ($resourceType) {
            File::class => 'Archivo',
            Folder::class => 'Carpeta',
            null, '' => 'Sin recurso',
            default => class_basename($resourceType),
        };
    }

    /**
     * Oculta ubicación física, nombre almacenado, checksum y cualquier clave
     * que parezca un secreto, conservando el resto del contexto del evento.
     *
     * @param  array<string, mixed>|null  $details
     * @return array<string, mixed>
     */
    public function redact(?array $details): array
    {
        if ($details === null) {
            return [];
        }

        foreach ($details as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $details[$key] = '[OCULTO]';

                continue;
            }

            if (is_string($key) && preg_match(self::REDACTED_KEY_PATTERN, $key) === 1) {
                $details[$key] = '[OCULTO]';

                continue;
            }

            if (is_array($value)) {
                $details[$key] = $this->redact($value);
            }
        }

        return $details;
    }

    /**
     * Resuelve el nombre visible de los recursos referenciados en una página
     * completa con dos consultas, en lugar de una por evento.
     *
     * @param  Collection<int, AuditLog>  $logs
     */
    private function attachResourceNames(Collection $logs): void
    {
        $fileNames = $this->namesFor(
            $logs,
            File::class,
            fn (array $ids): Collection => File::withTrashed()
                ->whereIn('id', $ids)
                ->pluck('display_name', 'id'),
        );

        $folderNames = $this->namesFor(
            $logs,
            Folder::class,
            fn (array $ids): Collection => Folder::withTrashed()
                ->whereIn('id', $ids)
                ->pluck('name', 'id'),
        );

        $logs->each(function (AuditLog $log) use ($fileNames, $folderNames): void {
            $names = match ($log->resource_type) {
                File::class => $fileNames,
                Folder::class => $folderNames,
                default => null,
            };

            $log->setAttribute(
                'resource_name',
                $names?->get($log->resource_id),
            );
            $log->setAttribute(
                'resource_label',
                $this->resourceLabel($log->resource_type),
            );
            $log->setAttribute('administrative', $log->isAdministrative());
        });
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     * @param  callable(list<string>): Collection<string, string>  $resolver
     * @return Collection<string, string>
     */
    private function namesFor(Collection $logs, string $type, callable $resolver): Collection
    {
        $ids = $logs
            ->where('resource_type', $type)
            ->pluck('resource_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $ids === [] ? collect() : $resolver($ids);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    private function relatedLogs(AuditLog $log): Collection
    {
        if ($log->resource_type === null || $log->resource_id === null) {
            return collect();
        }

        return AuditLog::query()
            ->with('user:id,name,last_name,email')
            ->where('resource_type', $log->resource_type)
            ->where('resource_id', $log->resource_id)
            ->whereKeyNot($log->getKey())
            ->latest('created_at')
            ->limit(10)
            ->get();
    }
}
