<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('imaging_request_id')
                ->constrained('imaging_requests')
                ->cascadeOnDelete();

            $table->foreignId('imaging_request_item_id')
                ->nullable()
                ->constrained('imaging_request_items')
                ->nullOnDelete();

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('clinic_patients')
                ->nullOnDelete();

            $table->foreignId('visit_record_id')
                ->nullable()
                ->constrained('visit_records')
                ->nullOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->nullOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained('clinic_devices')
                ->nullOnDelete();

            $table->unsignedBigInteger('upload_batch_id')->nullable();

            $table->string('source')->nullable();

            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();

            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');

            $table->string('device_name')->nullable();

            $table->string('modality')->nullable();
            $table->string('image_type')->nullable();
            $table->string('eye')->nullable();
            $table->string('region')->nullable();
            $table->string('image_label')->nullable();

            $table->dateTime('captured_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->boolean('is_primary')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('imaging_request_id');
            $table->index('imaging_request_item_id');
            $table->index('patient_id');
            $table->index('visit_record_id');
            $table->index('appointment_id');
            $table->index('uploaded_by');
            $table->index('device_id');
            $table->index('source');
            $table->index('modality');
            $table->index('image_type');
            $table->index('eye');
            $table->index('region');
            $table->index('captured_at');
            $table->index('uploaded_at');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_files');
    }
};
