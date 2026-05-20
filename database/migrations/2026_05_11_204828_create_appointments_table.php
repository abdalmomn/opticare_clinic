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
            $table->string('national_id')->nullable();
            $table->string('username')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('clinic_patients')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('pending'); // pending - confirmed - canceled
            $table->string('appointment_type')->default('consultation'); // consultation - follow_up - surgery - imaging - checkup
            $table->string('source')->default('secretary'); // secretary - doctor - patient_app - system
            $table->dateTime('appointment_date');
            $table->timestamps();

            $table->index('status');
            $table->index('appointment_type');
            $table->index('source');
            $table->index('appointment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
