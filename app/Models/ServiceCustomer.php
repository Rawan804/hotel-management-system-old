<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceCustomer extends Model
{
    use HasFactory;

    protected $primaryKey = 'ser_cust_id';

    protected $fillable = [
        'ser_id',
        'customer_id'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'ser_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}