<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFixedTaskRequest;
use App\Services\FixedTaskService;
use App\Models\FixedTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff;

class FixedTaskController extends Controller
{
    public function __construct(private FixedTaskService $service) {}

    public function index()
    {
        return response()->json(
            FixedTask::with('items')->get()
        );
    }

    public function store(StoreFixedTaskRequest $request)
    {
        $task = $this->service->create($request->validated());

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء المهمة الأساسية'
                : 'Fixed task created successfully',

            'data' => $task
        ]);
    }



    public function assignTask(StoreFixedTaskRequest $request)
{
    $creator = Auth::user();

    $targetStaff = Staff::findOrFail($request->staff_id);


    // المدير العام
    if ($creator->role === 'general_manager') {

        if (!in_array($targetStaff->role, [
            'supervisor',
            'service_manager'
        ])) {

            return response()->json([
                'message'=>'General manager can assign only supervisors and service manager'
            ],403);
        }

    }


    // مدير القسم
    elseif (in_array($creator->role, [
        'supervisor',
        'service_manager'
    ])) {


        if ($targetStaff->dep_id != $creator->dep_id) {

            return response()->json([
                'message'=>'You can only assign tasks inside your department'
            ],403);

        }


        if ($targetStaff->role !== 'employee') {

            return response()->json([
                'message'=>'You can only assign tasks to employees'
            ],403);

        }

    }


    else {

        return response()->json([
            'message'=>'Forbidden'
        ],403);

    }



    $task = $this->service->create(
        $request->validated()
    );


    return response()->json([
        'success'=>true,
        'data'=>$task
    ]);
}
}