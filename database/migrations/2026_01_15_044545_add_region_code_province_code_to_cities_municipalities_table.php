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
        // First, check if the unique index exists and drop it if it does
        $indexes = \DB::select("SHOW INDEX FROM cities_municipalities WHERE Key_name = 'cities_municipalities_code_psgc_version_id_unique'");
        if (!empty($indexes)) {
            Schema::table('cities_municipalities', function (Blueprint $table) {
                $table->dropUnique(['code', 'psgc_version_id']);
            });
        }

        Schema::table('cities_municipalities', function (Blueprint $table) {
            // Add region_code after region_id
            $table->string('region_code', 10)->nullable()->after('region_id');

            // Add province_code after province_id
            $table->string('province_code', 10)->nullable()->after('province_id');

            // Create new unique index with all required fields
            $table->unique(['code', 'psgc_version_id', 'province_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities_municipalities', function (Blueprint $table) {
            $table->dropColumn(['region_code', 'province_code']);
        });
    }
};
