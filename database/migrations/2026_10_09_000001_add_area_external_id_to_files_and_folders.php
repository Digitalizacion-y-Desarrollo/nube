<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->string('area_external_id', 100)->nullable()->after('department_id')->index();
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->string('area_external_id', 100)->nullable()->after('department_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            $table->dropIndex(['area_external_id']);
            $table->dropColumn('area_external_id');
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->dropIndex(['area_external_id']);
            $table->dropColumn('area_external_id');
        });
    }
};
