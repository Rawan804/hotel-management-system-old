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



    public function toggleItem(Request $request)
    {
        $request->validate([
            'task_item_id' => 'required|exists:task_item_statuses,id',
        ]);


      

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

    public function myTasks()
    {
        $staff = Auth::user();
if (!$staff->isWorkingNow()) {

    return response()->json([
        'success' => true,
        'data' => []
    ]);

}

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


    public function createTaskFromFixed($id)
{
    $staff = Auth::user();

    if (!$staff) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }


    $task = $this->service->createFromTemplate($id);


    return response()->json([
        'success' => true,
        'message' => app()->getLocale() === 'ar'
            ? 'تم إنشاء المهمة للموظف'
            : 'Task created for staff',
        'data' => $task
    ]);
}
}