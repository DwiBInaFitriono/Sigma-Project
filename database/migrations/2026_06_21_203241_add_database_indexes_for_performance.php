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
        // Add indexes to accelerometer_data table for dashboard queries
        if (Schema::hasTable('accelerometer_data')) {
            $indexes = Schema::getIndexes('accelerometer_data');
            $indexNames = array_column($indexes, 'name');
            
            Schema::table('accelerometer_data', function (Blueprint $table) use ($indexNames) {
                if (!in_array('accelerometer_data_recorded_at_magnitude_index', $indexNames)) {
                    $table->index(['recorded_at', 'magnitude'], 'accelerometer_data_recorded_at_magnitude_index');
                }
                if (!in_array('accelerometer_data_created_at_index', $indexNames)) {
                    $table->index('created_at', 'accelerometer_data_created_at_index');
                }
            });
        }

        // Add indexes to gps_data table for dashboard queries
        if (Schema::hasTable('gps_data')) {
            $indexes = Schema::getIndexes('gps_data');
            $indexNames = array_column($indexes, 'name');
            
            Schema::table('gps_data', function (Blueprint $table) use ($indexNames) {
                if (!in_array('gps_data_created_at_index', $indexNames)) {
                    $table->index('created_at', 'gps_data_created_at_index');
                }
            });
        }

        // Add indexes to seismic_events table
        // (recorded_at is already indexed in a previous migration)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from accelerometer_data table
        if (Schema::hasTable('accelerometer_data')) {
            Schema::table('accelerometer_data', function (Blueprint $table) {
                $table->dropIndex(['recorded_at', 'magnitude']);
                $table->dropIndex(['created_at']);
            });
        }

        // Drop indexes from gps_data table
        if (Schema::hasTable('gps_data')) {
            Schema::table('gps_data', function (Blueprint $table) {
                $table->dropIndex(['recorded_at']);
                $table->dropIndex(['created_at']);
            });
        }

        // Drop indexes from seismic_events table
        if (Schema::hasTable('seismic_events')) {
            Schema::table('seismic_events', function (Blueprint $table) {
                $table->dropIndex(['recorded_at']);
            });
        }
    }
};
