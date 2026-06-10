<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_file_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('imaging_file_id')
                ->constrained('imaging_files')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('clinic_patients')
                ->cascadeOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('visit_record_id')
                ->nullable()
                ->constrained('visit_records')
                ->nullOnDelete();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('imaging_file_id', 'idx_imaging_file_notes_file');
            $table->index('patient_id', 'idx_imaging_file_notes_patient');
            $table->index('doctor_id', 'idx_imaging_file_notes_doctor');
            $table->index('visit_record_id', 'idx_imaging_file_notes_visit');

            $table->unique(['imaging_file_id', 'doctor_id'], 'uniq_imaging_file_note_doctor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_file_notes');
    }
};
