<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Staff;

class UpdateStaffStatuses extends Command
{
    protected $signature = 'staff:update-statuses';

    protected $description = 'Update staff status based on shifts, approved leaves and active work';
public function handle()
{
    $staffs = Staff::where('is_active', true)
        ->whereIn('role', ['employee', 'supervisor', 'service_manager'])
        ->get();

    foreach ($staffs as $staff) {

        // استراحة يدوية - لا نلمسها إطلاقاً
        if ($staff->status === 'on_break') {
            continue;
        }

        $onLeave = $staff->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        $hasOpenTasks = $staff->tasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        $hasOpenRequests = $staff->serviceRequests()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        $hasAnyOpenWork = $hasOpenTasks || $hasOpenRequests;

        // برا الشفت/بإجازة + ما في أي شغل مفتوح => offline
        if (($onLeave || !$staff->isWorkingNow()) && !$hasAnyOpenWork) {
            $staff->status = 'offline';
            $staff->save();
            continue;
        }

        // الحمل تجاوز الحد
        if ($staff->service_load > $staff->max_load) {
            $staff->status = 'overloaded';
            $staff->save();
            continue;
        }

        // busy: Task in_progress فقط (مش pending) + Request أي حالة منهم
        $hasActiveFixedTask = $staff->tasks()
            ->where('status', 'in_progress')
            ->exists();

        $staff->status = ($hasActiveFixedTask || $hasOpenRequests) ? 'busy' : 'available';
        $staff->save();
    }

    return Command::SUCCESS;
}
}