<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class StoreOrder extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'shipping_address', 'payment_reference', 'disbursed_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'user_id',
        'reference',
        'total_amount',
        'total_cost',
        'total_profit',
        'status',
        'meta',
        'ledger_journal_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'meta' => 'array',
    ];

    protected static function booted()
    {
        static::updated(function ($order) {
            if ($order->wasChanged('status') && $order->status === 'completed') {
                $order->processVendorPayouts();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StoreOrderItem::class);
    }

    public function dispute()
    {
        return $this->hasOne(ShariaDispute::class, 'store_order_id');
    }

    public function processVendorPayouts()
    {
        if ($this->status !== 'completed') return;

        foreach ($this->items()->whereNotNull('vendor_id')->whereNull('vendor_paid_at')->get() as $item) {
            $vendor = $item->vendor;
            if (!$vendor || !$vendor->owner_user_id) continue;

            $owner = $vendor->owner;
            if (!$owner) continue;

            \Illuminate\Support\Facades\DB::transaction(function () use ($item, $vendor, $owner) {
                $owner->increment('balance', $item->vendor_amount);

                $item->update([
                    'vendor_paid_at' => now(),
                    'vendor_payout_reference' => 'PAYOUT_' . $this->reference . '_' . $item->id,
                ]);

                WalletTransaction::create([
                    'user_id' => $owner->id,
                    'type' => 'credit',
                    'amount' => $item->vendor_amount,
                    'reference' => $item->vendor_payout_reference,
                    'source' => 'vendor_payout',
                    'meta' => [
                        'store_order_id' => $this->id,
                        'store_order_item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'vendor_id' => $vendor->id,
                    ],
                ]);
            });
        }
    }
}
