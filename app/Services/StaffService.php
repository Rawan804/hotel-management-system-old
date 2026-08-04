<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Exception; 

use Illuminate\Support\Facades\Storage;
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
                throw new Exception( __('messages.The section must be specified when creating a supervisor'), 422);
            }
            $activeSupervisorExists = Staff::where('dep_id', $data['dep_id'])
                                            ->where('role', 'supervisor')
                                            ->where('is_active', true) ->exists();

            if ($activeSupervisorExists) {
                throw new Exception( __('messages.already has an active supervisor'), 422);
            }
        }

        $staff = Staff::create([
            'dep_id' => $data['dep_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'image' => $data['image'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'created_at' => $creator->staff_id,
        ]);
  
        return [
            'staff' => $staff
        ];

    
    }


public function updateRole(Staff $staff, array $data)
{
    $newRole = $data['role'];

    if ($newRole === 'supervisor') {
      
    $activeSupervisorExists = Staff::where('dep_id', $staff->dep_id)
                                        ->where('role', 'supervisor')
                                        ->where('is_active', true)
                                        ->where('staff_id', '!=', $staff->staff_id) 
                                       ->exists();

        if ($activeSupervisorExists) {
            throw new Exception(
  __('messages.already has an active supervisor'), 422);
        }
    }

    $staff->role = $newRole;
    $staff->save();

    return $staff;
}

public function updateInfo(Staff $staff, array $data, Staff $user)
{
    
    if ($user->role === 'supervisor') {

        if ($staff->dep_id !== $user->dep_id) {
            throw new Exception( __('messages.not_allowed'), 403);
        }

        unset($data['dep_id']);
     
        unset($data['role']); 
    }

    if (!empty($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    } else {
        unset($data['password']);   }
  
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