<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('clinic_patients')
                ->cascadeOnDelete();

            $table->foreignId('visit_record_id')
                ->nullable()
                ->constrained('visit_records')
                ->nullOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->string('title')->nullable();
            $table->longText('report_text')->nullable();

            $table->enum('status', ['draft', 'finalized'])
                ->default('draft');

            $table->timestamp('finalized_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('patient_id', 'idx_medical_reports_patient_id');
            $table->index('visit_record_id', 'idx_medical_reports_visit_record');
            $table->index('doctor_id', 'idx_medical_reports_doctor');
            $table->index('status', 'idx_medical_reports_status');
            $table->index('finalized_at', 'idx_medical_reports_finalized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_reports');
    }
};
