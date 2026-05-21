<?php

namespace App\Listeners\Security;

use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class LogRoleChange
{
    /**
     * Handle role/permission attachment/detachment events.
     */
    public function handle(object $event): void
    {
        $action = match (get_class($event)) {
            RoleAttached::class => 'Role assigned',
            RoleDetached::class => 'Role removed',
            PermissionAttached::class => 'Permission assigned',
            PermissionDetached::class => 'Permission removed',
            default => 'Access change',
        };

        $model = $event->model;

        // Attempt to get a descriptive name for the target
        $targetName = 'Unknown';
        if ($model instanceof User) {
            $targetName = $model->full_name;
        } elseif (isset($model->name)) {
            $targetName = $model->name;
        } else {
            $targetName = class_basename($model) . " #{$model->getKey()}";
        }

        $data = property_exists($event, 'rolesOrIds') ? $event->rolesOrIds : (property_exists($event, 'permissionsOrIds') ? $event->permissionsOrIds : null);

        // Handle if property_exists check failed (using ternary correctly)
        if (!isset($data)) {
             $data = $event->rolesOrIds ?? $event->permissionsOrIds ?? null;
        }

        activity('security')
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => $action,
                'target_id' => $model->getKey(),
                'target_type' => get_class($model),
                'changes' => $data,
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ])
            ->log("{$action} to {$targetName}");
    }
}
