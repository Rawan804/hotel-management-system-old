<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ApplyLeaveStatuses extends Command
{
    protected $signature = 'leave:apply-statuses';

    protected $description = 'تحويل حالة الموظف offline عند بدء إجازته الموافق عليها، وإرجاعها available عند انتهائها';

    public function handle(): int
    {
        $this->activateTodayLeaves();
        $this->deactivateEndedLeaves();

        return self::SUCCESS;
    }

    /**
     * أي إجازة approved تاريخ بدايتها اليوم -> offline
     */
    private function activateTodayLeaves(): void
    {
        $today = Carbon::today()->toDateString();

        LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', $today)
            ->with('staff')
            ->get()
            ->each(function (LeaveRequest $leave) {
                $employee = $leave->staff;

                if (!$employee) {
                    return;
                }

                if ($employee->status !== 'offline') {
                    $employee->update(['status' => 'offline']);
                    Log::info("Leave #{$leave->leave_id}: staff #{$employee->staff_id} -> offline (بدء الإجازة).");
                }
            });
    }

    /**
     * أي إجازة approved تاريخ نهايتها كان أمس -> رجّع available
     */
    private function deactivateEndedLeaves(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();

        LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('end_date', $yesterday)
            ->with('staff')
            ->get()
            ->each(function (LeaveRequest $leave) {
                $employee = $leave->staff;

                if (!$employee) {
                    return;
                }

                if ($employee->status === 'offline') {
                    $employee->update(['status' => 'available']);
                    Log::info("Leave #{$leave->leave_id}: staff #{$employee->staff_id} -> available (انتهاء الإجازة).");
                }
            });
    }
}