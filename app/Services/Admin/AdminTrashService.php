<?php

namespace App\Services\Admin;

use App\Models\File;
use App\Models\Folder;
use App\Services\Folders\FolderPathService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use RuntimeException;

class AdminTrashService
{
    public function __construct(
        private readonly FolderPathService $paths,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function files(array $filters): LengthAwarePaginator
    {
        return File::onlyTrashed()
            ->with([
                'owner:id,name,last_name,email',
                'department:id,name',
                'folder:id,name',
                'deletedBy:id,name,last_name,email',
            ])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('display_name', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['user_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where(function (Builder $query) use ($id): void {
                    $query->where('owner_id', $id)->orWhere('deleted_by', $id);
                }),
            )
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('department_id', $id),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('deleted_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('deleted_at', '<=', $date),
            )
            ->latest('deleted_at')
            ->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'files_page')
            ->withQueryString()
            ->through(function (File $file): File {
                $file->setAttribute('expires_at', $this->expiresAt($file->deleted_at));

                return $file;
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function folders(array $filters): LengthAwarePaginator
    {
        return Folder::onlyTrashed()
            ->with([
                'owner:id,name,last_name,email',
                'department:id,name',
                'deletedBy:id,name,last_name,email',
            ])
            ->withCount([
                'files as trashed_files_count' => fn (Builder $query): Builder => $query->onlyTrashed(),
                'children as trashed_children_count' => fn (Builder $query): Builder => $query->onlyTrashed(),
            ])
            ->when(
                $filters['q'] ?? null,
                fn (Builder $query, string $search): Builder => $query->where('name', 'like', "%{$search}%"),
            )
            ->when(
                $filters['user_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where(function (Builder $query) use ($id): void {
                    $query->where('owner_id', $id)->orWhere('deleted_by', $id);
                }),
            )
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('department_id', $id),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('deleted_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('deleted_at', '<=', $date),
            )
            ->latest('deleted_at')
            ->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'folders_page')
            ->withQueryString();
    }

    /**
     * @return array<string, int|string>
     */
    public function summary(): array
    {
        $storage = File::onlyTrashed()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(size_bytes), 0) as total_bytes')
            ->firstOrFail();

        $expiringSoon = File::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(max(0, $this->retentionDays() - 7)))
            ->count();

        return [
            'files' => (int) $storage->total_count,
            'folders' => Folder::onlyTrashed()->count(),
            'storage' => Number::fileSize((int) $storage->total_bytes, precision: 1),
            'retention_days' => $this->retentionDays(),
            'expiring_soon' => $expiringSoon,
        ];
    }

    public function retentionDays(): int
    {
        return max(1, (int) config('nube.trash_retention_days', 30));
    }

    public function expiresAt(?Carbon $deletedAt): ?Carbon
    {
        return $deletedAt?->copy()->addDays($this->retentionDays());
    }

    /**
     * La carpeta destino original sólo se reutiliza cuando sigue activa; en
     * cualquier otro caso el archivo vuelve a la raíz de su clasificación.
     */
    public function restoreDestinationFor(File $file): ?Folder
    {
        if ($file->folder_id === null) {
            return null;
        }

        return Folder::query()->find($file->folder_id);
    }

    /**
     * Restaura una carpeta eliminada, devolviéndola a la raíz cuando su carpeta
     * superior ya no está disponible, y actualiza las rutas lógicas afectadas.
     */
    public function restoreFolder(Folder $folder): Folder
    {
        $parent = $folder->parent_id === null
            ? null
            : Folder::query()->find($folder->parent_id);

        if ($this->folderNameIsTaken($folder, $parent)) {
            throw new RuntimeException(
                'Ya existe una carpeta activa con ese nombre en la ubicación de destino.',
            );
        }

        return DB::transaction(function () use ($folder, $parent): Folder {
            $folder->restore();
            $folder->update([
                'parent_id' => $parent?->id,
                'path_cache' => $this->paths->pathFor($parent, $folder->name),
            ]);

            $this->paths->refreshDescendantPaths($folder);

            return $folder->refresh();
        });
    }

    /**
     * Una carpeta sólo puede purgarse cuando no retiene archivos ni subcarpetas,
     * porque las llaves foráneas de `files` y `folders` son `restrictOnDelete`.
     */
    public function purgeFolder(Folder $folder): void
    {
        if (! $folder->trashed()) {
            throw new RuntimeException('La carpeta debe estar en la papelera.');
        }

        $retainedFiles = File::withTrashed()->where('folder_id', $folder->id)->count();
        $retainedFolders = Folder::withTrashed()->where('parent_id', $folder->id)->count();

        if ($retainedFiles > 0 || $retainedFolders > 0) {
            throw new RuntimeException(
                'La carpeta todavía contiene archivos o subcarpetas; elimínalos definitivamente antes de purgarla.',
            );
        }

        $folder->forceDelete();
    }

    private function folderNameIsTaken(Folder $folder, ?Folder $parent): bool
    {
        return Folder::query()
            ->where('owner_id', $folder->owner_id)
            ->where('name', $folder->name)
            ->when(
                $parent === null,
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
                fn (Builder $query): Builder => $query->where('parent_id', $parent->id),
            )
            ->whereKeyNot($folder->getKey())
            ->exists();
    }
}
