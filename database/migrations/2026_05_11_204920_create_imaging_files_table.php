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
            $table->foreignId('imaging_request_id')->constrained('imaging_requests')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('staff')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->string('device_name')->nullable();
            $table->string('modality')->nullable();
            $table->dateTime('captured_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_files');
    }
};
