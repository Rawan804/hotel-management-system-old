<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\RoomCategoryController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HotelNewsController;
use App\Http\Controllers\Api\FixedTaskController;
use App\Http\Controllers\Api\FixedTaskItemController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\LeaveRequestsController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StaffWorkController;
use App\Http\Controllers\Api\ServiceController;


Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $staff = $request->user()->load('department');

    return response()->json([
        'staff_id' => $staff->staff_id,
        'name' => $staff->name,
        'email' => $staff->email,
        'phone' => $staff->phone,
        'department' => $staff->department
    ]);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggleActive']);
    Route::put('/staff/{staff}/update-role', [StaffController::class, 'updateRole']);
    Route::post('staff/{staff}/update-info', [StaffController::class, 'updateInfo']);
});


/*
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
});*/


//مدير عام غرف تايب كاتيغوري
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/room-categories', [RoomCategoryController::class, 'store']);
    Route::put('/room-categories/{roomCategory}', [RoomCategoryController::class, 'update']);
    Route::delete('/room-categories/{roomCategory}', [RoomCategoryController::class, 'destroy']);
    Route::post('/room-categories/{id}/images', [RoomCategoryController::class, 'addImage']);

    Route::post('/room-types', [RoomTypeController::class, 'store']);
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update']);
    Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy']);

    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/available', [RoomController::class, 'availableRooms']);
    Route::get('/rooms/occupied', [RoomController::class, 'occupiedRooms']);
    Route::get('/rooms/maintenance', [RoomController::class, 'maintenanceRooms']);

    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

});

Route::prefix('room-types')->group(function () {
    Route::get('/', [RoomTypeController::class, 'index']);
   // Route::post('/', [RoomTypeController::class, 'store']);
   // Route::put('/{roomType}', [RoomTypeController::class, 'update']);
   // Route::delete('/{roomType}', [RoomTypeController::class, 'destroy']);
});

Route::prefix('room-categories')->group(function () {
    Route::get('/', [RoomCategoryController::class, 'index']);
   // Route::post('/', [RoomCategoryController::class, 'store']);
   // Route::put('/{roomCategory}', [RoomCategoryController::class, 'update']);
   // Route::delete('/{roomCategory}', [RoomCategoryController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {

    /*
    
     Fixed Tasks (Templates)
    
    */
    Route::get('/fixed-tasks', [FixedTaskController::class, 'index']);
    Route::post('/fixed-tasks', [FixedTaskController::class, 'store']);
    Route::put('/fixed-tasks/{id}', [FixedTaskController::class, 'update']);
    Route::delete('/fixed-tasks/{id}', [FixedTaskController::class, 'destroy']);

    Route::post('/fixed-task-items', [FixedTaskItemController::class, 'store']);
    Route::put('/fixed-task-items/{id}', [FixedTaskItemController::class, 'update']);
    Route::delete('/fixed-task-items/{id}', [FixedTaskItemController::class, 'destroy']);


    /*
     Tasks (Actual Work)
    */

    // إنشاء Task من Template
    Route::post('/tasks/from-template/{id}', [TaskController::class, 'createFromTemplate']);

    //  المهام الخاصة بالموظف المسجل دخول
    Route::get('/tasks/my', [TaskController::class, 'myTasks']);

    // مهام موظف محدد (لـ admin / supervisor)
    Route::get('/tasks/staff/{id}', [TaskController::class, 'staffTasks']);

    // تغيير حالة checklist item
    Route::post('/tasks/toggle-item', [TaskController::class, 'toggleItem']);
    
//زيادة هدول
    // بدء المهمة
    Route::post('/tasks/{id}/start', [TaskController::class, 'startTask']);

    // إنهاء المهمة
    Route::post('/tasks/{id}/complete', [TaskController::class, 'completeTask']);

});

//....................................................................


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/events', [EventController::class, 'store']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {
   
    Route::post('/news', [HotelNewsController::class, 'store']);
    Route::post('/news/{news}', [HotelNewsController::class, 'update']);
    Route::delete('/news/{news}', [HotelNewsController::class, 'destroy']);
    Route::get('/pinned-news', [HotelNewsController::class, 'pinnedNews']);
});
Route::middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::post('/halls/reserve', [HallController::class, 'reserve']);
    Route::get('/halls/reserve', [HallController::class, 'getHallReservation']);

    Route::post('/restaurants/reserve', [RestaurantController::class, 'reserve']);
    Route::get('/restaurants/reserve', [RestaurantController::class, 'getRestaurantReservation']);
      
});

Route::middleware('auth:sanctum')->group(function () {
    

     Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    // راوتات التعديل والحذف
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);   



    Route::put('/leaveRequest/{id}/status', [LeaveRequestsController::class, 'updateStatus']);
    Route::get('/leaveRequests', [LeaveRequestsController::class, 'getDepartmentLeaveRequests']);
   Route::put('/complaints/{id}/status', [ComplaintController::class, 'updateStatus']);
    Route::get('/complaints', [ComplaintController::class, 'getDepartmentComplaints']);    
   
    Route::get('reservationCount/{id}',[HallController::class,'ReservationCount']);
    Route::get('/restaurants/{restaurantId}/reservations', [RestaurantController::class, 'getRestaurantReservations']);
   Route::get('/halls/{hallId}/monthly-reservations', [HallController::class, 'monthlyReservations']);
    Route::get('/halls/{hallId}/reservations', [HallController::class, 'getHallReservations']);
    Route::get('/rooms/monthly-statistics', [BookingController::class, 'getMonthlyStats']); 
    Route::get('/rooms/stats', [BookingController::class, 'generalStats']);

    Route::post('halls/{hall_id}', [HallController::class, 'update']);
    Route::post('halls', [HallController::class, 'store']);
    
   Route::post('restaurants', [RestaurantController::class, 'store']);
   Route::post('restaurants/{res_id}',[RestaurantController::class,'update']);
    });
//..................................................................................................
//customer بدون حماية
//.................................................................................................
Route::post('/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/halls', [HallController::class, 'index']);
Route::get('/eventss', [EventController::class, 'index']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);


//customer حماية
//..................................................................................................

Route::middleware(['auth:sanctum', 'customer'])->group(function () {

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/cancel-all', [BookingController::class, 'cancelAllBookings']);
    Route::post('/bookings/cancel-by-date', [BookingController::class, 'cancelBookingsByDate']);
    Route::post('/bookings/cancel-by-period', [BookingController::class, 'cancelBookingsByPeriod']);

    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
    Route::get('/service-requests/my', [ServiceRequestController::class, 'customerRequests']);

    Route::delete('/restaurants/reservations/{id}', [RestaurantController::class, 'cancelReserve']);

  
    
    Route::patch('/halls/reservations/{id}/cancel', [HallController::class, 'cancelReserve']);
    Route::get('customer/my-bookings', [CustomerAuthController::class, 'myBookings']);
    Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
});



//..........................................................................................

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/get-otp', [AuthController::class, 'getOtp']);

});
Route::prefix('auth')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

});
//...................................................................................


Route::middleware(['auth:sanctum', 'staff'])->group(function () {

    Route::patch('/service-requests/{serviceRequest}/status', [ServiceRequestController::class, 'updateStatus']);
    Route::patch('/service-requests/{id}/start', [ServiceRequestController::class, 'start']);
    Route::patch('/service-requests/{id}/complete', [ServiceRequestController::class, 'complete']);

    Route::get('/service-types', [ServiceRequestController::class, 'serviceTypes']);
    Route::post('/service-types', [ServiceRequestController::class, 'storeServiceType']);

    Route::get('/service-requests', [ServiceRequestController::class, 'staffRequests']);

    Route::get('/tasks/my', [TaskController::class, 'myTasks']);
    Route::patch('/tasks/{id}/start', [TaskController::class, 'startTask']);
    Route::patch('/tasks/{id}/complete', [TaskController::class, 'completeTask']);
    Route::post('/tasks/toggle-item', [TaskController::class, 'toggleItem']);

    Route::get('/news', [HotelNewsController::class, 'index']);
    Route::get('/news/{news}', [HotelNewsController::class, 'show']);

    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::post('/leaveRequests', [LeaveRequestsController::class, 'store']);
});


//......................................................................
Route::middleware('auth:sanctum')->group(function () {

    // Rooms index
    Route::get('/rooms', [RoomController::class, 'index']);

    Route::get('/rooms/available', [RoomController::class, 'availableRooms']);

    Route::get('/rooms/occupied', [RoomController::class, 'occupiedRooms']);

    Route::get('/rooms/maintenance', [RoomController::class, 'maintenanceRooms']);
});
//.............................................................................
Route::middleware(['auth:staff'])->group(function () {
    Route::get('/events', [EventController::class, 'allEvents']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'updateEvent']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::get('/active', [EventController::class, 'activeEvents']);
    Route::get('/inactive', [EventController::class, 'inactiveEvents']);
});
//..............................................................................
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | SERVICE MANAGER ROUTES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/service-requests/pending-review',
        [ServiceRequestController::class, 'pendingReview']
    );

    Route::post(
        '/service-requests/{id}/review',
        [ServiceRequestController::class, 'review']
    );

});
//...................................................................
Route::get('/staff/load', [ServiceRequestController::class, 'staffLoad']);
//.......................................................................
//لمدير قسم الخدمات لتعرضلو مين اتحمل فوق طاقتو 
Route::middleware(['auth:sanctum', 'staff'])->group(function () {

    Route::get('/service-manager/overloaded-staff',
        [ServiceRequestController::class, 'overloadedStaff']);

});

Route::middleware('auth:sanctum')->group(function(){

    Route::post('/staff/firebase-token', [StaffController::class,'saveFirebaseToken']);
Route::get( '/notifications', [NotificationController::class,'index']);

Route::put('/notifications/{id}/read',[NotificationController::class,'markAsRead']
);
});


// عرض الخدمات داخل قسم محدد 
Route::get(
    '/services/department/{dep_id}',
    [ServiceController::class, 'getByDepartment']
);
//.......................................................................


Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/services',
        [ServiceController::class, 'index']
    );

    // إضافة خدمة
    Route::post(
        '/services',
        [ServiceController::class, 'store']
    );//اضافة خدمة جديدة تسنيم


    // تفعيل / إلغاء تفعيل خدمة
    Route::patch(
        '/services/{service}/toggle',
        [ServiceController::class, 'toggleActive']
    );



    Route::post(
    '/staff/shifts',
    [StaffController::class, 'addShift']
);

});
