<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Http\Requests\StoreBookingRequest;
use Illuminate\Http\Request;
use App\Models\Customer;
use Exception;
class BookingController extends Controller
{
    public function __construct(
        private BookingService $service
    ) {}

    // CREATE BOOKING
    public function store(StoreBookingRequest $request)
    {
        $user = $request->user();

        $result = $this->service->create(
            $request->validated(),
            $user
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'bookings' => $result['bookings']
        ], 201);
    }

    // MY BOOKINGS
    public function myBookings(Request $request)
    {
        $user = $request->user();

        $bookings = $this->service->myBookings($user);

        return response()->json([
            'success' => true,
            'message' => __('messages.bookings_retrieved'),
            'bookings' => $bookings
        ]);
    }

    // CANCEL BOOKING
    public function cancel(Request $request, $bookingId)
    {
        $user = $request->user();

        $booking = $user->bookings()
            ->where('book_id', $bookingId)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => __('messages.booking_not_found')
            ], 404);
        }

        $result = $this->service->cancel($booking, $user);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => __('messages.booking_cancel_failed')
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.booking_cancelled')
        ]);
    }


    public function generalStats(Request $request)
    {
        $month = $request->input('month'); // اذا بدي احدد لا شهر معين 
        $stats = $this->service->getGeneralRoomStats($month);

        return response()->json($stats);
    }
    
    
    
    public function getMonthlyStats(Request $request)
{
    $request->validate([
        'year'  => 'nullable|integer|min:2020|max:2099',
        'month' => 'nullable|integer|min:1|max:12',
    ]);

    try {
        $year = $request->input('year');
        $month = $request->input('month');

        $data = $this->service->getMonthlyOccupancyStats($year, $month);

        return response()->json([
            'success' => true,
            'message' => 'Monthly room statistics retrieved successfully.',
            'data'    => $data
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong!',
            'error'   => $e->getMessage()
        ], 500);
    }
}

}