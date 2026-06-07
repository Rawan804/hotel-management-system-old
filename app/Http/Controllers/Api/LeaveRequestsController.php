<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequests;
use App\Services\LeaveRequestsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
class LeaveRequestsController extends Controller
{
    protected LeaveRequestsService $leaveRequestsService;

    public function __construct(LeaveRequestsService $leaveRequestsService)
    {
        $this->leaveRequestsService = $leaveRequestsService;
    }

    public function store(StoreLeaveRequests $request): JsonResponse
    {
        try {

        $staffId = Auth::user()->staff_id;
            $leaveRequests = $this->leaveRequestsService->createLeave(
                $request->validated(), 
                $staffId
            );

            // إرجاع استجابة نجاح نجاح
            return response()->json([
                'success' => true,
                'message' => 'تم تقديم الاجازة بنجاح وبانتظار المراجعة.',
                'data'    => $leaveRequests
            ], 201);

        } catch (Exception $e) {
  Log::error('Error creating leave request: '.$e->getMessage());        
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع أثناء تقديم الاجازة، يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }}