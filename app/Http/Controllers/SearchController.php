<?php

namespace App\Http\Controllers;

use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\Folders\FolderPathService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly FolderPathService $paths,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->merge([
            'q' => trim((string) $request->input('q')),
        ]);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->loadMissing(['permissions:id,name', 'roles:id,name']);
        $search = $this->escapeLike($validated['q']);

        $folders = Folder::query()
            ->with(['owner:id,name,last_name', 'collaborators:id'])
            ->where('name', 'like', "%{$search}%")
            ->where(fn (Builder $query): Builder => $this->visibleCandidateScope($query, $user))
            ->orderBy('name')
            ->get()
            ->filter(fn (Folder $folder): bool => $this->canOpenFolder($user, $folder))
            ->map(fn (Folder $folder): array => [
                'id' => $folder->id,
                'kind' => 'folder',
                'name' => $folder->name,
                'meta' => $this->folderMeta($folder),
                'visibility' => $folder->visibility->label(),
                'url' => $this->folderUrl($folder),
            ]);

        $files = File::query()
            ->with(['owner:id,name,last_name', 'collaborators:id'])
            ->where('display_name', 'like', "%{$search}%")
            ->where(fn (Builder $query): Builder => $this->visibleCandidateScope($query, $user))
            ->latest('uploaded_at')
            ->get()
            ->filter(fn (File $file): bool => $user->can('view', $file))
            ->map(fn (File $file): array => [
                'id' => $file->id,
                'kind' => 'file',
                'name' => $file->display_name,
                'meta' => $this->fileMeta($file),
                'visibility' => $file->visibility->label(),
                'url' => $user->can('download', $file)
                    ? route('files.download', $file)
                    : null,
            ]);

        $results = $folders
            ->concat($files)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->take(12)
            ->values();

        return response()->json([
            'query' => $validated['q'],
            'results' => $results,
            'total' => $results->count(),
        ]);
    }

    private function visibleCandidateScope(Builder $query, User $user): Builder
    {
        return $query
            ->where('owner_id', $user->id)
            ->orWhere('visibility', FileVisibility::Public)
            ->when(
                $user->department_id,
                fn (Builder $query, int $departmentId): Builder => $query->orWhere(
                    fn (Builder $collaborative): Builder => $collaborative
                        ->where('visibility', FileVisibility::Collaborative)
                        ->where('department_id', $departmentId),
                ),
            );
    }

    private function folderUrl(Folder $folder): string
    {
        return match ($folder->visibility) {
            FileVisibility::Private => route('folders.mine.show', $folder),
            FileVisibility::Collaborative => route('folders.department.show', $folder),
            FileVisibility::Public => route('folders.public.show', $folder),
        };
    }

    private function canOpenFolder(User $user, Folder $folder): bool
    {
        foreach ($this->paths->ancestorsAndSelf($folder) as $ancestor) {
            if (! $user->can('view', $ancestor)) {
                return false;
            }
        }

        return true;
    }

    private function folderMeta(Folder $folder): string
    {
        $owner = trim("{$folder->owner?->name} {$folder->owner?->last_name}");

        return $owner === '' ? 'Carpeta' : "Carpeta · {$owner}";
    }

    private function fileMeta(File $file): string
    {
        $owner = trim("{$file->owner?->name} {$file->owner?->last_name}");
        $extension = strtoupper((string) $file->extension);

        return implode(' · ', array_filter([$extension ?: 'Archivo', $owner]));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value,
        );
    }
}
