<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_codes', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50);
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique('code', 'uniq_diagnosis_codes_code');
            $table->index('is_active', 'idx_diagnosis_codes_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_codes');
    }
};
