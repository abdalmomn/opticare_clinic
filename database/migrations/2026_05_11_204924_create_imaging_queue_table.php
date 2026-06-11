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

            $table->foreignId('imaging_request_id')
                ->constrained('imaging_requests')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->integer('queue_number')->nullable();

            $table->string('status')->default('waiting');

            $table->dateTime('called_at')->nullable();

            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique('imaging_request_id', 'uniq_imaging_queue_request');

            $table->index('room_id');
            $table->index('technician_id');
            $table->index('status');
            $table->index('queue_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_queue');
    }
};
