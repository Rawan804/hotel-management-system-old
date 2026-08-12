<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class StaffWorkController extends Controller
{
    public function myWork(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|integer|exists:staff,staff_id'
        ]);

        // المهام الثابتة
        $tasks = Task::with([
                'fixedTask',
                'items.item'
            ])
            ->where('staff_id', $request->staff_id)
            ->get()
            ->map(function ($task) {

                return [
                    'type' => 'fixed_task',
                    'id' => $task->id,
                    'status' => $task->status,
                    'name' => $task->fixedTask->name,
                    'items' => $task->items
                ];
            });

        // طلبات الزبائن
        $requests = ServiceRequest::with([
                'booking',
                'serviceType'
            ])
            ->where('staff_id', $request->staff_id)
            ->get()
            ->map(function ($request) {

                return [
                    'type' => 'service_request',
                    'id' => $request->id,
                    'status' => $request->status,
                    'service_type' => $request->serviceType?->name,
                    'booking_id' => $request->booking_id
                ];
            });

        // دمج القائمتين
        $allWork = $tasks
            ->concat($requests)
            ->sortByDesc('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $allWork
        ]);
    }
}