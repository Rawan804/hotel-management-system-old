<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequests;
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

    public function store(LeaveRequests $request): JsonResponse
    {
        try {

            $currentUser = Auth::user();

            $leaveRequests = $this->leaveRequestsService->createLeave(
                $request->validated(),
                $currentUser
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.created_success'),
                'data'    => $leaveRequests
            ], 201);

        } catch (Exception $e) {

            Log::error('Error creating leave request: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.created_error')
            ], 500);
        }
    }


    public function updateStatus(LeaveRequests $request, $id): JsonResponse
    {
        try {

            $supervisor = Auth::user();

            $leaveRequest = $this->leaveRequestsService->updateStatus(
                $id,
                $request->validated()['status'],
                $supervisor
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.updated_success'),
                'data' => $leaveRequest
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function getDepartmentLeaveRequests(): JsonResponse
    {
        try {

            $supervisor = Auth::user();

            $leaveRequests = $this->leaveRequestsService->getSupervisorLeaveRequests($supervisor);

            return response()->json([
                'success' => true,
                'message' => __('messages.fetch_successL'),
                'data' => $leaveRequests
            ], 200);

        } catch (\Exception $e) {

            $code = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $code);
        }
    }
}