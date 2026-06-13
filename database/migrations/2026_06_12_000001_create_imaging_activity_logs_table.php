<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('imaging_request_id')
                ->nullable()
                ->constrained('imaging_requests')
                ->nullOnDelete();

            $table->foreignId('imaging_file_id')
                ->nullable()
                ->constrained('imaging_files')
                ->nullOnDelete();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->string('action', 40)
                ->comment('Activity action: request_created, request_cancelled, payment_confirmed, payment_waived, sent_to_technician, started, file_uploaded, file_deleted, completed, direct_upload_created, external_upload_created, device_created, device_updated, device_activated, device_deactivated, device_deleted_or_retired');

            $table->string('from_status', 30)->nullable()
                ->comment('Imaging request status before the action (see imaging_requests.status)');
            $table->string('to_status', 30)->nullable()
                ->comment('Imaging request status after the action (see imaging_requests.status)');

            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('imaging_request_id');
            $table->index('imaging_file_id');
            $table->index('actor_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_activity_logs');
    }
};
