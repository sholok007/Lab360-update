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
            if (Schema::hasColumn('machines', 'user_name')) {
                $table->dropColumn('user_name');
            }
            if (Schema::hasColumn('machines', 'user_password')) {
                $table->dropColumn('user_password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('machines', function (Blueprint $table) {
            $table->string('user_name')->nullable();
            $table->string('user_password')->nullable();
        });
    }
};
