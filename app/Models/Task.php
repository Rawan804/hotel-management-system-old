<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $primaryKey = 'task_id';

    protected $fillable = [
        'staff_id',
        'name',
        'description',
        'status',
        'date'
    ];

    public function staff()
    {
        return $this->belongsTo(
            Staff::class,
            'staff_id'
        );
    }
}