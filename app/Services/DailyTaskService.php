<?php
namespace App\Services;

use App\Models\Staff;
use App\Models\FixedTask;
use App\Models\Task;

class DailyTaskService
{
    public function generate()
    {
        $staff = Staff::where('is_active', true)->get();
        $fixedTasks = FixedTask::where('is_active', true)->get();

        foreach ($staff as $member) {
            foreach ($fixedTasks as $task) {

                Task::firstOrCreate([
                    'staff_id' => $member->staff_id,
                    'fixed_task_id' => $task->id,
                    'created_at' => now()->format('Y-m-d')
                ], [
                    'status' => 'pending'
                ]);
            }
        }
    }
}