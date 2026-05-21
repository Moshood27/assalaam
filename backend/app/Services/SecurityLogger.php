<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity;

class SecurityLogger
{
    /**
     * Log a suspicious action.
     *
     * @param string $action Description of the action
     * @param array $metadata Additional context
     * @param User|null $user The user involved (defaults to current auth user)
     */
    public static function logSuspiciousAction(string $action, array $metadata = [], ?User $user = null): void
    {
        $user = $user ?? auth()->user();

        activity('security')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(array_merge([
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'suspicious' => true,
                'severity' => 'high',
            ], $metadata))
            ->log($action);
    }

    /**
     * Log an unauthorized access attempt.
     */
    public static function logUnauthorizedAccess(string $resource, array $metadata = []): void
    {
        self::logSuspiciousAction("Unauthorized access attempt to {$resource}", array_merge([
            'resource' => $resource,
            'severity' => 'medium',
        ], $metadata));
    }

    /**
     * Log a sensitive data change.
     */
    public static function logSensitiveChange(string $description, array $changes, ?User $subject = null): void
    {
        activity('audit')
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' => Request::ip(),
                'changes' => $changes,
                'sensitive' => true,
            ])
            ->log($description);
    }
}
