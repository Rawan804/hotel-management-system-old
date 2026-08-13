<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Services\NotificationService;

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

    public function updateStatus(
        int $leaveRequestId,
        string $status,
        $currentUser
    )
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
            if ($employee->role !== 'supervisor') {

                throw new \Exception(
                    __('messages.gm_only_approves_supervisors_leaves'),
                    403
                );

            }


         $leaveRequest->update([
            'status' => $status

            ]);
            $this->sendLeaveNotification(
                $employee,
                $status
            );
            return $leaveRequest;

        }
        if ($currentUser->role === 'supervisor') {
            if ($employee->role !== 'employee') {
                throw new \Exception(
                    __('messages.unauthorized_leave_edit'),
                    403
                );
            }

            if ((int)$currentUser->dep_id !==(int)$employee->dep_id) {

                throw new \Exception(
                    __('messages.unauthorized_different_department_leave'),
                    403
                );}
            $leaveRequest->update([
                'status' => $status

            ]);
            $this->sendLeaveNotification(
                $employee,
                $status
            );
            return $leaveRequest;

        }
        throw new \Exception(
            __('messages.unauthorized_status_update'),
            403
        );

    }

    private function sendLeaveNotification(
        $employee,
        string $status
    ): void
    {
        if ($status === 'approved') {
            $title = "Leave Approved";
            $message =
               __( 'messages.Your leave request has been approved');
        } else {
            $title = "Leave Rejected";
            $message =
               __('messages.Your leave request has been rejected.') ;


        }

        $this->notificationService->sendToStaff(
            $employee,
            $title,
            $message,
            [   'type' => 'leave_request',
                'status' => $status

            ]
        );}
    public function getSupervisorLeaveRequests($currentUser)
    {
        // المدير العام يرى إجازات المشرفي
        if ($currentUser->role === 'general_manager') {
            return LeaveRequest::whereHas(
                'staff',
                function ($query) {
                    $query->where(
                        'role',
                        'supervisor'
                    );})->get();}

        // المشرف يرى موظفي قسمه
        if ($currentUser->role === 'supervisor') {
            return LeaveRequest::whereHas(
                'staff',
                function ($query) use ($currentUser) {
                    $query->where(
                        'dep_id',
                        $currentUser->dep_id
                    )
                    ->where( 'role','employee'
                    ); }   )->get();


        }
        throw new \Exception(
            __('messages.unauthorized_view_data'),
            403
        );

    }


}