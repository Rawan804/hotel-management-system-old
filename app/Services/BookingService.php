<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Support\Facades\DB;
use  Carbon\Carbon;


class BookingService
{
    public function create(array $data, $customer)
    {
        if (!$customer) {
            return [
                'success' => false,
                'message' => __('messages.unauthorized')
            ];
        }

        $start = $data['startDate'];
        $end   = $data['endDate'];
        $count = $data['rooms_count'] ?? 1;

        $rooms = Room::where('room_category_id', $data['room_category_id'])
            ->where('status', 'available')
            ->whereDoesntHave('bookings', function ($q) use ($start, $end) {
                $q->where('status', 'confirmed')
                  ->where(function ($query) use ($start, $end) {
                      $query->where('startDate', '<=', $end)
                            ->where('endDate', '>=', $start);
                  });
            })
            ->limit($count)
            ->get();

        if ($rooms->count() < $count) {
            return [
                'success' => false,
                'message' => __('messages.not_enough_rooms')
            ];
        }

        $bookings = [];

        DB::transaction(function () use ($rooms, $customer, $start, $end, &$bookings) {

            foreach ($rooms as $room) {

                $booking = Booking::create([
                    'room_id'     => $room->id,
                    'customer_id' => $customer->id,
                    'startDate'   => $start,
                    'endDate'     => $end,
                    'status'      => 'confirmed'
                ]);

                $room->update(['status' => 'occupied']);

                $bookings[] = [
                    'book_id'     => $booking->book_id,
                    'room_id'     => $room->id,
                    'room_number' => $room->room_number,
                ];
            }
        });

        return [
            'success' => true,
            'message' => __('messages.booking_created'),
            'bookings' => $bookings
        ];
    }

  
    public function cancel($booking, $customer)
    {
        if (!$customer || $booking->customer_id !== $customer->id) {
            return false;
        }

        DB::transaction(function () use ($booking) {

            $booking->update([
                'status' => 'cancelled'
            ]);

            if ($booking->room) {
                $booking->room->update([
                    'status' => 'available'
                ]);
            }
        });

        return true;
    }
public function myBookings($customer)
{
    return Booking::with(['room:id,room_number,room_category_id'])
        ->where('customer_id', $customer->id)
        ->latest()
        ->get()
        ->map(function ($booking) {

            return [
                'book_id'     => $booking->book_id,
                'room_id'     => $booking->room_id,
                'room_number' => $booking->room?->room_number,
                'startDate'   => $booking->startDate,
                'endDate'     => $booking->endDate,
                'status'      => $booking->status,
            ];
        });
}



public function getGeneralRoomStats(?string $month = null): array
{
    if ($month) {
        // احصائية لشهر محدد فقط
        $totalRooms = Room::count();

        $bookedRoomIds = Booking::query()
            ->where('status', 'confirmed')
            ->where(function ($query) use ($month) {
                $query->whereRaw("DATE_FORMAT(startDate, '%Y-%m') = ?", [$month])
                    ->orWhereRaw("DATE_FORMAT(endDate, '%Y-%m') = ?", [$month])
                    ->orWhere(function ($q) use ($month) {
                        $monthStart = $month . '-01';
                        $monthEnd = date('Y-m-t', strtotime($monthStart));
                        $q->where('startDate', '<=', $monthStart)
                          ->where('endDate', '>=', $monthEnd);
                    });
            })
            ->distinct('room_id')
            ->pluck('room_id')
            ->toArray();

        $bookedRoomsCount = count($bookedRoomIds);

        $availableRooms = $totalRooms - $bookedRoomsCount;

        return [
            $month => [
                'total_rooms' => $totalRooms,
                'booked_rooms' => $bookedRoomsCount,
                'available_rooms' => $availableRooms,
            ],
        ];
    } else {
        // احصائيات لجميع الأشهر اللي موجودة في الحجوزات

        $totalRooms = Room::count();

        $months = Booking::query()
            ->where('status', 'confirmed')
            ->selectRaw("DISTINCT DATE_FORMAT(startDate, '%Y-%m') as month")
            ->pluck('month')
            ->toArray();

        $stats = [];

        foreach ($months as $monthItem) {
            $bookedRoomIds = Booking::query()
                ->where('status', 'confirmed')
                ->where(function ($query) use ($monthItem) {
                    $query->whereRaw("DATE_FORMAT(startDate, '%Y-%m') = ?", [$monthItem])
                        ->orWhereRaw("DATE_FORMAT(endDate, '%Y-%m') = ?", [$monthItem])
                        ->orWhere(function ($q) use ($monthItem) {
                            $monthStart = $monthItem . '-01';
                            $monthEnd = date('Y-m-t', strtotime($monthStart));
                            $q->where('startDate', '<=', $monthStart)
                              ->where('endDate', '>=', $monthEnd);
                        });
                })
                ->distinct('room_id')
                ->pluck('room_id')
                ->toArray();

            $bookedRoomsCount = count($bookedRoomIds);
            $availableRooms = $totalRooms - $bookedRoomsCount;

            $stats[$monthItem] = [
                'total_rooms' => $totalRooms,
                'booked_rooms' => $bookedRoomsCount,
                'available_rooms' => $availableRooms,
            ];
        }

        return $stats;
    }}
    
    
 // احضائيات الغرف للاشهر حسب النوع والغتغوري    
 public function getMonthlyOccupancyStats($year = null, $month = null)
 {

   $months = Booking::select(
        DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month")
    )
    ->groupBy('month')
    ->orderBy('month', 'asc')
    ->get()
    ->toArray();

$allStats = [];

foreach ($months as $monthRecord) {
    $yearMonth = $monthRecord['month'];
    list($y, $m) = explode('-', $yearMonth);
    $startOfMonth = Carbon::createFromDate($y, $m, 1)->startOfMonth()->toDateString();
    $endOfMonth = Carbon::createFromDate($y, $m, 1)->endOfMonth()->toDateString();

    $stats = RoomCategory::with(['roomType'])
        ->withCount([
            'rooms as total_rooms_count',
            'rooms as booked_rooms_count' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereHas('bookings', function ($bQuery) use ($startOfMonth, $endOfMonth) {
                    $bQuery->where('startDate', '<=', $endOfMonth)
                           ->where('endDate', '>=', $startOfMonth);
                });
            }
        ])->get();

    $allStats[$yearMonth] = $stats->map(function ($category) {
        $total = $category->total_rooms_count;
        $booked = $category->booked_rooms_count;
        $available = max(0, $total - $booked);
        return [
            'room_type_id'    => $category->room_type_id,
            'room_type_name'  => $category->room_type_name,
            'category_id'     => $category->id,
            'category_name'   => $category->name,
            'total_rooms'     => $total,
            'booked_rooms'    => $booked,
            'available_rooms' => $available,
        ];
    });
}

return $allStats;

}

}