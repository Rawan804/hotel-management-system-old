<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Staff;
use Symfony\Component\HttpFoundation\Response;

class StaffOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || get_class($user) !== Staff::class) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }

        return $next($request);
    }
}