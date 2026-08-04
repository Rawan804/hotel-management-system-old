<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Notification;
class NotificationService
{

    public function __construct(
        protected FirebaseService $firebaseService
    ) {
    }

public function sendToStaff(
    Staff $staff,
    string $title,
    string $message,
    array $data = []
): void
{

    Notification::create([

        'staff_id'=>$staff->staff_id,

        'title'=>$title,

        'body'=>$message,

        'type'=>$data['type'] ?? 'general',

        'data'=>$data

    ]);


    if(!$staff->fcm_token){

        return;

    }


    $this->firebaseService->sendNotification(

        $staff->fcm_token,

        $title,

        $message,

        $data

    );

}}