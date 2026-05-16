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
            $table->string('status')->default('pending'); // pending - confirmed - canceled
            $table->dateTime('appointment_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
