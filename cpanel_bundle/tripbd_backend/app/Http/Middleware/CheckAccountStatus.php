<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->status === 'suspended') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact TripBD support hotline: +880 9612-874723.'
                ], 403);
            }

            if ($user->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive. Please complete phone OTP verification.'
                ], 403);
            }
        }

        return $next($request);
    }
}
