<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'cost_price',
        'markup_percent',
        'stock_quantity',
        'track_stock',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'cost_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'stock_quantity' => 'integer',
        'track_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with Category model.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

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

    /**
     * Ensure the stored image path is exposed as a public URL for the frontend.
     * Accepts either a full URL, a /storage path, or a disk-relative path like 'products/abc.jpg'.
     */
    public function getImageUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $val = (string) $value;
        if (Str::startsWith($val, ['http://', 'https://', '/storage/'])) {
            return $val;
        }
        // Treat as a path on the public disk (where Filament uploads by default in this resource)
        try {
            return Storage::disk('public')->url($val);
        } catch (\Throwable $e) {
            // Fallback to original value if disk/url resolution fails
            return $val;
        }
    }
}
