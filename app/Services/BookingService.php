<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function create(array $data, $customer)
    {
        if (!$customer) return false;

        $room = Room::find($data['room_id']);
        if (!$room) return null;

        $start = $data['startDate'];
        $end   = $data['endDate'];

        // تحقق من وجود حجز متضارب
        $hasConflict = Booking::where('room_id', $room->room_id)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($start, $end) {
                $q->where('startDate', '<=', $end)
                  ->where('endDate', '>=', $start);
            })
            ->exists();

        if ($hasConflict) return false;

        $booking = Booking::create([
            'room_id' => $room->room_id,
            'customer_id' => $customer->customer_id,
            'startDate' => $start,
            'endDate' => $end,
            'status' => 'confirmed'
        ]);

        // تحديث حالة الغرفة
        $room->update(['status' => 'booked']);

        return $booking;
    }
/*
    public function myBookings($customer)
    {
        return Booking::with(['room'])
            ->where('customer_id', $customer->customer_id)
            ->latest()
            ->get();
    }*/
            public function myBookings($customer)
{
    return Booking::with(['room'])
        ->where('customer_id', $customer->customer_id)
        ->where('status', 'confirmed') // الآن يظهر فقط المؤكدة
        ->latest()
        ->get();
} 

    public function cancel(Booking $booking, $customer)
    {
        if (!$customer) return false;
        if ($booking->customer_id !== $customer->customer_id) return false;

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            if ($booking->room) {
                $booking->room->update(['status' => 'available']);
            }
        });

        return true;
    }
    public function cancelAll($customer)
{
    if (!$customer) return false;

    $bookings = Booking::where('customer_id', $customer->customer_id)
        ->where('status', 'confirmed')
        ->get();

    DB::transaction(function () use ($bookings) {
        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled']);
            if ($booking->room) {
                $booking->room->update(['status' => 'available']);
            }
        }
    });

    return true;
}

public function cancelByDate($customer, $date)
{
    if (!$customer) return false;

    $bookings = Booking::where('customer_id', $customer->customer_id)
        ->where('status', 'confirmed')
        ->where('startDate', '<=', $date)
        ->where('endDate', '>=', $date)
        ->get();

    if ($bookings->isEmpty()) return false;

    DB::transaction(function () use ($bookings) {
        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled']);
            if ($booking->room) {
                $booking->room->update(['status' => 'available']);
            }
        }
    });

    return true;
}
public function cancelByPeriod($customer, $fromDate, $toDate)
{
    if (!$customer) return false;

    $bookings = Booking::where('customer_id', $customer->customer_id)
        ->where('status', 'confirmed')
        ->where(function ($q) use ($fromDate, $toDate) {
            $q->whereBetween('startDate', [$fromDate, $toDate])
              ->orWhereBetween('endDate', [$fromDate, $toDate]);
        })
        ->get();

    if ($bookings->isEmpty()) {
        return false;
    }

    foreach ($bookings as $booking) {
        $booking->update([
            'status' => 'cancelled'
        ]);

        if ($booking->room) {
            $booking->room->update([
                'status' => 'available'
            ]);
        }
    }

    return true;
}
}