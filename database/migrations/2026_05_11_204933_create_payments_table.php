<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 20)
                ->comment('Payment method: cash, card, transfer');
            $table->dateTime('payment_date');
            $table->foreignId('received_by')->constrained('staff')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
