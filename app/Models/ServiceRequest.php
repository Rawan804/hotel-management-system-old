<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'booking_id',
        'dep_id',
        'staff_id',
        'service_type',
        'details',
        'status'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }


    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

   

    public function department() {
        return $this->belongsTo(Department::class, 'dep_id');
    }

    public function serviceType() {
        return $this->belongsTo(ServiceType::class);
    }
}