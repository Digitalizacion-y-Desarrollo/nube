<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')
                ->nullable()
                ->constrained('folders')
                ->restrictOnDelete();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();
            $table->string('name', 150);
            $table->string('visibility', 20)->index();
            $table->string('path_cache', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['parent_id', 'owner_id', 'name', 'deleted_at'],
                'folders_parent_owner_name_deleted_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
