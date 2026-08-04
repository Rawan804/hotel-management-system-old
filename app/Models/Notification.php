<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{

    protected $fillable = [

        'staff_id',

        'title',

        'body',

        'type',

        'is_read',

        'data'

    ];


    protected $casts = [

        'data'=>'array',

        'is_read'=>'boolean'

    ];


    public function staff()
    {
        return $this->belongsTo(
            Staff::class,
            'staff_id',
            'staff_id'
        );
    }

}