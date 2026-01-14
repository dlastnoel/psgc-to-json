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
            $table->foreignId('psgc_version_id')->nullable()->constrained()->nullOnDelete();
            $table->index('psgc_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropForeign(['psgc_version_id']);
            $table->dropIndex(['psgc_version_id']);
            $table->dropColumn('psgc_version_id');
        });
    }
};
