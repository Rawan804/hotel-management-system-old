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
    ];

    public function staff()
    {
        return $this->hasMany(Staff::class,'dep_id');
    }
}