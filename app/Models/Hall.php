<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hall extends Model
{
    use HasFactory;

    protected $primaryKey = 'hall_id';

    protected $fillable = [
        'image',
        'details',
        'capacity',
        'price'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'hall_id');
    }
}