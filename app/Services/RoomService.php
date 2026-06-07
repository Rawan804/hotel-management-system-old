<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomImage;

class RoomService
{
   public function getAll()
{
    return Room::with('images')
        ->where('status', 'available')
        ->get();
}

    public function getOne(Room $room)
    {
        return $room->load('images');
    }

    public function create(array $data)
    {
        $room = Room::create([
            'type' => $data['type'],
            'price' => $data['price'],
            'person_num' => $data['person_num'],
            'status' => 'available'
        ]);

        if (!empty($data['images'])) {
            foreach ($data['images'] as $image) {

                $path = $image->store(
                    'rooms',
                    'public'
                );

                RoomImage::create([
                    'room_id' => $room->room_id,
                    'image' => $path
                ]);
            }
        }

        return $room->load('images');
    }

    public function delete(Room $room)
    {
        $room->delete();
    }
}