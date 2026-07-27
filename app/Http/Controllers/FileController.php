<?php

namespace App\Http\Controllers;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Http\Requests\ChangeFileVisibilityRequest;
use App\Http\Requests\MoveFileRequest;
use App\Http\Requests\RenameFileRequest;
use App\Http\Requests\RestoreFileRequest;
use App\Http\Requests\UploadFileRequest;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Services\Files\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FileController extends Controller
{
    public function __construct(
        private readonly FileStorageService $storage,
    ) {}

    public function store(UploadFileRequest $request): RedirectResponse
    {
        $folder = $request->validated('folder_id') === null
            ? null
            : Folder::query()->findOrFail($request->validated('folder_id'));
        $visibility = FileVisibility::from($request->validated('visibility'));
        $collaborationScope = $visibility === FileVisibility::Collaborative
            ? CollaborationScope::from($request->validated('collaboration_scope'))
            : null;

        $this->authorize('upload', [File::class, $folder, $visibility]);

        try {
            $file = $this->storage->upload(
                $request->file('file'),
                $request->user(),
                $folder,
                $visibility,
                $collaborationScope,
                $request->validated('collaborators', []),
            );

            return back()->with('status', "El archivo «{$file->display_name}» fue cargado.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'file_error',
                'No fue posible guardar el archivo. Verifica el almacenamiento e intenta nuevamente.',
            );
        }
    }

    public function download(Request $request, File $file): StreamedResponse
    {
        $this->authorize('download', $file);
        abort_unless($this->storage->exists($file), 404);

        $this->auditFile($request, 'file.downloaded', $file, [
            'display_name' => $file->display_name,
            'folder_id' => $file->folder_id,
        ]);

        return Storage::disk($file->disk)->download($file->path, $file->display_name);
    }

    public function update(
        RenameFileRequest $request,
        File $file,
    ): RedirectResponse {
        $this->authorize('update', $file);
        try {
            $file = $this->storage->rename($file, $request->validated('display_name'));

            return back()->with('status', "El archivo fue renombrado a «{$file->display_name}».");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('file_error', 'No fue posible renombrar el archivo.');
        }
    }

    public function move(
        MoveFileRequest $request,
        File $file,
    ): RedirectResponse {
        $destinationId = $request->validated('destination_folder_id');
        $destination = $destinationId === null
            ? null
            : Folder::query()->findOrFail($destinationId);

        $this->authorize('move', [$file, $destination]);
        $file->loadMissing('owner');

        try {
            $file = $this->storage->move($file, $destination);

            return back()->with('status', "El archivo «{$file->display_name}» fue movido.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'file_error',
                'No fue posible mover el archivo. No se realizaron cambios.',
            );
        }
    }

    public function destroy(Request $request, File $file): RedirectResponse
    {
        $this->authorize('delete', $file);
        $file->loadMissing('owner');

        try {
            $file = $this->storage->delete($file);

            return back()->with('status', "El archivo «{$file->display_name}» fue enviado a la papelera.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'file_error',
                'No fue posible eliminar el archivo. No se realizaron cambios.',
            );
        }
    }

    public function changeVisibility(
        ChangeFileVisibilityRequest $request,
        File $file,
    ): RedirectResponse {
        $visibility = FileVisibility::from($request->validated('visibility'));
        $collaborationScope = $visibility === FileVisibility::Collaborative
            ? CollaborationScope::from($request->validated('collaboration_scope'))
            : null;
        $this->authorize('changeVisibility', [$file, $visibility]);
        $file->loadMissing('owner');

        try {
            $file = $this->storage->changeVisibility(
                $file,
                $visibility,
                $collaborationScope,
                $request->validated('collaborators', []),
            );

            return back()->with(
                'status',
                "El archivo «{$file->display_name}» ahora es {$visibility->label()}.",
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'file_error',
                'No fue posible cambiar la clasificación del archivo. No se realizaron cambios.',
            );
        }
    }

    public function restore(
        RestoreFileRequest $request,
        string $file,
    ): RedirectResponse {
        $trashedFile = File::onlyTrashed()->with('owner')->findOrFail($file);
        $destinationId = $request->validated('destination_folder_id');
        $destination = $destinationId === null
            ? null
            : Folder::query()->findOrFail($destinationId);

        $this->authorize('restore', [$trashedFile, $destination]);

        try {
            $restoredFile = $this->storage->restore($trashedFile, $destination);

            return back()->with('status', "El archivo «{$restoredFile->display_name}» fue restaurado.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'file_error',
                'No fue posible restaurar el archivo. No se realizaron cambios.',
            );
        }
    }

    public function forceDestroy(Request $request, string $file): RedirectResponse
    {
        $trashedFile = File::onlyTrashed()->with('owner')->findOrFail($file);
        $this->authorize('forceDelete', $trashedFile);

        try {
            $displayName = $trashedFile->display_name;
            $this->storage->forceDelete($trashedFile);

            return back()->with('status', "El archivo «{$displayName}» fue eliminado permanentemente.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'file_error',
                'No fue posible eliminar permanentemente el archivo.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function auditFile(
        Request $request,
        string $action,
        File $file,
        array $details,
    ): void {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
