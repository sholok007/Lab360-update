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
        Schema::rename('reactor_calibrate', 'reactor_calibrates');

        Schema::table('reactor_calibrates', function (Blueprint $table) {
            $table->float('value')->nullable(false)->change();
            $table->dropColumn('calibrated_at');
        });
    }

    public function down(): void
    {
        Schema::rename('reactor_calibrates', 'reactor_calibrate');

        Schema::table('reactor_calibrate', function (Blueprint $table) {
            $table->float('value')->nullable()->change();
            $table->timestamp('calibrated_at')->nullable();
        });
    }
};
