<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintRequest;
use App\Services\ComplaintService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Create a new complaint
     */
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
                'data'    => $complaint,
            ], 201);

        } catch (Exception $e) {

            $code = $e->getCode();

            if ($code < 400 || $code > 599) {
                $code = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Update complaint status
     */
    public function updateStatus(
        ComplaintRequest $request,
        int $id
    ): JsonResponse {
        try {
            $currentUser = Auth::user();

            $validated = $request->validated();

            $complaint = $this->complaintService->updateStatus(
                $id,
                $validated['status'],
                $currentUser
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.updated_success'),
                'data'    => $complaint,
            ], 200);

        } catch (Exception $e) {

            $code = $e->getCode();

            if ($code < 400 || $code > 599) {
                $code = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Get complaints according to current user's role
     */
    public function getDepartmentComplaints(): JsonResponse
    {
        try {
            $currentUser = Auth::user();

            $complaints = $this->complaintService
                ->getComplaintsForUser($currentUser);

            return response()->json([
                'success' => true,
                'message' => __('messages.fetch_successC'),
                'data'    => $complaints,
            ], 200);

        } catch (Exception $e) {

            $code = $e->getCode();

            if ($code < 400 || $code > 599) {
                $code = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }
}