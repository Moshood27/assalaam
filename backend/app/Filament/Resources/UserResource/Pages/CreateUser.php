<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\NewMemberAdminNotification;
use App\Mail\NewMemberWelcome;
use App\Models\User as UserModel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record; // Newly created User model

        DB::afterCommit(function () use ($user) {
            // Send welcome email to the new member if they have an email
            if (!empty($user->email)) {
                try {
                    Mail::to($user->email)->send(new NewMemberWelcome($user));
                } catch (\Throwable $e) {
                    // Swallow email errors to avoid blocking the admin action
                }
            }

            // Notify all admins about the new member
            try {
                $adminEmails = UserModel::query()
                    ->where('is_admin', true)
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->all();

                if (!empty($adminEmails)) {
                    Mail::to($adminEmails)->send(new NewMemberAdminNotification($user));
                }
            } catch (\Throwable $e) {
                // Ignore admin notification failures as well
            }
        });
    }
}
