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
        Schema::create('machine_data', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('test_name')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();

            $table->foreignId('reagent_a_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('reagent_b_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('reagent_c_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('reagent_d_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('reagent_e_location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->timestamp('test_started_at')->nullable();
            $table->timestamp('test_completed_at')->nullable();

            $table->boolean('bi_weekly')->default(false);
            $table->date('operations_expired_at')->nullable();

            $table->integer('r_color')->nullable();
            $table->integer('g_color')->nullable();
            $table->integer('b_color')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_data');
    }
};
