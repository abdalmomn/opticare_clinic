<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_measurements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('clinic_patients')
                ->cascadeOnDelete();

            $table->foreignId('visit_record_id')
                ->nullable()
                ->constrained('visit_records')
                ->nullOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->nullOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->string('visual_acuity_od', 50)->nullable();
            $table->string('visual_acuity_os', 50)->nullable();

            $table->decimal('iop_od', 5, 2)->nullable();
            $table->decimal('iop_os', 5, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('measured_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('patient_id', 'idx_eye_measurements_patient_id');
            $table->index('visit_record_id', 'idx_eye_measurements_visit_record');
            $table->index('appointment_id', 'idx_eye_measurements_appointment');
            $table->index('doctor_id', 'idx_eye_measurements_doctor');
            $table->index('measured_at', 'idx_eye_measurements_measured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_measurements');
    }
};
