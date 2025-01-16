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
        Schema::create('traffics', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp');
            $table->uuid('sensor_id');
            $table->ipAddress('source_ip');
            $table->integer('source_port');
            $table->ipAddress('destination_ip');
            $table->integer('destination_port');
            $table->integer('count');
            $table->timestamps();

            $table->foreign('sensor_id')->references('id')->on('sensors');
            $table->foreign('source_ip')->references('ip_address')->on('identities');
            $table->foreign('destination_ip')->references('ip_address')->on('identities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffics');
    }
};
