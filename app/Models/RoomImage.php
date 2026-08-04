<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_category_id',
        'image'
    ];

    protected $hidden = [
        'image',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'image_url'
    ];

    public function category()
    {
        return $this->belongsTo(
            RoomCategory::class,
            'room_category_id'
        );
    }

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image);
    }
}