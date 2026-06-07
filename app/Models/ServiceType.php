<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'dep_id'];

    public function department() {
        return $this->belongsTo(Department::class, 'dep_id');
    }
}