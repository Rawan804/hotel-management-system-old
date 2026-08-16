<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;

class LeaveRequestsService
{

    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    public function createLeave(array $data, $currentUser): LeaveRequest
    {
        if ((bool)$currentUser->is_active !== true) {
            throw new \Exception(__('messages.account_inactive'), 403);
        }

        return LeaveRequest::create([
            'staff_id'   => $currentUser->staff_id,
            'type'       => $data['type'],
            'status'     => 'pending',
            'reason'     => $data['reason'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
        ]);
    }

    public function updateStatus(int $leaveRequestId, string $status, $currentUser)
    {

        $leaveRequest = LeaveRequest::where(
            'leave_id',
            $leaveRequestId
        )->firstOrFail();

        $employee = $leaveRequest->staff()->first();

        if (!$employee) {
            throw new \Exception(
                __('messages.employee_not_found'),
                404
            );
        }

        if ($currentUser->role === 'general_manager') {
            if (!in_array($employee->role, ['supervisor', 'service_manager'])) {
                throw new \Exception(
                    __('messages.gm_only_approves_supervisors_leaves'),
                    403
                );
            }

            $leaveRequest->update(['status' => $status]);
            $this->syncEmployeeLeaveStatus($employee, $leaveRequest, $status);
            $this->sendLeaveNotification($employee, $status);
            return $leaveRequest;
        }

        if ($currentUser->role === 'supervisor') {
            if ($employee->role !== 'employee') {
                throw new \Exception(
                    __('messages.unauthorized_leave_edit'),
                    403
                );
            }

            if ((int)$currentUser->dep_id !== (int)$employee->dep_id) {
                throw new \Exception(
                    __('messages.unauthorized_different_department_leave'),
                    403
                );
            }

            $leaveRequest->update(['status' => $status]);
            $this->syncEmployeeLeaveStatus($employee, $leaveRequest, $status);
            $this->sendLeaveNotification($employee, $status);
            return $leaveRequest;
        }

        if ($currentUser->role === 'service_manager') {
            if ($employee->role !== 'employee') {
                throw new \Exception(
                    __('messages.unauthorized_leave_edit'),
                    403
                );
            }

            if ((int)$currentUser->dep_id !== (int)$employee->dep_id) {
                throw new \Exception(
                    __('messages.unauthorized_different_department_leave'),
                    403
                );
            }

            $leaveRequest->update(['status' => $status]);
            $this->syncEmployeeLeaveStatus($employee, $leaveRequest, $status);
            $this->sendLeaveNotification($employee, $status);
            return $leaveRequest;
        }

        throw new \Exception(
            __('messages.unauthorized_status_update'),
            403
        );
    }

    /**
     * لو الإجازة تمت الموافقة عليها وتاريخ اليوم يقع ضمن مدى الإجازة (بدأت فعلاً)
     * نحول حالة الموظف offline فوراً وقت الموافقة.
     * إذا الإجازة بتبلش بالمستقبل، ما منلمس الحالة الآن — الـ Scheduled Job
     * هو يلي رح يتكفل فيها يوم ما توصل تاريخ البداية.
     */
    private function syncEmployeeLeaveStatus($employee, LeaveRequest $leaveRequest, string $status): void
    {
        if ($status !== 'approved') {
            return;
        }

        $today     = Carbon::today();
        $startDate = Carbon::parse($leaveRequest->start_date)->startOfDay();
        $endDate   = Carbon::parse($leaveRequest->end_date)->startOfDay();

        if ($today->betweenIncluded($startDate, $endDate)) {
            $employee->update(['status' => 'offline']);
        }
    }

    private function sendLeaveNotification(
        $employee,
        string $status
    ): void {
        if ($status === 'approved') {
            $title   = "Leave Approved";
            $message = __('messages.Your leave request has been approved');
        } else {
            $title   = "Leave Rejected";
            $message = __('messages.Your leave request has been rejected.');
        }

        $this->notificationService->sendToStaff(
            $employee,
            $title,
            $message,
            [
                'type'   => 'leave_request',
                'status' => $status
            ]
        );
    }

    public function getSupervisorLeaveRequests($currentUser)
    {
        // المدير العام يرى إجازات المشرفين
        if ($currentUser->role === 'general_manager') {
            return LeaveRequest::whereHas(
                'staff',
                function ($query) {
                    $query->whereIn('role', ['service_manager', 'supervisor']);
                }
            )->get();
        }

        // المشرف يرى موظفي قسمه
        if ($currentUser->role === 'supervisor') {
            return LeaveRequest::whereHas(
                'staff',
                function ($query) use ($currentUser) {
                    $query->where('dep_id', $currentUser->dep_id)
                          ->where('role', 'employee');
                }
            )->get();
        }

        if ($currentUser->role === 'service_manager') {
            return LeaveRequest::whereHas(
                'staff',
                function ($query) use ($currentUser) {
                    $query->where('dep_id', $currentUser->dep_id)
                          ->where('role', 'employee');
                }
            )->get();
        }

        throw new \Exception(
            __('messages.unauthorized_view_data'),
            403
        );
    }
}