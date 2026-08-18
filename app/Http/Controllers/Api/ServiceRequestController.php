<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\Staff;
use App\Services\ServiceRequestService;
use Illuminate\Http\Request;
use App\Models\Booking;

class ServiceRequestController extends Controller
{

    public function __construct(
        private ServiceRequestService $service
    ) {}



    /*
    |--------------------------------------------------------------------------
    | CREATE REQUEST
    |--------------------------------------------------------------------------
    */
    public function store(StoreServiceRequestRequest $request)
    {

        $result = $this->service->create(
            $request->validated(),
            $request->user()
        );



        if (!$result) {

            return response()->json([
                'success' => false,
                'message' => 'No staff available in this department and shift'
            ], 409);

        }



        $staff = null;


        if ($result->staff_id) {

            $staff = Staff::where(
                'staff_id',
                $result->staff_id
            )->first();

        }

$data = [
      
   // 'dep_id' => $result->dep_id,

    'details' => $result->details,

    'location' => $result->location,

    'status' => $result->status,

];


// إذا كان طلب مخصص بدون خدمة
if($result->ser_id === null){

    $data['staff'] = null;

}else{

    // الطلب العادي يبقى مثل السابق
    $data = [

        'id' => $result->id,

        'booking_id' => $result->booking_id,

        'dep_id' => $result->dep_id,

        'ser_id' => $result->ser_id,
         'service_name' => $result->service 
            ? $result->service->name 
            : null,

        'details' => $result->details,

        'location' => $result->location,

        'status' => $result->status,

        //'weight' => $result->weight,

        //'assigned_at' => $result->assigned_at,

        'staff' => $staff ? [
            'name' => $staff->name,
        ] : null

    ];

}


return response()->json([

    'success' => true,

    'message' => 'Service request created successfully',

    'data' => $data

]);

    }





    /*
    |--------------------------------------------------------------------------
    | STAFF REQUESTS
    |--------------------------------------------------------------------------
    */
    public function staffRequests(Request $request)
    {

        $staff = $request->user();



        return response()->json([

            'success' => true,


            'data' => ServiceRequest::where(
    'staff_id',
    $staff->staff_id
)
->with('service')
->latest()
->get()

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | CUSTOMER REQUESTS
    |--------------------------------------------------------------------------
    */
    public function customerRequests(Request $request)
    {

        $customer = $request->user();



        return response()->json([

            'success' => true,


            'data' => ServiceRequest::whereHas(
                'booking',
                function ($q) use ($customer) {

                    $q->where(
                        'customer_id',
                        $customer->id
                    );

                }
            )

            ->latest()

            ->get()

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | START REQUEST
    |--------------------------------------------------------------------------
    */
    public function start($id)
    {

        $result = $this->service->startRequest($id);



        if (!$result) {

            return response()->json([

                'success' => false,

            'message' => __('messages.request_cannot_be_started')

            ],400);

        }



        return response()->json([

            'success' => true,

        'message' => __('messages.request_started'),

            'data' => $result

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | COMPLETE REQUEST
    |--------------------------------------------------------------------------
    */
    
    public function complete($id)
    {

        $result = $this->service->completeRequest($id);



        if (!$result) {

            return response()->json([

                'success' => false,

            'message' => __('messages.request_already_completed')

            ],400);

        }



        return response()->json([

            'success' => true,

                    'message' => __('messages.request_completed'),


            'data' => $result

        ]);

    }





    public function overloadedStaff(Request $request)
{
    $creator = $request->user();

    if ($creator->role !== 'service_manager') {

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);

    }

    $staff = Staff::where('role', 'employee')
        ->where('is_active', true)
        ->whereColumn('service_load', '>', 'max_load')
        ->with([
            'department',
            'serviceRequests'
        ])
        ->get()
        ->map(function ($employee) {

            $activeRequests = $employee->serviceRequests
                ->whereIn('status', [
                    'pending',
                    'in_progress'
                ])
                ->count();

            return [

                'staff_id' => $employee->staff_id,

                'name' => $employee->name,

'department' => optional($employee->department)->name,
/*'department' => $employee->department ? 
(
    app()->getLocale() == 'ar'
        ? $employee->department->name_ar
        : $employee->department->name_en
) 
: null,*/
                'current_load' => $employee->service_load,

                'max_load' => $employee->max_load,

                'overload_amount' =>
                    $employee->service_load - $employee->max_load,

                'capacity_percent' => round(
                    ($employee->service_load / $employee->max_load) * 100,
                    1
                ),

                'active_requests' => $activeRequests,

                'status' => $employee->status,

                'working_now' => $employee->isWorkingNow()

            ];

        })
        ->sortByDesc('overload_amount')
        ->values();

    return response()->json([

        'success' => true,

        'count' => $staff->count(),

        'data' => $staff

    ]);
}

//...............................................................


public function pendingReview(Request $request)
{
    $staff = $request->user();


    if ($staff->role !== 'service_manager') {

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);

    }


    $requests = ServiceRequest::where(
        'status',
        'pending_review'
    )
    ->latest()
    ->get()
    ->map(function ($request) {

        return [

            'id' => $request->id,

            'dep_id' => $request->dep_id,

            'service_name' => $request->service ? $request->service->name : null,

            'service_location' => $request->location,

            'details' => $request->details,

            'status' => $request->status

        ];

    });


    return response()->json([

        'success' => true,

        'data' => $requests

    ]);

}
//....................................................

public function review(Request $request, $id)
{

    $staff = $request->user();


    // فقط مدير قسم الخدمات
    if ($staff->role !== 'service_manager') {

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);

    }



    $serviceRequest = ServiceRequest::where(
        'status',
        'pending_review'
    )
    ->findOrFail($id);



    $request->validate([

        'dep_id' => 'required|exists:departments,dep_id',

        'weight' => 'required|integer|min:1'

    ]);



    $serviceRequest->update([

        'dep_id' => $request->dep_id,

        'weight' => $request->weight,

        'status' => 'pending'

    ]);



    // بعد تحديد القسم والوزن يتم توزيعه
    $assignedRequest = $this->service->assignReviewedRequest($serviceRequest);



    if(!$assignedRequest){

        return response()->json([

            'success' => false,

            'message' => 'No available staff in this department'

        ], 409);

    }



   /* return response()->json([

        'success' => true,

        'message' => 'Request reviewed and assigned successfully',

        'data' => $assignedRequest

    ]);
*/
$assignedRequest->load('staff');

return response()->json([

    'success' => true,

    'message' => 'Request reviewed and assigned successfully',

    'data' => [
        'id' => $assignedRequest->id,

        'service_name' => $assignedRequest->service
            ? $assignedRequest->service->name
            : null,

        'staff_name' => $assignedRequest->staff
            ? $assignedRequest->staff->name
            : null,

        'service_location' => $assignedRequest->location,

        'details' => $assignedRequest->details,

        'status' => $assignedRequest->status
    ]

]);
}}