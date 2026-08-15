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
    // =========================================================
    // إنشاء Task من Fixed Task Template
    // =========================================================
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


    // =========================================================
    // Toggle Checklist Item
    // =========================================================
    public function toggleItem($taskItemId)
    {
        return DB::transaction(function () use ($taskItemId) {

            $item = TaskItemStatus::findOrFail($taskItemId);

            $task = Task::findOrFail($item->task_id);

            $staff = Staff::find($task->staff_id);

            /*
            |--------------------------------------------------------------------------
            | معرفة حالة الـ item قبل التغيير
            |--------------------------------------------------------------------------
            */

            $wasDone = $item->is_done;


            /*
            |--------------------------------------------------------------------------
            | Toggle
            |--------------------------------------------------------------------------
            */

            $item->update([
                'is_done' => !$wasDone
            ]);


            /*
            |--------------------------------------------------------------------------
            | التحقق من حالة جميع الـ checklist items
            |--------------------------------------------------------------------------
            */

            $allDone = TaskItemStatus::where('task_id', $task->id)
                ->where('is_done', false)
                ->doesntExist();


            /*
            |--------------------------------------------------------------------------
            | إذا لم تكن كل العناصر منتهية
            |--------------------------------------------------------------------------
            */

            if (!$allDone) {

                /*
                |--------------------------------------------------------------------------
                | إذا كانت المهمة pending
                | أول checkbox = بدء المهمة
                |--------------------------------------------------------------------------
                */

                if ($task->status === 'pending') {

                    $task->update([
                        'status' => 'in_progress'
                    ]);


                    if ($staff) {

                        // زيادة الحمل مرة واحدة عند بدء المهمة
                        $staff->increment(
                            'service_load',
                            $task->weight ?? 1
                        );

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

                elseif ($task->status === 'completed') {

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
                }

            }


            /*
            |--------------------------------------------------------------------------
            | إذا اكتملت جميع الـ checklist
            |--------------------------------------------------------------------------
            */

            else {

                /*
                |--------------------------------------------------------------------------
                | إذا كانت المهمة قيد التنفيذ
                | ننهيها ونزيل وزنها من العامل
                |--------------------------------------------------------------------------
                */

                if ($task->status === 'in_progress') {

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
                }

                /*
                |--------------------------------------------------------------------------
                | إذا كانت أصلاً completed
                | لا نطرح الوزن مرة ثانية
                |--------------------------------------------------------------------------
                */

                elseif ($task->status !== 'completed') {

                    $task->update([
                        'status' => 'completed'
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | إعادة تحميل المهمة
            |--------------------------------------------------------------------------
            */

            return $task->load(
                'fixedTask',
                'items'
            );
        });
    }


    // =========================================================
    // تحديث حالة العامل
    // =========================================================
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


        /*
        |--------------------------------------------------------------------------
        | 1. إذا تجاوز الحد
        |--------------------------------------------------------------------------
        */

        if ($staff->service_load > $staff->max_load) {

            $staff->status = 'overloaded';

            $staff->save();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | 2. هل عنده Tasks قيد التنفيذ؟
        |--------------------------------------------------------------------------
        */

        $hasActiveTasks = Task::where(
            'staff_id',
            $staff->staff_id
        )
        ->where(
            'status',
            'in_progress'
        )
        ->exists();


        /*
        |--------------------------------------------------------------------------
        | 3. هل عنده Service Requests قيد التنفيذ؟
        |--------------------------------------------------------------------------
        */

        $hasActiveRequests = ServiceRequest::where(
            'staff_id',
            $staff->staff_id
        )
        ->where(
            'status',
            'in_progress'
        )
        ->exists();


        /*
        |--------------------------------------------------------------------------
        | 4. تحديد حالة العامل
        |--------------------------------------------------------------------------
        */

        if ($hasActiveTasks || $hasActiveRequests) {

            $staff->status = 'busy';

        } else {

            $staff->status = 'available';
        }


        $staff->save();
    }
}