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
        Schema::create('seismic_events', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 100)->nullable()->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('magnitude', 8, 4);
            $table->string('mmi_level', 10);
            $table->string('mmi_status', 50);
            $table->foreignId('accelerometer_data_id')->constrained('accelerometer_data')->cascadeOnDelete();
            $table->foreignId('gps_data_id')->nullable()->constrained('gps_data')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seismic_events');
    }
};
