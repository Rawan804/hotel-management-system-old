<?php

namespace App\Services;

use App\Models\Complaint;
use Exception;

class ComplaintService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function createComplaint(array $data, $currentUser): Complaint
    {
        if ((bool) $currentUser->is_active !== true) {
            throw new Exception(
                __('messages.account_inactive'),
                403
            );
        }

        return Complaint::create([
            'staff_id'    => $currentUser->staff_id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'status'      => 'pending',
        ]);
    }

    private function sendComplaintNotification(
        $employee,
        string $status
    ): void {
        if ($status === 'approved') {

            $title = __('messages.complaint_approved_title');

            $message = __('messages.complaint_approved_message');

        } elseif ($status === 'rejected') {

            $title = __('messages.complaint_rejected_title');

            $message = __('messages.complaint_rejected_message');

        } else {

            $title = __('messages.complaint_status_updated_title');

            $message = __('messages.complaint_status_updated_message');
        }

        $this->notificationService->sendToStaff(
            $employee,
            $title,
            $message,
            [
                'type'   => 'complaint',
                'status' => $status,
            ]
        );
    }

    public function updateStatus(
        int $complaintId,
        string $status,
        $currentUser
    ) {
        // التأكد من أن الحالة صحيحة
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new Exception(
                __('messages.invalid_complaint_status'),
                422
            );
        }

        $complaint = Complaint::where(
            'com_id',
            $complaintId
        )->firstOrFail();

        $employee = $complaint->staff()->first();

        if (!$employee) {
            throw new Exception(
                __('messages.employee_not_found'),
                404
            );
        }

        /*
        |--------------------------------------------------------------------------
        | General Manager
        |--------------------------------------------------------------------------
        */

        if ($currentUser->role === 'general_manager') {

            if (!in_array(
                $employee->role,
                ['supervisor', 'service_manager']
            )) {
                throw new Exception(
                    __('messages.gm_only_approves_supervisors_and_service_managers'),
                    403
                );
            }

            $complaint->update([
                'status' => $status
            ]);

            $this->sendComplaintNotification(
                $employee,
                $status
            );

            return $complaint;
        }

        /*
        |--------------------------------------------------------------------------
        | Supervisor
        |--------------------------------------------------------------------------
        */

        if ($currentUser->role === 'supervisor') {

            if ($employee->role !== 'employee') {
                throw new Exception(
                    __('messages.unauthorized_complaint_edit'),
                    403
                );
            }

            if (
                (int) $currentUser->dep_id !==
                (int) $employee->dep_id
            ) {
                throw new Exception(
                    __('messages.unauthorized_different_department'),
                    403
                );
            }

            $complaint->update([
                'status' => $status
            ]);

            $this->sendComplaintNotification(
                $employee,
                $status
            );

            return $complaint;
        }

        /*
        |--------------------------------------------------------------------------
        | Service Manager
        |--------------------------------------------------------------------------
        */

        if ($currentUser->role === 'service_manager') {

            if ($employee->role !== 'employee') {
                throw new Exception(
                    __('messages.unauthorized_complaint_edit'),
                    403
                );
            }

            if (
                (int) $currentUser->dep_id !==
                (int) $employee->dep_id
            ) {
                throw new Exception(
                    __('messages.unauthorized_different_department'),
                    403
                );
            }

            $complaint->update([
                'status' => $status
            ]);

            $this->sendComplaintNotification(
                $employee,
                $status
            );

            return $complaint;
        }

        throw new Exception(
            __('messages.unauthorized_status_update'),
            403
        );
    }

    public function getComplaintsForUser($user)
    {
        /*
        |--------------------------------------------------------------------------
        | General Manager
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'general_manager') {

            return Complaint::whereHas(
                'staff',
                function ($query) {
                    $query->whereIn(
                        'role',
                        [
                            'service_manager',
                            'supervisor'
                        ]
                    );
                }
            )->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Supervisor
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'supervisor') {

            return Complaint::whereHas(
                'staff',
                function ($query) use ($user) {
                    $query
                        ->where('dep_id', $user->dep_id)
                        ->where('role', 'employee');
                }
            )->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Service Manager
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'service_manager') {

            return Complaint::whereHas(
                'staff',
                function ($query) use ($user) {
                    $query
                        ->where('dep_id', $user->dep_id)
                        ->where('role', 'employee');
                }
            )->get();
        }

        throw new Exception(
            __('messages.unauthorized_view_data'),
            403
        );
    }
}