<?php
/*
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
}*/


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use App\Http\Requests\StoreRoomTypeRequest;
use Illuminate\Support\Facades\Auth;

class RoomTypeController extends Controller
{
    public function __construct(
        private RoomTypeService $service
    ) {}



    private function guardAdmin()
    {
        $creator = Auth::guard('staff')->user();


        if (!$creator) {

            return response()->json([
                'message' => 'Unauthenticated'
            ],401);

        }


        if ($creator->role !== 'general_manager') {

            return response()->json([
                'message' => 'Forbidden'
            ],403);

        }


        return null;
    }




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

        if ($check = $this->guardAdmin()) {
            return $check;
        }


        $type = $this->service->create(
            $request->validated()
        );


        return response()->json([

            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء نوع الغرفة بنجاح'
                : 'Room type created successfully',


            'data' => $type

        ],201);

    }





    public function update(
        StoreRoomTypeRequest $request,
        RoomType $roomType
    )
    {

        if ($check = $this->guardAdmin()) {
            return $check;
        }


        $type = $this->service->update(
            $roomType,
            $request->validated()
        );


        return response()->json([

            'message' => app()->getLocale() === 'ar'
                ? 'تم تعديل نوع الغرفة بنجاح'
                : 'Room type updated successfully',


            'data' => $type

        ]);

    }





    public function destroy(RoomType $roomType)
    {

        if ($check = $this->guardAdmin()) {
            return $check;
        }


        $this->service->delete($roomType);


        return response()->json([

            'message' => app()->getLocale() === 'ar'
                ? 'تم حذف نوع الغرفة بنجاح'
                : 'Room type deleted successfully'

        ]);

    }

}