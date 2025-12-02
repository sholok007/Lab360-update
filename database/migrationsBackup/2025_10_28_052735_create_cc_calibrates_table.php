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
        Schema::create('cc_calibrates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->float('value'); // calibration value (5.7, 50 etc.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cc_calibrates');
    }
};
