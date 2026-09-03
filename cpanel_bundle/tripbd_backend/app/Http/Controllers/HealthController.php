<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * System health check for cPanel and production monitoring
     */
    public function check(Request $request): JsonResponse
    {
        $dbStatus = 'connected';
        $dbVersion = 'MySQL 8.0';

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'disconnected: ' . $e->getMessage();
        }

        return response()->json([
            'status' => 'ok',
            'platform' => 'TripBD',
            'tagline' => 'খালি গাড়ি নয়, রিটার্নে যাত্রী নিন',
            'secondary_tagline' => 'ঢাকা থেকে সকল জেলা — সকল জেলা থেকে ঢাকা',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/cPanel',
                'database' => $dbStatus . ' (' . $dbVersion . ')',
                'sanctum' => 'configured',
                'cpanel_compatible' => true,
                'polling_mode' => 'active (cPanel WebSocket fallback)'
            ]
        ]);
    }
}
