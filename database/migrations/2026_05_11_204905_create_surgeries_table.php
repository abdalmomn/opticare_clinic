<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('staff')->cascadeOnDelete();
            $table->string('surgery_type');
            $table->string('status', 20)->default('scheduled')
                ->comment('Surgery status: scheduled, completed, canceled');
            $table->text('notes')->nullable();
            $table->dateTime('surgery_date');
            $table->dateTime('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgeries');
    }
};
