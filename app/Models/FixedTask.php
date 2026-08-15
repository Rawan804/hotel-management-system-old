<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FixedTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'dep_id',
        'name_ar',
        'name_en',

        'weight',

        'is_active'
    ];

    protected $appends = ['name'];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    public function items()
    {
        return $this->hasMany(FixedTaskItem::class, 'fixed_task_id', 'id');
    }

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }
}