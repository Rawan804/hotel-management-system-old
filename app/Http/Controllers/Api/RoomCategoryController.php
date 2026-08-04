<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomCategoryRequest;
use App\Models\RoomCategory;
use App\Services\RoomCategoryService;
use Illuminate\Http\Request;

class RoomCategoryController extends Controller
{
    public function __construct(
        private RoomCategoryService $service
    ) {}

   public function index()
{
    return response()->json(
        RoomCategory::with([
            'roomType',
            'images'
        ])
        ->withCount([
            'rooms as available_rooms_count' => function ($query) {
                $query->where('status', 'available');
            }
        ])
        ->get()
        ->makeHidden(['total_rooms'])
    );
}

    public function store(StoreRoomCategoryRequest $request)
    {
        $category = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء التصنيف بنجاح'
                : 'Room category created successfully',

            'data' => $category
        ], 201);
    }

    public function addImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image'
        ]);

        $category = RoomCategory::findOrFail($id);

        $path = $request
            ->file('image')
            ->store('room_images', 'public');

        $image = $category->images()->create([
            'image' => $path
        ]);

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم رفع الصورة بنجاح'
                : 'Image uploaded successfully',

            'data' => $image
        ]);
    }
}