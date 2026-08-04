<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\Staff;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $result = $this->authService->login(
            $request->only('email', 'password')
        );

        if (!$result) {
            return response()->json([
                'message' => __('messages.Invalid credentials')
            ], 401);
        }

        return response()->json($result);
    }


    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $result = $this->authService->sendOtp($request->only('email'));

        if ($result === null) {
            return response()->json(['message' => __('messages.User not found')], 404);
        }

        if ($result === 'cooldown') {
            return response()->json(['message' => __('messages.Please wait 30 seconds')], 429);
        }

        return response()->json(['message' => __('messages.OTP sent successfully')]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $result = $this->authService->resendOtp($request->only('email'));

        if ($result === null) {
            return response()->json(['message' => __('messages.User not found')], 404);
        }

        if ($result === 'cooldown') {
            return response()->json(['message' => __('messages.Please wait 30 seconds')], 429);
        }

        return response()->json(['message' => __('messages.OTP resent successfully')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $result = $this->authService->verifyOtp(
            $request->only('email', 'otp')
        );

        if ($result === null) {
            return response()->json(['message' => __('messages.User not found')], 404);
        }

        if ($result === false) {
            return response()->json(['message' => __('messages.Invalid or expired OTP')], 401);
        }

        return response()->json(['message' => __('messages.OTP verified successfully')]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
            'password' => 'required|min:6'
        ]);

        $result = $this->authService->resetPassword(
            $request->only('email', 'otp', 'password')
        );

        if ($result === null) {
            return response()->json(['message' => __('messages.User not found')], 404);
        }

        if ($result === false) {
            return response()->json(['message' => __('messages.Invalid or expired OTP')], 401);
        }

        return response()->json([
            'message' => __('messages.Password reset successful'),
            'token' => $result['token'],
            'staff' => $result['staff']
        ]);
    }

    public function getOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $result = $this->authService->getOtp($request->only('email'));

        if ($result === null) {
            return response()->json(['message' => __('messages.User not found')], 404);
        }

        if ($result === false) {
            return response()->json(['message' => __('messages.No OTP found')], 404);
        }

        return response()->json($result);
    }

   
    public function logout(Request $request)
    {
        $user = $request->user();

        $this->authService->logout($user);

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}