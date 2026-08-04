<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\URL;
class Restaurant extends Model
{
    use HasFactory;


    protected $primaryKey = 'res_id';
    
    protected $fillable = ['name_en', 'name_ar', 'image', 'details_en', 'details_ar'];

    /**
     * تحويل مسار الصورة إلى رابط كامل تلقائياً عند جلب البيانات
     */
    protected function image(): Attribute
    {
        return Attribute::make(
             get: function ($value) {

                // إذا كان الاسم مخزن مباشرة مثل "restaurant1.jpg" والمجلد في الـ public هو "restaurants"
                return URL::to('/restaurants/' . $value);
            }
        );
    }

    public function reservations()
    {
        return $this->hasMany(ReservationCustomer::class, 'res_id');
    }
}