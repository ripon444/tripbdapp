<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use App\Models\VehicleType;
use App\Models\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    /**
     * GET /api/v1/services
     * List all active service categories with their active vehicle types.
     */
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->with(['activeVehicleTypes' => function ($query) {
                $query->orderBy('base_fare', 'asc');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * GET /api/v1/services/{id}
     * Retrieve a single service category by ID or slug.
     */
    public function show($id): JsonResponse
    {
        $category = is_numeric($id)
            ? ServiceCategory::with('activeVehicleTypes')->find($id)
            : ServiceCategory::with('activeVehicleTypes')->where('slug', $id)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Service category not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * GET /api/v1/vehicle-types
     * List vehicle types, optionally filtered by service category.
     */
    public function vehicleTypes(Request $request): JsonResponse
    {
        $query = VehicleType::where('status', 'active')->with('serviceCategory:id,name,slug');

        if ($request->filled('service_category_id')) {
            $query->where('service_category_id', $request->service_category_id);
        } elseif ($request->filled('category_slug')) {
            $query->whereHas('serviceCategory', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        $vehicleTypes = $query->orderBy('base_fare', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $vehicleTypes
        ]);
    }

    /**
     * Admin: List all categories (active and inactive).
     * GET /api/v1/admin/services
     */
    public function adminIndex(): JsonResponse
    {
        $categories = ServiceCategory::withCount(['vehicleTypes', 'bookings'])
            ->with('vehicleTypes')
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Admin: Create a new service category.
     * POST /api/v1/admin/services
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:service_categories,name',
            'slug' => 'nullable|string|max:100|unique:service_categories,slug',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = ServiceCategory::create($data);

        AdminLog::create([
            'admin_id' => $request->user()?->id ?? 1,
            'action' => 'create_service_category',
            'target_type' => 'ServiceCategory',
            'target_id' => $category->id,
            'description' => "Created service category '{$category->name}'",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service category created successfully.',
            'data' => $category
        ], 201);
    }

    /**
     * Admin: Update an existing service category.
     * PUT /api/v1/admin/services/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = ServiceCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => "sometimes|required|string|max:100|unique:service_categories,name,{$id}",
            'slug' => "nullable|string|max:100|unique:service_categories,slug,{$id}",
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($validator->validated());

        AdminLog::create([
            'admin_id' => $request->user()?->id ?? 1,
            'action' => 'update_service_category',
            'target_type' => 'ServiceCategory',
            'target_id' => $category->id,
            'description' => "Updated service category '{$category->name}'",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service category updated successfully.',
            'data' => $category
        ]);
    }

    /**
     * Admin: Create a new vehicle type.
     * POST /api/v1/admin/vehicle-types
     */
    public function storeVehicleType(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:vehicle_types,slug',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'passenger_capacity' => 'required|integer|min:1',
            'load_capacity' => 'nullable|numeric|min:0',
            'base_fare' => 'required|numeric|min:0',
            'per_km_rate' => 'required|numeric|min:0',
            'per_hour_rate' => 'nullable|numeric|min:0',
            'minimum_fare' => 'required|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $vehicleType = VehicleType::create($data);

        AdminLog::create([
            'admin_id' => $request->user()?->id ?? 1,
            'action' => 'create_vehicle_type',
            'target_type' => 'VehicleType',
            'target_id' => $vehicleType->id,
            'description' => "Created vehicle type '{$vehicleType->name}'",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle type created successfully.',
            'data' => $vehicleType
        ], 201);
    }

    /**
     * Admin: Update vehicle type.
     * PUT /api/v1/admin/vehicle-types/{id}
     */
    public function updateVehicleType(Request $request, int $id): JsonResponse
    {
        $vehicleType = VehicleType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'service_category_id' => 'sometimes|required|exists:service_categories,id',
            'name' => 'sometimes|required|string|max:100',
            'slug' => "nullable|string|max:100|unique:vehicle_types,slug,{$id}",
            'description' => 'nullable|string|max:1000',
            'passenger_capacity' => 'nullable|integer|min:1',
            'load_capacity' => 'nullable|numeric|min:0',
            'base_fare' => 'nullable|numeric|min:0',
            'per_km_rate' => 'nullable|numeric|min:0',
            'per_hour_rate' => 'nullable|numeric|min:0',
            'minimum_fare' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicleType->update($validator->validated());

        AdminLog::create([
            'admin_id' => $request->user()?->id ?? 1,
            'action' => 'update_vehicle_type',
            'target_type' => 'VehicleType',
            'target_id' => $vehicleType->id,
            'description' => "Updated vehicle type '{$vehicleType->name}'",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle type updated successfully.',
            'data' => $vehicleType
        ]);
    }
}
