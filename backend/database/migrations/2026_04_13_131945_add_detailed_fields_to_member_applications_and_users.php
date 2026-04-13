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
        Schema::table('member_applications', function (Blueprint $table) {
            // Basic Personal Information
            $table->string('surname')->nullable()->after('name');
            $table->string('other_names')->nullable()->after('surname');
            $table->string('gender')->nullable();
            $table->string('native_place')->nullable();
            $table->date('dob')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('occupation')->nullable();

            // Contact Information
            $table->string('secondary_phone')->nullable();
            $table->string('residential_address')->nullable();
            $table->string('permanent_address')->nullable();

            // Business & Professional Information
            $table->string('nature_of_business')->nullable();
            $table->string('business_address')->nullable();
            $table->boolean('has_other_cooperatives')->default(false);
            $table->text('other_cooperative_details')->nullable();

            // Next of Kin
            $table->string('nok_name')->nullable();
            $table->string('nok_address')->nullable();
            $table->string('nok_phone')->nullable();
            $table->string('nok_relationship')->nullable();

            // Guarantor Details
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_address')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->string('guarantor_occupation')->nullable();
            $table->string('guarantor_signature_path')->nullable();

            // Religious Information & Imam's Attestation
            $table->string('religious_society_name')->nullable();
            $table->string('imam_name')->nullable();
            $table->string('mosque_address')->nullable();
            $table->string('imam_phone')->nullable();
            $table->string('duration_of_jamma_membership')->nullable();
            $table->boolean('imam_approval_status')->default(false);
            $table->timestamp('imam_approved_at')->nullable();

            // Information for Female Members
            $table->string('spouse_father_name')->nullable();
            $table->string('spouse_father_address')->nullable();
            $table->string('spouse_father_business_address')->nullable();
            $table->string('spouse_father_phone')->nullable();
            $table->string('spouse_father_consent_signature_path')->nullable();

            // Official Use Only
            $table->string('admission_form_number')->nullable();
            $table->date('admission_date')->nullable();
            $table->string('admission_officer_name')->nullable();
            $table->text('officer_recommendation')->nullable();
            $table->string('approval_status')->default('pending'); // pending, recommended, approved, rejected
            $table->string('president_signature_path')->nullable();
            $table->timestamp('president_signed_at')->nullable();
            $table->string('secretary_general_signature_path')->nullable();
            $table->timestamp('secretary_general_signed_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            // Persistent profile data
            $table->string('surname')->nullable()->after('name');
            $table->string('other_names')->nullable()->after('surname');
            $table->string('gender')->nullable();
            $table->string('native_place')->nullable();
            $table->date('dob')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('occupation')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('residential_address')->nullable();
            $table->string('permanent_address')->nullable();
            $table->string('nature_of_business')->nullable();
            $table->string('business_address')->nullable();

            // Next of Kin (already has some fields in Beneficiary, but keeping them on user is also common)
            $table->string('nok_name')->nullable();
            $table->string('nok_address')->nullable();
            $table->string('nok_phone')->nullable();
            $table->string('nok_relationship')->nullable();

            // Religious / Society info
            $table->string('religious_society_name')->nullable();

            // Official tracking
            $table->string('admission_form_number')->nullable();
            $table->date('admission_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropColumn([
                'surname', 'other_names', 'gender', 'native_place', 'dob', 'marital_status', 'occupation',
                'secondary_phone', 'residential_address', 'permanent_address',
                'nature_of_business', 'business_address', 'has_other_cooperatives', 'other_cooperative_details',
                'nok_name', 'nok_address', 'nok_phone', 'nok_relationship',
                'guarantor_name', 'guarantor_address', 'guarantor_phone', 'guarantor_occupation', 'guarantor_signature_path',
                'religious_society_name', 'imam_name', 'mosque_address', 'imam_phone', 'duration_of_jamma_membership', 'imam_approval_status', 'imam_approved_at',
                'spouse_father_name', 'spouse_father_address', 'spouse_father_business_address', 'spouse_father_phone', 'spouse_father_consent_signature_path',
                'admission_form_number', 'admission_date', 'admission_officer_name', 'officer_recommendation', 'approval_status',
                'president_signature_path', 'president_signed_at', 'secretary_general_signature_path', 'secretary_general_signed_at'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'surname', 'other_names', 'gender', 'native_place', 'dob', 'marital_status', 'occupation',
                'secondary_phone', 'residential_address', 'permanent_address',
                'nature_of_business', 'business_address',
                'nok_name', 'nok_address', 'nok_phone', 'nok_relationship',
                'religious_society_name',
                'admission_form_number', 'admission_date'
            ]);
        });
    }
};
