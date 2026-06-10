<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_diagnosis_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_record_id')
                ->constrained('visit_records')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('clinic_patients')
                ->cascadeOnDelete();

            $table->foreignId('diagnosis_code_id')
                ->constrained('diagnosis_codes')
                ->cascadeOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['visit_record_id', 'diagnosis_code_id'],
                'uniq_visit_diagnosis_code'
            );

            $table->index('visit_record_id', 'idx_visit_diag_codes_visit_record');
            $table->index('patient_id', 'idx_visit_diag_codes_patient');
            $table->index('diagnosis_code_id', 'idx_visit_diag_codes_code');
            $table->index('doctor_id', 'idx_visit_diag_codes_doctor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_diagnosis_codes');
    }
};
