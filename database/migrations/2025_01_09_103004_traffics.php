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
            $table->dateTime('timestamp');
            $table->unsignedBigInteger('sensor_id');
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');
            $table->ipAddress('source_ip');
            $table->foreign('source_ip')->references('ip_address')->on('identities')->onDelete('cascade');
            $table->integer('source_port');
            $table->ipAddress('destination_ip');
            $table->foreign('destination_ip')->references('ip_address')->on('identities')->onDelete('cascade');
            $table->integer('destination_port');
            $table->integer('count');
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
