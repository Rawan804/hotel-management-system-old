<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();

        // 🔍 البحث بالنوع
        if ($request->has('type')) {
            $query->where('type', 'like', '%' . $request->type . '%');
        }

        // 👥 البحث بعدد الأشخاص
        if ($request->has('person_num')) {
            $query->where('person_num', '>=', $request->person_num);
        }

        // 📅 التحقق من التوفر بالتواريخ
        if ($request->has(['startDate', 'endDate'])) {
            $start = $request->startDate;
            $end = $request->endDate;

            $query->whereDoesntHave('bookings', function ($q) use ($start, $end) {
                $q->where('status', 'confirmed')
                  ->where(function ($q2) use ($start, $end) {
                      $q2->whereBetween('startDate', [$start, $end])
                         ->orWhereBetween('endDate', [$start, $end])
                         ->orWhere(function ($q3) use ($start, $end) {
                             $q3->where('startDate', '<=', $start)
                                ->where('endDate', '>=', $end);
                         });
                  });
            });
        }

        return response()->json([
            'rooms' => $query->with('images')->get()
        ]);
    }

    public function show(Room $room)
    {
        return response()->json([
            'room' => $room->load('images')
        ]);
    }
}