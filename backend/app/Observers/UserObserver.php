<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\AttendanceRecord;
use App\Services\AdministrativeChargeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // If balance increased
        if ($user->wasChanged('balance') && $user->balance > $user->getOriginal('balance')) {
            // Process all outstanding dues
            app(AdministrativeChargeService::class)->settleAllDues($user);
        }
    }
}
