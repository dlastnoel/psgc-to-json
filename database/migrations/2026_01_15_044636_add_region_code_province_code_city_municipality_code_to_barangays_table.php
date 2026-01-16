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
        Schema::table('barangays', function (Blueprint $table) {
            // Add region_code after region_id
            $table->string('region_code', 10)->nullable()->after('region_id');

            // Add province_code after province_id
            $table->string('province_code', 10)->nullable()->after('province_id');

            // Add city_municipality_code after city_municipality_id
            $table->string('city_municipality_code', 10)->nullable()->after('city_municipality_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropColumn(['region_code', 'province_code', 'city_municipality_code']);
        });
    }
};
