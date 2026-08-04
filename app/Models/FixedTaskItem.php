<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FixedTaskItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_task_id',
        'name_ar',
        'name_en'
    ];

    protected $hidden = [
        'name_ar',
        'name_en',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['name'];

    public function fixedTask()
    {
        return $this->belongsTo(
            FixedTask::class,
            'fixed_task_id',
            'id'
        );
    }

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }
}