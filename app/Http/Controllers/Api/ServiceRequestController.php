<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Services\ServiceRequestService;

class ServiceRequestController extends Controller
{
    public function __construct(
        private ServiceRequestService $service
    ) {}

    // 🔹 إنشاء طلب خدمة
    public function store(StoreServiceRequestRequest $request)
    {
        $result = $this->service->create($request->validated());

        if (!$result) {
            return response()->json([
                'message' => 'No available staff in this department'
            ], 400);
        }

        return response()->json([
            'message' => 'Request created and assigned successfully',
            'data' => $result
        ]);
    }

    // 🔹 عرض load الموظفين
    public function staffLoad()
    {
        return response()->json(
            $this->service->staffLoad()
        );
    }
}