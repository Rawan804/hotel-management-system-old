<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }


    public function services()
    {
        return $this->hasMany(ServiceCustomer::class);
    }

     public function ReservationCustomer() {
    return $this->hasMany(ReservationCustomer::class, 'customer_id');
     }
public function hallReservations()
{
    return $this->hasMany(\App\Models\Reservation::class, 'customer_id');
}

public function restaurantReservations()
{
    return $this->hasMany(\App\Models\ReservationCustomer::class, 'customer_id');
}
    public function  Reservation() {
    return $this->hasMany( Reservation::class, 'customer_id');
     }


    }