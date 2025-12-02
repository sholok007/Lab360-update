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
            if (Schema::hasColumn('machines', 'wifi_ssid')) {
                $table->dropColumn('wifi_ssid');
            }
            if (Schema::hasColumn('machines', 'wifi_password')) {
                $table->dropColumn('wifi_password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('machines', function (Blueprint $table) {
            $table->string('wifi_ssid')->nullable()->after('machine_name');
            $table->string('wifi_password')->nullable()->after('wifi_ssid');
        });
    }
};
