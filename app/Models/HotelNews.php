<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
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
        'created_by'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'title',
        'content',
        'image_url'
    ];

    protected $hidden = [
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
    ];

    public function creator()
    {
        return $this->belongsTo(
            Staff::class,
            'created_by',
            'staff_id'
        );
    }

    public function getTitleAttribute()
    {
        return App::getLocale() === 'ar'
            ? $this->title_ar
            : $this->title_en;
    }

    public function getContentAttribute()
    {
        return App::getLocale() === 'ar'
            ? $this->content_ar
            : $this->content_en;
    }

    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }
}