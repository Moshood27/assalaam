<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ShariaBoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'bio',
        'photo_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Ensure the stored image path is exposed as a public URL for the frontend.
     */
    public function getPhotoUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $val = (string) $value;
        if (Str::startsWith($val, ['http://', 'https://', '/storage/'])) {
            return $val;
        }
        try {
            return Storage::disk('public')->url($val);
        } catch (\Throwable $e) {
            return $val;
        }
    }

    /**
     * Sanitize bio to prevent XSS while allowing basic formatting.
     */
    public function setBioAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['bio'] = $value;
            return;
        }

        // Allow only safe tags
        $allowedTags = '<p><br><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        $cleaned = strip_tags($value, $allowedTags);

        // Remove dangerous attributes like on* and javascript:
        $cleaned = preg_replace('/\s+on\w+="[^"]*"/i', '', $cleaned);
        $cleaned = preg_replace('/\s+on\w+=\'[^\']*\'/i', '', $cleaned);
        $cleaned = preg_replace('/\s+on\w+=[^\s>]+/i', '', $cleaned);
        $cleaned = preg_replace('/href="javascript:[^"]*"/i', 'href="#"', $cleaned);
        $cleaned = preg_replace('/href=\'javascript:[^\']*\'/i', 'href="#"', $cleaned);

        $this->attributes['bio'] = $cleaned;
    }
}
