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
        Schema::table('provinces', function (Blueprint $table) {
            // Add region_code after region_id
            $table->string('region_code', 10)->nullable()->after('region_id');

            // Add province_code (same as code column, for reference)
            $table->string('province_code', 10)->nullable()->after('region_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn(['region_code', 'province_code']);
        });
    }
};
