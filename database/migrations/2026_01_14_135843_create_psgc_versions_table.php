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
        Schema::create('psgc_versions', function (Blueprint $table) {
            $table->id();
            $table->string('quarter', 2)->nullable();
            $table->string('year', 4)->nullable();
            $table->date('publication_date')->nullable();
            $table->string('download_url')->nullable();
            $table->string('filename')->nullable();
            $table->boolean('is_current')->default(false);
            $table->integer('regions_count')->default(0);
            $table->integer('provinces_count')->default(0);
            $table->integer('cities_municipalities_count')->default(0);
            $table->integer('barangays_count')->default(0);
            $table->timestamps();

            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psgc_versions');
    }
};
