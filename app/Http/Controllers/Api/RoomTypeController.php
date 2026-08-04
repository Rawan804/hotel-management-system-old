<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use App\Http\Requests\StoreRoomTypeRequest;

class RoomTypeController extends Controller
{
    public function __construct(
        private RoomTypeService $service
    ) {}

    public function index()
    {
        $types = RoomType::all();

        return response()->json(
            $types->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => app()->getLocale() === 'ar'
                        ? $type->name_ar
                        : $type->name_en,

                    'description' => app()->getLocale() === 'ar'
                        ? $type->description_ar
                        : $type->description_en,

                    'image_url' => $type->image,
                ];
            })
        );
    }

    public function store(StoreRoomTypeRequest $request)
    {
        $type = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء نوع الغرفة بنجاح'
                : 'Room type created successfully',

            'data' => [
                'id' => $type->id,
                'name' => app()->getLocale() === 'ar'
                    ? $type->name_ar
                    : $type->name_en,

                'description' => app()->getLocale() === 'ar'
                    ? $type->description_ar
                    : $type->description_en,

                'image_url' => $type->image,
            ]
        ], 201);
    }
}