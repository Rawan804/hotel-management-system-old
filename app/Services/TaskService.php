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



$onLeave = $staff->leaves()
    ->where('status', 'approved')
    ->whereDate('start_date', '<=', now()->toDateString())
    ->whereDate('end_date', '>=', now()->toDateString())
    ->exists();

if (
    $staff->status === 'offline' ||
    !$staff->isWorkingNow() ||
    $onLeave
) {
    return null;
}


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
             $item->update(['is_done' => !$item->is_done
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
            };if ($newStatus !== $task->status) {
                $task->update(['status' => $newStatus]);
                 if ($staff) {
                    $this->refreshStaffStatus($staff);
                }
            }   return $task->load('fixedTask', 'items');
        });
    }

//اصلية
  /*
    private function refreshStaffStatus(Staff $staff)
    {

        

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
    }*/



/*

        private function refreshStaffStatus(Staff $staff)
{

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



    $hasActiveTasks = Task::where('staff_id', $staff->staff_id)
        ->where('status', 'in_progress')
        ->exists();



    $hasActiveRequests = ServiceRequest::where('staff_id', $staff->staff_id)
        ->where('status', 'in_progress')
        ->exists();



    if ($hasActiveTasks || $hasActiveRequests) {

        $staff->status = 'busy';

        $staff->save();

        return;
    }


  

    $hasPendingWork = Task::where('staff_id', $staff->staff_id)
            ->where('status', 'pending')
            ->exists()
        || ServiceRequest::where('staff_id', $staff->staff_id)
            ->where('status', 'pending')
            ->exists();

    if (!$hasPendingWork) {

        $onLeave = $staff->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        if ($onLeave || !$staff->isWorkingNow()) {

            $staff->status = 'offline';

            $staff->save();

            return;
        }
    }


    $staff->status = 'available';

    $staff->save();
}*/



//اخيرة



private function refreshStaffStatus(Staff $staff)
{
    if ($staff->status === 'on_break') {
        return;
    }

    $onLeave = $staff->leaves()
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', now())
        ->whereDate('end_date', '>=', now())
        ->exists();

    $hasOpenTasks = Task::where('staff_id', $staff->staff_id)
        ->whereIn('status', ['pending', 'in_progress'])
        ->exists();

    $hasOpenRequests = ServiceRequest::where('staff_id', $staff->staff_id)
        ->whereIn('status', ['pending', 'in_progress'])
        ->exists();

    $hasAnyOpenWork = $hasOpenTasks || $hasOpenRequests;

    if (($onLeave || !$staff->isWorkingNow()) && !$hasAnyOpenWork) {
        $staff->status = 'offline';
        $staff->save();
        return;
    }

    if ($staff->service_load > $staff->max_load) {
        $staff->status = 'overloaded';
        $staff->save();
        return;
    }

    $hasActiveTask = Task::where('staff_id', $staff->staff_id)
        ->where('status', 'in_progress')
        ->exists();

    $staff->status = ($hasActiveTask || $hasOpenRequests) ? 'busy' : 'available';
    $staff->save();
}
}