<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $primaryKey = 'ser_id';

    protected $fillable = [
        'name',
        'description'
    ];

    public function serviceCustomers()
    {
        return $this->hasMany(ServiceCustomer::class, 'ser_id');
    }
}