<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $primaryKey = 'room_id';

    protected $fillable = [
        'type',
        'price',
        'person_num',
        'status'
    ];

    public function bookings()
    {
        return $this->hasMany(
            Booking::class,
            'room_id'
        );
    }
    public function images()
{
    return $this->hasMany(
        RoomImage::class,
        'room_id'
    );
}
} 

