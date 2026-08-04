<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFixedTaskRequest;
use App\Services\FixedTaskService;
use App\Models\FixedTask;

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
}