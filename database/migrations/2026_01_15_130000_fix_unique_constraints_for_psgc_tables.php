<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For each table, drop code unique and create combined unique
        // Regions
        Schema::table('regions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'psgc_version_id']);
        });

        // Provinces
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'psgc_version_id']);
        });

        // Cities/Municipalities
        Schema::table('cities_municipalities', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'psgc_version_id']);
        });

        // Barangays
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'psgc_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop combined unique indexes and restore simple code unique
        Schema::table('regions', function (Blueprint $table) {
            $table->dropUnique(['code', 'psgc_version_id']);
            $table->unique(['code']);
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique(['code', 'psgc_version_id']);
            $table->unique(['code']);
        });

        Schema::table('cities_municipalities', function (Blueprint $table) {
            $table->dropUnique(['code', 'psgc_version_id']);
            $table->unique(['code']);
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->dropUnique(['code', 'psgc_version_id']);
            $table->unique(['code']);
        });
    }
};
