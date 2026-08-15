<?php

namespace App\Services;

use App\Models\Room;

class RoomService
{
    public function create(array $data): Room
    {
        return Room::create([
            'room_category_id' => $data['room_category_id'],
            'room_number' => $data['room_number'],
            'status' => $data['status'] ?? 'available'
        ]);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update([
            'room_category_id' => $data['room_category_id'],
            'room_number' => $data['room_number'],
            'status' => $data['status']
        ]);

        return $room;
    }

    public function delete(Room $room): bool
    {
        return $room->delete();
    }

public function updateRoomsStatus()
{
    $rooms = Room::all();

    foreach ($rooms as $room) {

        $hasActiveBooking = \App\Models\Booking::where('room_id', $room->id)
            ->where('status', 'confirmed')
            ->whereDate('startDate', '<=', now())
            ->whereDate('endDate', '>=', now())
            ->exists();


        if ($hasActiveBooking) {

            $room->update([
                'status' => 'occupied'
            ]);

        } else {

            $room->update([
                'status' => 'available'
            ]);

        }
    }
}
}