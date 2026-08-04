<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'staff_id',
        'fixed_task_id',
        'status',

        // 🔥 مهم
        'weight'
    ];

    public function fixedTask()
    {
        return $this->belongsTo(FixedTask::class, 'fixed_task_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(TaskItemStatus::class, 'task_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
}