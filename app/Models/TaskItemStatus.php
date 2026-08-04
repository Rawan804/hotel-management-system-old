<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskItemStatus extends Model
{
    protected $fillable = [
        'task_id',
        'fixed_task_item_id',
        'is_done'
    ];

    public function item()
    {
        return $this->belongsTo(
            FixedTaskItem::class,
            'fixed_task_item_id',
            'id'
        );
    }

    public function task()
    {
        return $this->belongsTo(
            Task::class,
            'task_id',
            'id'
        );
    }
}