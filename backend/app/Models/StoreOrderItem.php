<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'unit_cost',
        'line_total',
        'line_cost',
        'line_profit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_cost' => 'decimal:2',
        'line_profit' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
