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
         Schema::create('machine_others', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('machine_id');
            $table->enum('clarity_test', ['ON', 'OFF'])->default('OFF');
            $table->enum('tem_test', ['ON', 'OFF'])->default('OFF');
            $table->enum('alarm', ['ON', 'OFF'])->default('OFF');
            $table->timestamps();

            $table->foreign('machine_id')->references('id')->on('machines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_others');
    }
};
