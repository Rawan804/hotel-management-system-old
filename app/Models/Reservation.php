<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $primaryKey = 'resev_id';

    protected $fillable = [
        'hall_id',
        'customer_id',
        'date',
        'status'
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}