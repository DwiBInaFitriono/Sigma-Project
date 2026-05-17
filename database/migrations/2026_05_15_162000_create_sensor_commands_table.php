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
        Schema::create('sensor_commands', function (Blueprint $table) {
            $table->id();
            $table->enum('sensor_type', ['accelerometer', 'gps']);
            $table->enum('command', ['power_on', 'power_off', 'set_sensitivity', 'reset_default']);
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'executed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_commands');
    }
};
