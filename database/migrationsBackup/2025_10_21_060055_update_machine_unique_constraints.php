<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            // পুরনো unique constraint থাকলে ড্রপ করো
            $indexes = collect(DB::select('SHOW INDEX FROM machines'))->pluck('Key_name');
            if ($indexes->contains('machines_auth_code_unique')) {
                $table->dropUnique('machines_auth_code_unique');
            }
            if ($indexes->contains('machines_machine_name_unique')) {
                $table->dropUnique('machines_machine_name_unique');
            }

            // নতুন composite unique constraints যোগ করো
            $table->unique(['user_id', 'auth_code']);
            $table->unique(['user_id', 'machine_name']);
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'auth_code']);
            $table->dropUnique(['user_id', 'machine_name']);
        });
    }
};

