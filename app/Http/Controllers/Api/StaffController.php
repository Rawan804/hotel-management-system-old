<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateStaffInfoRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\SaveFcmTokenRequest;
use App\Models\StaffShift;
use Illuminate\Http\Request;
use Exception;
class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

   // عرض الموظفين
public function index()
{
    $user = Auth::user();

    $selectedColumns = ['staff_id', 'dep_id', 'name', 'email', 'phone', 'role','image']; 
   
    if ($user->role === 'general_manager') {
        $staff = Staff::select($selectedColumns)->where('is_active', true)
          ->with(['department' => function($query) {
         $query->select('dep_id', 'name_ar', 'name_en');
}])
            ->where('role', '!=', 'general_manager')
            ->get();
    }
    elseif ($user->role === 'supervisor') {
        $staff = Staff::select($selectedColumns)->where('is_active', true)
            ->with(['department' => function($query) {
    $query->select('dep_id', 'name_ar', 'name_en');
}])
            ->where('dep_id', $user->dep_id)
            ->where('role', '!=', 'supervisor')
            ->get();
    }
    else {
        return response()->json(['message' => 'Forbidden'], 403);
    }
$customResponse = $staff->map(function($member) {
        return [
            'id'              => $member->staff_id, // أضفنا الـ id هنا ليتم إرساله للفرونت إند
            'name'            => $member->name,
            'email'           => $member->email,
            'phone'           => $member->phone,
            'role'            => $member->role,
            'image'           => $member->image,
           'department_name_ar' => $member->department ? $member->department->name_ar
    : 'لا يوجد قسم',

'department_name_en' => $member->department? $member->department->name_en
    : 'No Department',    ];

    });
    return response()->json($customResponse);
}

    // إنشاء موظف
public function store(StoreStaffRequest $request, StaffService $service)
{
    $creator = Auth::user();
    if ($creator->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    try {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('staff_images', 'public');
            $data['image'] = $imagePath; 
        } else {
            $data['image'] = null;
        }

        $result = $service->create($data, $creator);    
        return response()->json([
            'message' => __('messages.Staff created successfully'),
            'staff' => $result['staff']
        ], 201);

    } catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 400);
    }
}

   // تفعيل / تعطيل
public function toggleActive(Staff $staff)
{
    $user = Auth::user();
    if ($user->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    if ($user->staff_id === $staff->staff_id) {
        return response()->json(['message' => __('messages.you cant activiate')], 400);
    }

    if ($user->role === 'supervisor' && $staff->dep_id !== $user->dep_id) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $staff->is_active = !$staff->is_active;
    $staff->save();

    return response()->json([
        'message' =>__('messages.updated_success'),
        'staff' => $staff
    ]);
}

//تعديل الرول 
public function updateRole(StoreStaffRequest $request, Staff $staff, StaffService $service)
{
    $user = Auth::user();

    if ($user->role !== 'general_manager') {
        return response()->json(['message' => __('messages.unauthorized')], 403);
    }

    if ($staff->role === 'general_manager') {
        return response()->json(['message' => __('messages.the general_manager ')], 400);
    }

    try {
        
    $data = $request->validated(); 
        
        $updatedStaff = $service->updateRole($staff, $data);

        return response()->json([
            'message' => __('messages.updated_role'),
            'staff' => $updatedStaff
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 400);
    }
}


// تابع تعديل المعلومات العادية
public function updateInfo(UpdateStaffInfoRequest $request, Staff $staff, StaffService $service)
{
    $user = Auth::user();

    if ($user->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    try {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($staff->image) {
                Storage::disk('public')->delete($staff->image);
            }
            $data['image'] = $request->file('image')->store('staff_images', 'public');
        }
        $updatedStaff = $service->updateInfo($staff, $data, $user);
        return response()->json([
            'message' => __('messages.update successfuly'),
            'staff' => $updatedStaff
        ], 200);
   } catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 400);
    }
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

        'days' => 'required|array',

        'days.*' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',

        'start_time' => 'required',

        'end_time' => 'required',

    ]);



    // الموظف
    $staff = Staff::findOrFail(
        $request->staff_id
    );



    // الصلاحيات حسب القسم
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



    $shifts = [];


    // إنشاء شيفت لكل يوم
    foreach ($request->days as $day) {


        $shift = StaffShift::create([

            'staff_id' => $staff->staff_id,

            'day_of_week' => $day,

            'start_time' => $request->start_time,

            'end_time' => $request->end_time,

            'is_active' => true

        ]);


        $shifts[] = $shift;

    }



    return response()->json([

        'success' => true,

        'message' => app()->getLocale() === 'ar'
            ? 'تمت إضافة الشيفتات بنجاح'
            : 'Shifts added successfully',

        'data' => $shifts

    ], 201);
}}