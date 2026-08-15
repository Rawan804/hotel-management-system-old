<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    // جلب جميع الأقسام
    public function getAll()
    {
        return Department::select('dep_id', 'name')->get();
    }

    // إنشاء قسم جديد
    public function create(array $data)
    {
        return Department::create([
            'name' => $data['name'],
        ]);
    }

    // تعديل قسم موجود
    public function update(Department $department, array $data)
    {
        $department->update([
            'name' => $data['name'],
        ]);

        return $department;
    }

    // حذف قسم
    public function delete(Department $department)
    {
        return $department->delete();
    }
}