<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_clinic_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            // null if medical center role (not clinic-specific)
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('role_name');
            // is_temporary: temporary assignment flag for doctors who are covering for others or have short-term roles
            $table->boolean('is_temporary')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();

            // one staff member can only have one instance of a specific role in a specific clinic
            $table->unique(['staff_id', 'clinic_id', 'role_name'], 'unique_staff_clinic_role');

            $table->index(['staff_id', 'clinic_id']);
            $table->index('role_name');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_clinic_roles');
    }
};
