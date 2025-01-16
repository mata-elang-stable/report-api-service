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
        Schema::create('sensor_metrics', function (Blueprint $table) {
            $table->dateTime('timestamp');
            $table->uuid('sensor_id');
            $table->integer('count');
            $table->timestamps();

            $table->primary(['timestamp', 'sensor_id']);
            $table->foreign('sensor_id')->references('id')->on('sensors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_metrics');
    }
};
