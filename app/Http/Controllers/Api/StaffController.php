<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // عرض الموظفين
    public function index()
    {
        $user = Auth::user();
if ($user->role === 'general_manager') {
    $staff = Staff::where('role', '!=', 'general_manager')->get();
}
        elseif ($user->role === 'supervisor') {
            $staff = Staff::where('dep_id', $user->dep_id)->where('role', '!=', 'supervisor')->get();
        }
        else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($staff);
    }

    // إنشاء موظف
 public function store(StoreStaffRequest $request, StaffService $service)
{
    $creator = Auth::user();

    if ($creator->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    try {
        // تمرير البيانات المفلترة والـ creator للخدمة
        $result = $service->create($request->validated(), $creator);

        return response()->json([
            'message' => 'Staff created successfully',
            'staff' => $result['staff']
        ], 201);

    } catch (\Exception $e) {
        // في حال فشل الشرط (وجود supervisor مفعّل أو عدم تحديد قسم)
        return response()->json([
            'message' => $e->getMessage()
        ], $e->getCode() ?: 400);
    }
}
   // تفعيل / تعطيل
public function toggleActive(Staff $staff)
{
    $user = Auth::user();
    if ($user->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    if ($user->staff_id === $staff->staff_id) {
        return response()->json(['message' => 'لا يمكنك إلغاء تفعيل حسابك الشخصي.'], 400);
    }

    if ($user->role === 'supervisor' && $staff->dep_id !== $user->dep_id) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $staff->is_active = !$staff->is_active;
    $staff->save();

    return response()->json([
        'message' => 'Updated successfully',
        'staff' => $staff
    ]);
}

   
}