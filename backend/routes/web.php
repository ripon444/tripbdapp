<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'platform' => 'TripBD - Bangladesh On-Demand Transport & Logistics',
        'status' => 'online',
        'environment' => config('app.env'),
        'api_version' => 'v1',
        'timestamp' => now()->toIso8601String(),
    ]);
});
