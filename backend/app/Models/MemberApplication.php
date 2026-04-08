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
        'email',
        'phone',
        'address',
        'branch_id',
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
