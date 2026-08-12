<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $primaryKey = 'dep_id';

    protected $fillable = [
        'name'
        //name_en
        //name_ar
    ];

    public function staff()
    {
        return $this->hasMany(Staff::class,'dep_id');
    }
    public function fixedTasks()
{
    return $this->hasMany(
        FixedTask::class,
        'dep_id',
        'dep_id'
    );
}
}