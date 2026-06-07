<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
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

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        try {

        $staffId = Auth::user()->staff_id;

         //  $staff = Staff::where('role', '!=', 'employee')->get();
        //$staffId = auth('staff')->id();
        //$staffId = auth()->id(); 

            // استدعاء السيرفيس لإنشاء الشكوى
            $complaint = $this->complaintService->createComplaint(
                $request->validated(), 
                $staffId
            );

            // إرجاع استجابة نجاح نجاح
            return response()->json([
                'success' => true,
                'message' => 'تم تقديم الشكوى بنجاح وبانتظار المراجعة.',
                'data'    => $complaint
            ], 201);

        } catch (Exception $e) {
        
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع أثناء تقديم الشكوى، يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }}