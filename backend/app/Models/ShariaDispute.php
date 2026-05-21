<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\User;
use App\Models\StoreOrder;
use App\Notifications\ShariaDisputeNotification;
use Illuminate\Support\Facades\Notification;

class ShariaDispute extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'store_order_id',
        'reason',
        'description',
        'status',
        'mediation_notes',
        'outcome_details',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Sanitize mediation notes (RichEditor).
     */
    public function setMediationNotesAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['mediation_notes'] = $value;
            return;
        }

        $allowedTags = '<p><br><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        $cleaned = strip_tags($value, $allowedTags);
        $cleaned = preg_replace('/\s+on\w+="[^"]*"/i', '', $cleaned);
        $cleaned = preg_replace('/\s+on\w+=\'[^\']*\'/i', '', $cleaned);
        $cleaned = preg_replace('/\s+on\w+=[^\s>]+/i', '', $cleaned);
        $cleaned = preg_replace('/href="javascript:[^"]*"/i', 'href="#"', $cleaned);
        $cleaned = preg_replace('/href=\'javascript:[^\']*\'/i', 'href="#"', $cleaned);

        $this->attributes['mediation_notes'] = $cleaned;
    }

    /**
     * Sanitize outcome details (Plain text).
     */
    public function setOutcomeDetailsAttribute($value)
    {
        $this->attributes['outcome_details'] = strip_tags($value);
    }

    protected static function booted()
    {
        static::created(function ($dispute) {
            // Notify Admins/Sharia Board who are suppose to receive it
            $user = $dispute->user;
            if (!$user) return;

            $recipients = $user->getAuthorizedAdmins()->filter(function ($admin) {
                return $admin->hasRole('sharia_board') || $admin->hasRole('super_admin');
            });

            if ($recipients->isEmpty()) {
                $recipients = $user->getAuthorizedAdmins();
            }

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ShariaDisputeNotification($dispute, 'created'));
            }
        });

        static::updated(function ($dispute) {
            if ($dispute->wasChanged('status')) {
                if ($dispute->status === 'resolved' || $dispute->status === 'rejected') {
                    $dispute->resolved_at = now();
                    $dispute->saveQuietly();
                }

                if ($dispute->user) {
                    $dispute->user->notify(new ShariaDisputeNotification($dispute, 'updated'));
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'mediation_notes', 'outcome_details', 'resolved_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    /**
     * Get the items for the order associated with this dispute.
     * This is used by the Filament resource for the repeater.
     */
    public function orderItems()
    {
        return $this->hasManyThrough(
            StoreOrderItem::class,
            StoreOrder::class,
            'id', // Local key on ShariaDispute's related model (StoreOrder)
            'store_order_id', // Local key on StoreOrderItem table (matches StoreOrder's ID)
            'store_order_id', // Foreign key on ShariaDispute table (points to StoreOrder)
            'id' // Local key on StoreOrder table
        );
    }
}
