<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class ServiceRequestService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 1. جلب الموظفين بالقسم
            $staffMembers = Staff::where('dep_id', $data['dep_id'])
                ->where('is_active', true)
                ->withCount([
                    'serviceRequests as load' => function ($q) {
                        $q->where('status', 'pending');
                    }
                ])
                ->get();

            if ($staffMembers->isEmpty()) {
                return null;
            }

            // 2. إيجاد أقل load
            $minLoad = $staffMembers->min('load');

            // 3. فلترة الموظفين الأقل load
            $leastLoaded = $staffMembers->where('load', $minLoad);

            // 4. اختيار عشوائي إذا في أكثر من واحد
            $assignedStaff = $leastLoaded->random();

            // 5. إنشاء الطلب
            return ServiceRequest::create([
                'booking_id'   => $data['booking_id'],
                'dep_id'       => $data['dep_id'],
                'staff_id'     => $assignedStaff->staff_id,
                'service_type' => $data['service_type'],
                'details'      => $data['details'] ?? null,
                'status'       => 'pending',
            ]);
        });
    }

    // مراقبة الضغط
    public function staffLoad()
    {
        return Staff::where('is_active', true)
            ->withCount([
                'serviceRequests as active_tasks_count' => function ($q) {
                    $q->where('status', 'pending');
                }
            ])
            ->orderBy('active_tasks_count')
            ->get();
    }
}