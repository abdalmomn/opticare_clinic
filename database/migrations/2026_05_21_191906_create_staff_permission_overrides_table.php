<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_permission_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_id')
                ->constrained('staff')
                ->cascadeOnDelete();

            $table->string('permission_name');

            $table->enum('effect', ['grant', 'deny']);
            // grant = add permission to this staff
            // deny  = block permission from this staff even if role has it

            $table->boolean('is_temporary')->default(false);
            $table->timestamp('expires_at')->nullable();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['staff_id', 'permission_name']);

            $table->index('effect');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_permission_overrides');
    }
};
