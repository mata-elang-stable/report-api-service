<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\AlertMessage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alert_metrics', function (Blueprint $table) {
            $table->dateTime('timestamp');
            $table->uuid('sensor_id');
            $table->foreignId('alert_id')->constrained('alert_messages');
            $table->integer('count');
            $table->timestamps();

            $table->primary(['timestamp', 'sensor_id', 'alert_id']);
            $table->foreign('sensor_id')->references('id')->on('sensors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_metrics');
    }
};
