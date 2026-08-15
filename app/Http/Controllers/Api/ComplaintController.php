<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintRequest;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    public function store(ComplaintRequest $request): JsonResponse
    {
        try {

            $currentUser = Auth::user();

            $complaint = $this->complaintService->createComplaint(
                $request->validated(),
                $currentUser
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.created_successC'),
                'data'    => $complaint
            ], 201);

        } catch (Exception $e) {

            $code = ($e->getCode() === 403) ? 403 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $code);
        }
    }

    public function updateStatus(ComplaintRequest $request, $id): JsonResponse
    {
        try {
            $currentUser = Auth::user();

            $complaint = $this->complaintService->updateStatus(
                $id,
                $request->validated()['status'],
                $currentUser
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.updated_success'),
                'data' => $complaint
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function getDepartmentComplaints(): JsonResponse
    {
        try {

            $supervisor = Auth::user();

            $complaints = $this->complaintService->getComplaintsForUser($supervisor);

            return response()->json([
                'success' => true,
                'message' => __('messages.fetch_successC'),
                'data' => $complaints
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