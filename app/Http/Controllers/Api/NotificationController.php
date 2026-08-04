<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    public function index()
    {

        $notifications = Auth::user()
            ->notifications()

            ->latest()

            ->get();


        return response()->json([

            'success'=>true,

            'data'=>$notifications

        ]);

    }



    public function markAsRead($id)
    {

        $notification = Auth::user()

            ->notifications()

            ->findOrFail($id);


        $notification->update([

            'is_read'=>true

        ]);


        return response()->json([

            'success'=>true

        ]);

    }

}