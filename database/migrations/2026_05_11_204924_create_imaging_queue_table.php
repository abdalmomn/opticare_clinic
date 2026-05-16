<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_request_id')->constrained('imaging_requests')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->integer('queue_number');
            $table->string('status')->default('waiting'); // waiting - called - completed - skipped
            $table->dateTime('called_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_queue');
    }
};
