<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folder_collaborators', function (Blueprint $table): void {
            $table->boolean('can_create_folder')->default(false)->after('can_download');
        });

        Schema::table('file_collaborators', function (Blueprint $table): void {
            $table->boolean('can_create_folder')->default(false)->after('can_download');
        });
    }

    public function down(): void
    {
        Schema::table('folder_collaborators', function (Blueprint $table): void {
            $table->dropColumn('can_create_folder');
        });

        Schema::table('file_collaborators', function (Blueprint $table): void {
            $table->dropColumn('can_create_folder');
        });
    }
};
