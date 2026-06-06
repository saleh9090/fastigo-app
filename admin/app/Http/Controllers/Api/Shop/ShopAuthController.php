<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = User::with(['company.subscriptionPackage', 'branch'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user->active) {
            return response()->json([
                'message' => 'User account is inactive.',
            ], 403);
        }

        if (! in_array($user->role, ['company_manager', 'branch_employee'], true) || ! $user->company_id) {
            return response()->json([
                'message' => 'User account is not allowed to access the business app.',
            ], 403);
        }

        $token = $user->createToken('shop-mobile')->plainTextToken;

        return response()->json([
            'user' => $user,
            'company' => $user->company,
            'branch' => $user->branch,
            'subscription' => $user->company?->subscriptionPackage,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Authenticated user is not a shop user.',
            ], 403);
        }

        $user->loadMissing(['company.subscriptionPackage', 'branch']);

        return response()->json([
            'user' => $user,
            'company' => $user->company,
            'branch' => $user->branch,
            'subscription' => $user->company?->subscriptionPackage,
            'role' => $user->role,
        ]);
    }

    public function subscription(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->company_id) {
            return response()->json([
                'message' => 'Authenticated user is not a shop user.',
            ], 403);
        }

        $user->loadMissing(['company.subscriptionPackage']);

        return response()->json([
            'company_status' => $user->company?->status,
            'subscription_start' => $user->company?->subscription_start,
            'subscription_end' => $user->company?->subscription_end,
            'can_create_bills' => (bool) $user->company?->canCreateBills(),
            'package' => $user->company?->subscriptionPackage,
        ]);
    }
}
