<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Staff;
use App\Models\FixedTask;
use App\Models\TaskItemStatus;
use Illuminate\Support\Facades\DB;

class TaskService
{
    // 🔵 Fixed Tasks → لكل الموظفين بالقسم
public function createFromTemplate($templateId)
{
    return DB::transaction(function () use ($templateId) {

        $template = FixedTask::with('items')->findOrFail($templateId);

        // 🔥 موظف واحد فقط
        $staff = Staff::findOrFail($template->staff_id);

        $task = Task::create([
            'staff_id'      => $staff->staff_id,
            'fixed_task_id' => $template->id,
            'status'        => 'pending',
        ]);

        foreach ($template->items as $item) {
            TaskItemStatus::create([
                'task_id'             => $task->id,
                'fixed_task_item_id'  => $item->id,
                'is_done'             => false
            ]);
        }

        return $task;
    });
}

    // 🔵 toggle checklist item
    public function toggleItem($taskItemId)
    {
        return DB::transaction(function () use ($taskItemId) {

            $item = TaskItemStatus::findOrFail($taskItemId);

            // toggle
            $item->update([
                'is_done' => !$item->is_done
            ]);

            $task = Task::findOrFail($item->task_id);
//  أول حركة على أي item → يبدأ العمل
     if ($task->status === 'pending') {
    $task->update([
        'status' => 'in_progress'
    ]);
   }
          $allDone = TaskItemStatus::where('task_id', $task->id)
    ->where('is_done', false)
    ->doesntExist();

if ($allDone) {
    $task->update([
        'status' => 'completed'
    ]);
}
return $task->load('fixedTask', 'items');        });
    }
}