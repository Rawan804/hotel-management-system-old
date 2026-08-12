<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use App\Models\Task;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(private TaskService $service) {}

    // إنشاء Tasks من Fixed Template
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

    // toggle checklist item
    public function toggleItem(Request $request)
    {
        $task = $this->service->toggleItem($request->task_item_id);

        // إعادة تقييم حالة المهمة
        $allDone = $task->items->every(fn($item) => $item->is_done);
        $anyDone = $task->items->some(fn($item) => $item->is_done);

        if ($allDone) {
            $newStatus = 'completed';
        } elseif ($anyDone) {
            $newStatus = 'in_progress';
        } else {
            $newStatus = 'pending';
        }

        $task->update(['status' => $newStatus]);

        $task->refresh();

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() === 'ar'
                ? 'تم التحديث'
                : 'Updated',
            'data' => $task
        ]);
    }

    // مهام موظف
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


    $tasks = Task::with(['fixedTask', 'items.item'])
        ->where('staff_id', $staff->staff_id)
        ->orderByDesc('id')
        ->get();


    return response()->json([
        'success' => true,
        'data' => $tasks
    ]);
}

    public function staffTasks($id)
    {
        $tasks = Task::with(['fixedTask', 'items.item'])
            ->where('staff_id', $id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    // بدء المهمة
    public function startTask($id)
    {
        $task = Task::findOrFail($id);

        if ($task->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'المهمة بدأت مسبقاً أو منتهية'
                    : 'Task already started or completed'
            ], 400);
        }

        $task->update(['status' => 'in_progress']);

        // 🔥 تحديث load الموظف (زيادة الوزن)
        if ($task->staff) {
            $task->staff->increment('service_load', $task->weight ?? 1);
        }

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() === 'ar'
                ? 'تم بدء المهمة'
                : 'Task started',
            'data' => $task
        ]);
    }

    // إنهاء المهمة
    public function completeTask($id)
    {
        $task = Task::findOrFail($id);

        if ($task->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'لا يمكن إنهاء مهمة قبل البدء بها'
                    : 'Start task first'
            ], 400);
        }

        if ($task->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'المهمة منتهية مسبقاً'
                    : 'Already completed'
            ], 400);
        }

        $task->update(['status' => 'completed']);

        // 🔥 تقليل الحمل عند الانتهاء
        if ($task->staff) {
            $task->staff->decrement('service_load', $task->weight ?? 1);
        }

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() === 'ar'
                ? 'تم إنهاء المهمة'
                : 'Task completed',
            'data' => $task
        ]);
    }
}