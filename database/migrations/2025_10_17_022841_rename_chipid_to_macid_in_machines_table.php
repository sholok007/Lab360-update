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
       Schema::table('machines', function (Blueprint $table) {
        // chip_id rename করে mac_id করা
        if (Schema::hasColumn('machines', 'chip_id')) {
            $table->renameColumn('chip_id', 'mac_id');
        }
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('machines', function (Blueprint $table) {
            // mac_id নাম chip_id এ revert করা
            if (Schema::hasColumn('machines', 'mac_id')) {
                $table->renameColumn('mac_id', 'chip_id');
            }
        });
    }
};
