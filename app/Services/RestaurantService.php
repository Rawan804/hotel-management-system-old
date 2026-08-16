<?php
namespace App\Services;

use App\Models\Restaurant;
use App\Models\ReservationCustomer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
class RestaurantService
{


public function createRestaurant(array $data): Restaurant
    {
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {

            $image = $data['image'];

            $imagePath = $image->store('resturants', 'public');

            $data['image'] = $imagePath;
        }

        return Restaurant::create($data);
    }

// 2. تابع تعديل المطعم
public function updateRestaurant(int $resId, array $data): Restaurant
{
    $restaurant = Restaurant::findOrFail($resId);

    $data = array_filter($data, function ($value) {
        return $value !== null;
    });

    if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {

        // حذف الصورة القديمة
        if ($restaurant->image) {
            Storage::disk('public')->delete($restaurant->image);
        }

        $imagePath = $data['image']->store('restaurants', 'public');
  $data['image'] = $imagePath;
    }

    $restaurant->update($data);

    return $restaurant;
}

 public function getAllRestaurants(): Collection
    {
        $locale = request()->header('Accept-Language', 'ar'); 

        $restaurants = Restaurant::select([
            'res_id',
            'name_en',
            'name_ar',
            'image',
            'details_en', 
            'details_ar'
        ])->get();

        return $restaurants->map(function ($restaurant) use ($locale) {
            return [
                'res_id'  => $restaurant->res_id,
                'image'   => $restaurant->image,
                'name'    => $locale === 'en' ? $restaurant->name_en : $restaurant->name_ar,
                'details' => $locale === 'en' ? $restaurant->details_en : $restaurant->details_ar,
            ];
        });   }

    
        public function createReservation(int $customerId, array $data): ReservationCustomer
    {
        return ReservationCustomer::create([
            'customer_id'      => $customerId,
            'res_id'           => $data['res_id'],
            'person_num'       => $data['person_num'],
            'reservation_time' => $data['reservation_time'],
        ]);
    } 
    ///عرض حجوزات كل المطاعم 
    public function getAllReservations(): Collection
    {

        return ReservationCustomer::get();
    }
  

//عرض حجوزات مطعم معين
public function getAllReservation($restaurantId): Collection
{
    $reservations = ReservationCustomer::with('customer')
        ->where('res_id', $restaurantId)
        ->get();

    return $reservations->map(function ($reservation) {
        return [
            'res_cus_id'       => $reservation->res_cus_id,
            'customer_id'=>$reservation->customer_id,
            'customer_name'    => $reservation->customer->name , 
            'person_num'       => $reservation->person_num,
            'reservation_time' => $reservation->reservation_time
       ];
    });
}
    
    public function cancelReservation(int $customerId, int $reservationId): bool
{
    $reservation = ReservationCustomer::where('customer_id', $customerId)
                                      ->where('res_cus_id', $reservationId)
                                      ->first();

    if (!$reservation) {
        return false; }

    return $reservation->delete();
}
}