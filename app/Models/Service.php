<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $primaryKey = 'ser_id';

    protected $fillable = [
        'dep_id',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'weight',
        'is_active',
    ];

    public function customers()
    {
        return $this->belongsToMany(
            Customer::class,
            'service_customers',
            'ser_id',
            'customer_id',
            'ser_id',
            'id'
        )->withTimestamps();
    }

    // 🔥 ضمان قيمة افتراضية للوزن
    public function getWeightAttribute($value)
    {
        return $value ?? 1;
    }

    // 🔥 JSON response
    public function toArray(): array
    {
        return [
            'id' => $this->ser_id,

            'name' => app()->getLocale() === 'ar'
                ? $this->name_ar
                : $this->name_en,

            'description' => app()->getLocale() === 'ar'
                ? $this->description_ar
                : $this->description_en,

            // 🔥 مهم جدًا للتوزيع
            'weight' => $this->weight,
        ];
    }
}