<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'name_ar',
        'name_en',
        'capacity',
        'price',
        'total_rooms',
        'description_ar',
        'description_en',
        'image'
    ];

    // نخفي الخام
    protected $hidden = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'image',
        'room_type_id',
        'created_at',
        'updated_at',
        'roomType'
    ];

    // الجاهز للـ API
    protected $appends = [
        'name',
        'description',
        'image_url',
        'room_type_name'
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class);
    }
    public function rooms()
{
    return $this->hasMany(Room::class);
}

    // الاسم حسب اللغة
    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }

    // الوصف حسب اللغة
    public function getDescriptionAttribute()
    {
        return app()->getLocale() === 'ar'
            ? $this->description_ar
            : $this->description_en;
    }

    // الصورة
    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }

    // 🔥 اسم النوع فقط (مع لغة)
    public function getRoomTypeNameAttribute()
    {
        if (!$this->roomType) {
            return null;
        }

        return app()->getLocale() === 'ar'
            ? $this->roomType->name_ar
            : $this->roomType->name_en;
    }
}