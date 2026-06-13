<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 20)->default('open')
                ->comment('Conversation status: open, closed, waiting');
            $table->dateTime('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
