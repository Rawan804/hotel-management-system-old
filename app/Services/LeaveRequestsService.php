<?php

namespace App\Services;

use App\Models\LeaveRequest;
class LeaveRequestsService
{
    public function createLeave(array $data, int $staffId): LeaveRequest
    {
        return LeaveRequest::create([
            'staff_id'   => $staffId,
            'title'      => $data['title'],
            'type'       => $data['type'],      
            'status'     => 'pending',
            'reason'     => $data['reason'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],    
        ]);
    }
}

