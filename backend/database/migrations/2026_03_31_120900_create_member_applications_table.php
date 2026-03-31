<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_applications', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->string('password_hash');
            // Documents
            $table->string('passport_path')->nullable();
            $table->string('id_card_path')->nullable();
            $table->string('proof_of_address_path')->nullable();
            // OTPs
            $table->string('email_otp_hash')->nullable();
            $table->string('sms_otp_hash')->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('phone_verified_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('last_otp_sent_at')->nullable();
            $table->unsignedSmallInteger('email_otp_attempts')->default(0);
            $table->unsignedSmallInteger('sms_otp_attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_applications');
    }
};
