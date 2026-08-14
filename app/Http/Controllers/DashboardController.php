<?php

namespace App\Http\Controllers;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();
        $authenticatedUser->loadMissing([
            'department:id,name',
            'permissions:id,name',
            'roles:id,name',
        ]);

        $accessibleFiles = File::query()
            ->with([
                'folder:id,name,path_cache,visibility',
                'owner:id,name,last_name',
                'collaborators:id',
            ])
            ->where(function ($query) use ($authenticatedUser): void {
                $query->where('owner_id', $authenticatedUser->id)
                    ->orWhere('visibility', FileVisibility::Public)
                    ->when(
                        $authenticatedUser->department_id,
                        fn ($query, $departmentId) => $query->orWhere(
                            fn ($collaborative) => $collaborative
                                ->where('visibility', FileVisibility::Collaborative)
                                ->where('department_id', $departmentId),
                        ),
                    );
            })
            ->latest('uploaded_at')
            ->get()
            ->filter(fn (File $file): bool => $authenticatedUser->can('view', $file))
            ->values();

        $accessibleFolders = Folder::query()
            ->with(['owner:id,name,last_name', 'collaborators:id'])
            ->where(function ($query) use ($authenticatedUser): void {
                $query->where('owner_id', $authenticatedUser->id)
                    ->orWhere('visibility', FileVisibility::Public)
                    ->when(
                        $authenticatedUser->department_id,
                        fn ($query, $departmentId) => $query->orWhere(
                            fn ($collaborative) => $collaborative
                                ->where('visibility', FileVisibility::Collaborative)
                                ->where('department_id', $departmentId),
                        ),
                    );
            })
            ->latest('updated_at')
            ->get()
            ->filter(fn (Folder $folder): bool => $authenticatedUser->can('view', $folder))
            ->values();

        $trashCount = File::onlyTrashed()
            ->where('owner_id', $authenticatedUser->id)
            ->count()
            + Folder::onlyTrashed()
                ->where('owner_id', $authenticatedUser->id)
                ->count();

        return view('dashboard', [
            'user' => $this->userData($authenticatedUser),
            'permissions' => $request->session()->get('access.permissions', []),
            'today' => now()->translatedFormat('j \d\e F \d\e Y'),
            'indicators' => [
                [
                    'label' => 'Archivos privados',
                    'value' => $accessibleFiles->where('visibility', FileVisibility::Private)->count(),
                    'hint' => 'Acceso exclusivo tuyo',
                    'icon' => 'lock-keyhole',
                ],
                [
                    'label' => 'Archivos colaborativos',
                    'value' => $accessibleFiles->where('visibility', FileVisibility::Collaborative)->count(),
                    'hint' => 'Disponibles para tu equipo',
                    'icon' => 'users',
                ],
                [
                    'label' => 'Archivos públicos',
                    'value' => $accessibleFiles->where('visibility', FileVisibility::Public)->count(),
                    'hint' => 'Disponibles internamente',
                    'icon' => 'globe',
                ],
                [
                    'label' => 'Papelera',
                    'value' => $trashCount,
                    'hint' => $trashCount === 1 ? 'Elemento eliminado' : 'Elementos eliminados',
                    'icon' => 'trash',
                ],
            ],
            'files' => $accessibleFiles
                ->take(5)
                ->map(fn (File $file): array => $this->fileData($authenticatedUser, $file))
                ->all(),
            'folders' => $accessibleFolders
                ->take(4)
                ->map(fn (Folder $folder): array => $this->folderData($folder))
                ->all(),
            'activities' => AuditLog::query()
                ->where('user_id', $authenticatedUser->id)
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn (AuditLog $log): array => $this->activityData($log))
                ->all(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function userData(User $user): array
    {
        return [
            'name' => trim("{$user->name} {$user->last_name}"),
            'first_name' => $user->name,
            'department' => $user->department?->name ?? 'Sin departamento',
            'avatar' => $user->avatarUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileData(User $user, File $file): array
    {
        $location = match ($file->visibility) {
            FileVisibility::Private => 'Mis archivos',
            FileVisibility::Collaborative => 'Mi departamento',
            FileVisibility::Public => 'Públicos',
        };

        if ($file->folder) {
            $location .= $file->folder->path_cache ?: "/{$file->folder->name}";
        }

        return [
            'name' => $file->display_name,
            'visibility' => $file->visibility->label(),
            'tone' => $file->visibility->value,
            'location' => $location,
            'modified' => $file->uploaded_at?->diffForHumans() ?? 'Sin fecha',
            'size' => $this->formatBytes($file->size_bytes),
            'icon' => $this->fileIcon($file->extension),
            'download_url' => $user->can('download', $file)
                ? route('files.download', $file)
                : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function folderData(Folder $folder): array
    {
        $section = match ($folder->visibility) {
            FileVisibility::Private => 'mine',
            FileVisibility::Collaborative => 'department',
            FileVisibility::Public => 'public',
        };

        return [
            'name' => $folder->name,
            'location' => $folder->visibility->label(),
            'time' => $folder->updated_at?->diffForHumans() ?? 'Sin fecha',
            'url' => match ($section) {
                'mine' => route('folders.mine.show', $folder),
                'department' => route('folders.department.show', $folder),
                default => route('folders.public.show', $folder),
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    private function activityData(AuditLog $log): array
    {
        $details = $log->details ?? [];
        $resourceName = (string) ($details['display_name'] ?? $details['name'] ?? 'un elemento');
        if (in_array($log->action, ['auth.login', 'auth.logout'], true)) {
            $resourceName = 'Nube Municipal';
        }

        [$verb, $icon] = match ($log->action) {
            'file.uploaded' => ['Subiste', 'arrow-up'],
            'file.downloaded' => ['Descargaste', 'arrow-down'],
            'file.renamed' => ['Renombraste', 'file-text'],
            'file.moved' => ['Moviste', 'arrow-left-right'],
            'file.deleted', 'file.permanently_deleted' => ['Eliminaste', 'trash'],
            'file.restored' => ['Restauraste', 'arrow-up'],
            'file.visibility_changed' => ['Cambiaste la clasificación de', 'eye'],
            'folder.created' => ['Creaste la carpeta', 'folder-plus'],
            'folder.renamed' => ['Renombraste la carpeta', 'folder'],
            'folder.deleted' => ['Eliminaste la carpeta', 'trash'],
            'folder.visibility_changed' => ['Cambiaste la clasificación de la carpeta', 'eye'],
            'auth.login' => ['Iniciaste sesión en', 'user'],
            'auth.logout' => ['Cerraste sesión en', 'user'],
            default => ['Actualizaste', 'clock'],
        };

        return [
            'text' => "{$verb} {$resourceName}",
            'time' => $log->created_at?->diffForHumans() ?? 'Sin fecha',
            'icon' => $icon,
        ];
    }

    private function fileIcon(?string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'jpg', 'jpeg', 'png' => 'file-image',
            'xls', 'xlsx', 'csv' => 'file-chart',
            'pdf' => 'file-badge',
            default => 'file-text',
        };
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
