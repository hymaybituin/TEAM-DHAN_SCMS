<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();
    
        // $request->session()->regenerate();
    
        // Create a new personal access token
        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;
    
        // Create a JsonResponse
        $jsonResponse = response()->json([
            'user' => $user,
            'token' => $token,
        ], 200);
    
        // Convert the JsonResponse to a Response
        return response()->make($jsonResponse->content(), $jsonResponse->status(), $jsonResponse->headers->all());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
