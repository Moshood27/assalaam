<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Business & Professional Information extra
            $table->boolean('has_other_cooperatives')->default(false)->after('business_address');
            $table->text('other_cooperative_details')->nullable()->after('has_other_cooperatives');

            // Guarantor Details
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_address')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->string('guarantor_occupation')->nullable();
            $table->string('guarantor_signature_path')->nullable();

            // Religious Information & Imam's Attestation
            $table->string('imam_name')->nullable();
            $table->string('mosque_address')->nullable();
            $table->string('imam_phone')->nullable();
            $table->string('duration_of_jamma_membership')->nullable();
            $table->boolean('imam_approval_status')->default(false);
            $table->timestamp('imam_approved_at')->nullable();
            $table->string('imam_signature_path')->nullable();

            // Documents
            $table->string('passport_path')->nullable();
            $table->string('id_card_path')->nullable();
            $table->string('proof_of_address_path')->nullable();

            // Information for Female Members
            $table->string('spouse_father_name')->nullable();
            $table->string('spouse_father_address')->nullable();
            $table->string('spouse_father_business_address')->nullable();
            $table->string('spouse_father_phone')->nullable();
            $table->string('spouse_father_consent_signature_path')->nullable();

            // Official Use Only
            $table->string('admission_officer_name')->nullable();
            $table->text('officer_recommendation')->nullable();
            $table->string('approval_status')->default('approved'); // If they are in users, they are probably approved
            $table->string('president_signature_path')->nullable();
            $table->timestamp('president_signed_at')->nullable();
            $table->string('secretary_general_signature_path')->nullable();
            $table->timestamp('secretary_general_signed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'has_other_cooperatives', 'other_cooperative_details',
                'guarantor_name', 'guarantor_address', 'guarantor_phone', 'guarantor_occupation', 'guarantor_signature_path',
                'imam_name', 'mosque_address', 'imam_phone', 'duration_of_jamma_membership', 'imam_approval_status', 'imam_approved_at', 'imam_signature_path',
                'passport_path', 'id_card_path', 'proof_of_address_path',
                'spouse_father_name', 'spouse_father_address', 'spouse_father_business_address', 'spouse_father_phone', 'spouse_father_consent_signature_path',
                'admission_officer_name', 'officer_recommendation', 'approval_status',
                'president_signature_path', 'president_signed_at', 'secretary_general_signature_path', 'secretary_general_signed_at'
            ]);
        });
    }
};
