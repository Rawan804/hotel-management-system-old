<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password'
    ];

    protected $hidden = [
        'password'
    ];

    public function bookings()
    {
        return $this->hasMany(
            Booking::class,
            'customer_id'
        );
    }

    public function services()
    {
        return $this->hasMany(
            ServiceCustomer::class,
            'customer_id'
        );
    }
}