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