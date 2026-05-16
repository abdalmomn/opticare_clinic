<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedulable_type'); // staff - room
            $table->unsignedBigInteger('schedulable_id');
            $table->tinyInteger('day_of_week'); // 0=Sunday ... 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['schedulable_type', 'schedulable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
