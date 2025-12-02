<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pending_device_commands', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('last_attempt_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('pending_device_commands', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'last_attempt_at']);
        });
    }
};
