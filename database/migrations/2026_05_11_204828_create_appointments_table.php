<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained('clinic_patients')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('status', 30)->default('booked')
                ->comment('Appointment workflow status: booked, confirmed, waiting, in_progress, completed, cancelled, no_show');
            $table->string('type', 40)->default('consultation')
                ->comment('Appointment type: consultation, follow_up, imaging, consultation_and_imaging, surgery_preparation');
            $table->string('source', 30)->default('secretary')
                ->comment('Appointment creation source: secretary, doctor, patient_app, system');
            $table->dateTime('appointment_at');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedInteger('queue_number')->nullable()
                ->comment('Daily waiting queue number assigned during check-in');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();

            $table->index('doctor_id', 'idx_appointments_doctor');
            $table->index('room_id', 'idx_appointments_room');
            $table->index('status', 'idx_appointments_status');
            $table->index('type', 'idx_appointments_type');
            $table->index('source', 'idx_appointments_source');
            $table->index('appointment_date', 'idx_appointments_date');

            $table->index(
                ['doctor_id', 'appointment_date'],
                'idx_appointments_doctor_date'
            );

            $table->index(
                ['appointment_date', 'status'],
                'idx_appointments_date_status'
            );

            $table->unique(
                ['appointment_date', 'queue_number'],
                'uniq_appointments_daily_queue'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
