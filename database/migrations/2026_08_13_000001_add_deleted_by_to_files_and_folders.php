<?php

use App\Models\File;
use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            $table->foreignId('deleted_by')
                ->nullable()
                ->after('deleted_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->foreignId('deleted_by')
                ->nullable()
                ->after('deleted_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Los elementos que ya estaban en la papelera conservan su actor original
        // recuperándolo de la bitácora de auditoría, que es la única fuente
        // disponible para las eliminaciones previas a esta columna.
        $this->backfill('files', File::class, 'file.deleted');
        $this->backfill('folders', Folder::class, 'folder.deleted');
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deleted_by');
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deleted_by');
        });
    }

    private function backfill(string $table, string $resourceType, string $action): void
    {
        $ids = DB::table($table)
            ->whereNotNull('deleted_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            $userId = DB::table('audit_logs')
                ->where('resource_type', $resourceType)
                ->where('resource_id', (string) $id)
                ->where('action', $action)
                ->whereNotNull('user_id')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('user_id');

            if ($userId !== null) {
                DB::table($table)
                    ->where('id', $id)
                    ->update(['deleted_by' => $userId]);
            }
        }
    }
};
