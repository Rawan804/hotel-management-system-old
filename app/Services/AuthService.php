<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Mail\PasswordChangedMail;

class AuthService
{
    // 🔐 LOGIN
    public function login(array $data)
    {
        $staff = Staff::where('email', $data['email'])->first();

        if (!$staff || !Hash::check($data['password'], $staff->password)) {
            return false;
        }

        $token = $staff->createToken('staff-token')->plainTextToken;

        return [
            'token' => $token,
            'staff' => $staff
        ];
    }

    public function sendOtp(array $data)
    {
        $staff = Staff::where('email', $data['email'])->first();

        if (!$staff) {
            return null;
        }

        if ($staff->updated_at && $staff->updated_at->diffInSeconds(now()) < 30) {
            return 'cooldown';
        }

        $otp = (string) random_int(100000, 999999);

        $staff->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($staff->email)->send(new OtpMail($otp));

        return true;
    }

    public function resendOtp(array $data)
    {
        $staff = Staff::where('email', $data['email'])->first();

        if (!$staff) {
            return null;
        }

        if ($staff->updated_at && $staff->updated_at->diffInSeconds(now()) < 30) {
            return 'cooldown';
        }

        $otp = (string) random_int(100000, 999999);

        $staff->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($staff->email)->send(new OtpMail($otp));

        return true;
    }

    public function verifyOtp(array $data)
    {
        $staff = Staff::where('email', $data['email'])->first();

        if (!$staff) {
            return null;
        }

        if (
            !$staff->otp_code ||
            $staff->otp_code !== $data['otp'] ||
            now()->gt($staff->otp_expires_at)
        ) {
            return false;
        }

        return true;
    }

  public function resetPassword(array $data)
{
    $staff = Staff::where('email', $data['email'])->first();

    if (!$staff) {
        return null;
    }

    if (
        !$staff->otp_code ||
        $staff->otp_code !== $data['otp'] ||
        now()->gt($staff->otp_expires_at)
    ) {
        return false;
    }

    $staff->update([
        'password' => Hash::make($data['password']),
        'otp_code' => null,
        'otp_expires_at' => null,
    ]);

    $staff->tokens()->delete();

    $token = $staff->createToken('staff-token')->plainTextToken;

    Mail::to($staff->email)->send(new PasswordChangedMail());

    return [
        'token' => $token,
        'staff' => $staff
    ];
}

    public function getOtp(array $data)
    {
        $staff = Staff::where('email', $data['email'])->first();

        if (!$staff) {
            return null;
        }

        if (!$staff->otp_code) {
            return false;
        }

        return [
            'otp' => $staff->otp_code,
            'expires_at' => $staff->otp_expires_at
        ];
    }
    public function logout($staff)
{
    if ($staff) {
        // حذف التوكن الحالي المستخدم في طلب تسجيل الخروج
        $staff->currentAccessToken()->delete();
        return true;
    }
    return false;
}
}