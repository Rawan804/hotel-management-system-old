<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DepartmentService;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentService $departmentService
    ) {}

    // عرض جميع الأقسام
    public function index()
    {
        return response()->json(
            $this->departmentService->getAll()
        );
    }

    // إضافة قسم جديد
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department = $this->departmentService->create($validatedData);

        return response()->json([
            'message' => 'Department created successfully',
            'data' => $department
        ], 201);
    }

    // تعديل قسم
    public function update(Request $request, Department $department)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $updatedDepartment = $this->departmentService->update($department, $validatedData);

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => $updatedDepartment
        ]);
    }

    // حذف قسم
    public function destroy(Department $department)
    {
        $this->departmentService->delete($department);

        return response()->json([
            'message' => 'Department deleted successfully'
        ]);
    }
}