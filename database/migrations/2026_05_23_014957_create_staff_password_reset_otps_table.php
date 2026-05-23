<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
            $table->string('email')->index();
            $table->string('captcha_token_hash')->nullable();
            $table->string('otp_hash');
            $table->timestamp('otp_expires_at');
            $table->unsignedSmallInteger('resend_count')->default(0);
            $table->timestamp('resend_available_at')->nullable();
            $table->string('reset_token_hash')->nullable();
            $table->timestamp('reset_token_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_password_reset_otps');
    }
};
