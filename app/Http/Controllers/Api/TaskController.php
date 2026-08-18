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
    $task = $this->service->createFromTemplate($id);

    if (!$task) {
        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'لا يمكن إنشاء المهمة، الموظف خارج الشيفت أو في إجازة'
                : 'The task cannot be created because the employee is outside their shift or on leave',
        ], 422);
    }

    return response()->json([
        'success' => true,
        'message' => app()->getLocale() === 'ar'
            ? 'تم إنشاء المهمة للموظف'
            : 'Task created for staff',
        'data' => $task,
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

           // 'data' => $task
           'data' => $this->transformTask($task)

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

         //   'data' => $tasks
         'data' => $tasks->map(
    fn($task) => $this->transformTask($task)
)

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

           // 'data' => $tasks
            'data' => $tasks->map(
        fn($task) => $this->transformTask($task)
    )

        ]);
    }


    /*public function createTaskFromFixed($id)
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
}*/
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

    if (!$task) {
        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'لا يمكن إنشاء المهمة، الموظف خارج الشيفت أو في إجازة'
                : 'The task cannot be created because the employee is outside their shift or on leave',
        ], 422);
    }

    return response()->json([
        'success' => true,
        'message' => app()->getLocale() === 'ar'
            ? 'تم إنشاء المهمة للموظف'
            : 'Task created for staff',
        'data' => $task
    ]);
}

private function transformTask($task)
{
    return [
        'id' => $task->id,

        'staff_id' => $task->staff_id,

      'status' => match($task->status) {

    'pending' => __('messages.status.pending'),

    'in_progress' => __('messages.status.in_progress'),

    'completed' => __('messages.status.completed'),

    default => $task->status,
},
        'weight' => $task->weight,

        'fixed_task' => $task->fixedTask,

        'items' => $task->items,
    ];
}
}