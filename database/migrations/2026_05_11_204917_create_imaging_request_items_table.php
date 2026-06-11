<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imaging_request_items')) {
            return;
        }

        Schema::create('imaging_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_request_id')->constrained('imaging_requests')->cascadeOnDelete();
            $table->string('image_type');
            $table->string('eye')->nullable();
            $table->string('region')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('requested');
            $table->timestamps();

            $table->index(['imaging_request_id', 'status']);
            $table->index('image_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_request_items');
    }
};
