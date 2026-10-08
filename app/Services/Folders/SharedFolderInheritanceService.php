<?php

namespace App\Services\Folders;

use App\Enums\CollaborationScope;
use App\Enums\CollaboratorPermission;
use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;

/**
 * Resolves the boundary of a selected-user shared folder.  Descendants are
 * deliberately copies of that boundary: they never own an access list.
 */
class SharedFolderInheritanceService
{
    public function rootFor(?Folder $folder): ?Folder
    {
        $root = null;
        $seen = [];

        while ($folder !== null && ! isset($seen[$folder->id])) {
            $seen[$folder->id] = true;
            if ($folder->collaboration_scope === CollaborationScope::Selected) {
                $root = $folder;
            }
            $folder = $folder->parent()->first();
        }

        return $root;
    }

    public function rootForFile(File $file): ?Folder
    {
        return $this->rootFor($file->folder()->first());
    }

    public function isInherited(Folder $folder): bool
    {
        return ($root = $this->rootFor($folder)) !== null && $root->id !== $folder->id;
    }

    public function canAccess(User $user, Folder $root): bool
    {
        if ($root->owner_id === $user->id) {
            return true;
        }

        if ($root->visibility === FileVisibility::Collaborative
            && $root->department_id === $user->department_id
            && $user->hasRole('admin_area')) {
            return true;
        }

        return $root->collaboratorCan($user, CollaboratorPermission::View);
    }

    /** @return array<string, FileVisibility|CollaborationScope|null> */
    public function attributes(Folder $root): array
    {
        return [
            'visibility' => $root->visibility,
            'collaboration_scope' => $root->collaboration_scope,
        ];
    }

    public function syncFolder(Folder $folder, Folder $root): void
    {
        $folder->collaborators()->sync($this->pivotData($root));
    }

    public function syncFile(File $file, Folder $root): void
    {
        $file->collaborators()->sync($this->pivotData($root));
    }

    /** Propagates root metadata and the exact pivot set to every descendant. */
    public function propagate(Folder $root): void
    {
        $ids = [$root->id];
        $frontier = [$root->id];
        while ($frontier !== []) {
            $frontier = Folder::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        $attributes = $this->attributes($root);
        Folder::query()->whereIn('id', $ids)->where('id', '!=', $root->id)->update($attributes);
        File::query()->whereIn('folder_id', $ids)->update($attributes);

        foreach (Folder::query()->whereIn('id', $ids)->get() as $folder) {
            $this->syncFolder($folder, $root);
        }
        foreach (File::query()->whereIn('folder_id', $ids)->get() as $file) {
            $this->syncFile($file, $root);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function pivotData(Folder $root): array
    {
        // The caller may just have synchronized this relation in the same request.
        $root->load('collaborators');

        return $root->collaborators->mapWithKeys(fn (User $user): array => [$user->id => [
            'can_view' => (bool) $user->pivot->can_view,
            'can_download' => (bool) $user->pivot->can_download,
            'can_create_folder' => (bool) $user->pivot->can_create_folder,
            'can_rename' => (bool) $user->pivot->can_rename,
            'can_move' => (bool) $user->pivot->can_move,
            'can_delete' => (bool) $user->pivot->can_delete,
            'created_at' => $user->pivot->created_at,
            'expires_at' => $user->pivot->expires_at,
        ]])->all();
    }
}
