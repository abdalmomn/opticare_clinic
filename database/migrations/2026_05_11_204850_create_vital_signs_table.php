<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_record_id')->constrained('visit_records')->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('staff')->cascadeOnDelete();
            $table->string('blood_pressure')->nullable();
            $table->integer('heart_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
