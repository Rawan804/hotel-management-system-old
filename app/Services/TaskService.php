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

          

            $wasDone = $item->is_done;


        

            $item->update([
                'is_done' => !$wasDone
            ]);


           

            $allDone = TaskItemStatus::where('task_id', $task->id)
                ->where('is_done', false)
                ->doesntExist();



            if (!$allDone) {

           

                if ($task->status === 'pending') {

                    $task->update([
                        'status' => 'in_progress'
                    ]);


                   /* if ($staff) {

                        // زيادة الحمل مرة واحدة عند بدء المهمة
                        $staff->increment(
                            'service_load',
                            $task->weight ?? 1
                        );

                        // العامل بدأ العمل
                        $this->refreshStaffStatus($staff);
                    }*/
                        if ($staff) {

    // العامل بدأ العمل
    $this->refreshStaffStatus($staff);

}
                }


                /*
                |--------------------------------------------------------------------------
                | إذا كانت المهمة completed وتم إلغاء checkbox
                | المهمة تعود للعمل
                |--------------------------------------------------------------------------
                */

              /*  elseif ($task->status === 'completed') {

                    $task->update([
                        'status' => 'in_progress'
                    ]);


                    if ($staff) {

                        // إعادة وزن المهمة لأنها عادت قيد التنفيذ
                        $staff->increment(
                            'service_load',
                            $task->weight ?? 1
                        );

                        $this->refreshStaffStatus($staff);
                    }
                }*/
                    elseif ($task->status === 'completed') {

    $task->update([
        'status' => 'in_progress'
    ]);


    if ($staff) {

        $this->refreshStaffStatus($staff);

    }
}

            }



            else {

                /*
                |--------------------------------------------------------------------------
                | إذا كانت المهمة قيد التنفيذ
                | ننهيها ونزيل وزنها من العامل
                |--------------------------------------------------------------------------
                */

               /* if ($task->status === 'in_progress') {

                    $task->update([
                        'status' => 'completed'
                    ]);


                    if ($staff) {

                        $staff->service_load = max(
                            0,
                            $staff->service_load - ($task->weight ?? 1)
                        );

                        $staff->save();

                        // إعادة حساب حالة العامل
                        $this->refreshStaffStatus($staff);
                    }
                }*/
                    if ($task->status === 'in_progress') {

    $task->update([
        'status' => 'completed'
    ]);


    if ($staff) {

        // إعادة حساب حالة العامل فقط
        $this->refreshStaffStatus($staff);

    }
}


                elseif ($task->status !== 'completed') {

                    $task->update([
                        'status' => 'completed'
                    ]);
                }
            }


           

            return $task->load(
                'fixedTask',
                'items'
            );
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