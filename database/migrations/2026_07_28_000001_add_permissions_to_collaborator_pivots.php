<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folder_collaborators', function (Blueprint $table): void {
            $this->addPermissionColumns($table);
        });

        Schema::table('file_collaborators', function (Blueprint $table): void {
            $this->addPermissionColumns($table);
        });
    }

    public function down(): void
    {
        Schema::table('file_collaborators', function (Blueprint $table): void {
            $this->dropPermissionColumns($table);
        });

        Schema::table('folder_collaborators', function (Blueprint $table): void {
            $this->dropPermissionColumns($table);
        });
    }

    private function addPermissionColumns(Blueprint $table): void
    {
        $table->boolean('can_view')->default(true)->after('user_id');
        $table->boolean('can_download')->default(true)->after('can_view');
        $table->boolean('can_rename')->default(false)->after('can_download');
        $table->boolean('can_move')->default(false)->after('can_rename');
        $table->boolean('can_delete')->default(false)->after('can_move');
    }

    private function dropPermissionColumns(Blueprint $table): void
    {
        $table->dropColumn([
            'can_view',
            'can_download',
            'can_rename',
            'can_move',
            'can_delete',
        ]);
    }
};
