<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLoginRequest;
use App\Http\Requests\CustomerRegisterRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use App\Services\CustomerAuthService;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    public function __construct(
        private CustomerAuthService $customerAuthService
    ) {}


    public function register(CustomerRegisterRequest $request)
    {
        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'message' => __('messages.register_success'),
            'customer' => $customer,
            'token' => $token
        ], 201);
    }

   
    public function login(CustomerLoginRequest $request)
    {
        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json([
                'message' => __('messages.invalid_credentials')
            ], 401);
        }

        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'message' => __('messages.login_success'),
            'customer' => $customer,
            'token' => $token
        ]);
    }


    public function logout(Request $request)
    {
        $this->customerAuthService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }


    public function myBookings(Request $request)
    {
        $bookings = $this->customerAuthService->getMyBookings($request->user());

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }
}