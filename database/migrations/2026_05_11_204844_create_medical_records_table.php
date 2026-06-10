<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('clinic_patients')
                ->cascadeOnDelete();

            $table->text('summary')->nullable();

            $table->unsignedBigInteger('last_visit_id')->nullable();
            $table->timestamp('last_visit_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique('patient_id', 'uniq_medical_records_patient_id');
            $table->index('last_visit_id', 'idx_medical_records_last_visit');
            $table->index('last_visit_at', 'idx_medical_records_last_visit_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
