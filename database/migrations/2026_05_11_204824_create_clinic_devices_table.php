<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            $table->string('name');

            $table->string('device_identifier')->nullable()->unique();
            $table->string('serial_number')->nullable();

            $table->string('device_type');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();

            $table->string('status', 20)->default('active')
                ->comment('Clinic device status: active, maintenance, offline, retired');

            $table->dateTime('last_maintenance_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('room_id');
            $table->index('device_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_devices');
    }
};
