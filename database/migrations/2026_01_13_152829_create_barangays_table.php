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
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_municipality_id')->nullable()->constrained('cities_municipalities')->nullOnDelete();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('correspondence_code', 10)->nullable();
            $table->string('geographic_level');
            $table->timestamps();

            $table->index('code');
            $table->index('correspondence_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
