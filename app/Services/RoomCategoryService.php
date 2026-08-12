<?php

namespace App\Services;

use App\Models\RoomCategory;
use Illuminate\Support\Facades\Storage;

class RoomCategoryService
{
    public function create(array $data): RoomCategory
    {
        if (!empty($data['image'])) {
            $data['image'] = $data['image']->store(
                'room_categories',
                'public'
            );
        }

        return RoomCategory::create($data);
    }

    public function update(RoomCategory $category, array $data): RoomCategory
    {
        if (!empty($data['image'])) {

            if ($category->image) {
                Storage::disk('public')->delete(
                    $category->image
                );
            }

            $data['image'] = $data['image']->store(
                'room_categories',
                'public'
            );
        }

        $category->update($data);

        return $category;
    }

    public function delete(RoomCategory $category): bool
    {
        if ($category->image) {
            Storage::disk('public')->delete(
                $category->image
            );
        }

        foreach ($category->images as $image) {
            Storage::disk('public')->delete(
                $image->image
            );
        }

        return $category->delete();
    }
}