<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'name',
        'surname',
        'other_names',
        'gender',
        'native_place',
        'dob',
        'marital_status',
        'occupation',
        'email',
        'phone',
        'secondary_phone',
        'address',
        'residential_address',
        'permanent_address',
        'branch_id',
        'nature_of_business',
        'business_address',
        'has_other_cooperatives',
        'other_cooperative_details',
        'nok_name',
        'nok_address',
        'nok_phone',
        'nok_relationship',
        'guarantor_name',
        'guarantor_address',
        'guarantor_phone',
        'guarantor_occupation',
        'guarantor_signature_path',
        'religious_society_name',
        'imam_name',
        'mosque_address',
        'imam_phone',
        'duration_of_jamma_membership',
        'imam_approval_status',
        'imam_approved_at',
        'spouse_father_name',
        'spouse_father_address',
        'spouse_father_business_address',
        'spouse_father_phone',
        'spouse_father_consent_signature_path',
        'admission_form_number',
        'admission_date',
        'admission_officer_name',
        'officer_recommendation',
        'approval_status',
        'president_signature_path',
        'president_signed_at',
        'secretary_general_signature_path',
        'secretary_general_signed_at',
        'password_hash',
        'passport_path',
        'id_card_path',
        'proof_of_address_path',
        'email_otp_hash',
        'sms_otp_hash',
        'otp_expires_at',
        'email_verified_at',
        'phone_verified_at',
        'submitted_at',
        'finalized_at',
        'last_otp_sent_at',
        'email_otp_attempts',
        'sms_otp_attempts',
    ];

    protected $casts = [
        'dob' => 'date',
        'has_other_cooperatives' => 'boolean',
        'imam_approval_status' => 'boolean',
        'imam_approved_at' => 'datetime',
        'admission_date' => 'date',
        'president_signed_at' => 'datetime',
        'secretary_general_signed_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
        'last_otp_sent_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
