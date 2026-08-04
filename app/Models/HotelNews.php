<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelNews extends Model
{
    use HasFactory;

    protected $table = 'hotel_news';

    protected $fillable = [
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'image',
        'is_pinned',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    // فقط رابط الصورة (بدون إظهار image الخام)
    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }
}