<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFixedTaskRequest;
use App\Services\FixedTaskService;
use App\Services\TaskService;
use App\Models\FixedTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff;


class FixedTaskController extends Controller
{
    public function __construct(
    private FixedTaskService $service,
    private TaskService $taskService
) {}

/*
    public function index()
    {
        return response()->json(
            FixedTask::with('items')->get()
        );
    }
*/
public function index()
{
    $creator = Auth::user();

    if (!$creator) {
        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'غير مصرح'
                : 'Unauthenticated'
        ], 401);
    }

    $query = FixedTask::with('items');

    // General Manager → يشوف كل القوالب
    if ($creator->role === 'general_manager') {

        $tasks = $query->get();
    }

    // Supervisor / Service Manager → قوالب قسمهم فقط
    elseif (in_array($creator->role, [
        'supervisor',
        'service_manager'
    ])) {

        $tasks = $query
            ->where('dep_id', $creator->dep_id)
            ->get();
    }

    // باقي الأدوار ممنوعة
    else {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'غير مصرح لك بعرض قوالب المهام'
                : 'You are not authorized to view task templates'
        ], 403);
    }

    return response()->json([
        'success' => true,
        'data' => $tasks
    ]);
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

    if (!$creator) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من صلاحية إنشاء القالب
    |--------------------------------------------------------------------------
    */

    if (!in_array($creator->role, [
        'general_manager',
        'supervisor',
        'service_manager'
    ])) {

        return response()->json([
            'success' => false,
            'message' => app()->getLocale() === 'ar'
                ? 'غير مصرح لك بإنشاء قالب مهمة'
                : 'You are not authorized to create a fixed task template'
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Supervisor / Service Manager
    | القالب يجب أن يكون ضمن قسمه
    |--------------------------------------------------------------------------
    */

    if (in_array($creator->role, [
        'supervisor',
        'service_manager'
    ])) {

        if ($request->dep_id != $creator->dep_id) {

            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'يمكنك إنشاء قوالب لقسمك فقط'
                    : 'You can only create task templates for your department'
            ], 403);
        }
    }

  

    $task = $this->service->create(
        $request->validated()
    );

    return response()->json([
        'success' => true,
        'message' => app()->getLocale() === 'ar'
            ? 'تم إنشاء قالب المهمة بنجاح'
            : 'Fixed task template created successfully',
        'data' => $task
    ]);
}}