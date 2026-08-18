<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\Staff;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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


           /* $booking = Booking::where('customer_id', $customer->id)
                ->where('status','confirmed')
                ->latest()
                ->first();
*/
$booking = Booking::where('customer_id', $customer->id)
    ->where('status', 'confirmed')
    ->whereDate('startDate', '<=', now())
    ->whereDate('endDate', '>=', now())
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
    ->where('role', 'employee')
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
   /* $staffs = $staffs->filter(function ($staff) {

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
*/
// فقط الموظفين داخل الشفت الحالي
// فقط الموظفين داخل الشفت الحالي حسب التاريخ والوقت
$staffs = $staffs->filter(function ($staff) {

    foreach ($staff->shifts as $shift) {


        $today = strtolower(
            Carbon::now()->englishDayOfWeek
        );


        $shiftDay = strtolower(
            trim($shift->day_of_week)
        );


        $currentTime = now()->format('H:i:s');


        if(
            $shift->is_active &&
            $shiftDay === $today &&
            $currentTime >= $shift->start_time &&
            $currentTime <= $shift->end_time
        ){

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


               /*) Staff::where(
                    'staff_id',
                    $request->staff_id

                )->update([

                    'status'=>'busy'

                ]);*/
                $staff = Staff::find($request->staff_id);
if ($staff) {
    $this->refreshStaffStatus($staff);
}

/*
// استخدم refreshStaffStatus بدلاً من التحديث المباشر
$staff = Staff::find($request->staff_id);
$this->refreshStaffStatus($staff);
        */    }



            return $request->fresh('staff');


        });

    }






    /*
    |--------------------------------------------------------------------------
    | COMPLETE REQUEST
    |--------------------------------------------------------------------------
    */
/*
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
// تحديث حالة الموظف باستخدام الدالة الموحدة
//$this->refreshStaffStatus($staff);



                $staff->save();


            }



            return $request->fresh('staff');


        });

    }

*/

//completeeee
public function completeRequest($id)
{
    return DB::transaction(function() use($id){

        $request = ServiceRequest::findOrFail($id);

        if($request->status==='done'){
            return null;
        }

        $request->update(['status'=>'done']);

        $staff = Staff::find($request->staff_id);

        if($staff){

            // نقص اللود (هاد السطر ضل زي ما هو، ما تغير)
            $staff->service_load = max(0, $staff->service_load - ($request->weight ?? 1));
            $staff->save();

            //  بدل كل الـ if/elseif/else، بس نادينا الدالة الموحدة
            $this->refreshStaffStatus($staff);
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

*/

//الاصلي

/*
//الاخير
private function refreshStaffStatus($staff)
{
    // إذا الموظف برا الشيفت
    if (!$staff->isWorkingNow()) {
        $staff->status = 'offline';
    }

    // إذا الموظف عنده إجازة معتمدة حالياً
    elseif (
        $staff->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists()
    ) {
        $staff->status = 'offline';
    }

    // إذا الموظف باستراحة
    elseif ($staff->status === 'on_break') {
        $staff->status = 'on_break';
    }

    // إذا الحمل تجاوز الحد
    elseif ($staff->service_load > $staff->max_load) {
        $staff->status = 'overloaded';
    }

    // إذا عنده طلبات pending أو in_progress
    elseif (
        $staff->serviceRequests()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists()
    ) {
        $staff->status = 'busy';
    }

    // ما عنده طلبات
    else {
        $staff->status = 'available';
    }

    $staff->save();
}*/

/* solve2
private function refreshStaffStatus($staff)
{
    // 1. التحقق من الحمل أولاً
    if ($staff->service_load > $staff->max_load) {
        $staff->status = 'overloaded';
    }
    else {
        // 2. التحقق من وجود طلبات قيد التنفيذ (in_progress) فقط
        $hasInProgress = ServiceRequest::where('staff_id', $staff->staff_id)
            ->where('status', 'in_progress')
            ->exists();

        if ($hasInProgress) {
            $staff->status = 'busy';
        } else {
            $staff->status = 'available';
        }
    }

    $staff->save();
}
*/


/*
private function refreshStaffStatus($staff)
{

    if($staff->service_load > $staff->max_load){

        $staff->status = 'overloaded';

    }


    $staff->save();

}*/

/*
private function refreshStaffStatus($staff)
{

    if($staff->service_load > $staff->max_load){

        $staff->status='overloaded';

    }
    else{

        $activeRequests = ServiceRequest::where(
            'staff_id',
            $staff->staff_id
        )
        ->whereIn('status',[
            'pending',
            'in_progress'
        ])
        ->count();


        if($activeRequests > 0){
            $staff->status='busy';
        }
        else{
            $staff->status='available';
        }

    }


    $staff->save();

}


*/



//اخر وحدة 
private function refreshStaffStatus($staff)
{
    if ($staff->status === 'on_break') {
        $staff->save();
        return;
    }

    $onLeave = $staff->leaves()
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', now())
        ->whereDate('end_date', '>=', now())
        ->exists();

    $hasOpenTasks = $staff->tasks()
        ->whereIn('status', ['pending', 'in_progress'])
        ->exists();

    $hasOpenRequests = $staff->serviceRequests()
        ->whereIn('status', ['pending', 'in_progress'])
        ->exists();

    $hasAnyOpenWork = $hasOpenTasks || $hasOpenRequests;

    if (($onLeave || !$staff->isWorkingNow()) && !$hasAnyOpenWork) {
        $staff->status = 'offline';
        $staff->save();
        return;
    }

    if ($staff->service_load > $staff->max_load) {
        $staff->status = 'overloaded';
        $staff->save();
        return;
    }

    $hasActiveTask = $staff->tasks()
        ->where('status', 'in_progress')
        ->exists();

    $staff->status = ($hasActiveTask || $hasOpenRequests) ? 'busy' : 'available';
    $staff->save();
}

// new



public function reassignDelayedRequests()
{
    return DB::transaction(function () {


        $delayedRequests = ServiceRequest::where('status', 'pending')
         //   ->whereNotNull('staff_id')
           // ->where('assigned_at', '<=', now()->subMinutes(15))
           ->where(function ($q) {
    $q->whereNotNull('staff_id')
      ->where('assigned_at', '<=', now()->subMinutes(15));
})
->orWhere(function ($q) {
    $q->where('status', 'pending')
      ->whereNull('staff_id');
})
            ->get();


        foreach ($delayedRequests as $request) {


            // الموظف القديم
            $oldStaff = Staff::find($request->staff_id);


            if($oldStaff){

                $oldStaff->service_load =
                    max(
                        0,
                        $oldStaff->service_load - ($request->weight ?? 1)
                    );


                $this->refreshStaffStatus($oldStaff);

            }



            // إزالة الموظف القديم
            $request->update([

                'staff_id'=>null,

                'assign_attempts'=>
                    $request->assign_attempts + 1

            ]);



            // اختيار موظف جديد
            $newStaff = $this->findBestStaff(
                $request->dep_id
            );



            if($newStaff){


                $request->update([

                    'staff_id'=>$newStaff->staff_id,

                    'assigned_at'=>now()

                ]);


                $newStaff->increment(
                    'service_load',
                    $request->weight ?? 1
                );


                $this->refreshStaffStatus($newStaff);


            }


        }


        return $delayedRequests;

    });
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