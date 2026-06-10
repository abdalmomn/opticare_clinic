<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_report_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medical_report_id')
                ->constrained('medical_reports')
                ->cascadeOnDelete();

            $table->foreignId('imaging_request_id')
                ->nullable()
                ->constrained('imaging_requests')
                ->nullOnDelete();

            $table->foreignId('imaging_file_id')
                ->nullable()
                ->constrained('imaging_files')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('medical_report_id', 'idx_report_images_report_id');
            $table->index('imaging_request_id', 'idx_report_images_request_id');
            $table->index('imaging_file_id', 'idx_report_images_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_report_images');
    }
};
