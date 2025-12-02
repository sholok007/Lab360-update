<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('machines', function (Blueprint $table) {
            $table->string('auth_code')->unique()->after('machine_name');
        });
    }

    public function down(): void {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('auth_code');
        });
    }
};
