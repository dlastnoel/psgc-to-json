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
        // Drop the existing unique index by name if it exists
        $indexes = \DB::select("SHOW INDEX FROM cities_municipalities WHERE Key_name = 'cities_municipalities_code_psgc_version_id_province_id_unique'");
        if (!empty($indexes)) {
            \DB::statement("ALTER TABLE cities_municipalities DROP INDEX cities_municipalities_code_psgc_version_id_province_id_unique");
        }

        Schema::table('cities_municipalities', function (Blueprint $table) {
            // Add new unique index with all required fields (use shorter name)
            $table->unique(['code', 'psgc_version_id', 'province_id', 'province_code'], 'cities_municipalities_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique index
        Schema::table('cities_municipalities')->dropUnique(['code', 'psgc_version_id', 'province_id', 'province_code']);
    }
};
