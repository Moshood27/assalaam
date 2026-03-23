<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost_price',
        'markup_percent',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Computed selling price based on cost and markup percent.
     * Example: cost 100, markup 10 => 110
     */
    public function getSellingPriceAttribute(): string
    {
        $cost = (float) ($this->cost_price ?? 0);
        $percent = (float) ($this->markup_percent ?? 0);
        $price = $cost + ($cost * ($percent / 100));
        // Ensure 2dp string to be consistent with decimal casts
        return number_format($price, 2, '.', '');
    }
}
