<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    public function getAll()
    {
        return Department::select(
            'dep_id',
            'name'
        )->get();
    }
}
/*<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    public function getAll()
    {
        $locale = app()->getLocale();

        return Department::select(
            'dep_id',
            'name_ar',
            'name_en'
        )
        ->get()
        ->map(function ($department) use ($locale) {

            return [
                'dep_id' => $department->dep_id,

                'name' => $locale === 'ar'
                    ? $department->name_ar
                    : $department->name_en
            ];

        });
    }
}*/