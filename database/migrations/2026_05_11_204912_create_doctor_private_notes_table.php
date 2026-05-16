<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_private_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('visit_record_id')->nullable()->constrained('visit_records')->nullOnDelete();
            $table->foreignId('doctor_id')->constrained('staff')->cascadeOnDelete();
            $table->text('note');
            $table->string('visibility')->default('private'); // private - shared_internal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_private_notes');
    }
};
