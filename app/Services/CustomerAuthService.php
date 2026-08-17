<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
class CustomerAuthService
{
    public function register(array $data)
    {
        return Customer::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
    }

    public function login(array $data)
    {
        $customer = Customer::where('email', $data['email'])->first();

        if (!$customer) {
            return null;
        }

        if (!Hash::check($data['password'], $customer->password)) {
            return null;
        }

        $customer->tokens()->delete();

        $token = $customer->createToken('customer-token')->plainTextToken;

        return [
            'token' => $token,
            'customer' => [
                'customer_id' => $customer->customer_id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ]
        ];
    }
    

    public function logout($customer)
{
    if ($customer) {

        $customer->currentAccessToken()->delete();
        return true;
    }
    return false;
}
 
   /////الحجورات 
 public function getMyBookings($customer)
{
    $locale = app()->getLocale();
  $rooms = $customer->bookings()
        ->where('startDate', '>=', now())
         ->where('status','confirmed')
        ->with(['room.category.roomType'])
        ->get()
        ->map(function($booking) use ($locale) {
            $room = $booking->room;
            $category = $room->category;
            $type = $category->roomType;

            return [
                'type'=>'room',
                'booking_id' => $booking->book_id,
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'category_name' => $category ? $category->{'name_'.$locale} : null,
                'type_name' => $type ? $type->{'name_'.$locale} : null,
                'startDate' => $booking->startDate,
                'endDate' => $booking->endDate,
            ];
        });

    $restaurants = $customer->ReservationCustomer()
        ->where('reservation_time', '>=', now())
        ->with('restaurant')
        ->get()
        ->map(function($booking) use ($locale){
            return [
                'booking_id' => $booking->res_cus_id,
                'res_id'=> $booking->res_id,
                'type' => 'restaurant',
                'name' => $booking->restaurant ? ($locale === 'ar' ? $booking->restaurant->name_ar : $booking->restaurant->name_en) : null,
                'restaurant_image' => $booking->restaurant ? $booking->restaurant->image : null,
                'person_num' => $booking->person_num,
                'reservation_time' => $booking->reservation_time,
            ];
        });

    $meetings = $customer->Reservation()
        ->where('start_time', '>=', now())
       ->where('status', 'pending')
        ->with('hall')
        ->get()
        ->map(function($booking) use ($locale){
            return [
                'booking_id' => $booking->resev_id,
                'type' => 'meeting_room',
                'hall_id' => $booking->hall_id,
                'name' => $booking->hall ? ($locale === 'ar' ? $booking->hall->name_ar : $booking->hall->name_en) : null,
                'hall_image' => $booking->hall ? $booking->hall->image : null,
             ///   'status' => $booking->status,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
            ];
        });

    return $rooms->concat($restaurants)->concat($meetings)->values()->all();
}


public function getCustomerWithBookingsAndServices(int $customerId)
{
    $locale = request()->header('Accept-Language', 'ar');

    $customer = \App\Models\Customer::with([
        'bookings.room',
        'bookings.serviceRequests.service',
        'bookings.serviceRequests.staff',
        'hallReservations.hall',
        'restaurantReservations.restaurant',
    ])->findOrFail($customerId);

    return [
        'customer_id' => $customer->id,
        'name'        => $customer->name,
        'phone'       => $customer->phone ?? null,

        // حجوزات الغرف + الخدمات المرتبطة فيها
        'room_bookings' => $customer->bookings->map(function ($booking) use ($locale) {

            return [
                'book_id'     => $booking->book_id,
                'room_number' => optional($booking->room)->room_number,
                'startDate'   => $booking->startDate,
                'endDate'     => $booking->endDate,
                'status'      => $booking->status,

                'services' => $booking->serviceRequests->map(function ($req) use ($locale) {

                    return [
                        'id'           => $req->id,
                        'service_name' => $req->service
                            ? ($locale === 'en' ? $req->service->name_en : $req->service->name_ar)
                            : null,
                        'details'      => $req->details,
                        'location'     => $req->location,
                        'weight'       => $req->weight,
                        'status'       => $req->status,
                        'staff_name'   => optional($req->staff)->name,
                    ];

                }),
            ];
        }),

        // حجوزات القاعات
        'hall_bookings' => $customer->hallReservations->map(function ($resv) use ($locale) {

            return [
                'resev_id'   => $resv->resev_id,
                'hall_id'    => $resv->hall_id,
                'hall_name'  => $resv->hall
                    ? ($locale === 'en' ? $resv->hall->name_en : $resv->hall->name_ar)
                    : null,
                'start_time' => $resv->start_time,
                'end_time'   => $resv->end_time,
                'status'     => $resv->status,
            ];
        }),

        // حجوزات المطاعم
        'restaurant_bookings' => $customer->restaurantReservations->map(function ($resv) use ($locale) {

            return [
                'res_cus_id'       => $resv->res_cus_id,
                'res_id'           => $resv->res_id,
                'restaurant_name'  => $resv->restaurant
                    ? ($locale === 'en' ? $resv->restaurant->name_en : $resv->restaurant->name_ar)
                    : null,
                'person_num'       => $resv->person_num,
                'reservation_time' => $resv->reservation_time,
            ];
        }),
    ];
}

public function getAllcustomers(): Collection
    {
        $locale = request()->header('Accept-Language', 'ar'); 
        $customers = Customer::select(['id', 'name', 'phone', 'email'])->get();

    return $customers->map(function ($customer) use ($locale) {
   

    return [
        'customer_id'   => $customer->id,
        'name'      =>$customer->name,
        'phone'   => $customer->phone,
        'email'     => $customer->email,
        'password'  => $customer->password,
    ];
}); 
    }

}