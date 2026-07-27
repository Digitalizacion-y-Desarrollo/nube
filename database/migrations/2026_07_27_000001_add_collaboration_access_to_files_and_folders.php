<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->string('collaboration_scope', 20)
                ->nullable()
                ->after('visibility')
                ->index();
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->string('collaboration_scope', 20)
                ->nullable()
                ->after('visibility')
                ->index();
        });

        Schema::create('folder_collaborators', function (Blueprint $table): void {
            $table->foreignUuid('folder_id')
                ->constrained('folders')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['folder_id', 'user_id']);
            $table->index(['user_id', 'folder_id']);
        });

        Schema::create('file_collaborators', function (Blueprint $table): void {
            $table->foreignUuid('file_id')
                ->constrained('files')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['file_id', 'user_id']);
            $table->index(['user_id', 'file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_collaborators');
        Schema::dropIfExists('folder_collaborators');

        Schema::table('files', function (Blueprint $table): void {
            $table->dropColumn('collaboration_scope');
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->dropColumn('collaboration_scope');
        });
    }
};
