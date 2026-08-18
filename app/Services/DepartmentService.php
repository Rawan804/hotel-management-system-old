<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    public function getAll()
    {
        $locale = app()->getLocale();

        $nameColumn = $locale === 'ar' ? 'name_ar' : 'name_en';

        return Department::select(
            'dep_id',
            "$nameColumn as name"
        )->get();
    }


    public function create(array $data)
    {
        return Department::create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
        ]);
    }

  
    public function update(Department $department, array $data)
    {
        $department->update([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
        ]);

        return $department;
    }
}