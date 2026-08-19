<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\HallReservationRequest;
use App\Http\Requests\HallRequest;
use App\Http\Requests\UpdateReservationStatusRequest ;
use Illuminate\Support\Str;
use App\Services\HallService ;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;


class HallController extends Controller
{
    protected HallService $hallService;

   public function __construct(HallService $hallService)
{

    $this->hallService = $hallService;
}
    // 1. تابع إضافة قاعة جديدة
    public function store(HallRequest $request): JsonResponse
    {
        try {
           
        $hall = $this->hallService->createHall($request->all());


            return response()->json([
                'message' => __('messages.created_successfully'),
                'data'    => $this->formatHallResponse($hall)
            ], 201);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    
        }

    // 2.تابع تعديل القاعة
     public function update(HallRequest $request, $hall_id): JsonResponse
    {
        try {
         
          $hall = $this->hallService->updateHall((int) $hall_id, $request->all());
          return response()->json([
                'message' => __('messages.update successfuly'),
                'data'    => $this->formatHallResponse($hall)
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    //تابع مساعد
   private function formatHallResponse($hall): array
{
    $imagePath = $hall->image;
    
    $imageUrl = \Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) 
        ? $imagePath 
        : url('halls/' . $imagePath);

    return [
        'hall_id'     => $hall->hall_id,
        'name_en'     => $hall->name_en,
        'name_ar'     => $hall->name_ar,
        'image'       => $imageUrl,
        'details_en'  => $hall->details_en,
        'details_ar'  => $hall->details_ar,
        'price'       => $hall->price,
        'capacity'    => $hall->capacity
    ];
}

//تابع العرض
    public function index(): JsonResponse
    {
        $halls = $this->hallService->getAllHalls();

        return response()->json([
            'status'  => true,

            'message' => __('messages.fetch_successs'),
            'data'    => $halls
        ], 200);
    }

    //تابع الحجز
    public function reserve(HallReservationRequest $request): JsonResponse
    {

        try {
            $customerId = Auth::id();
            $reservation = $this->hallService->createReservation($customerId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('messages.Your request is pending, please check your bookings'),
                'data'    => $reservation
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

//عرض كل الحجوزات

    public function getHallReservation(): JsonResponse
    {
        $reservation = $this->hallService->getAllReservations();

        return response()->json([
            'status'  => true,
            'message' => __('messages.halls_reservations_fetch_success'),
            'data'    => $reservation
        ], 200);
    }

//عرض حجوزات قاعة معينة
    public function getHallReservations($hallId): JsonResponse
    {
        $reservations = $this->hallService->getAllReservation($hallId);

        return response()->json([
            'success' => true,
            'message' => __('messages.hall_reservations_fetch_success'),
            'data'    => $reservations
        ]);
    }



    //الغاء الحجز

    public function cancelReserve(int $id): JsonResponse
    {
        $customerId = Auth::id();
        $isCancelled = $this->hallService->cancelReservation($customerId, $id);
        if (!$isCancelled) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.halls_cancel_failed')
            ], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.halls_cancel_success')
        ], 200);
    }


    //عدد حجوزات قاعة

    public function monthlyReservations($hallId): JsonResponse
    {
        $stats = $this->hallService->countMonthlyReservationsByHall((int) $hallId);
        return response()->json($stats);
    }


  //تغيير حالة حجز القاعة
public function updateReservationStatus(
    UpdateReservationStatusRequest $request,
    int $res_id
): JsonResponse {

    try {
   $currentUser = Auth::user();

        $reservation = $this->hallService->updateReservationStatus(
            $res_id,
            $request->validated()['status'],
            $currentUser
        );

        return response()->json([
            'success' => true,

            'message' => $reservation->status === 'confirmed'
                ? __('messages.reservation_approved_successfully')
                : __('messages.reservation_rejected_successfully'),

            'data' => [
                'resev_id'    => $reservation->resev_id,
                'customer_id' => $reservation->customer_id,
                'hall_id'     => $reservation->hall_id,
                'start_time'  => $reservation->start_time,
                'end_time'    => $reservation->end_time,
                'status'      => $reservation->status,
            ],
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode() >= 400 && $e->getCode() <= 599
            ? $e->getCode()
            : 400
        );
    }
}
}

