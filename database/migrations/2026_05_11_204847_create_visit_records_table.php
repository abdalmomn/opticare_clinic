<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('clinic_patients')
                ->cascadeOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->nullOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->enum('status', ['draft', 'finalized', 'cancelled'])
                ->default('draft');

            $table->enum('visit_type', [
                'consultation',
                'follow_up',
                'emergency',
                'post_op',
            ])->default('consultation');

            $table->dateTime('visit_at')->nullable();

            $table->text('chief_complaint')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('notes')->nullable();

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

            $table->unique('appointment_id', 'uniq_visit_records_appointment_id');
            $table->index('patient_id', 'idx_visit_records_patient_id');
            $table->index('doctor_id', 'idx_visit_records_doctor_id');
            $table->index('status', 'idx_visit_records_status');
            $table->index('visit_type', 'idx_visit_records_visit_type');
            $table->index('visit_at', 'idx_visit_records_visit_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_records');
    }
};
