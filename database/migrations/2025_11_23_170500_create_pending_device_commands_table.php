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
        Schema::create('pending_device_commands', function (Blueprint $table) {
            $table->id();
            $table->string('mac_id');
            $table->string('command');
            $table->text('payload')->nullable();
            $table->string('transaction_id')->unique(); // Unique ID for tracking acknowledgment
            $table->enum('status', ['pending', 'acknowledged', 'failed', 'timeout'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            $table->index(['mac_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_device_commands');
    }
};
