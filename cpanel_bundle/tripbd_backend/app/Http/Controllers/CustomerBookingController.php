<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Booking;

class CustomerBookingController extends Controller
{
    /**
     * GET /api/v1/customer/bookings
     * Retrieve paginated list of bookings owned by the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::where('customer_id', $user->id)
            ->with(['serviceCategory', 'vehicleType', 'driver:id,name,phone,avatar', 'vehicle'])
            ->orderBy('created_at', 'desc');

        // Status filtering
        if ($request->has('status') && !empty($request->status)) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        // Date range filtering
        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $perPage = min(50, (int) $request->get('per_page', 15));
        $bookings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }
}
