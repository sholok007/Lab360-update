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
        Schema::create('esp_sensor', function (Blueprint $table) {
            $table->id();
            $table->string('test_name')->nullable();
            $table->string('brand')->nullable();
            $table->string('color_r')->nullable();
            $table->string('color_g')->nullable();
            $table->string('color_b')->nullable();
            $table->string('tem')->nullable();
            $table->string('sen_1')->nullable();
            $table->string('sen_2')->nullable();
            $table->string('sen_3')->nullable();
            $table->string('sen_4')->nullable();
            $table->string('sen_5')->nullable();
            $table->string('sen_6')->nullable();
            $table->timestamp('recorded_at')->useCurrent(); // date & time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esp_sensor');
    }
};
