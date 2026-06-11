<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_requests', function (Blueprint $table) {
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

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            $table->unsignedBigInteger('invoice_item_id')->nullable();

            $table->string('source')->nullable();

            $table->string('request_type');

            $table->text('notes')->nullable();

            $table->string('status')->default('pending_payment');
            $table->string('payment_status')->nullable()->default('pending');

            $table->string('priority')->default('normal');

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('sent_to_technician_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamp('payment_confirmed_at')->nullable();
            $table->timestamp('sent_to_technician_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            $table->index('patient_id');
            $table->index('visit_record_id');
            $table->index('appointment_id');
            $table->index('requested_by');
            $table->index('technician_id');
            $table->index('room_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('priority');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_requests');
    }
};
