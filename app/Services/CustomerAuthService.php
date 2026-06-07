<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

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
}