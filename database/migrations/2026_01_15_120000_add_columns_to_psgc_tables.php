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
        Schema::table('regions', function (Blueprint $table) {
            $table->string('old_name')->nullable()->after('name');
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->boolean('is_capital')->default(false)->after('geographic_level');
            $table->boolean('is_elevated_city')->default(false)->after('is_capital');
            $table->string('old_name')->nullable()->after('name');
            $table->index('is_elevated_city');
            $table->index('is_capital');
        });

        Schema::table('cities_municipalities', function (Blueprint $table) {
            $table->string('old_name')->nullable()->after('name');
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->string('old_name')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['old_name']);
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropIndex(['is_elevated_city']);
            $table->dropIndex(['is_capital']);
            $table->dropColumn(['is_elevated_city', 'is_capital', 'old_name']);
        });

        Schema::table('cities_municipalities', function (Blueprint $table) {
            $table->dropColumn(['old_name']);
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->dropColumn(['old_name']);
        });
    }
};
