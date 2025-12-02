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
        Schema::create('reactor_calibrate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade'); // machine relation
            $table->float('value')->nullable(); // calibration value
            $table->timestamp('calibrated_at')->nullable(); // when calibration happened
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactor_calibrate');
    }
};
