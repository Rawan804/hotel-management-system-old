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

    $selectedColumns = ['staff_id', 'dep_id', 'name', 'email', 'phone', 'role','image', 'status']; 
   
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


  elseif ($user->role === 'service_manager') {
        $staff = Staff::select($selectedColumns)->where('is_active', true)
            ->with(['department' => function($query) {
    $query->select('dep_id', 'name_ar', 'name_en');
}])
            ->where('dep_id', $user->dep_id)
            ->where('role', '!=', 'service_manager')
            ->get();
    }


    else {
        return response()->json(['message' => 'Forbidden'], 403);
    }
$customResponse = $staff->map(function($member) {
        return [
            'id'              => $member->staff_id,
            'name'            => $member->name,
            'email'           => $member->email,
            'phone'           => $member->phone,
            'role'            => $member->role,
            'image'           => $member->image,
             'status' => $member->status,
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
// عرض شيفتات موظف معين
public function getShifts(Staff $staff)
{
    $user = Auth::user();

    // موظف عادي يقدر يشوف شيفتاته فقط
    if ($user->role === 'employee' && $user->staff_id !== $staff->staff_id) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // المشرف / مدير الخدمات يشوف بس موظفي قسمه
    if (in_array($user->role, ['supervisor', 'service_manager']) && $staff->dep_id !== $user->dep_id) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $shifts = StaffShift::where('staff_id', $staff->staff_id)
        ->where('is_active', true)
        ->select('id', 'day_of_week', 'start_time', 'end_time')
        ->orderByRaw("FIELD(day_of_week, 'sunday','monday','tuesday','wednesday','thursday','friday','saturday')")
        ->get();

    return response()->json([
        'success' => true,
        'staff_id' => $staff->staff_id,
        'staff_name' => $staff->name,
        'shifts' => $shifts
    ]);
}
// إضافة شيفت لموظف
// إضافة شيفت لموظف
public function addShift(Request $request)
{
    $user = Auth::user();


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

 

    $request->validate([
        'staff_id' => 'required|exists:staff,staff_id',

        'days' => 'required|array',

        'days.*' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',

       'start_time' => 'required|date_format:H:i',
'end_time' => 'required|date_format:H:i|after:start_time',
    ]);

  

    $staff = Staff::findOrFail(
        $request->staff_id
    );

   
    if ($user->role === 'general_manager') {

        if (!in_array($staff->role, [
            'supervisor',
            'service_manager'
        ])) {

            return response()->json([
                'message' => app()->getLocale() === 'ar'
                    ? 'المدير العام يمكنه إضافة الشيفتات للمشرف أو مدير الخدمات فقط'
                    : 'General Manager can add shifts only to Supervisor or Service Manager'
            ], 403);
        }
    }

  

    elseif (in_array($user->role, [
        'supervisor',
        'service_manager'
    ])) {

        if ($staff->role !== 'employee') {

            return response()->json([
                'message' => app()->getLocale() === 'ar'
                    ? 'يمكن إضافة الشيفتات للموظفين فقط'
                    : 'Shifts can only be added to employees'
            ], 403);
        }

        if ($staff->dep_id != $user->dep_id) {

            return response()->json([
                'message' => app()->getLocale() === 'ar'
                    ? 'يمكنك إضافة الشيفتات لموظفي قسمك فقط'
                    : 'You can only add shifts to employees in your department'
            ], 403);
        }
    }


    foreach ($request->days as $day) {

        $conflict = StaffShift::where(
            'staff_id',
            $staff->staff_id
        )
        ->where(
            'day_of_week',
            $day
        )
        ->where('is_active', true)

        // الشرط الأساسي لمنع تداخل الأوقات
        ->where(function ($query) use ($request) {

            $query->where(
                'start_time',
                '<',
                $request->end_time
            )
            ->where(
                'end_time',
                '>',
                $request->start_time
            );

        })
        ->exists();

        if ($conflict) {

            return response()->json([
                'success' => false,

                'message' => app()->getLocale() === 'ar'
                    ? "يوجد تعارض في الشيفت ليوم {$day} لهذا الموظف"
                    : "There is a shift time conflict on {$day} for this employee"
            ], 422);
        }
    }

  

    $shifts = [];

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

 

    if ($staff->isWorkingNow()) {

        $onLeave = $staff->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        if (!$onLeave) {

            $hasActiveTask = $staff->tasks()
                ->where('status', 'in_progress')
                ->exists();

          
            $hasOpenRequest = $staff->serviceRequests()
                ->whereIn('status', [
                    'pending',
                    'in_progress'
                ])
                ->exists();

            if ($staff->service_load > $staff->max_load) {

                $staff->status = 'overloaded';

            } elseif ($hasActiveTask || $hasOpenRequest) {
                $staff->status = 'busy';

            } else {

                $staff->status = 'available';
            }

            $staff->save();
        }
    }

 
    return response()->json([
        'success' => true,

        'message' => app()->getLocale() === 'ar'
            ? 'تمت إضافة الشيفتات بنجاح'
            : 'Shifts added successfully',

        'data' => $shifts

    ], 201);
}
}