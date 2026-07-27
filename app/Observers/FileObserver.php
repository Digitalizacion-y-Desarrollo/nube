<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\File;
use Illuminate\Support\Facades\Auth;

class FileObserver
{
    public function created(File $file): void
    {
        $this->record($file, 'file.uploaded', [
            'display_name' => $file->display_name,
            'folder_id' => $file->folder_id,
            'size_bytes' => $file->size_bytes,
            'mime_type' => $file->mime_type,
        ]);
    }

    public function updated(File $file): void
    {
        $changes = [];
        $safeAttributes = [
            'folder_id',
            'owner_id',
            'department_id',
            'original_name',
            'display_name',
            'extension',
            'mime_type',
            'size_bytes',
            'visibility',
            'collaboration_scope',
            'uploaded_at',
        ];

        foreach ($safeAttributes as $attribute) {
            if ($file->wasChanged($attribute)) {
                $changes[$attribute] = [
                    'before' => $file->getOriginal($attribute),
                    'after' => $file->getAttribute($attribute),
                ];
            }
        }

        foreach (['stored_name', 'disk', 'path', 'checksum'] as $sensitiveAttribute) {
            if ($file->wasChanged($sensitiveAttribute)) {
                $changes[$sensitiveAttribute] = ['changed' => true];
            }
        }

        if ($changes === []) {
            $changes['other_metadata'] = [
                'changed_fields' => array_values(array_diff(
                    array_keys($file->getChanges()),
                    ['updated_at'],
                )),
            ];
        }

        $action = match (true) {
            $file->wasChanged('visibility') => 'file.visibility_changed',
            $file->wasChanged('display_name') => 'file.renamed',
            $file->wasChanged('folder_id') => 'file.moved',
            default => 'file.updated',
        };

        $this->record($file, $action, [
            'display_name' => $file->display_name,
            'changes' => $changes,
        ]);
    }

    public function deleted(File $file): void
    {
        if ($file->isForceDeleting()) {
            return;
        }

        $this->record($file, 'file.deleted', [
            'display_name' => $file->display_name,
            'folder_id' => $file->folder_id,
        ]);
    }

    public function restored(File $file): void
    {
        $this->record($file, 'file.restored', [
            'display_name' => $file->display_name,
            'folder_id' => $file->folder_id,
        ]);
    }

    public function forceDeleted(File $file): void
    {
        $this->record($file, 'file.permanently_deleted', [
            'display_name' => $file->display_name,
            'folder_id' => $file->folder_id,
            'deleted_at' => $file->deleted_at?->toISOString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function record(File $file, string $action, array $details): void
    {
        $request = app()->bound('request') ? request() : null;

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
