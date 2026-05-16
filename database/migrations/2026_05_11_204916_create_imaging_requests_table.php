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
            $table->foreignId('patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('visit_record_id')->nullable()->constrained('visit_records')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('request_type');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending - in_progress - completed - canceled
            $table->string('priority')->default('normal'); // normal - urgent
            $table->timestamp('created_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_requests');
    }
};
