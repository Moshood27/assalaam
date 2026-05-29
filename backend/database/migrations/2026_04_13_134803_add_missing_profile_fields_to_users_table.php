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
            if (!Schema::hasColumn('users', 'has_other_cooperatives')) {
                $table->boolean('has_other_cooperatives')->default(false)->after('business_address');
            }
            if (!Schema::hasColumn('users', 'other_cooperative_details')) {
                $table->text('other_cooperative_details')->nullable()->after('has_other_cooperatives');
            }

            // Guarantor Details
            if (!Schema::hasColumn('users', 'guarantor_name')) {
                $table->string('guarantor_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'guarantor_address')) {
                $table->string('guarantor_address')->nullable();
            }
            if (!Schema::hasColumn('users', 'guarantor_phone')) {
                $table->string('guarantor_phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'guarantor_occupation')) {
                $table->string('guarantor_occupation')->nullable();
            }
            if (!Schema::hasColumn('users', 'guarantor_signature_path')) {
                $table->string('guarantor_signature_path')->nullable();
            }

            // Religious Information & Imam's Attestation
            if (!Schema::hasColumn('users', 'imam_name')) {
                $table->string('imam_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'mosque_address')) {
                $table->string('mosque_address')->nullable();
            }
            if (!Schema::hasColumn('users', 'imam_phone')) {
                $table->string('imam_phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'duration_of_jamma_membership')) {
                $table->string('duration_of_jamma_membership')->nullable();
            }
            if (!Schema::hasColumn('users', 'imam_approval_status')) {
                $table->boolean('imam_approval_status')->default(false);
            }
            if (!Schema::hasColumn('users', 'imam_approved_at')) {
                $table->timestamp('imam_approved_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'imam_signature_path')) {
                $table->string('imam_signature_path')->nullable();
            }

            // Documents
            if (!Schema::hasColumn('users', 'passport_path')) {
                $table->string('passport_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'id_card_path')) {
                $table->string('id_card_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'proof_of_address_path')) {
                $table->string('proof_of_address_path')->nullable();
            }

            // Information for Female Members
            if (!Schema::hasColumn('users', 'spouse_father_name')) {
                $table->string('spouse_father_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'spouse_father_address')) {
                $table->string('spouse_father_address')->nullable();
            }
            if (!Schema::hasColumn('users', 'spouse_father_business_address')) {
                $table->string('spouse_father_business_address')->nullable();
            }
            if (!Schema::hasColumn('users', 'spouse_father_phone')) {
                $table->string('spouse_father_phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'spouse_father_consent_signature_path')) {
                $table->string('spouse_father_consent_signature_path')->nullable();
            }

            // Official Use Only
            if (!Schema::hasColumn('users', 'admission_officer_name')) {
                $table->string('admission_officer_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'officer_recommendation')) {
                $table->text('officer_recommendation')->nullable();
            }
            if (!Schema::hasColumn('users', 'approval_status')) {
                $table->string('approval_status')->default('approved'); // If they are in users, they are probably approved
            }
            if (!Schema::hasColumn('users', 'president_signature_path')) {
                $table->string('president_signature_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'president_signed_at')) {
                $table->timestamp('president_signed_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'secretary_general_signature_path')) {
                $table->string('secretary_general_signature_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'secretary_general_signed_at')) {
                $table->timestamp('secretary_general_signed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'has_other_cooperatives', 'other_cooperative_details',
                'guarantor_name', 'guarantor_address', 'guarantor_phone', 'guarantor_occupation', 'guarantor_signature_path',
                'imam_name', 'mosque_address', 'imam_phone', 'duration_of_jamma_membership', 'imam_approval_status', 'imam_approved_at', 'imam_signature_path',
                'passport_path', 'id_card_path', 'proof_of_address_path',
                'spouse_father_name', 'spouse_father_address', 'spouse_father_business_address', 'spouse_father_phone', 'spouse_father_consent_signature_path',
                'admission_officer_name', 'officer_recommendation', 'approval_status',
                'president_signature_path', 'president_signed_at', 'secretary_general_signature_path', 'secretary_general_signed_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
