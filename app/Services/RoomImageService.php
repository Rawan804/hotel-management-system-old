<?php

namespace App\Services;

use App\Models\RoomCategory;
use App\Models\RoomImage;

class RoomImageService
{
    public function create(RoomCategory $category, $image): RoomImage
    {
        $path = $image->store(
            'room_images',
            'public'
        );

        return $category->images()->create([
            'image' => $path
        ]);
    }
}