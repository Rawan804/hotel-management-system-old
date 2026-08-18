<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    // تحديد اسم المفتاح الرئيسي لأن الاسم غير المعتاد (ليس id)
    protected $primaryKey = 'dep_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = [
      
        'name_ar',
        'name_en'
    ];

    // علاقة القسم بالموظفين (القسم يملك عدة موظفين)
    public function staff()
    {
        return $this->hasMany(Staff::class, 'dep_id', 'dep_id');
    }

    // علاقة القسم المهام الثابتة
    public function fixedTasks()
    {
        return $this->hasMany(FixedTask::class, 'dep_id', 'dep_id');
    }
}
   