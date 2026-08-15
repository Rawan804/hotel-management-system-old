<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(private TaskService $service)
    {
    }


    // =========================================================
    // إنشاء Tasks من Fixed Task Template
    // =========================================================
    public function createFromTemplate($id)
    {
        $this->service->createFromTemplate($id);

        return response()->json([
            'success' => true,

            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء المهام للموظفين'
                : 'Tasks created for staff',
        ]);
    }


    // =========================================================
    // Toggle Checklist Item
    // =========================================================
    public function toggleItem(Request $request)
    {
        $request->validate([
            'task_item_id' => 'required|exists:task_item_statuses,id',
        ]);


        /*
        |--------------------------------------------------------------------------
        | TaskService مسؤول عن:
        |
        | 1. تغيير حالة الـ checkbox
        | 2. بدء المهمة
        | 3. تغيير حالة الموظف إلى busy
        | 4. زيادة service_load
        | 5. إنهاء المهمة عند اكتمال كل العناصر
        | 6. إنقاص service_load
        | 7. إعادة حساب حالة الموظف
        |--------------------------------------------------------------------------
        */

        $task = $this->service->toggleItem(
            $request->task_item_id
        );


        return response()->json([

            'success' => true,

            'message' => app()->getLocale() === 'ar'
                ? 'تم التحديث'
                : 'Updated',

            'data' => $task

        ]);
    }


    // =========================================================
    // مهام الموظف الحالي
    // =========================================================
    public function myTasks()
    {
        $staff = Auth::user();


        if (!$staff) {

            return response()->json([

                'success' => false,

                'message' => app()->getLocale() === 'ar'
                    ? 'غير مصرح'
                    : 'Unauthenticated'

            ], 401);
        }


        $tasks = Task::with([
                'fixedTask',
                'items.item'
            ])

            ->where(
                'staff_id',
                $staff->staff_id
            )

            ->orderByDesc('id')

            ->get();


        return response()->json([

            'success' => true,

            'data' => $tasks

        ]);
    }


    // =========================================================
    // عرض مهام موظف معين
    // =========================================================
    public function staffTasks($id)
    {
        $tasks = Task::with([
                'fixedTask',
                'items.item'
            ])

            ->where(
                'staff_id',
                $id
            )

            ->orderByDesc('id')

            ->get();


        return response()->json([

            'success' => true,

            'data' => $tasks

        ]);
    }
}