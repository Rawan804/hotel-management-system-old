<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    // جلب جميع الأقسام
    public function getAll()
    {
        return Department::select('dep_id', 'name_ar','name_en')->get();
    }

    // إنشاء قسم جديد
    public function create(array $data)
    {
        return Department::create([
            'name_ar' => $data['name_ar'],
             'name_en' => $data['name_en']
        ]);
    }

    // تعديل قسم موجود
    public function update(Department $department, array $data)
    {
        $department->update([
            'name_ar' => $data['name_ar'],
             'name_en' => $data['name_en']
        ]);

        return $department;
    }

    // // حذف قسم
    // public function delete(Department $department)
    // {
    //     return $department->delete();
    // }
}