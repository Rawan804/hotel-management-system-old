<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\URL;
class Hall extends Model
{
    use HasFactory;

    protected $primaryKey = 'hall_id';

    protected $fillable = [
        'image',
        'details_ar',
        'details_en',
        'name_en',
        'capacity',
        'name_ar',
        'price'
    ];
public function getImageAttribute($value)
{
    return asset('storage/' . $value);
}


    

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'hall_id');
    }
}