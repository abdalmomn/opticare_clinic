<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('room_type', 30)
                ->comment('Room type: imaging, clinic, surgery, lab, reception, external_center');
            $table->foreignId('parent_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('room_type', 'idx_rooms_room_type');
            $table->index('is_active', 'idx_rooms_is_active');
            $table->index(
                ['room_type', 'is_active'],
                'idx_rooms_type_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
