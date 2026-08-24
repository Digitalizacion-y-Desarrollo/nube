<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['folder_collaborators', 'file_collaborators'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->timestamp('expires_at')->nullable()->after('created_at')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['folder_collaborators', 'file_collaborators'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            });
        }
    }
};
