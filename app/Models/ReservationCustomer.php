<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationCustomer extends Model
{
    use HasFactory;

    protected $primaryKey = 'res_cus_id';

    protected $fillable = [
        'customer_id',
        'res_id',
        'person_num',
        'reservation_time'    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'res_id');
    }
}