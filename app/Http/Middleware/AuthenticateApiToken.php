<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->input('api_token') ?? $request->header('X-Api-Token');

        if ($token === null) {
            return response()->json([
                'success' => false,
                'message' => 'Token autentikasi tidak ditemukan.',
            ], 401);
        }

        $user = User::where('api_token', $token)->first();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi autentikasi kedaluwarsa atau tidak valid.',
            ], 401);
        }

        // Set authenticated user context for the request
        Auth::login($user);

        return $next($request);
    }
}
