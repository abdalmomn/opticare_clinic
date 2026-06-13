<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
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

            $table->longText('prescription_text')->nullable();

            $table->string('status', 20)
                ->default('draft')
                ->comment('Prescription status: draft, finalized');

            $table->timestamp('finalized_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('patient_id', 'idx_prescriptions_patient_id');
            $table->index('visit_record_id', 'idx_prescriptions_visit_record');
            $table->index('doctor_id', 'idx_prescriptions_doctor');
            $table->index('status', 'idx_prescriptions_status');
            $table->index('finalized_at', 'idx_prescriptions_finalized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
