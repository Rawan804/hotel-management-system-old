<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{
    use HasFactory;

    protected $primaryKey = 'res_id';

    protected $fillable = [
        'name',
        'image',
        'details'
    ];

    public function reservations()
    {
        return $this->hasMany(ReservationCustomer::class, 'res_id');
    }
}