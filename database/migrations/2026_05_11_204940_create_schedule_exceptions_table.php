<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('schedulable_type');
            $table->unsignedBigInteger('schedulable_id');
            $table->date('exception_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('type', 20)
                ->comment('Schedule exception type: holiday, maintenance, emergency, custom');
            $table->text('reason')->nullable();
            $table->boolean('is_full_day')->default(true);
            $table->timestamps();

            $table->index(['schedulable_type', 'schedulable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
    }
};
