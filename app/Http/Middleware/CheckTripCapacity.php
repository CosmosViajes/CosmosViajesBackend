<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ReservedTrip;
use App\Models\SpaceTrip;

class CheckTripCapacity {
    public function handle(Request $request, Closure $next)
    {
        $trip = SpaceTrip::findOrFail($request->trip_id);
        $reserved = ReservedTrip::where('trip_id', $request->trip_id)->sum('quantity');
        
        if (($reserved + $request->quantity) > $trip->capacity) {
            return response()->json([
                'message' => 'Capacidad excedida',
                'available' => $trip->capacity - $reserved
            ], 422);
        }

        return $next($request);
    }
}