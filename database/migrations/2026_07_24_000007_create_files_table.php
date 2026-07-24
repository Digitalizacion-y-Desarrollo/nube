<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')
                ->nullable()
                ->constrained('folders')
                ->restrictOnDelete();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();
            $table->string('original_name');
            $table->string('display_name');
            $table->string('stored_name')->unique();
            $table->string('disk', 50)->default('nube');
            $table->string('path', 500);
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('visibility', 20)->index();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('uploaded_at')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
