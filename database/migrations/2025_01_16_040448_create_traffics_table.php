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
            $table->ipAddress('source_ip')->nullable();
            $table->integer('source_port')->default(0);
            $table->ipAddress('destination_ip')->nullable();
            $table->integer('destination_port')->default(0);
            $table->integer('count');
            $table->timestamps();

            $table->foreign('sensor_id')->references('id')->on('sensors');
            $table->foreign('source_ip')->references('ip_address')->on('identities')->nullable();
            $table->foreign('destination_ip')->references('ip_address')->on('identities')->nullable();
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
