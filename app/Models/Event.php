<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title_ar',
        'title_en',

        'description_ar',
        'description_en',

        'location_ar',
        'location_en',

        'event_date',
        'event_time',

        'image',

        'contact_phone',

        'is_active'
    ];

    protected $hidden = [
        'title_ar',
        'title_en',

        'description_ar',
        'description_en',

        'location_ar',
        'location_en',

        'image',

        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'title',
        'description',
        'location',
        'image_url'
    ];

    public function getTitleAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->title_ar
            : $this->title_en;
    }

    public function getDescriptionAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->description_ar
            : $this->description_en;
    }

    public function getLocationAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->location_ar
            : $this->location_en;
    }

    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }
}