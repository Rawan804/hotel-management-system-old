<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_category_id',
        'room_number',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function category()
    {
        return $this->belongsTo(
            RoomCategory::class,
            'room_category_id'
        );
    }

    public function bookings()
    {
        return $this->hasMany(
            Booking::class,
            'room_id'
        );
    }
}