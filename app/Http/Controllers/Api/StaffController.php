<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffInfoRequest;
use App\Models\Staff;
use App\Services\StaffService;
use App\Http\Requests\SaveFcmTokenRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    //عرض الموظفين 

    public function index(): JsonResponse
    {
        $user = Auth::user();

        $selectedColumns = [
            'staff_id',
            'dep_id',
            'name',
            'email',
            'phone',
            'role',
            'image',
            'max_load',
            'service_load',
            'status',
        ];

        if ($user->role === 'general_manager') {

            $staff = Staff::select($selectedColumns)
                ->where('is_active', true)
                ->with([
                    'department' => function ($query) {
                        $query->select('dep_id', 'name');
                    }
                ])
                ->where('role', '!=', 'general_manager')
                ->get();
        }

        elseif (in_array($user->role, ['supervisor', 'service_manager'])) {

            $staff = Staff::select($selectedColumns)
                ->where('is_active', true)
                ->with([
                    'department' => function ($query) {
                        $query->select('dep_id', 'name');
                    }
                ])
                ->where('dep_id', $user->dep_id)
                ->whereNotIn('role', [
                    'supervisor',
                    'service_manager',
                ])
                ->get();
        }
        else {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $customResponse = $staff->map(function ($member) {

            return [
              'id'=>$member->staff_id,
              'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'role' => $member->role,
                'image' => $member->image,
                'max_load' => $member->max_load,
                'service_load' => $member->service_load,
                'status' => $member->status,
                'department_name' => $member->department
                    ? $member->department->name
                    : 'لا يوجد قسم',
            ];
        }); 

        return response()->json($customResponse);
    }
    //| إنشاء موظف 

    public function store(
        StoreStaffRequest $request,
        StaffService $service
    ): JsonResponse {

        $creator = Auth::user();
        if (!in_array($creator->role, [
            'general_manager',
            'supervisor',
            'service_manager',
        ])) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        try {

            $data = $request->validated();
            if ($request->hasFile('image')) {
             $imagePath = $request ->file('image')->store('staff_images', 'public');
                $data['image'] = $imagePath;

            } else {

                $data['image'] = null;
            }
            $result = $service->create($data, $creator);

            return response()->json([
                'message' => __('messages.Staff created successfully'),
                'staff' => $result['staff'],
            ], 201);

        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], $this->getExceptionStatusCode($e));
        }
    }

   //تفعيل وتعطيل
    public function toggleActive(Staff $staff): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, [
            'general_manager',
            'supervisor',
            'service_manager',
        ])) {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }


        if ($user->staff_id === $staff->staff_id) {

            return response()->json([
                'message' => __('messages.you cant activiate')
            ], 400);
        }


        if (
            in_array($user->role, ['supervisor', 'service_manager']) &&
            $staff->dep_id !== $user->dep_id
        ) {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $staff->is_active = !$staff->is_active;
        $staff->save();
        return response()->json([
           'message' => __('messages.updated_success'),
            'staff' => $staff,
        ]);
    }

//تعديل الرول
    public function updateRole(
        StoreStaffRequest $request,
        Staff $staff,
        StaffService $service
    ): JsonResponse {

        $user = Auth::user();
        if ($user->role !== 'general_manager') {

            return response()->json([
                'message' => __('messages.unauthorized')
            ], 403);
        }
        if ($staff->role === 'general_manager') {

            return response()->json([
                'message' => __('messages.the general_manager')
            ], 400);
        }

        try {

            $data = $request->validated();

            $updatedStaff = $service->updateRole(
                $staff,
                $data
            );

            return response()->json([
                'message' => __('messages.updated_role'),
                'staff' => $updatedStaff,
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], $this->getExceptionStatusCode($e));
        }
    }
    //تعديل المعلومات للموظف
    public function updateInfo(
        UpdateStaffInfoRequest $request,
        Staff $staff,
        StaffService $service
    ): JsonResponse {

        $user = Auth::user();
        if (!in_array($user->role, [
            'general_manager',
            'supervisor',
            'service_manager',
        ])) {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        try {

            $data = $request->validated();

            if ($request->hasFile('image')) {

                if ($staff->image) {
                    Storage::disk('public')->delete($staff->image);
                }

                $data['image'] = $request
                    ->file('image')
                    ->store('staff_images', 'public');
            }

            $updatedStaff = $service->updateInfo(
                $staff,
                $data,
                $user
            );

            return response()->json([
                'message' => __('messages.update successfuly'),
                'staff' => $updatedStaff,
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], $this->getExceptionStatusCode($e));
        }
    }


    private function getExceptionStatusCode(Exception $e): int
    {
        return is_int($e->getCode()) &&
            $e->getCode() >= 100 &&  $e->getCode() <= 599
            ? $e->getCode()
            : 400;
    }

public function saveFirebaseToken(
    SaveFcmTokenRequest $request,
    StaffService $service
)

{
    $staff = Auth::user();


    $service->saveFirebaseToken(
        $staff,
        $request->validated()['fcm_token']
    );


    return response()->json([

        'success' => true,

        'message' => 'Firebase token saved successfully'
    ]);
}






// إضافة شيفت لموظف
public function addShift(Request $request)
{
    $user = Auth::user();


    // الصلاحيات
    if (!in_array($user->role, [
        'general_manager',
        'supervisor',
        'service_manager'
    ])) {

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'غير مسموح'
                : 'Forbidden'
        ], 403);

    }



    // البيانات المطلوبة
    $request->validate([

        'staff_id' => 'required|exists:staff,staff_id',

        'shift_date' => 'required|date',

        'start_time' => 'required',

        'end_time' => 'required',

    ]);



    // الموظف الذي نضيف له الشيفت
    $staff = Staff::findOrFail(
        $request->staff_id
    );



    // المدير العام يقدر على كل الأقسام
    // الباقي فقط ضمن قسمه
    if (
        $user->role !== 'general_manager'
        &&
        $staff->dep_id != $user->dep_id
    ) {

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'يمكنك إضافة الشيفتات لموظفي قسمك فقط'
                : 'You can only add shifts for your department'
        ],403);

    }



    // إنشاء الشيفت
    $shift = StaffShift::create([

        'staff_id' => $staff->staff_id,

        'shift_date' => $request->shift_date,

        'start_time' => $request->start_time,

        'end_time' => $request->end_time,

        'is_active' => true

    ]);



    return response()->json([

        'success' => true,

        'message' => app()->getLocale() === 'ar'
            ? 'تمت إضافة الشيفت بنجاح'
            : 'Shift added successfully',

        'data' => $shift

    ], 201);
}}
