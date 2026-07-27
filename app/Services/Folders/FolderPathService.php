<?php

namespace App\Services\Folders;

use App\Models\Folder;
use Illuminate\Support\Collection;
use RuntimeException;

class FolderPathService
{
    public function pathFor(?Folder $parent, string $name): string
    {
        $parentPath = $parent === null
            ? ''
            : rtrim($parent->path_cache ?: $this->logicalPath($parent), '/');

        return $parentPath.'/'.trim($name);
    }

    public function logicalPath(Folder $folder): string
    {
        return $this->ancestorsAndSelf($folder)
            ->map(fn (Folder $item): string => "/{$item->name}")
            ->implode('');
    }

    /**
     * @return Collection<int, Folder>
     */
    public function ancestorsAndSelf(Folder $folder): Collection
    {
        $folders = collect();
        $current = $folder;
        $visited = [];

        while ($current !== null) {
            if (isset($visited[$current->id])) {
                throw new RuntimeException('Se detectó una referencia circular entre carpetas.');
            }

            $visited[$current->id] = true;
            $folders->prepend($current);

            if ($folders->count() > 100) {
                throw new RuntimeException('La profundidad máxima de carpetas fue excedida.');
            }

            $current = $current->parent()->first();
        }

        return $folders->values();
    }

    public function refreshDescendantPaths(Folder $folder): void
    {
        $folder->children()
            ->orderBy('name')
            ->get()
            ->each(function (Folder $child) use ($folder): void {
                $child->update([
                    'path_cache' => $this->pathFor($folder, $child->name),
                ]);

                $this->refreshDescendantPaths($child);
            });
    }
}
