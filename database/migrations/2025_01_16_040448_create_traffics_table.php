<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffics', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp');
            $table->uuid('sensor_id');
            // Change these columns to store the identity ID (bigint)
            $table->unsignedBigInteger('source_ip')->nullable();
            $table->integer('source_port')->default(0);
            $table->unsignedBigInteger('destination_ip')->nullable();
            $table->integer('destination_port')->default(0);
            $table->integer('count');
            $table->timestamps();

            $table->foreign('sensor_id')->references('id')->on('sensors');
            $table->foreign('source_ip')->references('id')->on('identities');
            $table->foreign('destination_ip')->references('id')->on('identities');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffics');
    }
};
