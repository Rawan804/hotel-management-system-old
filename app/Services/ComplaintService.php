<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintService
{
   
    public function createComplaint(array $data, int $staffId): Complaint
    {
        return Complaint::create([
            'staff_id'    => $staffId,
            'title'       => $data['title'],
            'description' => $data['description'],
            'status'      => 'pending', 
        ]);
    }
}