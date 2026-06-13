<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('visit_record_id')->nullable()->constrained('visit_records')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('status', 20)->default('unpaid')
                ->comment('Invoice status: unpaid, partial, paid, canceled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('staff')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
