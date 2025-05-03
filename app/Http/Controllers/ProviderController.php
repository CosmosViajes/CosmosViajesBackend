<?php

namespace App\Http\Controllers;

use App\Models\User;

class ProviderController extends Controller
{
    public function index()
    {
        $providers = User::where('is_company', true)
            ->where('is_provider', true)
            ->withCount('spaceTrips')
            ->get();

        return response()->json($providers);
    }
}