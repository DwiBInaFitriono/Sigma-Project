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
        Schema::table('gps_data', function (Blueprint $table) {
            $table->index('recorded_at');
        });

        Schema::table('accelerometer_data', function (Blueprint $table) {
            $table->index('recorded_at');
            $table->index(['magnitude', 'recorded_at']);
        });

        Schema::table('seismic_events', function (Blueprint $table) {
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('gps_data', function (Blueprint $table) {
            $table->dropIndex(['recorded_at']);
        });

        Schema::table('accelerometer_data', function (Blueprint $table) {
            $table->dropIndex(['recorded_at']);
            $table->dropIndex(['magnitude', 'recorded_at']);
        });

        Schema::table('seismic_events', function (Blueprint $table) {
            $table->dropIndex(['recorded_at']);
        });
    }
};
