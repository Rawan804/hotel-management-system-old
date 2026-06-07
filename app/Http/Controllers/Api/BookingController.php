<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\BookingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    // إنشاء حجز جديد
    public function store(StoreBookingRequest $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Please login first'], 401);

        $booking = $this->bookingService->create($request->validated(), $user);

        if ($booking === null) return response()->json(['message' => 'Room not found'], 404);
        if ($booking === false) return response()->json(['message' => 'Room is not available'], 400);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking
        ], 201);
    }

    // عرض حجوزات المستخدم
    public function myBookings(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Please login first'], 401);

        return response()->json([
            'message' => 'Bookings retrieved successfully',
            'bookings' => $this->bookingService->myBookings($user)
        ]);
    }

    // إلغاء الحجز
    public function cancel(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Please login first'], 401);

        $result = $this->bookingService->cancel($booking, $user);

        if (!$result) return response()->json(['message' => 'Not allowed or invalid booking'], 403);

        return response()->json(['message' => 'Booking cancelled successfully']);
    }
    // إلغاء كل الحجوزات الخاصة بالمستخدم
public function cancelAllBookings(Request $request)
{
    $user = $request->user();
    if (!$user) return response()->json(['message' => 'Please login first'], 401);

    $result = $this->bookingService->cancelAll($user);

    if (!$result) return response()->json(['message' => 'No bookings to cancel'], 404);

    return response()->json(['message' => 'All bookings cancelled successfully']);
}

// إلغاء الحجوزات بتاريخ محدد
public function cancelBookingsByDate(Request $request)
{
    $user = $request->user();
    if (!$user) return response()->json(['message' => 'Please login first'], 401);

    $request->validate([
        'date' => 'required|date'
    ]);

    $date = $request->date;

    $result = $this->bookingService->cancelByDate($user, $date);

    if (!$result) return response()->json(['message' => 'No bookings found on this date'], 404);

    return response()->json(['message' => "Bookings on {$date} cancelled successfully"]);
}
public function cancelBookingsByPeriod(Request $request)
{
    $user = $request->user();

    $request->validate([
        'fromDate' => 'required|date',
        'toDate'   => 'required|date|after_or_equal:fromDate',
    ]);

    $result = $this->bookingService->cancelByPeriod(
        $user,
        $request->fromDate,
        $request->toDate
    );

    if (!$result) {
        return response()->json([
            'message' => 'No bookings found in this period'
        ], 404);
    }

    return response()->json([
        'message' => 'Bookings cancelled successfully'
    ]);
}
}