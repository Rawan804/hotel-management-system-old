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
        $task = $this->service->create(
            $request->validated()
        );

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'لا يمكن إنشاء المهمة، الموظف خارج الشيفت أو في إجازة'
                    : 'Cannot create the task because the employee is outside their shift or on leave',
            ], 422);
        }

        return response()->json([
            'success' => true,
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
                    'success' => false,
                    'message' => 'General manager can assign only supervisors and service manager'
                ], 403);
            }
        }

        // مدير القسم
        elseif (in_array($creator->role, [
            'supervisor',
            'service_manager'
        ])) {

            if ($targetStaff->dep_id != $creator->dep_id) {

                return response()->json([
                    'success' => false,
                    'message' => 'You can only assign tasks inside your department'
                ], 403);
            }

            if ($targetStaff->role !== 'employee') {

                return response()->json([
                    'success' => false,
                    'message' => 'You can only assign tasks to employees'
                ], 403);
            }
        }

        else {

            return response()->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
        }

        

        $onLeave = $targetStaff->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

    

        if (
            $targetStaff->status === 'offline' ||
            $onLeave ||
            !$targetStaff->isWorkingNow()
        ) {

            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'لا يمكن إسناد المهمة، الموظف خارج الشيفت أو في إجازة'
                    : 'Cannot assign the task because the employee is outside their shift or on leave',
            ], 422);
        }

       

        $task = $this->service->create(
            $request->validated()
        );

      

        if (!$task) {

            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'لا يمكن إسناد المهمة، الموظف خارج الشيفت أو في إجازة'
                    : 'Cannot assign the task because the employee is outside their shift or on leave',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() === 'ar'
                ? 'تم إسناد المهمة بنجاح'
                : 'Task assigned successfully',
            'data' => $task
        ]);
    }
}