<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Exception; 

class StaffService
{
    public function create(array $data, Staff $creator)
    {
        // 1. إذا كان المنشئ supervisor
        if ($creator->role === 'supervisor') {
            $data['dep_id'] = $creator->dep_id;
            $data['role'] = 'employee';
        }

        if ($creator->role === 'general_manager' && $data['role'] === 'supervisor') {
            if (empty($data['dep_id'])) {
                throw new Exception('يجب تحديد القسم عند إنشاء مسؤول.', 422);
            }
            $activeSupervisorExists = Staff::where('dep_id', $data['dep_id'])
                                            ->where('role', 'supervisor')
                                            ->where('is_active', true) // 👈 تأكد من اسم الحقل عندك (مثلا true أو 1)
                                            ->exists();

            if ($activeSupervisorExists) {
                throw new Exception('هذا القسم يمتلك مسؤول مفعّل بالفعل. لا يمكن إنشاء مسؤول آخر إلا بعد إلغاء تفعيل الحالي.', 422);
            }
        }

        $staff = Staff::create([
            'dep_id' => $data['dep_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'created_by' => $creator->staff_id,
        ]);

        return [
            'staff' => $staff
        ];
    }
}