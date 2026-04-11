<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
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

    protected static function booted()
    {
        static::created(function ($dispute) {
            // Notify Admins/Sharia Board
            $recipients = User::role('sharia_board')->get();
            if ($recipients->isEmpty()) {
                $recipients = User::where('is_admin', true)->get();
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
            ->dontLogEmptyChanges();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }
}
