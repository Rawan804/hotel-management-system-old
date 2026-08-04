<?php

namespace App\Services;

use App\Models\RoomType;
use Illuminate\Support\Facades\Storage;

class RoomTypeService
{
    public function create(array $data): RoomType
    {
        if (!empty($data['image'])) {
            $data['image'] = $data['image']->store(
                'room_types',
                'public'
            );
        }

        return RoomType::create($data);
    }

    public function update(RoomType $type, array $data): RoomType
    {
        if (!empty($data['image'])) {

            if ($type->image) {
                Storage::disk('public')->delete($type->image);
            }

            $data['image'] = $data['image']->store(
                'room_types',
                'public'
            );
        }

        $type->update($data);

        return $type;
    }

    public function delete(RoomType $type): bool
    {
        if ($type->image) {
            Storage::disk('public')->delete($type->image);
        }

        return $type->delete();
    }
}