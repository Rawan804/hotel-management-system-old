<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;

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
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json($result);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->authService->sendOtp(
            $request->only('email')
        );

        if ($result === null) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($result === 'cooldown') {
            return response()->json([
                'message' => 'Please wait 30 seconds'
            ], 429);
        }

        return response()->json([
            'message' => 'OTP sent successfully'
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->authService->resendOtp(
            $request->only('email')
        );

        if ($result === null) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($result === 'cooldown') {
            return response()->json([
                'message' => 'Please wait 30 seconds'
            ], 429);
        }

        return response()->json([
            'message' => 'OTP resent successfully'
        ]);
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
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($result === false) {
            return response()->json([
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        return response()->json([
            'message' => 'OTP verified successfully'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $result = $this->authService->resetPassword(
            $request->only('email', 'password')
        );

        if ($result === null) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Password reset successful'
        ]);
    }

    public function getOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->authService->getOtp(
            $request->only('email')
        );

        if ($result === null) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($result === false) {
            return response()->json([
                'message' => 'No OTP found'
            ], 404);
        }

        return response()->json($result);
    }
}