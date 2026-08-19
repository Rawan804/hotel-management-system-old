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


public function createFromTemplate(Request $request, $id)
{
    $request->validate([
        'staff_id' => 'required|exists:staff,staff_id',
    ]);

    $task = $this->service->createFromTemplate(
        $id,
        $request->staff_id
    );
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

    if (!$staff) {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'غير مصرح'
                : 'Unauthenticated'
        ], 401);
    }

    $hasOpenTasks = Task::where('staff_id', $staff->staff_id)
        ->whereIn('status', ['pending', 'in_progress'])
        ->exists();

    // إذا انتهى الشيفت وما عاد عليه أي مهمة مفتوحة
    if (!$staff->isWorkingNow() && !$hasOpenTasks) {

        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    $tasks = Task::with([
            'fixedTask',
            'items.item'
        ])
        ->where('staff_id', $staff->staff_id)
        ->orderByDesc('id')
        ->get();

    return response()->json([
        'success' => true,
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

public function createTaskFromFixed(Request $request, $id)
{
    $creator = Auth::user();

    if (!$creator) {
        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'غير مصرح'
                : 'Unauthenticated'
        ], 401);
    }

    // الموظف الذي ستُسند إليه المهمة
    $request->validate([
        'staff_id' => 'required|exists:staff,staff_id',
    ]);

    $targetStaff = \App\Models\Staff::findOrFail(
        $request->staff_id
    );

    // قالب المهمة
    $fixedTask = \App\Models\FixedTask::findOrFail($id);


   

    if ($creator->role === 'general_manager') {

        if (!in_array($targetStaff->role, [
            'supervisor',
            'service_manager'
        ])) {

            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'المدير العام يمكنه إسناد المهام فقط للمشرف أو مدير الخدمات'
                    : 'General Manager can assign tasks only to Supervisor or Service Manager'
            ], 403);
        }
    }



    elseif (in_array($creator->role, [
        'supervisor',
        'service_manager'
    ])) {

        // يجب أن يكون الهدف Employee
        if ($targetStaff->role !== 'employee') {

            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'يمكن إسناد المهمة للموظفين فقط'
                    : 'Tasks can only be assigned to employees'
            ], 403);
        }

        // الموظف يجب أن يكون من نفس قسم المدير
        if ($targetStaff->dep_id != $creator->dep_id) {

            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'يمكنك إسناد المهام لموظفي قسمك فقط'
                    : 'You can only assign tasks to employees in your department'
            ], 403);
        }
    }



    else {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'غير مصرح لك بإسناد المهام'
                : 'You are not authorized to assign tasks'
        ], 403);
    }


  

    if ($targetStaff->dep_id != $fixedTask->dep_id) {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'الموظف لا ينتمي إلى قسم المهمة'
                : 'The employee does not belong to the task department'
        ], 422);
    }


  

    $onLeave = $targetStaff->leaves()
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', now()->toDateString())
        ->whereDate('end_date', '>=', now()->toDateString())
        ->exists();




    if (
        $targetStaff->status === 'offline' ||
        $onLeave ||
        !$targetStaff->isWorkingNow()
    ) {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'لا يمكن إسناد المهمة، الموظف خارج الشيفت أو في إجازة أو Offline'
                : 'Cannot assign the task because the employee is outside the shift, on leave, or offline'
        ], 422);
    }


   

    $task = $this->service->createFromTemplate(
        $id,
        $targetStaff->staff_id
    );


    if (!$task) {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'لا يمكن إنشاء المهمة'
                : 'The task cannot be created'
        ], 422);
    }


    return response()->json([
        'success' => true,
        'message' => app()->getLocale() === 'ar'
            ? 'تم إسناد المهمة بنجاح'
            : 'Task assigned successfully',
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