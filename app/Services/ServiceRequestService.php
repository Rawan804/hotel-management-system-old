<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\Staff;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceRequestService
{

    /*
    |--------------------------------------------------------------------------
    | CREATE SERVICE REQUEST
    |--------------------------------------------------------------------------
    */
    public function create(array $data, $customer)
    {
        return DB::transaction(function () use ($data, $customer) {


            $booking = Booking::where('customer_id', $customer->id)
                ->where('status','confirmed')
                ->latest()
                ->first();


            if(!$booking){
                return null;
            }

/*

            $service = Service::find($data['ser_id'] ?? null);


            if(!$service){
                return null;
            }



            $weight = $service->weight ?? 1;
*/
$service = Service::find($data['ser_id'] ?? null);


// طلب مخصص بدون خدمة جاهزة
if(!$service){

    return ServiceRequest::create([

        'booking_id' => $booking->book_id,

'dep_id'=>$data['dep_id'],

        'ser_id' => null,

        'staff_id' => null,

        'details' => $data['details'],

        'location' => $data['location'] 
            ?? optional($booking->room)->room_number,

        'weight' => null,

        'status' => 'pending_review'

    ]);

}


$weight = $service->weight ?? 1;


            $staff = $this->findBestStaff(
                $data['dep_id']
            );



            if(!$staff){

                return null;

            }




            $request = ServiceRequest::create([

                'booking_id'=>$booking->book_id,

                'dep_id'=>$data['dep_id'],

                'ser_id'=>$service->ser_id,

                'staff_id'=>$staff->staff_id,


                'details'=>$data['details'] ?? null,

'location' => $data['location'] 
    ?? optional($booking->room)->room_number,

                'weight'=>$weight,


                'status'=>'pending',

                'assigned_at'=>now(),

            ]);




            // زيادة الحمل
            $staff->increment(
                'service_load',
                $weight
            );



            // تحديث الحالة
            $this->refreshStaffStatus($staff);



            return $request->fresh('staff');


        });
    }




    /*
    |--------------------------------------------------------------------------
    | FIND BEST STAFF
    |--------------------------------------------------------------------------
    */
private function findBestStaff($dep_id)
{
    $staffs = Staff::where('dep_id', $dep_id)
        ->where('is_active', true)
        ->whereNotIn('status', [
            'offline',
            //'overloaded',
            'on_break'
        ])
        
        ->with([
            'serviceRequests',
            'shifts'
        ])
        ->get();


    // فقط الموظفين داخل الشفت الحالي
    $staffs = $staffs->filter(function ($staff) {

        foreach ($staff->shifts as $shift) {

            $today = now()->format('Y-m-d');

            $shiftDate = Carbon::parse($shift->shift_date)
                ->format('Y-m-d');


            if (
                $shift->is_active &&
                $shiftDate === $today &&
                now()->between(
                    Carbon::parse($shift->start_time),
                    Carbon::parse($shift->end_time)
                )
            ) {
                return true;
            }
        }

        return false;
    });



    if ($staffs->isEmpty()) {
        return null;
    }

// إذا كل الموظفين overloaded نختار الأقل تجاوزاً للحد
// الموظفين الذين لم يتجاوزوا الحد
$normalStaff = $staffs->filter(function ($staff) {

    return $staff->service_load <= $staff->max_load;

});


// إذا يوجد موظفين ضمن طاقتهم نستخدمهم فقط
if ($normalStaff->isNotEmpty()) {

    $staffs = $normalStaff;

}


// إذا كل الموظفين تجاوزوا الحد
else {

    $staffs = $staffs
        ->sortBy(function ($staff) {

            // مقدار التجاوز
            return $staff->service_load - $staff->max_load;

        });

}

// إذا كل الموظفين تجاوزوا الحد
$allOverloaded = $staffs->every(function ($staff) {

    return $staff->status === 'overloaded';

});


if($allOverloaded){

    return $staffs
        ->sortBy('service_load')
        ->first();

}


// إعطاء أولوية للموظفين المتاحين دائماً

$availableStaff = $staffs->filter(function ($staff) {

    return $staff->status === 'available';

});


if ($availableStaff->isNotEmpty()) {

    $staffs = $availableStaff;

}

    return $staffs
        ->map(function ($staff) {


            /*
            |--------------------------------------------------------------------------
            | SCORE SYSTEM
            |--------------------------------------------------------------------------
            */

            $score = 0;


            // 1) الحالة
          /*  if ($staff->status === 'available') {

                $score += 40;

            } elseif ($staff->status === 'busy') {

                $score += 10;

            }*/
               if ($staff->status === 'available') {

    $score += 100;

} elseif ($staff->status === 'busy') {

    $score += 20;


}


            // 2) القدرة المتبقية
          /*  $capacity =
                $staff->max_load -
                $staff->service_load;


            $score += ($capacity * 2);

*/
// 2) القدرة المتبقية
$capacity =
    $staff->max_load -
    $staff->service_load;


$score += ($capacity * 2);


// إذا كان الموظف overloaded نقلل فرصته حسب مقدار التجاوز
if($staff->service_load > $staff->max_load){

    $overload =
        $staff->service_load - $staff->max_load;

    $score -= ($overload * 3);

}

            // 3) عدد الطلبات الحالية
            $activeRequests =
                $staff->serviceRequests
                ->whereIn('status',[
                    'pending',
                    'in_progress'
                ])
                ->count();


            $score -= ($activeRequests * 15);



            return [
                'staff'=>$staff,
                'score'=>$score
            ];


        })
        ->sortByDesc('score')
        ->first()['staff'];
}



    /*
    |--------------------------------------------------------------------------
    | START REQUEST
    |--------------------------------------------------------------------------
    */
    public function startRequest($id)
    {

        return DB::transaction(function() use($id){


            $request = ServiceRequest::findOrFail($id);



            if($request->status !== 'pending'){

                return null;

            }



            $request->update([

                'status'=>'in_progress'

            ]);




            if($request->staff_id){


                Staff::where(
                    'staff_id',
                    $request->staff_id

                )->update([

                    'status'=>'busy'

                ]);

            }



            return $request->fresh('staff');


        });

    }






    /*
    |--------------------------------------------------------------------------
    | COMPLETE REQUEST
    |--------------------------------------------------------------------------
    */

    public function completeRequest($id)
    {

        return DB::transaction(function() use($id){



            $request = ServiceRequest::findOrFail($id);



            if($request->status==='done'){

                return null;

            }




            $request->update([

                'status'=>'done'

            ]);




            $staff = Staff::find(
                $request->staff_id
            );



            if($staff){


                // نقص اللود
                $staff->service_load =
                    max(
                        0,
                        $staff->service_load -
                        ($request->weight ?? 1)
                    );



                $activeRequests =
                    ServiceRequest::where(
                        'staff_id',
                        $staff->staff_id
                    )

                    ->whereIn('status',[

                        'pending',

                        'in_progress'

                    ])

                    ->count();




               if($staff->service_load > $staff->max_load){

    // ما زال فوق الطاقة
    $staff->status = 'overloaded';

}
elseif($activeRequests > 0){

    // عنده طلبات لكنه ضمن الطاقة
    $staff->status = 'busy';

}
else{

    // لا يوجد طلبات والحمل ضمن الحد
    $staff->status = 'available';

}



                $staff->save();


            }



            return $request->fresh('staff');


        });

    }






    /*
    |--------------------------------------------------------------------------
    | STAFF STATUS
    |--------------------------------------------------------------------------
    */
/*
    private function refreshStaffStatus($staff)
    {


        if(
            $staff->service_load >
            $staff->max_load
        ){


            $staff->status='overloaded';


        }



        $staff->save();


    }

*/private function refreshStaffStatus($staff)
{

    if($staff->service_load > $staff->max_load){

        $staff->status='overloaded';

    }
    elseif($staff->service_load <= $staff->max_load){

        if(
            $staff->status === 'overloaded'
        ){
            $staff->status='available';
        }

    }


    $staff->save();

}

/*
|--------------------------------------------------------------------------
| ASSIGN REVIEWED CUSTOM REQUEST
|--------------------------------------------------------------------------
*/
public function assignReviewedRequest(ServiceRequest $request)
{

    return DB::transaction(function () use ($request) {


        $staff = $this->findBestStaff(
            $request->dep_id
        );


        if(!$staff){

            return null;

        }



        $request->update([

            'staff_id' => $staff->staff_id,

            'status' => 'pending',

            'assigned_at' => now()

        ]);



        // زيادة الحمل حسب الوزن الذي حدده المدير
        $staff->increment(
            'service_load',
            $request->weight
        );



        // تحديث حالة الموظف
        $this->refreshStaffStatus($staff);



        return $request->fresh('staff');


    });

}
    
}