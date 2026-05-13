<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVirtualAccount extends Model
{
    protected $fillable = [
        'user_id',
        'paystack_customer_code',
        'paystack_authorization_code',
        'dva_account_number',
        'dva_bank_name',
        'dva_account_name',
        'dva_verification_meta',
        'flw_dva_data',
        'monnify_customer_reference',
        'monnify_dva_data',
    ];

    protected $casts = [
        'dva_verification_meta' => 'array',
        'flw_dva_data' => 'array',
        'monnify_dva_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
