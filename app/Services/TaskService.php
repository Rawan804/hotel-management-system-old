<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Staff;
use App\Models\FixedTask;
use App\Models\TaskItemStatus;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class TaskService
{
   
    public function createFromTemplate($templateId)
    {
        return DB::transaction(function () use ($templateId) {

            $template = FixedTask::with('items')->findOrFail($templateId);

            // كل Fixed Task مربوط بموظف محدد
            $staff = Staff::findOrFail($template->staff_id);

            $task = Task::create([
                'staff_id'      => $staff->staff_id,
                'fixed_task_id' => $template->id,
                'status'        => 'pending',
                'weight'        => $template->weight ?? 1,
            ]);

            foreach ($template->items as $item) {

                TaskItemStatus::create([
                    'task_id'            => $task->id,
                    'fixed_task_item_id' => $item->id,
                    'is_done'            => false
                ]);

            }

            return $task;
        });
    }



   public function toggleItem($taskItemId)
    {
        return DB::transaction(function () use ($taskItemId) {

            $item = TaskItemStatus::findOrFail($taskItemId);
            $task = Task::findOrFail($item->task_id);
            $staff = Staff::find($task->staff_id);

            $item->update([
                'is_done' => !$item->is_done
            ]);

            // نحسب الحالة الصحيحة مباشرة من عدد العناصر المنجزة
            // بدل شروط if/elseif جزئية كانت ناقصة حالة الرجوع لـ pending
            $totalItems = TaskItemStatus::where('task_id', $task->id)->count();
            $doneItems = TaskItemStatus::where('task_id', $task->id)
                ->where('is_done', true)
                ->count();

            $newStatus = match (true) {
                $doneItems === 0 => 'pending',
                $doneItems === $totalItems => 'completed',
                default => 'in_progress',
            };

            if ($newStatus !== $task->status) {
                $task->update(['status' => $newStatus]);

                if ($staff) {
                    $this->refreshStaffStatus($staff);
                }
            }

            return $task->load('fixedTask', 'items');
        });
    }


  
    private function refreshStaffStatus(Staff $staff)
    {

        /*
        |--------------------------------------------------------------------------
        | لا نغيّر الحالات الخاصة يدوياً
        |--------------------------------------------------------------------------
        |
        | إذا العامل Offline أو On Break
        | لا نريد أن يتحول تلقائياً إلى Available / Busy.
        |
        */

        if (in_array($staff->status, [
            'offline',
            'on_break'
        ])) {

            return;
        }



        if ($staff->service_load > $staff->max_load) {

            $staff->status = 'overloaded';

            $staff->save();

            return;
        }



        $hasActiveTasks = Task::where(
            'staff_id',
            $staff->staff_id
        )
        ->where(
            'status',
            'in_progress'
        )
        ->exists();



        $hasActiveRequests = ServiceRequest::where(
            'staff_id',
            $staff->staff_id
        )
        ->where(
            'status',
            'in_progress'
        )
        ->exists();


      

        if ($hasActiveTasks || $hasActiveRequests) {

            $staff->status = 'busy';

        } else {

            $staff->status = 'available';
        }


        $staff->save();
    }
}