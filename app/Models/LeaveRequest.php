<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'leave_id';

    protected $fillable = [
        'staff_id',
        'title',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}