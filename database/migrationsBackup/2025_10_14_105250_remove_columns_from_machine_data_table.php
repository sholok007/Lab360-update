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
        Schema::table('machine_data', function (Blueprint $table) {
            $table->dropColumn([
                'test_started_at',
                'test_completed_at',
                'bi_weekly',
                'operations_expired_at',
                'r_color',
                'g_color',
                'b_color',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('machine_data', function (Blueprint $table) {
            $table->timestamp('test_started_at')->nullable();
            $table->timestamp('test_completed_at')->nullable();
            $table->boolean('bi_weekly')->nullable();
            $table->timestamp('operations_expired_at')->nullable();
            $table->integer('r_color')->nullable();
            $table->integer('g_color')->nullable();
            $table->integer('b_color')->nullable();
        });
    }
};
