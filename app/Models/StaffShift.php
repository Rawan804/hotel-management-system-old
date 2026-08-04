<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffShift extends Model
{
    protected $table = 'staff_shifts';

    protected $fillable = [
        'staff_id',
        'shift_date',
        'start_time',
        'end_time',
        'is_active',
    ];
protected $casts = [
    'shift_date' => 'date',
    'is_active'  => 'boolean',
];
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
}