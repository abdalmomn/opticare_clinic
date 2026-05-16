<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_medical_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('title');
            $table->string('file_type');
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('staff')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_medical_files');
    }
};
