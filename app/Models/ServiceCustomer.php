<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCustomer extends Model
{
    use HasFactory;

    protected $table = 'service_customers';

    protected $primaryKey = 'ser_cust_id';

    protected $fillable = [
        'ser_id',
        'customer_id',
    ];

      public function service()
    {
        return $this->belongsTo(Service::class, 'ser_id', 'ser_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->ser_cust_id,

            'service_id' => $this->ser_id,
            'customer_id' => $this->customer_id,

            'service' => $this->service ? $this->service->toArray() : null,
        ];
    }
}