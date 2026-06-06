<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Customer;
use App\Models\OtpVerification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->merge([
            'country_code' => $request->input('country_code', '+968'),
        ]);

        $validated = $request->validate([
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $countryCode = $this->normalizeCountryCode($validated['country_code']);
        $phone = $this->normalizePhone($validated['phone']);
        $fullPhone = $countryCode . $phone;
        $otpCode = (string) random_int(100000, 999999);

        Customer::firstOrCreate(
            [
                'country_code' => $countryCode,
                'phone' => $phone,
            ],
            [
                'active' => true,
            ],
        );

        OtpVerification::create([
            'phone' => $fullPhone,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = [
            'message' => 'OTP created.',
            'expires_in' => 300,
        ];

        if (app()->environment('local')) {
            $response['otp_code'] = $otpCode;
        }

        return response()->json($response);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30'],
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $countryCode = $this->normalizeCountryCode($validated['country_code']);
        $phone = $this->normalizePhone($validated['phone']);
        $fullPhone = $countryCode . $phone;

        $otpVerification = OtpVerification::where('phone', $fullPhone)
            ->latest()
            ->first();

        if (
            ! $otpVerification ||
            $otpVerification->otp_code !== $validated['otp_code'] ||
            $otpVerification->verified_at !== null ||
            $otpVerification->expires_at->isPast()
        ) {
            throw ValidationException::withMessages([
                'otp_code' => 'The OTP code is invalid or expired.',
            ]);
        }

        $otpVerification->update([
            'verified_at' => now(),
        ]);

        $customer = Customer::firstOrCreate(
            [
                'country_code' => $countryCode,
                'phone' => $phone,
            ],
            [
                'active' => true,
            ],
        );

        $token = $customer->createToken('customer-mobile')->plainTextToken;

        return response()->json([
            'customer' => $customer,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'customer' => $request->user(),
        ]);
    }

    private function normalizeCountryCode(string $countryCode): string
    {
        $countryCode = preg_replace('/\D+/', '', $countryCode);

        if ($countryCode === '') {
            throw ValidationException::withMessages([
                'country_code' => 'The country code must contain digits.',
            ]);
        }

        return '+' . $countryCode;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if ($phone === '') {
            throw ValidationException::withMessages([
                'phone' => 'The phone number must contain digits.',
            ]);
        }

        return $phone;
    }
}
