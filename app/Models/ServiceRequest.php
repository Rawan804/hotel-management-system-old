<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'dep_id',
        'ser_id',
        'staff_id',
        'details',
        'location',
        'status',

        // 🔥 بدل الوقت
        'weight',

        // tracking
        'assign_attempts',
        'assigned_at',
        'accepted_at',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'book_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'dep_id', 'dep_id');
    }
public function service()
{
    return $this->belongsTo(
        Service::class,
        'ser_id',
        'ser_id'
    );
}
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
    

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
/*
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'service_location' =>
                $this->location ?: 'Room ' . optional($this->booking->room)->room_number,

            'dep_id' => $this->dep_id,
            'details' => $this->details,
            'status' => $this->status,

            // 🔥 النظام الجديد
            'weight' => $this->weight,

            'assign_attempts' => $this->assign_attempts,
            'assigned_at' => $this->assigned_at,
            'accepted_at' => $this->accepted_at,
            //جديد
          /*  'staff' => $this->staff ? [
    'staff_id' => $this->staff->staff_id,
    'name' => $this->staff->name,
    'status' => $this->staff->status,
] : null,*/
       /* ];
    }
*/
public function toArray(): array
{
    return [
        'id' => $this->id,

        'service_name' => $this->service
            ? (
                app()->getLocale() === 'ar'
                ? $this->service->name_ar
                : $this->service->name_en
            )
            : null,

        'service_location' =>
            $this->location ?: 'Room ' . optional($this->booking->room)->room_number,

        'details' => $this->details,

        'status' => $this->status,
    ];
}
}