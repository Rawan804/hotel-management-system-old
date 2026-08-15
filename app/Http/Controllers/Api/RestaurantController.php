<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantReservationRequest;
use App\Http\Requests\RestaurantRequest;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Exception; 
class RestaurantController extends Controller

{
    protected RestaurantService $restaurantService;

    public function __construct(RestaurantService $restaurantService)
    {
        $this->restaurantService = $restaurantService;
    }

    // 1. تابع إضافة مطعم
    public function store(RestaurantRequest $request): JsonResponse
    {
        try {
           
        $hall = $this->restaurantService->createRestaurant($request->all());

            return response()->json([
                'message' => __('messages.created_successfully'),
                'data'    => $this->formatHallResponse($hall)
            ], 201);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // تابع تعديل المطعم
     public function update(RestaurantRequest $request, $res_id): JsonResponse
    {
        try {
         
          $restaurant = $this->restaurantService->updateRestaurant((int) $res_id, $request->all());
          //  $hall = $this->hallService->updateHall($hall_id, $request->all());

            return response()->json([
                'message' => __('messages.update successfuly'),
                'data'    => $this->formatHallResponse($restaurant)
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    //تابع مساعد
    private function formatHallResponse($restaurant): array
    {
        $imagePath = $restaurant->image;
        
        // تعديل: دمج مجلد restaurants مع الاسم إذا لم يكن رابطاً خارجياً جاهزاً
        $imageUrl = Str::startsWith($imagePath, ['http://', 'https://']) 
            ? $imagePath 
            : url('storage/restaurant/' . $imagePath);

        return [
            'res_id'      => $restaurant->res_id,
            'name_en'     => $restaurant->name_en,
            'name_ar'     => $restaurant->name_ar,
            'image'       => $imageUrl, // سيظهر الرابط كاملاً وصحيحاً هنا
            'details_en'  => $restaurant->details_en,
            'details_ar'  => $restaurant->details_ar
        ];
    }



    public function index(): JsonResponse
    {
        $restaurants = $this->restaurantService->getAllRestaurants();

        return response()->json([
            'status'  => true,
            'message' => __('messages.fetch_success'),
            'data'    => $restaurants
        ], 200);
    }

    public function reserve(RestaurantReservationRequest $request): JsonResponse
    {
        $customerId = Auth::id();

        $reservation = $this->restaurantService->createReservation(
            $customerId,
            $request->validated()
        );

        return response()->json([
            'status'  => true,
            'message' => __('messages.reserved_success'),
            'data'    => $reservation
        ], 201);
    }

    public function getRestaurantReservation(): JsonResponse
    {
        $reservation = $this->restaurantService->getAllReservations();

        return response()->json([
            'status'  => true,
            'message' => __('messages.reservations_fetch_success'),
            'data'    => $reservation
        ], 200);
    }

    public function getRestaurantReservations($restaurantId)
    {
        $reservations = $this->restaurantService->getAllReservation($restaurantId);

        return response()->json([
            'success' => true,
            'message' => __('messages.restaurant_reservations_fetch_success'),
            'data' => $reservations
        ]);
    }

    public function cancelReserve(int $id): JsonResponse
    {
        $customerId = Auth::id();

        $isCancelled = $this->restaurantService->cancelReservation($customerId, $id);

        if (!$isCancelled) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.cancel_failed')
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.cancel_success')
        ], 200);
    }
}