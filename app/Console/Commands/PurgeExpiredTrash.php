<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\Files\FileStorageService;
use Illuminate\Console\Command;
use Throwable;

class PurgeExpiredTrash extends Command
{
    protected $signature = 'files:purge-trash';

    protected $description = 'Elimina permanentemente los archivos que llevan 30 días en la papelera';

    public function handle(FileStorageService $storage): int
    {
        $retentionDays = max(1, (int) config('nube.trash_retention_days', 30));
        $cutoff = now()->subDays($retentionDays);
        $deleted = 0;
        $failed = 0;

        File::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($files) use ($storage, &$deleted, &$failed): void {
                foreach ($files as $file) {
                    try {
                        $storage->forceDelete($file);
                        $deleted++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                        $this->error("No se pudo eliminar el archivo {$file->id}.");
                    }
                }
            });

        $this->info("Purga finalizada: {$deleted} archivo(s) eliminado(s), {$failed} error(es).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
