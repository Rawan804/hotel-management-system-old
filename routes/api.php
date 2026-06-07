<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\HotelNewsController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::get('/me', function (Request $request) {

    $staff = $request->user()->load('department');

    return response()->json([
        'staff_id' => $staff->staff_id,
        'name' => $staff->name,
        'email' => $staff->email,
        'phone' => $staff->phone,

        'department' => [
            'dep_id' => $staff->department->dep_id,
            'name' => $staff->department->name,
        ],
    ]);

})->middleware('auth:sanctum');



Route::get(
    '/departments',
    [DepartmentController::class, 'index']
);

use App\Http\Controllers\Api\StaffController;

Route::middleware('auth:sanctum')->group(function () {

    // عرض الموظفين
    Route::get('/staff', [StaffController::class, 'index']);

    // إنشاء موظف
    Route::post('/staff', [StaffController::class, 'store']);

    // تفعيل / تعطيل
    Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggleActive']);

    // حذف موظف
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy']);
});



Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);



Route::middleware('auth:sanctum')->group(function () {

    Route::post('/rooms', [RoomController::class, 'store']);

    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

});





Route::post('/staff', [StaffController::class, 'store']);


Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/auth/get-otp', [AuthController::class, 'getOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);


Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/send-otp', [AuthController::class, 'sendOtp']);

    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/get-otp', [AuthController::class, 'getOtp']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/news', [HotelNewsController::class, 'index']);

    Route::get('/news/{news}', [HotelNewsController::class, 'show']);

    Route::post('/news', [HotelNewsController::class, 'store']);

    Route::post('/news/{news}', [HotelNewsController::class, 'update']);

    Route::delete('/news/{news}', [HotelNewsController::class, 'destroy']);
});





Route::post('/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // إلغاء كل الحجوزات دفعة واحدة
    Route::post('/bookings/cancel-all', [BookingController::class, 'cancelAllBookings']);

    // إلغاء الحجوزات بتاريخ محدد
    Route::post('/bookings/cancel-by-date', [BookingController::class, 'cancelBookingsByDate']);
    Route::post(
    '/bookings/cancel-by-period',
    [BookingController::class, 'cancelBookingsByPeriod']
);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/service-requests', [ServiceRequestController::class, 'index']);
    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
    Route::patch('/service-requests/{serviceRequest}/status', [ServiceRequestController::class, 'updateStatus']);
    Route::get('/staff/load', [ServiceRequestController::class, 'staffLoad']);

});
Route::post('/service-requests', [ServiceRequestController::class, 'store']);
Route::get('/staff-load', [ServiceRequestController::class, 'staffLoad']);


Route::get('/events', [EventController::class, 'index']);
Route::post('/events', [EventController::class, 'store']);
Route::delete('/events/{event}', [EventController::class, 'destroy']);