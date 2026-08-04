<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !($user instanceof Customer)) {
            return response()->json([
                'message' => __('messages.unauthorized')
            ], 403);
        }

        return $next($request);
    }
}