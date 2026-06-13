<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_patients', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('national_id')->nullable();
            $table->string('passport_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('identity_type', 20)
                ->default('national_id')
                ->comment('Patient identity document type: national_id, passport');
            $table->string('identity_number', 50)->nullable();
            $table->string('phone')->nullable();
            $table->string('gender', 10)->nullable()
                ->comment('Patient gender: male, female');
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status', 20)->nullable()
                ->comment('Marital status: single, married, divorced, widowed');
            $table->string('medical_file_number')->nullable();
            $table->unsignedBigInteger('central_user_id')->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('blood_type', 3)->nullable()
                ->comment('ABO/Rh blood type: A+, A-, B+, B-, AB+, AB-, O+, O-');
            $table->boolean('is_smoker')->nullable();
            $table->boolean('drinks_alcohol')->nullable();
            $table->json('chronic_diseases')->nullable();
            $table->json('diabetes_details')->nullable();
            $table->json('allergies')->nullable();
            $table->json('current_medications')->nullable();
            $table->json('previous_eye_surgeries')->nullable();
            $table->boolean('wears_glasses_or_lenses')->nullable();
            $table->text('family_ocular_history')->nullable();

            $table->string('status', 20)
                ->default('active')
                ->comment('Patient status: active, inactive, archived, deceased');
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
            $table->string('archive_reason', 30)->nullable()
                ->comment('Archive reason: no_longer_patient, transferred, duplicate, deceased, other');

            $table->text('archive_notes')->nullable();

            $table->date('deceased_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('identity_number', 'uniq_cp_identity_number');
            $table->unique('medical_file_number', 'uniq_cp_medical_file_number');

            $table->index('national_id', 'idx_cp_national_id');
            $table->index('passport_id', 'idx_cp_passport_id');
            $table->index('central_user_id', 'idx_cp_central_user');
            $table->index('phone', 'idx_cp_phone');
            $table->index('full_name', 'idx_cp_full_name');
            $table->index('is_active', 'idx_cp_is_active');
            $table->index('status', 'idx_cp_status');
            $table->index('archived_at', 'idx_cp_archived_at');
            $table->index('archive_reason', 'idx_cp_archive_reason');
            $table->index('deceased_at', 'idx_cp_deceased_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patients');
    }
};
