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
        Schema::create('machine_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('reagent_id');
            $table->unsignedBigInteger('location_id');
            $table->string('chip_id')->default('CHIP-001'); 
            $table->timestamps();

            // Foreign keys
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->foreign('reagent_id')->references('id')->on('reagents')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_setups');
    }
};
