<?php

namespace App\Services;

use Exception;
use App\Models\Complaint;

class ComplaintService
{
    public function createComplaint(array $data, $currentUser): Complaint
    {
        if ((bool)$currentUser->is_active !== true) {
            throw new \Exception(__('messages.account_inactive'), 403);
        }

        return Complaint::create([
            'staff_id'    => $currentUser->staff_id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'status'      => 'pending',
        ]);
    }

    public function updateStatus(int $complaintId, string $status, $currentUser)
    {
        $complaint = Complaint::where('com_id', $complaintId)->firstOrFail();
        $employee = $complaint->staff()->first();
        
        if ($currentUser->role === 'general_manager') {
            if ($employee->role !== 'supervisor') {
                throw new \Exception(__('messages.gm_only_approves_supervisors'), 403);
            }
            
            $complaint->update(['status' => $status]);
            return $complaint;
        }

        if ($currentUser->role === 'supervisor') {
            if ($employee->role !== 'employee') {
                throw new \Exception(__('messages.unauthorized_complaint_edit'), 403);
            }

            if ((int)$currentUser->dep_id !== (int)$employee->dep_id) {
                throw new \Exception(__('messages.unauthorized_different_department'), 403);
            }

            $complaint->update(['status' => $status]);
            return $complaint;
        }

        throw new \Exception(__('messages.unauthorized_status_update'), 403);
    }

    public function getComplaintsForUser($user)
    {
        if ($user->role === 'general_manager') {
            return Complaint::whereHas('staff', function ($query) {
                $query->where('role', 'supervisor');
            })->get();
        }

        if ($user->role === 'supervisor') {
            return Complaint::whereHas('staff', function ($query) use ($user) {
                $query->where('dep_id', $user->dep_id)
                      ->where('role', 'employee');
            })->get();
        }

        throw new \Exception(__('messages.unauthorized_view_data'), 403);
    }
}