<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function __construct(
        private RoomService $service
    ) {}

    private function guardAdmin()
    {
        $creator = Auth::guard('staff')->user();

        if (!$creator) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($creator->role !== 'general_manager') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }


    private function transformRoom($room)
    {
        return [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'status' => $room->status,

            'category' => $room->category ? [

                'name' => app()->getLocale() === 'ar'
                    ? $room->category->name_ar
                    : $room->category->name_en,

                'price' => $room->category->price,
                'capacity' => $room->category->capacity,

                'room_type' => $room->category->roomType ? (
                    app()->getLocale() === 'ar'
                        ? $room->category->roomType->name_ar
                        : $room->category->roomType->name_en
                ) : null,

            ] : null,
        ];
    }


    public function index()
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }

        $this->service->updateRoomsStatus();

        $rooms = Room::with('category.roomType')->get();

        return response()->json([
            'message' => 'Rooms fetched successfully',
            'data' => $rooms->map(fn($room) => $this->transformRoom($room))
        ]);
    }


    public function availableRooms()
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }

        $this->service->updateRoomsStatus();

        $rooms = Room::with('category.roomType')
            ->where('status', 'available')
            ->orderBy('room_number')
            ->get();

        return response()->json([
            'message' => 'Available rooms fetched successfully',
            'data' => $rooms->map(fn($room) => $this->transformRoom($room))
        ]);
    }


    public function occupiedRooms()
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }

        $this->service->updateRoomsStatus();

        $rooms = Room::with('category.roomType')
            ->where('status', 'occupied')
            ->get();

        return response()->json([
            'message' => 'Occupied rooms fetched successfully',
            'data' => $rooms->map(fn($room) => $this->transformRoom($room))
        ]);
    }


    public function maintenanceRooms()
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }

        $rooms = Room::with('category.roomType')
            ->where('status', 'maintenance')
            ->orderBy('room_number')
            ->get();

        return response()->json([
            'message' => 'Maintenance rooms fetched successfully',
            'data' => $rooms->map(fn($room) => $this->transformRoom($room))
        ]);
    }


    public function store(StoreRoomRequest $request)
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }

        $room = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء الغرفة بنجاح'
                : 'Room created successfully',

            'data' => $this->transformRoom(
                $room->load('category.roomType')
            )
        ], 201);
    }



    public function update(UpdateRoomRequest $request, $id)
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }


        $room = Room::findOrFail($id);


        $room = $this->service->update(
            $room,
            $request->validated()
        );


        return response()->json([

            'message' => app()->getLocale() === 'ar'
                ? 'تم تعديل الغرفة بنجاح'
                : 'Room updated successfully',

            'data' => $this->transformRoom(
                $room->load('category.roomType')
            )
        ]);
    }



    public function destroy($id)
    {
        if ($check = $this->guardAdmin()) {
            return $check;
        }


        $room = Room::findOrFail($id);


        $this->service->delete($room);


        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم حذف الغرفة بنجاح'
                : 'Room deleted successfully'
        ]);
    }
}