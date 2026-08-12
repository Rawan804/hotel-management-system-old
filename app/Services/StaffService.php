<?php

namespace App\Services;

use App\Models\Staff;
use Exception;
use Illuminate\Support\Facades\Hash;

class StaffService
{
//اضافة موظف
public function create(array $data, Staff $creator): array
    {
      
        if (in_array($creator->role, ['supervisor','service_manager',
        ])) {

            $data['dep_id'] = $creator->dep_id;
            $data['role'] = 'employee';
        }
        if (
            $creator->role === 'general_manager' && $data['role'] === 'supervisor'
        ) {

            if (empty($data['dep_id'])) {
                throw new Exception(
                    __('messages.The section must be specified when creating a supervisor'),
                    422
                );}

            $activeSupervisorExists = Staff::where(
                    'dep_id',
                    $data['dep_id']
                )
                ->where('role', 'supervisor')
                ->where('is_active', true)
                ->exists();

            if ($activeSupervisorExists) {
                throw new Exception(
                    __('messages.already has an active supervisor'),
                    422
                );
            }
        }

        if ($creator->role === 'general_manager' && $data['role'] === 'service_manager'
        ) {
            if (empty($data['dep_id'])) {
              throw new Exception(
                    __('messages.The section must be specified when creating a service manager'),
                    422
                ); }
            $activeServiceManagerExists = Staff::where(
                    'dep_id',
                    $data['dep_id']
                )
                ->where('role', 'service_manager')
                ->where('is_active', true)
                ->exists();

            if ($activeServiceManagerExists) {

                throw new Exception(
                    __('messages.already has an active service manager'),
                    422
                );
            }
        }

        $staff = Staff::create([
            'dep_id' => $data['dep_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'image' => $data['image'] ?? null,
            'password' => Hash::make($data['password']),
            'created_at' => now(),
            'role' => $data['role'],
            'status' => $data['status'],
            'max_load' => $data['max_load'],
            'service_load' => $data['service_load'],
        ]);

        return [
            'staff' => $staff,
        ];
    }

 //تعديل الرول
    public function updateRole(
        Staff $staff,
        array $data
    ): Staff {$newRole = $data['role'];

        if ($newRole === 'general_manager') {
            throw new Exception(
                __('messages.cannot assign general manager role'),
                403
            ); }

        if ($newRole === 'supervisor') {

            $activeSupervisorExists = Staff::where(
                    'dep_id',
                    $staff->dep_id
                )
                ->where('role', 'supervisor')
                ->where('is_active', true)
                ->where('staff_id', '!=', $staff->staff_id)
                ->exists();

            if ($activeSupervisorExists) {
                throw new Exception(
                    __('messages.already has an active supervisor'),
                    422
                ); }
        }

        if ($newRole === 'service_manager') {

            $activeServiceManagerExists = Staff::where(
                    'dep_id',  $staff->dep_id
                )
                ->where('role', 'service_manager')
                ->where('is_active', true)
                ->where('staff_id', '!=', $staff->staff_id)
                ->exists();

            if ($activeServiceManagerExists) {

                throw new Exception(
                    __('messages.already has an active service manager'),
                    422
                );
            }
        }
        $staff->role = $newRole;
        $staff->save();
        return $staff;}


//تعديل معلومات الموظف
    public function updateInfo(Staff $staff,array $data, Staff $user):
     Staff {

        if (in_array($user->role, ['supervisor','service_manager'])) {

            if ($staff->dep_id !== $user->dep_id) {

                throw new Exception(
                    __('messages.not_allowed'),
                    403
                );
            }
            unset($data['dep_id']);
            unset($data['role']);
        }
        if (!empty($data['password'])) {

            $data['password'] = Hash::make(
                $data['password']
            );

        } else {

            unset($data['password']);
        }
        $staff->update($data);

        return $staff;
    }

      public function saveFirebaseToken(
      $staff,
    string $token
    ): bool
     {

    $staff->update([

        'fcm_token' => $token

    ]);

    return true;
}

    }