<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $primaryKey = 'new_id';

    protected $fillable = [
        'image',
        'title',
        'description'
    ];
}