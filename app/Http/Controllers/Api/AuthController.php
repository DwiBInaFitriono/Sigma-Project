<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Handle API login.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email atau kata sandi Anda salah.',
            ], 401);
        }

        // Generate token
        $token = Str::random(80);
        $user->forceFill([
            'api_token' => $token,
        ])->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Handle API registration.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Generate token
        $token = Str::random(80);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'api_token' => $token,
        ]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    /**
     * Handle API logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $user->forceFill([
                'api_token' => null,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Handle API profile query.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
