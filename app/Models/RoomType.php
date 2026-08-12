<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'image'
    ];

    protected $hidden = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'image',
        'created_at',
        'updated_at'
    ];

    public function categories()
    {
        return $this->hasMany(RoomCategory::class);
    }
}